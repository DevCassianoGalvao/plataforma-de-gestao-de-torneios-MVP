<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Repositories\MatchReportRepository;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class MatchReportHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php'; $pdo = Database::connection(); $matchId = (int) $pdo->query("SELECT match_id FROM match_reports ORDER BY id LIMIT 1")->fetchColumn(); $match = $pdo->query('SELECT round_id FROM matches WHERE id = ' . $matchId)->fetch(); $versionId = (int) $pdo->query('SELECT current_version_id FROM match_reports WHERE match_id = ' . $matchId)->fetchColumn(); $verification = (string) $pdo->query('SELECT verification_code FROM match_report_versions WHERE id = ' . $versionId)->fetchColumn();
        self::login($router, 'admin@torneios.local');
        $html = $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/sumula')); assert_same(200, $html->status, 'Preview HTML da sumula nao abriu'); assert_true(str_contains($html->body, 'Súmula digital') && str_contains($html->body, $verification), 'Preview da sumula nao renderizou dados e codigo');
        $pdf = $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/sumula/pdf')); assert_same(200, $pdf->status, 'Download PDF nao abriu'); assert_same('application/pdf', $pdf->headers['Content-Type'] ?? '', 'MIME do PDF incorreto'); assert_true(str_starts_with($pdf->body, '%PDF-1.4'), 'Download nao retornou PDF real');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/sumulas/versoes/' . $versionId . '/pdf'))->status, 'Download de versao historica falhou');
        $zip = $router->dispatch(Request::fake('GET', '/torneio-online/admin/sumulas/rodadas/' . $match['round_id'] . '.zip')); assert_same(200, $zip->status, 'Pacote por rodada nao abriu'); assert_true(str_starts_with($zip->body, 'PK'), 'Pacote HTTP nao retornou ZIP');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/partidas/' . $matchId . '/sumula/gerar', ['_csrf' => 'invalid']))->status, 'CSRF da sumula foi aceito');
        self::logout($router); self::login($router, 'prestacao@torneios.local'); assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/sumula'))->status, 'Prestacao de contas recebeu sumula privada'); self::logout($router);
        $repo = new MatchReportRepository($pdo); $storage = new StorageService(); foreach ($repo->versions($matchId) as $version) $storage->delete($version['storage_path']); foreach (glob(dirname(__DIR__, 2) . '/storage/private/reports/packages/*') ?: [] as $package) @unlink($package);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123'])); assert_same(302, $response->status, 'Login da sumula falhou'); assert_true(Auth::authenticated(), 'Sessao da sumula nao foi criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout da sumula falhou'); Session::destroy();
    }
}
