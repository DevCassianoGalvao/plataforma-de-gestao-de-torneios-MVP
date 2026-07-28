# Plano e Evidencias de Teste

## Comandos

```text
php bin/lint.php
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
APP_ENV=test DB_NAME=torneios_mvp_http_test SEED_DEMO_PASSWORD=... php bin/console.php migrate
APP_ENV=test DB_NAME=torneios_mvp_http_test SEED_DEMO_PASSWORD=... php bin/console.php db:seed
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/copa-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows, use `$env:NOME='valor'` e `C:\xampp\php\php.exe` quando o PHP nao estiver no PATH.

## Cobertura

| Camada | Arquivos | Cobertura |
|---|---|---|
| Unitario | `tests/Unit/ChampionshipTest.php` | slug, datas, cores, preset e regras estruturadas |
| Integracao | `tests/Integration/ChampionshipIntegrationTest.php` | seed, escopo, versionamento, publicacao, status e uploads |
| HTTP de contrato | `tests/Http/ChampionshipHttpTest.php` | administrador, organizador atribuido, organizador externo, treinador e CSRF |
| HTTP real | `bin/http-test.php` | servidor PHP, cookies, base path, campeonamentos e regulamento |
| Lint | `bin/lint.php` | sintaxe de todos os PHP versionados |

## Resultado da Etapa 3

- `LINT_OK files=92`
- `CHAMPIONSHIP_TESTS_OK unit=3 integration=3 http=3`
- `REAL_HTTP_TESTS_OK checks=8`

Migrations, seed duplo, banco descartavel, servidor HTTP, uploads validos e invalidos foram validados. Bancos e arquivos temporarios foram removidos.

## Limites

Nao existem testes nem implementacao de equipes, atletas, partidas, sumula, portal, noticias ou Vai e Vem.
