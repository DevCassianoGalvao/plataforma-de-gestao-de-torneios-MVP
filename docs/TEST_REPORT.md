# Relatório de Testes

## UI foundation - 27/07/2026

- `php tests/ui_foundation_e2e.php`: aprovado (`UI_FOUNDATION_E2E_OK`). Verifica tokens, tema dark, shell responsivo, componentes, foco visivel, reduced motion, carregamento modular de CSS e cores de campeonato validadas.
- Esta verificacao e estrutural. A validacao visual em navegador de 320px a 1920px permanece pendente e nao e alegada como concluida. O smoke HTTP local tambem nao foi executado porque o executor bloqueou a inicializacao do servidor local.

## Dashboard UI - 27/07/2026

- `php tests/dashboard_ui_e2e.php`: aprovado (`DASHBOARD_UI_E2E_OK`). Cobre login, controles funcionais, dashboard global, central do campeonato, tema, drawer e regras responsivas presentes no HTML/CSS/JS.

## Definitive release audit - 27/07/2026

- Full available regression passed: `FULL_AUDIT_REGRESSION_OK` (lint plus 13 PHP test scripts).
- This result is insufficient for production: `http_authenticated_e2e.php` is source-level control verification, not authenticated HTTP integration. Refer to `docs/FINAL_RELEASE_AUDIT.md` for the release decision.

## Production preparation - 27/07/2026

- Clean install approved: `powershell.exe -ExecutionPolicy Bypass -File bin/clean-install.ps1 -DatabaseName torneios_test_disposable` created disposable DB, ran migrations 001-017, seeded, ran integration and removed DB.
- `tests/clean_install_e2e.php`, `tests/security_e2e.php`, `tests/http_authenticated_e2e.php`: approved.
- Important limitation: HTTP authenticated test currently verifies application controls statically; browser/socket login, logout and CSRF exchange remains pending.

## Accountability and exports - 27/07/2026

- `php tests/accountability_e2e.php`: approved (`ACCOUNTABILITY_E2E_OK`). Covers published news/gallery persistence, dashboard metrics, CSV job and ZIP job output.

## Public portal - 27/07/2026

- `php tests/public_portal_e2e.php`: approved (`PUBLIC_PORTAL_E2E_OK`). Covers published slugs, private-field absence, draft championship hiding, team page and 404.

## Rectification - 27/07/2026

- `php tests/rectification_e2e.php`: approved (`RECTIFICATION_E2E_OK`). Covers immutable snapshot, request, approval, impact decision requirement and transactional application.
- `php tests/tournament_e2e.php`: approved after normal homologation began creating immutable version snapshots.

## Advanced sports rules - 27/07/2026

- `php tests/sports_rules_e2e.php`: approved (`SPORTS_RULES_E2E_OK`). Covers configured W.O., standings points, point penalty/revocation, yellow-card suspension, lineup block and suspension fulfillment.
- After migration 019, the existing sports-rule regression and tournament workflow were rerun. Full advanced scenarios for mini-table, cleanup, substitutions and shootout remain pending and are not claimed as approved.

## Assisted administrative workflow - 27/07/2026

- `php tests/admin_workflow_e2e.php`: approved (`ADMIN_WORKFLOW_E2E_OK`). It guards against manual technical inputs and JSON lineup input, verifies controller CSRF/scope checks, persists assisted team/athlete/staff creation and rejects registering an athlete into a different team.

## Authorization and scope - 27/07/2026

- `php tests/authorization_e2e.php`: approved (`AUTHORIZATION_E2E_OK`), including cross-project, tournament, team, match, athlete and private-document IDOR cases.
- `php tests/tournament_e2e.php`, PHP lint, migrations 013-015, `tests/integration.php` and `tests/smoke.php`: approved after authorization changes.
- Authenticated HTTP login/session/CSRF/403/404 remains a real pending test.

## Seed demo - 27/07/2026

- `php database/seed.php --demo`: aprovado duas vezes consecutivas.
- `php tests/demo_seed.php`: aprovado (`DEMO_SEED_OK`), incluindo bloqueio em `APP_ENV=production`.
- Dados validados: 3 campeonatos, 16 equipes, 288 atletas fictícios, 35 integrantes de comissão, 20 usuários `@example.com`, regulamentos, grupos, eventos, classificação, mata-mata, arquivos e escopo de equipe.
- O algoritmo round-robin foi corrigido: grupos ímpares agora geram confrontos de todas as equipes sem repetição estrutural.
- Smoke HTTP demo: os três portais por slug retornaram `200`.

## Fluxo operacional mínimo - 27/07/2026

- `tests/tournament_e2e.php`: aprovado (`TOURNAMENT_E2E_OK`). Criou organização, projeto, campeonato, preset, 10 equipes, 70 atletas e inscrições aprovadas; distribuiu dois grupos; gerou e homologou 20 jogos; classificou quatro equipes por grupo; gerou, homologou e avançou quartas, semifinais e final; confirmou campeão e vice; gerou PDF da súmula final em `storage/private/reports`; validou isolamento entre campeonatos, escopo de equipe, operador sem permissão de homologar, retificação e portal público com dados homologados.
- Resultado mais recente: `TOURNAMENT_E2E_OK 37 private/reports/match-95-1eaaf18fe74b.pdf`.

## Ambiente

- PHP 8.2.12 (XAMPP), MySQL local e banco `torneios` existente.
- Execução de auditoria em 27/07/2026, sem apagar banco ou uploads existentes.

## Executados e aprovados

- Lint de todos os arquivos PHP em `app`, `bin`, `public` e `tests`: aprovado.
- `php bin/migrate.php`: aprovado na base atual, sem migrations pendentes.
- `php bin/seed.php`: aprovado.
- `php tests/integration.php`: aprovado (`REPOSITORY_CRUD_OK`). Cobre CRUD genérico, soft delete, paginação, upload com MIME permitido, reset de senha por serviço, escopo de download em dois campeonatos, auditoria básica, preset/versionamento, round-robin em memória, evento de gol/anulação e estrutura de PDF.
- `php tests/smoke.php`: aprovado (`PUBLIC_OK name`).
- HTTP anônimo: home, jogos e rankings retornaram 200; rota pública desconhecida retornou 404; URL direta para `storage/private` retornou 404; download administrativo sem sessão retornou 302 para login.
- Headers HTTP verificados: CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` e `Permissions-Policy`.

## Não executados ou insuficientes

- Login HTTP autenticado, logout, recuperação, sessão e rate limit: o POST com credencial de desenvolvimento foi bloqueado pela política do executor, não pelo aplicativo. Não há teste automatizado equivalente.
- Fluxo administrativo completo e fluxo público completo: não executáveis porque as rotas e fluxos obrigatórios ainda não existem.
- CSRF, XSS, SQL injection, upload executável, IDOR em CRUDs, permissões granulares, isolamento de equipes, responsividade e instalação limpa: sem cobertura automatizada.
- PDF foi validado estruturalmente no gerador, não por renderizador externo.

## Conclusão

Os testes confirmam o fluxo operacional mínimo por serviços e rotas implementadas. Eles não comprovam todos os requisitos do PRD: interface assistida de cadastros, autorização em todo CRUD genérico, confronto direto, disciplina completa, reconstrução da chave após retificação e instalação limpa ainda requerem cobertura própria.


## Product navigation foundation - 27/07/2026

- Baseline regression passed before navigation changes: PHP lint plus integration, authorization, tournament, sports, rectification, accountability, public portal, security and structural UI checks.
- 	ests/navigation_http_e2e.php: passed (NAVIGATION_HTTP_E2E_OK) against temporary PHP server with APP_BASE_PATH=/copa-online.
- Test uses real GET/POST, CSRF, regenerated sessions, role landing redirects, scoped 200, forbidden 403, legacy 403, 404, breadcrumbs and subdirectory links.
- This is HTTP integration, not browser/mobile visual acceptance.

## Navigation route extension - 28/07/2026

- PHP lint for changed controller, view, routes and HTTP test: approved.
- `tests/navigation_http_e2e.php`: approved (`NAVIGATION_HTTP_E2E_OK`) against a temporary PHP server with `APP_BASE_PATH=/copa-online`.
- Verified real login/CSRF/session redirects and menus for seven profiles; organizer scope denial by changed championship slug; legacy 403 for communication and legacy notice for superadmin; assigned operator detail/operation routes; communication 403 for operation; 404 and mobile-drawer markup.
- `tests/authorization_e2e.php`: approved (`AUTHORIZATION_E2E_OK`).
- `tests/tournament_e2e.php`: approved (`TOURNAMENT_E2E_OK`); generated private PDF is local test output and was not staged.
- `php database/seed.php --demo`: approved; seed now idempotently creates/reativates one `match_operator_assignments` record for `operador@example.com`, which is required by the HTTP navigation fixture.

## Reconstruction final audit - 28/07/2026

- Lint, `integration.php`, `authorization_e2e.php`, `sports_rules_e2e.php`, `rectification_e2e.php` and `accountability_e2e.php`: aprovados nesta execucao.
- `public_portal_e2e.php`: reprovado. A suite esta acoplada ao portal generico anterior e a separacao parcial de views publicas ainda nao possui substituta HTTP. Este resultado bloqueia liberacao.
- Nenhuma busca textual foi reclassificada como E2E: testes estruturais continuam insuficientes para provar jornada de usuario.
