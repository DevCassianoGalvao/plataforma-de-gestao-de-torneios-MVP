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
        assert_same(['0001_foundation.sql', '0002_authentication.sql', '0003_championships_and_regulations.sql', '0004_teams_staff_and_formations.sql', '0005_athletes_guardians_and_documents.sql', '0006_registration_roster_settings.sql', '0007_groups_rounds_schedule.sql', '0008_tactical_lineups.sql', '0009_match_operation.sql', '0010_discipline_and_suspensions.sql', '0011_standings_and_knockout.sql', '0012_digital_match_reports.sql', '0013_news_blog.sql', '0014_transfers_market.sql', '0015_authentication_integrity_repair.sql', '0016_user_profile_avatar.sql', '0017_operation_accountability_and_governance.sql', '0018_admin_notifications_and_categories.sql', '0019_demo_championship_adult.sql', '0020_portal_engagement_and_officials.sql'], $first, 'Migrations nao aplicadas');
        assert_same([], $runner->migrate(), 'Migration nao idempotente');
        $status = $runner->status();
        assert_same('applied', $status[0]['status'] ?? null, 'Status da migration incorreto');
        $health = Database::connection()->query('SELECT status FROM foundation_health WHERE id = 1')->fetchColumn();
        assert_true($health === 'ok', 'Health check nao persistido');
        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'password_reset_tokens', 'login_attempts', 'audit_logs', 'seasons', 'categories', 'championships', 'championship_user_assignments', 'regulations', 'regulation_format_settings', 'regulation_points_settings', 'regulation_tiebreakers', 'regulation_discipline_settings', 'regulation_match_settings', 'regulation_documents', 'regulation_roster_settings', 'regulation_required_documents', 'regulation_transfer_settings', 'staff_roles', 'tactical_formations', 'tactical_formation_slots', 'teams', 'team_user_assignments', 'team_staff', 'positions', 'athletes', 'athlete_secondary_positions', 'legal_guardians', 'athlete_guardians', 'athlete_document_types', 'athlete_documents', 'athlete_registrations', 'athlete_registration_history', 'venues', 'competition_phases', 'competition_groups', 'group_teams', 'competition_rounds', 'matches', 'match_schedule_changes', 'administrative_decisions', 'match_lineups', 'match_lineup_players', 'match_lineup_staff', 'match_lineup_history', 'match_operations', 'match_operator_assignments', 'match_officials', 'match_operation_events', 'match_substitutions', 'match_operation_history', 'discipline_ledger', 'discipline_processing_runs', 'discipline_suspensions', 'discipline_suspension_fulfillments', 'discipline_card_resets', 'discipline_history', 'competition_standings', 'standings_calculation_runs', 'knockout_rounds', 'knockout_ties', 'competition_results', 'match_reports', 'match_report_versions', 'news_articles', 'transfer_movements', 'transfer_movement_history', 'admin_notifications', 'admin_notification_reads', 'championship_officials', 'public_contact_messages'] as $table) {
            assert_true((bool) Database::connection()->query('SHOW TABLES LIKE ' . Database::connection()->quote($table))->fetchColumn(), 'Tabela ausente: ' . $table);
        }
    }
}
