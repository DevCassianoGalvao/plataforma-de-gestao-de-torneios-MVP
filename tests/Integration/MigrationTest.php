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
        $backupMigrations = array_values(array_filter($first, static fn (string $migration): bool => str_starts_with($migration, '0034_') || str_starts_with($migration, '0035_') || str_starts_with($migration, '0039_') || str_starts_with($migration, '0042_')));
        $first = array_values(array_diff($first, $backupMigrations));
        assert_same(['0001_foundation.sql', '0002_authentication.sql', '0003_championships_and_regulations.sql', '0004_teams_staff_and_formations.sql', '0005_athletes_guardians_and_documents.sql', '0006_registration_roster_settings.sql', '0007_groups_rounds_schedule.sql', '0008_tactical_lineups.sql', '0009_match_operation.sql', '0010_discipline_and_suspensions.sql', '0011_standings_and_knockout.sql', '0012_digital_match_reports.sql', '0013_news_blog.sql', '0014_transfers_market.sql', '0015_authentication_integrity_repair.sql', '0016_user_profile_avatar.sql', '0017_operation_accountability_and_governance.sql', '0018_admin_notifications_and_categories.sql', '0019_demo_championship_adult.sql', '0020_portal_engagement_and_officials.sql', '0021_partner_type_schema_repair.sql', '0022_role_consolidation.sql', '0023_transfer_request_permission.sql', '0024_team_manager_identity_permission.sql', '0025_accountability_permission_label.sql', '0026_ensure_accountability_role.sql', '0027_championship_carousel_slides.sql', '0028_match_publication_lifecycle.sql', '0029_match_review_rectification.sql', '0030_configurable_match_evidence_checklist.sql', '0031_round_coverage_monitoring.sql', '0032_isolated_tournament_simulations.sql', '0033_advanced_regulation_and_eligibility.sql', '0036_accountability_completion.sql', '0037_advanced_rectification.sql', '0038_retention_policies.sql', '0040_permanent_data_deletion.sql', '0041_user_deletion_permission.sql'], $first, 'Migrations nao aplicadas');
        assert_same(['0034_application_backups.sql', '0035_backup_settings.sql', '0039_backup_schedule_intervals.sql', '0042_regulation_copa_brasil_rules.sql'], $backupMigrations, 'Migrations de extensao nao aplicadas');
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
        assert_true((bool) Database::connection()->query("SHOW COLUMNS FROM application_backup_settings LIKE 'schedule_interval_days'")->fetchColumn(), 'Periodicidade de backup ausente');
        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'password_reset_tokens', 'login_attempts', 'audit_logs', 'seasons', 'categories', 'championships', 'championship_carousel_slides', 'championship_user_assignments', 'regulations', 'regulation_format_settings', 'regulation_points_settings', 'regulation_tiebreakers', 'regulation_discipline_settings', 'regulation_match_settings', 'regulation_documents', 'regulation_roster_settings', 'regulation_required_documents', 'regulation_transfer_settings', 'regulation_advanced_settings', 'regulation_eligibility_rules', 'regulation_eligibility_exceptions', 'regulation_change_logs', 'staff_roles', 'tactical_formations', 'tactical_formation_slots', 'teams', 'team_user_assignments', 'team_staff', 'positions', 'athletes', 'athlete_secondary_positions', 'legal_guardians', 'athlete_guardians', 'athlete_document_types', 'athlete_registrations', 'athlete_registration_history', 'venues', 'competition_phases', 'competition_groups', 'group_teams', 'competition_rounds', 'matches', 'match_publications', 'match_schedule_changes', 'administrative_decisions', 'match_lineups', 'match_lineup_players', 'match_lineup_staff', 'match_lineup_history', 'match_operations', 'match_operator_assignments', 'match_officials', 'match_operation_events', 'match_substitutions', 'match_operation_history', 'match_media', 'championship_evidence_checklist_items', 'match_evidence_history', 'match_evidence_exceptions', 'discipline_ledger', 'discipline_processing_runs', 'discipline_suspensions', 'discipline_suspension_fulfillments', 'discipline_card_resets', 'discipline_history', 'competition_standings', 'standings_calculation_runs', 'knockout_rounds', 'knockout_ties', 'competition_results', 'match_reports', 'match_report_versions', 'news_articles', 'transfer_movements', 'transfer_movement_history', 'admin_notifications', 'admin_notification_reads', 'championship_officials', 'public_contact_messages', 'simulation_scenarios', 'simulation_matches', 'simulation_match_events', 'application_backups', 'application_backup_settings', 'championship_accountability_settings', 'match_rectification_changes', 'championship_rectification_settings', 'retention_policies', 'retention_actions'] as $table) {
            assert_true((bool) Database::connection()->query('SHOW TABLES LIKE ' . Database::connection()->quote($table))->fetchColumn(), 'Tabela ausente: ' . $table);
        }
    }
}
