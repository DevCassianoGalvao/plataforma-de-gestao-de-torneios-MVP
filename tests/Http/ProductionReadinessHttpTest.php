<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use function Tests\assert_same;
use function Tests\assert_true;

final class ProductionReadinessHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        Session::destroy();
        $protected = $router->dispatch(Request::fake('GET', '/torneio-online/admin'));
        assert_same(302, $protected->status, 'Rota protegida nao exigiu autenticacao');
        assert_true(!str_contains((string) ($protected->headers['Location'] ?? ''), '://'), 'Redirect de autenticacao exposto como URL externa');
        $login = $router->dispatch(Request::fake('GET', '/torneio-online/login'));
        preg_match('/name="_csrf" value="([^"]+)"/', $login->body, $match);
        $csrf = html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8');
        $auth = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => 'admin@torneios.local', 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123', 'next' => '//evil.example']));
        assert_same(302, $auth->status, 'Login valido falhou durante smoke de seguranca');
        assert_true(!str_contains((string) ($auth->headers['Location'] ?? ''), 'evil.example'), 'Open redirect aceito no login');
        assert_true(Auth::authenticated(), 'Sessao nao foi criada');
        $portal = $router->dispatch(Request::fake('GET', '/torneio-online/campeonatos/copa-brasil-de-talentos-2026'));
        assert_same(200, $portal->status, 'Portal nao abriu durante smoke de seguranca');
        foreach (['Content-Security-Policy', 'X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy'] as $header) assert_true(isset($portal->headers[$header]), 'Header ausente no portal: ' . $header);
        assert_true(!str_contains($portal->body, 'private_notes'), 'Campo privado vazou no portal');
        $notifications = $router->dispatch(Request::fake('GET', '/torneio-online/admin/notificacoes'));
        assert_same(200, $notifications->status, 'Central de notificacoes nao abriu para administrador');
        assert_true(str_contains($notifications->body, 'notification-center') && str_contains($notifications->body, 'Atividades recentes'), 'Central de notificacoes nao renderizou o feed operacional');
        Database::connection()->exec("UPDATE application_backup_settings SET provider = 'google_drive', google_drive_folder_link = 'https://drive.google.com/drive/folders/test' WHERE id = 1");
        $backups = $router->dispatch(Request::fake('GET', '/torneio-online/admin/backups'));
        assert_same(200, $backups->status, 'Painel de backups nao abriu para administrador');
        assert_true(str_contains($backups->body, 'Periodicidade') && str_contains($backups->body, 'GOOGLE_DRIVE_ACCESS_TOKEN'), 'Configuracao de periodicidade/token nao renderizou');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/backups/1/excluir', ['_csrf' => 'invalid']))->status, 'CSRF de exclusao de backup foi aceito');
        $now = date('Y-m-d H:i:s');
        $key = 'backup-http-delete-' . bin2hex(random_bytes(4));
        $filename = $key . '.zip';
        $backupPath = dirname(__DIR__, 2) . '/storage/backups/' . $filename;
        file_put_contents($backupPath, 'backup http fixture');
        $insertBackup = Database::connection()->prepare('INSERT INTO application_backups (backup_key, type, status, local_status, validation_status, remote_status, local_path, size_bytes, created_by, started_at, completed_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertBackup->execute([$key, 'manual', 'completed', 'completed', 'valid', 'not_configured', 'storage/backups/' . $filename, 19, (int) Auth::user()['id'], $now, $now, $now, $now]);
        $backupId = (int) Database::connection()->lastInsertId();
        try {
            $deleteBackup = $router->dispatch(Request::fake('POST', '/torneio-online/admin/backups/' . $backupId . '/excluir', ['_csrf' => Security::csrfToken()]));
            assert_same(302, $deleteBackup->status, 'Exclusao HTTP do backup falhou');
            assert_same(0, (int) Database::connection()->query('SELECT COUNT(*) FROM application_backups WHERE id = ' . $backupId . ' AND deleted_at IS NULL')->fetchColumn(), 'Rota de exclusao ignorou o identificador do backup');
        } finally {
            @unlink($backupPath);
            Database::connection()->prepare('DELETE FROM audit_logs WHERE action = ? AND resource_id = ?')->execute(['backup.deleted', (string) $backupId]);
            Database::connection()->prepare('DELETE FROM application_backups WHERE id = ?')->execute([$backupId]);
        }
        $monitoring = $router->dispatch(Request::fake('GET', '/torneio-online/admin/rodadas/acompanhamento'));
        assert_same(200, $monitoring->status, 'Acompanhamento por rodada nao abriu para administrador');
        assert_true(str_contains($monitoring->body, 'Acompanhamento por rodada'), 'Painel de acompanhamento nao renderizou');
        $roundId = (int) Database::connection()->query('SELECT id FROM competition_rounds ORDER BY id LIMIT 1')->fetchColumn();
        $export = $router->dispatch(Request::fake('GET', '/torneio-online/admin/rodadas/' . $roundId . '/acompanhamento/exportar'));
        assert_same(200, $export->status, 'Exportacao de pendencias por rodada falhou');
        assert_true(str_contains((string) ($export->headers['Content-Type'] ?? ''), 'text/csv'), 'Exportacao nao retornou CSV');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout de smoke falhou');
        Session::destroy();
    }
}
