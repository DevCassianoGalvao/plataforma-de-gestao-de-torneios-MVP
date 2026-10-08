<?php
declare(strict_types=1);

/*
 * Lanca gols/cartoes de uma rodada a partir de um JSON e aprova (homologa) as partidas,
 * disparando disciplina, sumula e classificacao como a aprovacao pela Central operacional.
 * Nao cria atleta: todo nome precisa bater com uma inscricao APROVADA do time.
 *
 * Uso:
 *   php bin/importar-rodada.php <arquivo.json> --email=admin@exemplo.com            (simulacao, nao grava)
 *   php bin/importar-rodada.php <arquivo.json> --email=admin@exemplo.com --aplicar   (grava)
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Repositories\DisciplineRepository;
use App\Repositories\MatchOperationRepository;
use App\Repositories\MatchReportRepository;
use App\Repositories\StandingsRepository;
use App\Services\AuditService;
use App\Services\CompetitionProgressService;
use App\Services\DisciplineService;
use App\Services\MatchReportHtmlRenderer;
use App\Services\MatchReportPdf;
use App\Services\MatchReportService;
use App\Services\StandingsService;
use App\Services\StorageService;

$file = null;
$email = null;
$apply = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--aplicar') $apply = true;
    elseif (str_starts_with($arg, '--email=')) $email = trim(substr($arg, 8));
    else $file = $arg;
}
if (!$file || !$email) {
    fwrite(STDERR, "Uso: php bin/importar-rodada.php <arquivo.json> --email=seu@email [--aplicar]\n");
    exit(1);
}
$data = json_decode((string) @file_get_contents($file), true);
if (!is_array($data) || empty($data['championship']) || empty($data['matches'])) {
    fwrite(STDERR, "Arquivo JSON invalido: {$file}\n");
    exit(1);
}

$pdo = Database::connection();
$norm = static fn (string $v): string => mb_strtolower(preg_replace('/\s+/u', ' ', trim($v)) ?? '', 'UTF-8');

$user = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
$user->execute([$email]);
$user = $user->fetch();
if (!$user) { fwrite(STDERR, "Usuario nao encontrado: {$email}\n"); exit(1); }

$champ = $pdo->prepare('SELECT id, name FROM championships WHERE name = ? AND deleted_at IS NULL');
$champ->execute([$data['championship']]);
$champs = $champ->fetchAll();
if (count($champs) !== 1) { fwrite(STDERR, "Campeonato '{$data['championship']}' encontrado " . count($champs) . " vezes (esperado 1).\n"); exit(1); }
$championshipId = (int) $champs[0]['id'];

$errors = [];
$plan = [];
foreach ($data['matches'] as $i => $spec) {
    $label = ($i + 1) . '. ' . $spec['home'] . ' x ' . $spec['away'];
    $stmt = $pdo->prepare("SELECT m.id, m.status, m.home_team_id, m.away_team_id, ht.name AS home_name, at.name AS away_name FROM matches m INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status NOT IN ('homologated', 'cancelled') AND ((ht.name = ? AND at.name = ?) OR (ht.name = ? AND at.name = ?))");
    $stmt->execute([$championshipId, $spec['home'], $spec['away'], $spec['away'], $spec['home']]);
    $found = $stmt->fetchAll();
    if (count($found) !== 1) { $errors[] = "{$label}: partida pendente encontrada " . count($found) . " vezes (esperado 1)."; continue; }
    $match = $found[0];
    $teamIds = [$match['home_name'] => (int) $match['home_team_id'], $match['away_name'] => (int) $match['away_team_id']];

    $op = $pdo->prepare('SELECT id, status FROM match_operations WHERE match_id = ?');
    $op->execute([(int) $match['id']]);
    $op = $op->fetch();
    if ($op && $op['status'] !== 'open') { $errors[] = "{$label}: operacao ja esta '{$op['status']}', nao mexo."; continue; }
    $existing = $pdo->prepare('SELECT COUNT(*) FROM match_operation_events WHERE match_id = ? AND valid = 1');
    $existing->execute([(int) $match['id']]);
    if ((int) $existing->fetchColumn() > 0) { $errors[] = "{$label}: ja tem eventos lancados na Central; nao duplico."; continue; }

    $events = [];
    $goals = [$spec['home'] => 0, $spec['away'] => 0];
    foreach ($spec['events'] as $event) {
        $teamId = $teamIds[$event['team']] ?? null;
        if (!$teamId) { $errors[] = "{$label}: time '{$event['team']}' nao e desta partida."; continue; }
        $roster = $pdo->prepare('SELECT a.id, a.full_name, ar.status FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id WHERE ar.championship_id = ? AND ar.team_id = ?');
        $roster->execute([$championshipId, $teamId]);
        $hits = array_values(array_filter($roster->fetchAll(), static fn (array $r): bool => $norm($r['full_name']) === $norm($event['athlete'])));
        $approved = array_values(array_filter($hits, static fn (array $r): bool => $r['status'] === 'approved'));
        if (count($approved) !== 1) {
            $why = $hits === [] ? 'nao inscrito no time' : 'inscricao com status ' . implode('/', array_column($hits, 'status'));
            $errors[] = "{$label}: '{$event['athlete']}' ({$event['team']}) — {$why}.";
            continue;
        }
        if (!in_array($event['type'], ['goal', 'yellow', 'second_yellow', 'red'], true)) { $errors[] = "{$label}: tipo '{$event['type']}' nao suportado."; continue; }
        if ($event['type'] === 'goal') $goals[$event['team']] = ($goals[$event['team']] ?? 0) + 1;
        $events[] = ['type' => $event['type'], 'team_id' => $teamId, 'team' => $event['team'], 'athlete_id' => (int) $approved[0]['id'], 'athlete' => $approved[0]['full_name']];
    }
    if ([$goals[$spec['home']] ?? 0, $goals[$spec['away']] ?? 0] !== [(int) $spec['score'][0], (int) $spec['score'][1]]) {
        $errors[] = "{$label}: gols lancados (" . ($goals[$spec['home']] ?? 0) . 'x' . ($goals[$spec['away']] ?? 0) . ") nao batem com o placar {$spec['score'][0]}x{$spec['score'][1]}.";
    }
    $plan[] = ['label' => $label, 'match_id' => (int) $match['id'], 'system' => $match['home_name'] . ' (mandante) x ' . $match['away_name'], 'score' => $spec['score'], 'events' => $events];
}

$names = ['goal' => 'GOL', 'yellow' => 'AMARELO', 'second_yellow' => '2o AMARELO', 'red' => 'VERMELHO'];
echo ($apply ? "=== APLICANDO ===\n" : "=== SIMULACAO (nada sera gravado) ===\n");
echo "Campeonato: {$champs[0]['name']} (#{$championshipId}) | Usuario: {$user['name']}\n\n";
foreach ($plan as $p) {
    echo "{$p['label']}  [partida #{$p['match_id']} — {$p['system']}]  placar {$p['score'][0]}x{$p['score'][1]}\n";
    foreach ($p['events'] as $e) echo "   - {$names[$e['type']]}: {$e['athlete']} ({$e['team']})\n";
}
if ($errors !== []) {
    echo "\nPROBLEMAS — nada foi gravado:\n";
    foreach ($errors as $error) echo "   ! {$error}\n";
    exit(1);
}
if (!$apply) {
    echo "\nTudo confere. Para gravar, rode de novo com --aplicar.\n";
    exit(0);
}

$audit = new AuditService($pdo);
$operations = new MatchOperationRepository($pdo);
$discipline = new DisciplineService(new DisciplineRepository($pdo), $audit);
$reports = new MatchReportService(new MatchReportRepository($pdo), new StorageService(), $audit, new MatchReportHtmlRenderer(), new MatchReportPdf());
$standingsRepository = new StandingsRepository($pdo);
$competition = new CompetitionProgressService($standingsRepository, new StandingsService($standingsRepository, $audit), $audit);
$userId = (int) $user['id'];

echo "\n";
foreach ($plan as $p) {
    $matchId = $p['match_id'];
    $pdo->beginTransaction();
    try {
        $operation = $operations->ensure($matchId, $userId);
        foreach ($p['events'] as $e) {
            $operations->createEvent(['match_id' => $matchId, 'team_id' => $e['team_id'], 'person_type' => 'athlete', 'athlete_id' => $e['athlete_id'], 'related_athlete_id' => null, 'event_type' => $e['type'], 'period' => 'regular', 'minute' => null, 'notes' => 'Importado da sumula manuscrita.', 'created_by' => $userId]);
        }
        $operations->finish((int) $operation['id'], $matchId, $userId);
        $operations->homologate((int) $operation['id'], $matchId, $userId);
        $pdo->commit();
    } catch (\Throwable $exception) {
        $pdo->rollBack();
        fwrite(STDERR, "{$p['label']}: FALHOU, nada gravado nesta partida — {$exception->getMessage()}\n");
        exit(1);
    }
    $match = array_merge($operations->find($matchId) ?? [], ['id' => $matchId, 'status' => 'homologated']);
    $score = $operations->score($operations->find($matchId));
    echo "{$p['label']}: aprovada, placar no sistema {$score['home_score']}x{$score['away_score']}\n";

    $result = $discipline->processHomologatedMatch($match, $userId);
    if (!$result['ok']) echo "   ! disciplina: " . implode(' ', $result['errors']) . "\n";
    try {
        $result = $reports->generateForHomologatedMatch($match, $userId);
        if (!$result['ok']) echo "   ! sumula PDF: " . implode(' ', $result['errors']) . " (gere depois pela tela da partida)\n";
    } catch (\Throwable $exception) {
        echo "   ! sumula PDF: {$exception->getMessage()} (gere depois pela tela da partida)\n";
    }
    $result = $competition->afterHomologation($match, $userId);
    if (!$result['ok']) echo "   ! classificacao: " . implode(' ', $result['errors'] ?? []) . "\n";
    $audit->record('match_operation.imported_from_manual_sheet', $userId, 'match', $matchId, ['events' => count($p['events'])], null);
}
echo "\nConcluido.\n";
