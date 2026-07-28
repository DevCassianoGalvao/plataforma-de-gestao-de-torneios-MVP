<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

use App\Support\Database;

set_exception_handler(static function (Throwable $exception): never { fwrite(STDERR, $exception->getMessage().PHP_EOL); exit(1); });
function fail(string $message): never { fwrite(STDERR, $message.PHP_EOL); exit(1); }
function assertNavigation(bool $condition, string $message): void { if (!$condition) fail($message); }
function httpRequest(string $host, int $port, string $path, string $method='GET', string $body='', string $cookie=''): array
{
    $socket = @stream_socket_client('tcp://'.$host.':'.$port, $errno, $error, 3);
    if (!$socket) fail('HTTP connection failed: '.$error);
    $headers = $method.' '.$path." HTTP/1.1\r\nHost: ".$host."\r\nConnection: close\r\n";
    if ($cookie !== '') $headers .= 'Cookie: '.$cookie."\r\n";
    if ($method === 'POST') $headers .= "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($body)."\r\n";
    fwrite($socket, $headers."\r\n".$body);
    $response = stream_get_contents($socket);
    fclose($socket);
    [$rawHeaders,$content] = explode("\r\n\r\n", $response, 2) + ['', ''];
    preg_match('#HTTP/\d\.\d\s+(\d+)#', $rawHeaders, $status);
    preg_match_all('/^Set-Cookie:\s*([^;\r\n]+)/mi', $rawHeaders, $cookies);
    preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $location);
    $cookieValues = $cookies[1] ?? [];
    return ['status'=>(int)($status[1] ?? 0),'body'=>$content,'cookie'=>$cookieValues ? (string) end($cookieValues) : '','location'=>trim($location[1] ?? '')];
}
function loginAt(string $host, int $port, string $basePath, string $email): array
{
    $form = httpRequest($host, $port, $basePath.'/login');
    assertNavigation($form['status'] === 200, 'Login form did not return 200.');
    preg_match('/name="_csrf"\s+value="([^"]+)"/', $form['body'], $token);
    assertNavigation(!empty($token[1]) && $form['cookie'] !== '', 'Login form did not issue CSRF/session.');
    $body = http_build_query(['_csrf'=>$token[1],'email'=>$email,'password'=>'Teste@2026']);
    $login = httpRequest($host, $port, $basePath.'/login', 'POST', $body, $form['cookie']);
    assertNavigation($login['status'] === 302, 'Valid login did not redirect for '.$email.'.');
    return ['cookie'=>$login['cookie'] ?: $form['cookie'],'location'=>$login['location']];
}

$url = getenv('NAVIGATION_TEST_URL') ?: 'http://127.0.0.1:18080/copa-online';
$parts = parse_url($url);
$host = (string) ($parts['host'] ?? '127.0.0.1');
$port = (int) ($parts['port'] ?? 80);
$basePath = rtrim((string) ($parts['path'] ?? ''), '/');
assertNavigation($basePath !== '', 'NAVIGATION_TEST_URL must include the application base path.');
$db = Database::connection();
$lookup = $db->prepare('SELECT t.id,t.slug FROM user_role_assignments a JOIN roles r ON r.id=a.role_id JOIN tournaments t ON t.id=a.tournament_id WHERE r.role_key="tournament_organizer" AND a.status="active" AND a.deleted_at IS NULL AND t.deleted_at IS NULL LIMIT 1');
$lookup->execute();
$tournament = $lookup->fetch();
if (!$tournament) fail('Demo organizer tournament fixture missing. Run php database/seed.php --demo first.');

$roles = ['admin@example.com'=>'/admin/visao-geral','projeto@example.com'=>'/admin/projetos','organizador@example.com'=>'/admin/campeonatos/','treinador01@example.com'=>'/minha-equipe','operador@example.com'=>'/admin/partidas/atribuidas','comunicacao@example.com'=>'/admin/conteudo','auditoria@example.com'=>'/admin/prestacao-de-contas'];
$sessions = [];
    foreach ($roles as $email=>$expected) {
        $sessions[$email] = loginAt($host, $port, $basePath, $email);
        assertNavigation(str_contains($sessions[$email]['location'], $expected), 'Unexpected landing redirect for '.$email.': '.$sessions[$email]['location']);
        $sessionCheck = httpRequest($host, $port, $basePath.'/admin', 'GET', '', $sessions[$email]['cookie']);
        assertNavigation($sessionCheck['status'] === 302 && str_contains($sessionCheck['location'], $expected), 'Session was not retained for '.$email.': '.$sessionCheck['status'].' '.$sessionCheck['location']);
    }
$menuLabels = ['admin@example.com'=>'Organizacoes','projeto@example.com'=>'Projetos','organizador@example.com'=>'Equipes','treinador01@example.com'=>'Minha equipe','operador@example.com'=>'Partidas atribuidas','comunicacao@example.com'=>'Noticias','auditoria@example.com'=>'Indicadores'];
foreach ($menuLabels as $email=>$label) {
    $landingPath = (string) parse_url($sessions[$email]['location'], PHP_URL_PATH);
    $page = httpRequest($host, $port, $landingPath, 'GET', '', $sessions[$email]['cookie']);
    assertNavigation($page['status'] === 200 && str_contains($page['body'], $label), 'Profile menu missing expected label '.$label.' for '.$email.'.');
}
$organizer = $sessions['organizador@example.com'];
$organizerPath = (string) parse_url($organizer['location'], PHP_URL_PATH);
$organizerSlug = basename(rtrim($organizerPath, '/'));
assertNavigation($organizerSlug !== '' && $organizerSlug !== 'campeonatos', 'Organizer redirect did not identify a championship.');
$context = httpRequest($host, $port, $basePath.'/admin/campeonatos/'.$organizerSlug.'/equipes', 'GET', '', $organizer['cookie']);
assertNavigation($context['status'] === 200, 'Scoped championship module did not return 200: '.$context['status'].' '.substr(strip_tags($context['body']), 0, 180));
assertNavigation((str_contains($context['body'], 'Campeonato ativo') || str_contains($context['body'], 'Cadastro assistido')) && str_contains($context['body'], 'Equipes'), 'Championship context or breadcrumb missing.');
assertNavigation(str_contains($context['body'], $basePath.'/admin/campeonatos/'), 'Subdirectory base path missing from navigation.');
$forbidden = httpRequest($host, $port, $basePath.'/admin/campeonatos/'.$organizerSlug.'/grupos', 'GET', '', $sessions['comunicacao@example.com']['cookie']);
assertNavigation($forbidden['status'] === 403, 'Communication profile accessed protected competition module.');
$otherTournament = $db->prepare('SELECT slug FROM tournaments WHERE id<>? AND deleted_at IS NULL ORDER BY id LIMIT 1');
$otherTournament->execute([(int) $tournament['id']]);
$otherSlug = (string) $otherTournament->fetchColumn();
if ($otherSlug !== '') {
    $outsideScope = httpRequest($host, $port, $basePath.'/admin/campeonatos/'.$otherSlug, 'GET', '', $organizer['cookie']);
    assertNavigation($outsideScope['status'] === 404, 'Organizer accessed another championship by changing the slug.');
}
$legacy = httpRequest($host, $port, $basePath.'/admin/tournaments/'.$tournament['id'].'/operation', 'GET', '', $sessions['comunicacao@example.com']['cookie']);
assertNavigation($legacy['status'] === 403, 'Legacy operation was not restricted to superadmin.');
$legacyAdmin = httpRequest($host, $port, $basePath.'/admin/tournaments/'.$tournament['id'].'/operation', 'GET', '', $sessions['admin@example.com']['cookie']);
assertNavigation($legacyAdmin['status'] === 200 && str_contains($legacyAdmin['body'], 'Interface legada em processo de substituicao'), 'Superadmin legacy notice unavailable.');
$assigned = $db->prepare('SELECT m.id FROM match_operator_assignments a JOIN matches m ON m.id=a.match_id JOIN users u ON u.id=a.user_id WHERE u.email=? AND a.status="active" AND a.deleted_at IS NULL AND m.deleted_at IS NULL ORDER BY m.id LIMIT 1');
$assigned->execute(['operador@example.com']);
$assignedMatch = (int) $assigned->fetchColumn();
assertNavigation($assignedMatch > 0, 'Demo operator assignment fixture missing.');
$matchDetail = httpRequest($host, $port, $basePath.'/admin/partidas/'.$assignedMatch, 'GET', '', $sessions['operador@example.com']['cookie']);
assertNavigation($matchDetail['status'] === 200 && (str_contains($matchDetail['body'], 'Detalhe da partida') || str_contains($matchDetail['body'], 'Partida')), 'Assigned match detail route unavailable: '.$matchDetail['status'].' '.substr(strip_tags($matchDetail['body']), 0, 180));
$matchOperation = httpRequest($host, $port, $basePath.'/admin/partidas/'.$assignedMatch.'/operar', 'GET', '', $sessions['operador@example.com']['cookie']);
assertNavigation($matchOperation['status'] === 200 && str_contains($matchOperation['body'], 'Central da partida'), 'Assigned match operation route unavailable.');
$matchDenied = httpRequest($host, $port, $basePath.'/admin/partidas/'.$assignedMatch.'/operar', 'GET', '', $sessions['comunicacao@example.com']['cookie']);
assertNavigation($matchDenied['status'] === 403, 'Unauthorized profile operated an assigned match: '.$matchDenied['status'].' '.substr(strip_tags($matchDenied['body']), 0, 180));
$missing = httpRequest($host, $port, $basePath.'/admin/campeonatos/campeonato-inexistente', 'GET', '', $organizer['cookie']);
assertNavigation($missing['status'] === 404, 'Unknown championship did not return 404.');
assertNavigation(str_contains($context['body'], 'data-drawer') && str_contains($context['body'], 'data-drawer-toggle'), 'Mobile navigation structure missing.');
echo "NAVIGATION_HTTP_E2E_OK\n";
