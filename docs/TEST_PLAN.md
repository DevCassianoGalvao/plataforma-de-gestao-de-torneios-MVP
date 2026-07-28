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
| Unitario Etapa 4 | `tests/Unit/TeamTest.php` | slug, cores, status, equipe, comissao e regras de upload |
| Integracao Etapa 4 | `tests/Integration/TeamIntegrationTest.php` | migration, seed idempotente, escopo, vinculos, comissao, formacoes, slots, status e upload |
| HTTP de contrato Etapa 4 | `tests/Http/TeamHttpTest.php` | perfis, IDOR, CRUD, status, comissao, formacao, upload, CSRF e base path |
| Unitario Etapa 5 | `tests/Unit/AthleteTest.php` | idade, categoria, status, responsavel e MIME |
| Integracao Etapa 5 | `tests/Integration/AthleteIntegrationTest.php` | migration, seed duplo, posicoes, duplicidade, responsavel, cifragem, escopo e arquivos |
| HTTP de contrato Etapa 5 | `tests/Http/AthleteHttpTest.php` | CRUD, menor, documentos, upload, privacidade, IDOR, CSRF e exclusao logica |

## Resultado da Etapa 3

- `LINT_OK files=92`
- `CHAMPIONSHIP_TESTS_OK unit=3 integration=3 http=3`
- `REAL_HTTP_TESTS_OK checks=8`

Migrations, seed duplo, banco descartavel, servidor HTTP, uploads validos e invalidos foram validados. Bancos e arquivos temporarios foram removidos.

## Resultado da Etapa 4

- `LINT_OK files=114`
- `TEAM_TESTS_OK unit=4 integration=4 http=4`
- `REAL_HTTP_TESTS_OK checks=11`
- migration `0004` aplicada em banco descartavel;
- seed executado duas vezes sem duplicar 9 formacoes, 99 slots, 10 equipes, 10 vinculos e 20 membros;
- uploads validos e invalidos, escopo por perfil e `APP_BASE_PATH=/copa-online` validados;
- banco, servidor temporario e arquivos de teste removidos apos a validacao.

## Limites

Nao existem inscricoes, partidas, escalacoes, sumula operacional, portal, noticias ou Vai e Vem. O teste HTTP real cobre as rotas principais de equipes, formacao e atletas, mas nao substitui a UI/UX definitiva.

## Resultado da Etapa 5

- `LINT_OK files=136`;
- `MVP_TESTS_OK unit=5 integration=5 http=5`;
- `REAL_HTTP_TESTS_OK checks=16`;
- migration `0005` aplicada em banco descartavel;
- seed executado duas vezes sem duplicar 13 posicoes, 6 tipos, 20 atletas, 20 responsaveis, 20 vinculos, 20 documentos e 20 posicoes secundarias;
- uploads validos, MIME real, limite, executavel bloqueado, escopo, IDOR, CSRF, privacidade e `APP_BASE_PATH=/copa-online` validados;
- banco, servidor temporario e arquivos de teste removidos apos a validacao.
