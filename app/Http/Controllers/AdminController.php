<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

final class AdminController extends Controller
{
    public function dashboard(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'system.access');
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page('Painel administrativo', 'admin/dashboard', ['user' => $guard, 'role' => $this->authorization->primaryRole($guard), 'metrics' => $this->metrics()]);
    }

    private function metrics(): array
    {
        $pdo = Database::connection();
        $count = static function (\PDO $pdo, string $sql): int {
            try {
                return (int) $pdo->query($sql)->fetchColumn();
            } catch (\Throwable) {
                return 0;
            }
        };
        return [
            'championships' => $count($pdo, "SELECT COUNT(*) FROM championships WHERE deleted_at IS NULL"),
            'teams' => $count($pdo, "SELECT COUNT(*) FROM teams WHERE deleted_at IS NULL AND status = 'active'"),
            'athletes' => $count($pdo, "SELECT COUNT(*) FROM athletes WHERE deleted_at IS NULL AND status = 'active'"),
            'registrations' => $count($pdo, "SELECT COUNT(*) FROM athlete_registrations WHERE status = 'approved'"),
            'pending_registrations' => $count($pdo, "SELECT COUNT(*) FROM athlete_registrations WHERE status IN ('submitted', 'under_review', 'pending_correction')"),
            'upcoming_matches' => $count($pdo, "SELECT COUNT(*) FROM matches WHERE status IN ('scheduled', 'confirmed') AND (match_date IS NULL OR match_date >= CURRENT_DATE)"),
            'awaiting_homologation' => $count($pdo, "SELECT COUNT(*) FROM match_operations WHERE status = 'awaiting_homologation'"),
            'suspended' => $count($pdo, "SELECT COUNT(*) FROM discipline_suspensions WHERE status = 'active'"),
            'at_risk' => $count($pdo, "SELECT COUNT(*) FROM (SELECT athlete_id FROM discipline_ledger WHERE status = 'considered' AND card_type = 'yellow' GROUP BY athlete_id HAVING COUNT(*) >= 2) warning_rows"),
            'homologated_results' => $count($pdo, "SELECT COUNT(*) FROM matches WHERE status = 'homologated'"),
            'published_news' => $count($pdo, "SELECT COUNT(*) FROM news_articles WHERE deleted_at IS NULL AND status = 'published'"),
        ];
    }
}
