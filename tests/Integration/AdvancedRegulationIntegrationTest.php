<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RegulationRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\RegulationService;
use function Tests\assert_same;
use function Tests\assert_true;

final class AdvancedRegulationIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $championshipId = (int) $pdo->query('SELECT id FROM championships ORDER BY id LIMIT 1')->fetchColumn();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        $repository = new RegulationRepository($pdo);
        $published = $repository->published($championshipId);
        $service = new RegulationService($pdo, $repository, new AuditService($pdo));
        $draftId = $service->ensureDraft($championshipId, (int) $admin['id']);
        $draft = $repository->findWithSettings($draftId);
        $phases = $repository->phases($championshipId);
        if (count($phases) < 2) {
            $now = date('Y-m-d H:i:s');
            $pdo->prepare("INSERT INTO competition_phases (championship_id,name,slug,phase_type,sequence_number,group_count,teams_per_group,qualified_per_group,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$championshipId, 'Fase eliminatoria de teste', 'fase-eliminatoria-teste', 'knockout', 2, 0, 0, 0, 'draft', (int) $admin['id'], $now, $now]);
            $phases = $repository->phases($championshipId);
        }
        assert_true(count($phases) >= 2, 'Base de teste precisa de duas fases para elegibilidade.');
        $data = ['name' => $draft['name'], 'effective_from' => $draft['effective_from'], 'format' => $draft['format_settings'], 'points' => $draft['points_settings'], 'discipline' => $draft['discipline_settings'], 'match' => $draft['match_settings'], 'roster' => array_merge($draft['roster_settings'], ['required_document_type_ids' => array_column($draft['required_documents'], 'document_type_id')]), 'tiebreakers' => $draft['tiebreakers'], 'advanced' => ['maximum_staff_members' => 4, 'maximum_teams' => 12, 'allow_registration_after_start' => 0, 'registration_requires_approval' => 1, 'require_complete_documents' => 1, 'require_minor_authorization' => 1, 'roster_change_limit' => 2, 'roster_change_deadline' => null, 'roster_change_phase_limit' => null, 'transfers_enabled' => 0, 'transfers_blocked' => 1, 'block_athlete_played_other_team' => 1, 'allow_administrative_exception' => 1, 'exception_reason_required' => 1, 'abandoned_match_rule' => 'administrative_decision', 'cancelled_match_rule' => 'administrative_decision', 'postponed_match_rule' => 'reschedule'], 'eligibility_rules' => [['source_phase_id' => (int) $phases[0]['id'], 'destination_phase_id' => (int) $phases[1]['id'], 'minimum_participations' => 1, 'participation_type' => 'played', 'registration_approved_before' => null, 'require_no_suspension' => 1, 'require_same_team' => 1, 'require_complete_documents' => 1, 'allow_exception' => 1, 'reason_required' => 1]], 'knockout_pairings' => $draft['knockout_pairings']];
        $saved = $service->save($championshipId, (int) $admin['id'], $data);
        assert_true($saved['ok'], 'Configuracao avancada nao salvou no rascunho.');
        $stored = $repository->findWithSettings($draftId);
        assert_same(4, (int) $stored['advanced_settings']['maximum_staff_members'], 'Limite de comissao nao foi salvo.');
        assert_same(1, count($stored['eligibility_rules']), 'Regra entre fases nao foi salva.');
        assert_true((int) $pdo->query('SELECT COUNT(*) FROM regulation_change_logs WHERE regulation_id = ' . $draftId)->fetchColumn() > 0, 'Historico de alteracao ausente.');
        if ($published) assert_same((int) $published['id'], (int) $repository->published($championshipId)['id'], 'Rascunho alterou regulamento publicado silenciosamente.');
    }
}
