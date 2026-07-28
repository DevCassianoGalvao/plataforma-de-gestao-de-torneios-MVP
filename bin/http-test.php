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
    $logout = request('POST', '/logout', ['_csrf' => csrf($admin['body'])]);
    check($logout['status'] === 302, 'Logout nao redirecionou');
    echo "REAL_HTTP_TESTS_OK checks=6\n";
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
