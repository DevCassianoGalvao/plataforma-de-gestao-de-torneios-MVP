# Plano e Evidencias de Teste

## Comandos

```text
php bin/lint.php
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
APP_ENV=test DB_NAME=torneios_mvp_http_test SEED_DEMO_PASSWORD=... php bin/console.php migrate
APP_ENV=test DB_NAME=torneios_mvp_http_test SEED_DEMO_PASSWORD=... php bin/console.php db:seed
HTTP_TEST_BASE_URL=http://127.0.0.1:18080/copa-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows, os comandos acima usam variaveis de ambiente do PowerShell e `C:\xampp\php\php.exe` quando o PHP nao esta no PATH.

## Cobertura implementada

| Camada | Arquivos | Cobertura |
|---|---|---|
| Unitario | `tests/Unit/FoundationTest.php`, `tests/Unit/AuthTest.php` | base path, escape, CSRF, senha, token e hash |
| Integracao | `tests/Integration/MigrationTest.php`, `tests/Integration/AuthIntegrationTest.php` | migrations, tabelas, seed idempotente, usuarios, papeis, permissoes, token, uso unico e auditoria |
| HTTP de contrato | `tests/Http/FoundationHttpTest.php`, `tests/Http/AuthenticationHttpTest.php` | login valido/invalido, sessao, logout, protecao, 403 e redirecionamento por perfil |
| HTTP real | `bin/http-test.php` | servidor PHP, cookies, CSRF, login, redirect, rota protegida, logout e base path |
| Lint | `bin/lint.php` | sintaxe de todos os PHP versionados |

## Resultado atual

Os testes executados na Etapa 2 passaram: `LINT_OK files=58`, `AUTH_TESTS_OK unit=2 integration=2 http=2` e `REAL_HTTP_TESTS_OK checks=6`. O banco descartavel foi removido depois da validacao.

## Limites

Ainda nao ha testes de campeonatos, equipes, atletas, partidas, sumula, portal ou UI/UX definitiva. Eles entram com as etapas correspondentes.
