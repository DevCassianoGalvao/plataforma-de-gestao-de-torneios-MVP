<?php
declare(strict_types=1);

$base = rtrim(getenv('HTTP_TEST_BASE_URL') ?: '', '/');
$email = getenv('TEST_EMAIL') ?: 'admin@torneios.local';
$password = getenv('TEST_PASSWORD') ?: '';
if ($base === '' || $password === '') {
    fwrite(STDERR, "HTTP_TEST_BASE_URL e TEST_PASSWORD sao obrigatorios.\n");
    exit(1);
}
if (!extension_loaded('curl')) {
    fwrite(STDERR, "Extensao cURL obrigatoria para o teste HTTP real.\n");
    exit(1);
}

$jar = tempnam(sys_get_temp_dir(), 'torneios-http-');

try {
    $getLogin = request('GET', '/login');
    check($getLogin['status'] === 200, 'GET /login nao respondeu 200');
    $csrf = csrf($getLogin['body']);
    check($csrf !== '', 'CSRF ausente na tela de login');

    $invalid = request('POST', '/login', ['_csrf' => $csrf, 'email' => $email, 'password' => 'senha-incorreta']);
    check($invalid['status'] === 422 && str_contains($invalid['body'], 'E-mail ou senha invalidos.'), 'Login invalido nao foi tratado genericamente');

    $valid = request('POST', '/login', ['_csrf' => csrf($invalid['body']), 'email' => $email, 'password' => $password]);
    check($valid['status'] === 302 && str_contains($valid['headers'], '/admin'), 'Login valido nao redirecionou para /admin');

    $admin = request('GET', '/admin/usuarios');
    check($admin['status'] === 200 && str_contains($admin['body'], 'Usuarios'), 'Rota protegida nao abriu para administrador');
    $championships = request('GET', '/admin/campeonatos');
    check($championships['status'] === 200 && str_contains($championships['body'], 'Campeonatos'), 'Rota de campeonatos nao abriu para administrador');
    $regulation = request('GET', '/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento');
    check($regulation['status'] === 200 && str_contains($regulation['body'], 'Regulamento'), 'Rota de regulamento nao abriu para administrador');
    $teams = request('GET', '/admin/equipes');
    check($teams['status'] === 200 && str_contains($teams['body'], 'Equipes'), 'Rota de equipes nao abriu para administrador');
    $team = request('GET', '/admin/equipes/estrela-norte-fc');
    check($team['status'] === 200 && str_contains($team['body'], 'Estrela Norte FC'), 'Pagina da equipe nao abriu');
    $formation = request('GET', '/admin/equipes/estrela-norte-fc/formacao');
    check($formation['status'] === 200 && str_contains($formation['body'], 'Formacao padrao'), 'Pagina de formacao nao abriu');
    $athletes = request('GET', '/admin/atletas');
    check($athletes['status'] === 200 && str_contains($athletes['body'], 'Atletas'), 'Rota de atletas nao abriu para administrador');
    $positions = request('GET', '/admin/posicoes');
    check($positions['status'] === 200 && str_contains($positions['body'], 'Posicoes'), 'Catalogo de posicoes nao abriu');
    $athlete = request('GET', '/admin/atletas/1');
    check($athlete['status'] === 200 && str_contains($athlete['body'], 'Atleta'), 'Detalhe de atleta nao abriu');
    $guardians = request('GET', '/admin/atletas/1/responsaveis');
    check($guardians['status'] === 200 && str_contains($guardians['body'], 'Responsaveis legais'), 'Rota de responsaveis nao abriu');
    $documents = request('GET', '/admin/atletas/1/documentos');
    check($documents['status'] === 200 && str_contains($documents['body'], 'Documentos'), 'Rota de documentos nao abriu');
    $registrations = request('GET', '/admin/inscricoes');
    check($registrations['status'] === 200 && str_contains($registrations['body'], 'Inscricoes'), 'Rota de inscricoes nao abriu');
    $roster = request('GET', '/admin/inscricoes/elenco');
    check($roster['status'] === 200 && str_contains($roster['body'], 'Elenco oficial'), 'Rota de elenco nao abriu');
    $schedule = request('GET', '/admin/tabela');
    check($schedule['status'] === 200 && str_contains($schedule['body'], 'Tabela e partidas'), 'Rota de tabela nao abriu');
    $phases = request('GET', '/admin/fases');
    check($phases['status'] === 200 && str_contains($phases['body'], 'Fases'), 'Rota de fases nao abriu');
    $venues = request('GET', '/admin/locais');
    check($venues['status'] === 200 && str_contains($venues['body'], 'Locais'), 'Rota de locais nao abriu');
    $assistant = request('GET', '/admin/tabela/assistente');
    check($assistant['status'] === 200 && str_contains($assistant['body'], 'Assistente de tabela'), 'Rota de assistente nao abriu');
    $match = request('GET', '/admin/partidas/1');
    check($match['status'] === 200 && str_contains($match['body'], 'Agenda'), 'Rota de partida nao abriu');
    $lineups = request('GET', '/admin/partidas/1/escalacoes');
    check($lineups['status'] === 200 && str_contains($lineups['body'], 'Escalacoes da partida'), 'Rota de escalacoes nao abriu');
    $operation = request('GET', '/admin/partidas/1/operacao');
    $operationText = strip_tags($operation['body']);
    check($operation['status'] === 200 && str_contains($operation['body'], 'Central operacional da partida'), 'Central operacional nao abriu status=' . $operation['status'] . ' body=' . substr($operationText, 0, 300) . ' tail=' . substr($operationText, -500));
    $logout = request('POST', '/logout', ['_csrf' => csrf($admin['body'])]);
    check($logout['status'] === 302, 'Logout nao redirecionou');
    echo "REAL_HTTP_TESTS_OK checks=25\n";
} finally {
    if (is_string($jar) && is_file($jar)) {
        unlink($jar);
    }
}

function request(string $method, string $path, array $fields = []): array
{
    global $base, $jar;
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_POSTFIELDS => $method === 'POST' ? http_build_query($fields) : null,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $raw = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerLength = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    curl_close($handle);
    return ['status' => $status, 'headers' => substr($raw, 0, $headerLength), 'body' => substr($raw, $headerLength)];
}

function csrf(string $html): string
{
    return preg_match('/name="_csrf" value="([^"]+)"/', $html, $matches) === 1 ? html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8') : '';
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
