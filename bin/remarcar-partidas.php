<?php
declare(strict_types=1);

/*
 * Remarca partidas de um campeonato em bloco e restaura a partida cancelada sem querer.
 *   - partidas de --de2 passam para --para2 (feito primeiro)
 *   - partidas de --de1 passam para --para1
 *   - a partida CANCELADA de --de2 volta a "agendada" e vai para --para2 (exatamente uma; se houver mais, nao mexe)
 * Mantem horario e local. Registra historico de agenda e auditoria em cada partida.
 *
 * Uso:
 *   php bin/remarcar-partidas.php --email=admin@x.com --campeonato="Copa Brasil de Talentos 2026" \
 *       --de1=2026-11-01 --para1=2026-11-08 --de2=2026-11-08 --para2=2026-11-15            (simulacao)
 *   ... --aplicar                                                                              (grava)
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Repositories\ScheduleRepository;
use App\Services\AuditService;

$opt = ['aplicar' => false];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--aplicar') $opt['aplicar'] = true;
    elseif (preg_match('/^--([a-z0-9]+)=(.*)$/', $arg, $m)) $opt[$m[1]] = $m[2];
}
foreach (['email', 'campeonato', 'de1', 'para1', 'de2', 'para2'] as $required) {
    if (empty($opt[$required])) { fwrite(STDERR, "Faltou --{$required}=...\n"); exit(1); }
}
foreach (['de1', 'para1', 'de2', 'para2'] as $d) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $opt[$d]);
    if (!$parsed || $parsed->format('Y-m-d') !== $opt[$d]) { fwrite(STDERR, "Data invalida em --{$d}\n"); exit(1); }
}
if ($opt['para1'] !== $opt['de2']) { fwrite(STDERR, "Esperado: --para1 igual a --de2 (as partidas de de2 saem antes).\n"); exit(1); }

$pdo = Database::connection();
$user = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
$user->execute([$opt['email']]);
$user = $user->fetch();
if (!$user) { fwrite(STDERR, "Usuario nao encontrado: {$opt['email']}\n"); exit(1); }
$champ = $pdo->prepare('SELECT id, name FROM championships WHERE name = ? AND deleted_at IS NULL AND archived_at IS NULL');
$champ->execute([$opt['campeonato']]);
$champs = $champ->fetchAll();
if (count($champs) !== 1) { fwrite(STDERR, "Campeonato encontrado " . count($champs) . " vezes (esperado 1).\n"); exit(1); }
$championshipId = (int) $champs[0]['id'];

$select = "SELECT m.id, m.match_date, m.match_time, m.venue_id, m.status, m.home_team_id, m.away_team_id, m.observation, ht.name AS home, at.name AS away, g.name AS grupo, r.round_number
    FROM matches m INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id
    INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id
    WHERE m.championship_id = ? AND m.match_date = ? ORDER BY m.match_time, m.id";
$fetch = static function (string $date) use ($pdo, $select, $championshipId): array {
    $s = $pdo->prepare($select);
    $s->execute([$championshipId, $date]);
    return $s->fetchAll();
};
$movable = ['draft', 'scheduled', 'confirmed', 'postponed'];
$label = static fn (array $m): string => "#{$m['id']} {$m['home']} x {$m['away']} ({$m['grupo']} R{$m['round_number']}) " . substr((string) $m['match_time'], 0, 5);

$day1 = $fetch($opt['de1']);
$day2 = $fetch($opt['de2']);
$errors = [];
$plan = []; // [match, para_data, restaurar?]
$skipped = [];

$cancelled2 = array_values(array_filter($day2, static fn (array $m): bool => $m['status'] === 'cancelled'));
if (count($cancelled2) === 0) {
    $any = $pdo->prepare("SELECT m.id, ht.name AS home, at.name AS away, m.match_date FROM matches m INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status = 'cancelled'");
    $any->execute([$championshipId]);
    $others = $any->fetchAll();
    $errors[] = "Nenhuma partida CANCELADA em {$opt['de2']}." . ($others ? ' Canceladas no campeonato: ' . implode('; ', array_map(static fn (array $m): string => "#{$m['id']} {$m['home']} x {$m['away']} em " . ($m['match_date'] ?: 'sem data'), $others)) : ' Nao ha nenhuma cancelada no campeonato.');
} elseif (count($cancelled2) > 1) {
    $errors[] = 'Mais de uma partida cancelada em ' . $opt['de2'] . ' (' . implode('; ', array_map($label, $cancelled2)) . '). Nao sei qual restaurar: me diga.';
}

foreach ($day2 as $m) {
    if ($m['status'] === 'cancelled') { if (count($cancelled2) === 1) $plan[] = [$m, $opt['para2'], true]; continue; }
    if (in_array($m['status'], $movable, true)) $plan[] = [$m, $opt['para2'], false];
    else $skipped[] = [$m, $opt['de2']];
}
foreach ($day1 as $m) {
    if (in_array($m['status'], $movable, true)) $plan[] = [$m, $opt['para1'], false];
    else $skipped[] = [$m, $opt['de1']];
}

// Estado final do campeonato (data/hora/times/local) para detectar choque de horario.
$final = [];
$all = $pdo->prepare("SELECT id, match_date, match_time, venue_id, status, home_team_id, away_team_id FROM matches WHERE championship_id = ? AND status NOT IN ('cancelled')");
$all->execute([$championshipId]);
foreach ($all->fetchAll() as $row) $final[(int) $row['id']] = $row;
foreach ($plan as [$m, $newDate, $restore]) { $final[(int) $m['id']] = array_merge($m, ['match_date' => $newDate, 'status' => $restore ? 'scheduled' : $m['status']]); }
$byKey = [];
foreach ($final as $row) {
    if (empty($row['match_date']) || empty($row['match_time'])) continue;
    foreach ([['t', $row['home_team_id']], ['t', $row['away_team_id']], ['v', $row['venue_id']]] as [$kind, $ref]) {
        if (!$ref) continue;
        $byKey[$row['match_date'] . ' ' . $row['match_time'] . " {$kind}{$ref}"][] = (int) $row['id'];
    }
}
$moved = array_map(static fn (array $p): int => (int) $p[0]['id'], $plan);
foreach ($byKey as $key => $ids) {
    if (count(array_unique($ids)) > 1 && array_intersect($ids, $moved)) $errors[] = 'Choque de horario/equipe/local em ' . $key . ': partidas #' . implode(', #', array_unique($ids));
}

echo ($opt['aplicar'] ? "=== APLICANDO ===\n" : "=== SIMULACAO (nada sera gravado) ===\n");
echo "Campeonato: {$champs[0]['name']} (#{$championshipId}) | Usuario: {$user['name']}\n\n";
echo 'Partidas em ' . $opt['de1'] . ': ' . count($day1) . ' | em ' . $opt['de2'] . ': ' . count($day2) . "\n\n";
foreach ($plan as [$m, $newDate, $restore]) {
    echo ($restore ? '  RESTAURAR + ' : '  ') . $label($m) . ": {$m['match_date']} -> {$newDate}" . ($restore ? " (de CANCELADA para AGENDADA)\n" : "\n");
}
foreach ($skipped as [$m, $date]) echo "  NAO MEXE ({$m['status']}): " . $label($m) . " em {$date}\n";
$rounds = $pdo->prepare("SELECT g.name AS grupo, r.round_number, r.period_start, r.period_end FROM competition_rounds r INNER JOIN competition_groups g ON g.id = r.group_id INNER JOIN competition_phases p ON p.id = r.phase_id WHERE p.championship_id = ? AND (r.period_start IN (?, ?) OR r.period_end IN (?, ?))");
$rounds->execute([$championshipId, $opt['de1'], $opt['de2'], $opt['de1'], $opt['de2']]);
foreach ($rounds->fetchAll() as $r) echo "  AVISO: rodada {$r['grupo']} R{$r['round_number']} tem periodo {$r['period_start']}..{$r['period_end']} (nao alterado por este script)\n";

if ($errors !== []) {
    echo "\nPROBLEMAS - nada foi gravado:\n";
    foreach ($errors as $e) echo "   ! {$e}\n";
    exit(1);
}
if (!$opt['aplicar']) { echo "\nTudo confere. Para gravar, rode de novo com --aplicar.\n"; exit(0); }

$schedules = new ScheduleRepository($pdo);
$audit = new AuditService($pdo);
$userId = (int) $user['id'];
$pdo->beginTransaction();
try {
    foreach ($plan as [$m, $newDate, $restore]) {
        $status = $restore ? 'scheduled' : $m['status'];
        $reason = $restore ? "Partida restaurada (cancelada sem querer) e remarcada de {$m['match_date']} para {$newDate}." : "Remarcacao em bloco de {$m['match_date']} para {$newDate}.";
        $schedules->updateMatchAgenda((int) $m['id'], ['match_date' => $newDate, 'match_time' => $m['match_time'], 'venue_id' => $m['venue_id'], 'status' => $status, 'observation' => $m['observation']]);
        $schedules->recordScheduleChange((int) $m['id'], ['action' => $restore ? 'restore' : 'reschedule', 'from_date' => $m['match_date'], 'from_time' => $m['match_time'], 'from_venue_id' => $m['venue_id'], 'to_date' => $newDate, 'to_time' => $m['match_time'], 'to_venue_id' => $m['venue_id'], 'reason' => $reason], $userId);
        $audit->record($restore ? 'schedule.restored' : 'schedule.reschedule', $userId, 'match', (int) $m['id'], ['reason' => $reason, 'from' => $m['match_date'], 'to' => $newDate], null);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'FALHOU, nada foi gravado: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "\nConcluido: " . count($plan) . " partida(s) atualizada(s).\n";
