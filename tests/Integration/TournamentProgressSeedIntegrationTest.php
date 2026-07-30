<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\TournamentProgressSeed;
use App\Database\MatchLineupDemoSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class TournamentProgressSeedIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        TournamentProgressSeed::run($pdo);
        MatchLineupDemoSeed::run($pdo);

        $championship = $pdo->query("SELECT id, status, visibility FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetch();
        assert_true((bool) $championship, 'Campeonato ficticio ausente para a simulacao');
        assert_same('in_progress', $championship['status'], 'Simulacao nao iniciou o campeonato');
        assert_same('public', $championship['visibility'], 'Simulacao nao publicou o campeonato');

        $championshipId = (int) $championship['id'];
        $groupPhaseId = (int) $pdo->query("SELECT id FROM competition_phases WHERE championship_id = {$championshipId} AND slug = 'fase-grupos' LIMIT 1")->fetchColumn();
        $knockoutPhaseId = (int) $pdo->query("SELECT id FROM competition_phases WHERE championship_id = {$championshipId} AND phase_type = 'knockout' LIMIT 1")->fetchColumn();
        assert_same(20, (int) $pdo->query("SELECT COUNT(*) FROM matches WHERE phase_id = {$groupPhaseId} AND status = 'homologated'")->fetchColumn(), 'Fase de grupos nao foi concluida');
        assert_same(8, (int) $pdo->query("SELECT COUNT(*) FROM competition_standings WHERE phase_id = {$groupPhaseId} AND situation = 'qualified'")->fetchColumn(), 'Classificados da fase de grupos incorretos');
        assert_same(4, (int) $pdo->query("SELECT COUNT(*) FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE kr.phase_id = {$knockoutPhaseId} AND kr.stage = 'quarterfinals' AND kt.status = 'finished'")->fetchColumn(), 'Quartas de final nao foram concluidas');
        assert_same(2, (int) $pdo->query("SELECT COUNT(*) FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id INNER JOIN matches m ON m.id = kt.match_id WHERE kr.phase_id = {$knockoutPhaseId} AND kr.stage = 'semifinals' AND kt.home_team_id IS NOT NULL AND kt.away_team_id IS NOT NULL AND m.status = 'scheduled'")->fetchColumn(), 'Semifinais nao foram agendadas');
        assert_same(4, (int) $pdo->query("SELECT COUNT(*) FROM match_lineups ml INNER JOIN matches m ON m.id = ml.match_id INNER JOIN knockout_ties kt ON kt.match_id = m.id INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE kr.phase_id = {$knockoutPhaseId} AND kr.stage = 'semifinals' AND ml.status = 'confirmed'")->fetchColumn(), 'Escalações da simulação não foram confirmadas');

        $first = self::snapshot($pdo, $championshipId, $knockoutPhaseId);
        TournamentProgressSeed::run($pdo);
        MatchLineupDemoSeed::run($pdo);
        assert_same($first, self::snapshot($pdo, $championshipId, $knockoutPhaseId), 'Reexecucao da simulacao alterou a quantidade de dados');
    }

    private static function snapshot(\PDO $pdo, int $championshipId, int $knockoutPhaseId): array
    {
        return [
            (int) $pdo->query("SELECT COUNT(*) FROM match_operation_events e INNER JOIN matches m ON m.id = e.match_id WHERE m.championship_id = {$championshipId}")->fetchColumn(),
            (int) $pdo->query("SELECT COUNT(*) FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE kr.phase_id = {$knockoutPhaseId}")->fetchColumn(),
            (int) $pdo->query("SELECT COUNT(*) FROM matches WHERE phase_id = {$knockoutPhaseId}")->fetchColumn(),
        ];
    }
}
