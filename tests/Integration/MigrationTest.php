<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\MigrationRunner;
use function Tests\assert_same;
use function Tests\assert_true;

final class MigrationTest
{
    public static function run(): void
    {
        $runner = new MigrationRunner(Database::connection(), dirname(__DIR__, 2) . '/database/migrations');
        $first = $runner->migrate();
        $expected = array_map('basename', glob(dirname(__DIR__, 2) . '/database/migrations/*.sql') ?: []);
        sort($expected, SORT_STRING);
        assert_same($expected, $first, 'Migrations nao aplicadas');
        assert_same([], $runner->migrate(), 'Migration nao idempotente');
        $status = $runner->status();
        assert_same('applied', $status[0]['status'] ?? null, 'Status da migration incorreto');
        $health = Database::connection()->query('SELECT status FROM foundation_health WHERE id = 1')->fetchColumn();
        assert_true($health === 'ok', 'Health check nao persistido');
        assert_true((bool) Database::connection()->query("SHOW COLUMNS FROM championship_sponsors LIKE 'partner_type'")->fetchColumn(), 'Reparo do tipo de parceiro nao aplicado');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'championship_document_deadlines'")->fetchColumn(), 'Tabela de prazo documental ausente');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'simulation_scenarios'")->fetchColumn(), 'Tabela de cenarios isolados ausente');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'regulation_eligibility_rules'")->fetchColumn(), 'Tabela de elegibilidade ausente');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'regulation_competition_rules'")->fetchColumn(), 'Tabela de regras específicas da Copa ausente');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'application_backups'")->fetchColumn(), 'Tabela de backups ausente');
        assert_true((bool) Database::connection()->query("SHOW TABLES LIKE 'application_backup_settings'")->fetchColumn(), 'Tabela de configuracao de backups ausente');
        assert_true((bool) Database::connection()->query("SHOW COLUMNS FROM championships LIKE 'requires_guardian'")->fetchColumn(), 'Politica de responsavel legal ausente');
        assert_true((bool) Database::connection()->query("SHOW COLUMNS FROM application_backup_settings LIKE 'schedule_interval_days'")->fetchColumn(), 'Periodicidade de backup ausente');
        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'password_reset_tokens', 'login_attempts', 'audit_logs', 'seasons', 'categories', 'championships', 'championship_carousel_slides', 'championship_user_assignments', 'regulations', 'regulation_format_settings', 'regulation_points_settings', 'regulation_tiebreakers', 'regulation_discipline_settings', 'regulation_match_settings', 'regulation_documents', 'regulation_roster_settings', 'regulation_required_documents', 'regulation_transfer_settings', 'regulation_advanced_settings', 'regulation_eligibility_rules', 'regulation_eligibility_exceptions', 'regulation_change_logs', 'staff_roles', 'tactical_formations', 'tactical_formation_slots', 'teams', 'team_user_assignments', 'team_staff', 'positions', 'athletes', 'athlete_secondary_positions', 'legal_guardians', 'athlete_guardians', 'athlete_document_types', 'athlete_registrations', 'athlete_registration_history', 'venues', 'competition_phases', 'competition_groups', 'group_teams', 'competition_rounds', 'matches', 'match_publications', 'match_schedule_changes', 'administrative_decisions', 'match_lineups', 'match_lineup_players', 'match_lineup_staff', 'match_lineup_history', 'match_operations', 'match_operator_assignments', 'match_officials', 'match_operation_events', 'match_substitutions', 'match_operation_history', 'match_media', 'championship_evidence_checklist_items', 'match_evidence_history', 'match_evidence_exceptions', 'discipline_ledger', 'discipline_processing_runs', 'discipline_suspensions', 'discipline_suspension_fulfillments', 'discipline_card_resets', 'discipline_history', 'competition_standings', 'standings_calculation_runs', 'knockout_rounds', 'knockout_ties', 'competition_results', 'match_reports', 'match_report_versions', 'news_articles', 'transfer_movements', 'transfer_movement_history', 'admin_notifications', 'admin_notification_reads', 'championship_officials', 'public_contact_messages', 'simulation_scenarios', 'simulation_matches', 'simulation_match_events', 'application_backups', 'application_backup_settings', 'championship_accountability_settings', 'match_rectification_changes', 'championship_rectification_settings', 'retention_policies', 'retention_actions'] as $table) {
            assert_true((bool) Database::connection()->query('SHOW TABLES LIKE ' . Database::connection()->quote($table))->fetchColumn(), 'Tabela ausente: ' . $table);
        }
    }
}
