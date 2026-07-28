# Testes da Fundação

PHP 8.2 e MySQL local foram usados nesta verificação.

Quando o runtime estiver disponível:

- `Get-ChildItem -Recurse -Filter *.php | % { php -l $_.FullName }`
- `php bin/migrate.php`
- `php bin/seed.php`
- iniciar com `php -S localhost:8080 -t public public/index.php`
- validar login, CSRF, acesso anônimo a `/admin`, CRUD, soft delete, auditoria e `/campeonatos/copa-brasil-de-talentos`.

Verificações implementadas:

- `php tests/integration.php`: criação, atualização e exclusão lógica via repositório PDO.
- `php tests/smoke.php`: renderização pública do campeonato seed.

Testes automatizados PHPUnit ficam planejados para a Fase 5, após a conexão de banco de teste e definição do fluxo de escopos por perfil.
