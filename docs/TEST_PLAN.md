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
| Unitario Etapa 6 | `tests/Unit/RegistrationTest.php` | transicoes, numero pretendido e periodo de inscricao |
| Integracao Etapa 6 | `tests/Integration/RegistrationIntegrationTest.php` | seed duplo, historico, fluxo, regras, elenco, escopo e IDOR |
| HTTP de contrato Etapa 6 | `tests/Http/RegistrationHttpTest.php` | criar, enviar, analisar, aprovar, elenco, CSRF, IDOR, 403 e base path |
| Unitario Etapa 7 | `tests/Unit/ScheduleTest.php` | round-robin par/impar, folgas, ida e volta, status e agenda |
| Integracao Etapa 7 | `tests/Integration/ScheduleIntegrationTest.php` | migration, seed duplo, limites, idempotencia, conflitos, agenda, escopo e IDOR |
| HTTP de contrato Etapa 7 | `tests/Http/ScheduleHttpTest.php` | fases, grupos, locais, assistente, partida, CSRF, escopo, IDOR, 403 e base path |
| Unitario Etapa 8 | `tests/Unit/LineupTest.php` | status de rascunho/confirmacao e papeis de jogador |
| Integracao Etapa 8 | `tests/Integration/LineupIntegrationTest.php` | seed duplo, onze titulares, reservas, goleiro, capitao, posicoes, fora de posicao, equipe, duplicidade, confirmacao, reabertura, escopo e IDOR |
| HTTP de contrato Etapa 8 | `tests/Http/LineupHttpTest.php` | central, campo funcional, distribuicao automatica, confirmacao, CSRF, perfis, IDOR e base path |
| Unitario Etapa 9 | `tests/Unit/MatchOperationTest.php` | tipos de registro, periodos, minutos opcionais e limites |
| Integracao Etapa 9 | `tests/Integration/MatchOperationIntegrationTest.php` | seed duplo, eventos, placar, penaltis, substituicoes, arbitragem, checklist, finalizacao, homologacao, escopo e IDOR |
| HTTP de contrato Etapa 9 | `tests/Http/MatchOperationHttpTest.php` | central, perfis, CSRF, IDOR, privacidade e base path |
| Unitario Etapa 11 | `tests/Unit/StandingsTest.php` | fases permitidas e preset eliminatorio |
| Integracao Etapa 11 | `tests/Integration/StandingsIntegrationTest.php` | partidas homologadas, pontos, desempates configurados, recalculo idempotente, quartas, semifinais, final, penaltis, campeao, vice e geracao repetida |
| HTTP de contrato Etapa 11 | `tests/Http/StandingsHttpTest.php` | classificacao, CSRF, comunicacao negada e base path |
| Unitario Etapa 12 | `tests/Unit/MatchReportTest.php` | assinatura PDF, duas paginas e campos estruturais HTML |
| Integracao Etapa 12 | `tests/Integration/MatchReportIntegrationTest.php` | dados homologados, PDF privado, idempotencia, nova versao, preservacao do historico e ZIP |
| HTTP de contrato Etapa 12 | `tests/Http/MatchReportHttpTest.php` | preview, PDF atual/historico, pacote, CSRF, privacidade e base path |

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

## Resultado da Etapa 6

- `LINT_OK` com todos os PHP versionados;
- testes unitarios, integracao e HTTP em banco descartavel;
- seed de inscricoes executado duas vezes sem duplicar registros;
- fluxo de envio, analise, pendencia, correcao, aprovacao e elenco oficial validado;
- periodo fechado, idade incompatível, documento ausente/vencido, limite, duplicidade, escopo, IDOR, CSRF e historico validados;
- testes HTTP reais mantem `APP_BASE_PATH=/copa-online`;
- banco, servidor temporario, uploads e arquivos de teste removidos apos a validacao.

## Resultado da Etapa 9

- lint PHP dos arquivos alterados;
- MVP_TESTS_OK unit=9 integration=9 http=9;
- migration 0009 aplicada em banco descartavel;
- seed de operacao executado duas vezes sem duplicar operador ou arbitragem;
- gols, gol contra, assistencias, cartoes, ocorrencias, penaltis e placar administrativo validados;
- penaltis separados do placar normal, limite e janela de substituicoes validados;
- checklist, finalizacao do operador, bloqueio posterior, homologacao separada, CSRF, escopo, IDOR e APP_BASE_PATH=/copa-online validados;
- banco descartavel e artefatos temporarios removidos apos a validacao.

## Resultado da Etapa 8

- lint PHP dos arquivos alterados;
- `MVP_TESTS_OK unit=8 integration=8 http=8`;
- migration `0008` aplicada em banco descartavel;
- seed de atletas elegiveis executado duas vezes sem duplicar 120 registros ficticios;
- distribuicao automatica, onze titulares, reservas, goleiro, capitao, posicao secundaria, fora de posicao e ajuste manual validados;
- confirmacao, bloqueio de edicao, reabertura autorizada, historico, escopo, IDOR, CSRF e `APP_BASE_PATH=/copa-online` validados;
- banco descartavel e artefatos temporarios removidos apos a validacao.

## Limites

Nao existem operacao de partida, gols, cartoes, classificacao final, sumula operacional, portal, noticias ou Vai e Vem. O teste HTTP real cobre as rotas principais de inscricoes, elenco, tabela e central de escalacoes, mas nao substitui a UI/UX definitiva.

## Resultado da Etapa 11

- `LINT_OK files=206`;
- `MVP_TESTS_OK unit=11 integration=11 http=11`;
- migration `0011` aplicada em banco descartavel;
- recalculo por grupo, fonte homologada, pontuacao, criterios configurados, mini-tabela, chave completa e penaltis validados;
- idempotencia de snapshots, ties, partidas e resultado final validada;
- CSRF, escopo, comunicacao negada e `APP_BASE_PATH=/copa-online` validados;
- banco descartavel removido apos a validacao.

## Resultado da Etapa 12

- `LINT_OK files=216`;
- `MVP_TESTS_OK unit=12 integration=12 http=12`;
- migration `0012` aplicada em banco descartavel;
- HTML, PDF 1.4 A4 com duas paginas, caracteres, equipes, atletas, eventos, arbitragem, ocorrencias e confirmacoes validados;
- versao repetida idempotente, retificacao em nova versao, arquivo anterior preservado e pacote ZIP validado;
- permissao, escopo, privacidade, CSRF e `APP_BASE_PATH=/copa-online` validados;
- arquivos privados de teste removidos ao fim da suite.

## Etapa 13 — noticias e blog

- unitario: estados editoriais e normalizacao de slug;
- integracao: seed duplo, CRUD, publicacao, slug duplicado, escopo de comunicacao, exclusao logica e consulta publica;
- HTTP: painel editorial, capa otimizada, rascunho fora do portal, publicacao, busca, XSS escapado, CSRF, IDOR, treinador bloqueado, capa publica e base path `/copa-online`;
- migration: `0013_news_blog.sql` aplicada em banco descartavel;
- resultado: `MVP_TESTS_OK unit=13 integration=13 http=13` e `LINT_OK files=230`;
- artefatos temporarios e banco de teste removidos ao final da suite.

## Etapa 14 - Vai e Vem

- unitario: tipos, status e transicoes terminais;
- integracao: seed duplo, escopo, publicacao, historico, notas internas e preservacao de `athletes.team_id`;
- HTTP: painel, fluxo pendente/aprovado/publicado, filtros, privacidade publica, foto, CSRF, IDOR, treinador bloqueado e base path `/copa-online`;
- migration: `0014_transfers_market.sql` aplicada em banco descartavel;
- resultado esperado: `MVP_TESTS_OK unit=14 integration=14 http=14` e lint PHP completo;
- banco descartavel e artefatos temporarios removidos ao final da suite.

## Resultado da Etapa 7

- `LINT_OK files=165`;
- `MVP_TESTS_OK unit=7 integration=7 http=7`;
- `REAL_HTTP_TESTS_OK checks=23`;
- migration `0007` aplicada em banco descartavel;
- seed executado duas vezes sem duplicar 2 grupos, 10 vinculos, 10 rodadas ou 20 partidas;
- round-robin de grupos pares e impares, folgas, ida e volta, idempotencia, conflitos basicos e historico de agenda validados;
- escopo, IDOR, CSRF e `APP_BASE_PATH=/copa-online` validados;
- banco, servidor temporario e arquivos de teste removidos apos a validacao.

## Resultado da Etapa 5

- `LINT_OK files=136`;
- `MVP_TESTS_OK unit=5 integration=5 http=5`;
- `REAL_HTTP_TESTS_OK checks=16`;
- migration `0005` aplicada em banco descartavel;
- seed executado duas vezes sem duplicar 13 posicoes, 6 tipos, 20 atletas, 20 responsaveis, 20 vinculos, 20 documentos e 20 posicoes secundarias;
- uploads validos, MIME real, limite, executavel bloqueado, escopo, IDOR, CSRF, privacidade e `APP_BASE_PATH=/copa-online` validados;
- banco, servidor temporario e arquivos de teste removidos apos a validacao.
## Etapa 10 — disciplina

- unit: tipos de pessoa/cartão e quantidade de suspensão;
- integration: seed idempotente, ledger após homologação, cartão anulado, suspensão manual/revogada, histórico e bloqueio de atleta suspenso na escalação;
- HTTP: base path /copa-online, CSRF, tela disciplinar e 403 para comunicação;
- migration: migration 0010 em banco descartável;
- validation: lint PHP, seed executado duas vezes e limpeza do banco de teste.
