<?php
declare(strict_types=1);

$base = rtrim((string) (getenv('MANAGEMENT_TEST_URL') ?: 'http://127.0.0.1:18080/copa-online'), '/');
$cookie = '';
function request(string $method, string $url, array $data = []): array {
    global $cookie;
    $headers = "Content-Type: application/x-www-form-urlencoded\r\n";
    if ($cookie !== '') $headers .= "Cookie: {$cookie}\r\n";
    $context = stream_context_create(['http'=>['method'=>$method,'header'=>$headers,'content'=>$data ? http_build_query($data) : '','ignore_errors'=>true,'max_redirects'=>0]]);
    $body = file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#HTTP/\S+ (\d+)#', $header, $m)) $status = (int) $m[1];
        if (stripos($header, 'Set-Cookie:') === 0 && preg_match('/^[^;]+/', trim(substr($header, 11)), $m)) $cookie = $m[0];
    }
    return [$status, $body ?: '', $http_response_header ?? []];
}
function csrf(string $html): string { if (!preg_match('/name="_csrf" value="([^"]+)"/', $html, $m)) throw new RuntimeException('CSRF token ausente.'); return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'); }
function expect(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

[$status, $login] = request('GET', $base.'/login');
expect($status === 200, 'Login indisponivel.');
[$status] = request('POST', $base.'/login', ['_csrf'=>csrf($login),'email'=>'organizador@example.com','password'=>'Teste@2026']);
expect($status === 302, 'Login de organizador falhou.');
$slug = 'copa-brasil-de-talentos-2026';
foreach (['equipes','atletas','comissao','responsaveis','inscricoes','documentos','configuracoes'] as $module) {
    [$status, $html] = request('GET', $base.'/admin/campeonatos/'.$slug.'/'.$module);
    expect($status === 200 && str_contains($html, 'Cadastro assistido'), 'Tela assistida indisponivel: '.$module);
    expect(!str_contains($html, 'Project Id') && !str_contains($html, 'settings_json'), 'Campo tecnico exposto: '.$module);
}
[$status, $html] = request('GET', $base.'/admin/campeonatos/'.$slug.'/equipes');
expect($status === 200, 'Tela de equipes indisponivel.');
[$status] = request('POST', $base.'/admin/campeonatos/'.$slug.'/equipes/salvar', ['_csrf'=>csrf($html),'name'=>'Equipe HTTP '.date('His'),'short_name'=>'HTTP','acronym'=>'HTP','city'=>'Cidade Teste','primary_color'=>'#1463A5','secondary_color'=>'#0F7B52']);
expect($status === 302, 'Criacao de equipe nao redirecionou.');
[$status] = request('POST', $base.'/admin/campeonatos/'.$slug.'/equipes/salvar', ['_csrf'=>'invalido','name'=>'Nao deve criar']);
expect($status === 403, 'CSRF invalido nao foi bloqueado.');
[$status] = request('GET', $base.'/admin/campeonatos/copa-serra-sub-15-2026/equipes');
expect($status === 404, 'Escopo de outro campeonato foi exposto.');
echo "management_http_e2e: OK\n";
