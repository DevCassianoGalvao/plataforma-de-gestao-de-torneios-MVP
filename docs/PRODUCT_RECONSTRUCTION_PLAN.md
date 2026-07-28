# Product Reconstruction Plan

## Operating rules

- [ ] Do not add CSS over legacy styles without a selector-ownership consolidation step.
- [ ] Do not expose IDs, JSON, database columns or internal state values in product UI.
- [ ] Do not use generic CRUD as final interface.
- [ ] Do not combine complex journeys into one page.
- [ ] Do not call file/text assertions E2E.
- [ ] Do not mark a feature complete without a navigable route, persistence, server authorization, failure state and proportional test evidence.
- [ ] Preserve validated backend behavior and executed migrations.
- [ ] Do not alter sports rules without service regression coverage.
- [ ] Do not delete legacy route/CSS before replacement acceptance exists.
- [ ] Create one focused commit per completed stage.

## Stage 0 - Baseline and safety

- [x] Create reconstruction branch: `refactor/product-reconstruction`.
- [ ] Add browser harness and disposable visual fixture contract.
- [ ] Freeze generic CRUD in navigation and document its temporary internal status.
- [ ] Capture baseline routes/screenshots and selector ownership report.

Dependencies: current branch, migrations, demo seed.
Files affected: test tooling, route/navigation config, docs; no migration rewrite.
Acceptance: baseline reproducible; no loss of existing routes/data.
Risk: hidden dependency on generic routes/CSS.
Migration: additive only.

| Item | Item acceptance |
|---|---|
| Browser harness | A disposable database fixture starts the PHP server and one browser test can log in without touching a real database. |
| Generic CRUD freeze | `AdminController` generic routes remain reachable only outside primary navigation and the menu has no `admin/crud.php` destination. |
| Baseline capture | Every route currently registered in `public/index.php` is listed with a desktop/mobile screenshot and CSS selector owner. |

## Stage 1 - Architecture and navigation

- [ ] Build one administrative shell with context switcher, role-aware menu and account controls.
- [ ] Replace route-shell placeholders with named domain routes and compatibility adapters from existing numeric/generic routes.
- [ ] Create global and championship dashboards as dedicated task pages.
- [x] Remove mega screen as primary entry point; retain it as superadmin-only legacy interface during transition.

Dependencies: Stage 0, `AuthPolicy`, `ScopeService`.
Files affected: `public/index.php`, route modules, layout/partials, dashboard controllers/views, navigation JS/CSS.
Acceptance: each role lands in a scoped dashboard and can navigate only to allowed areas.
Tests: HTTP role matrix; Playwright login/navigation desktop/mobile.
Risk: broken deep links and accidental authorization regression.
Migration: retain old routes as protected compatibility adapters until all destinations ship.

| Item | Current evidence / item acceptance |
|---|---|
| Administrative shell | `app/Views/layouts/base.php` renders one accessible shell, current context and account controls at all allowed admin routes. |
| Named routes | `public/index.php` no longer uses the entity loop as product navigation; old `/admin/{entity}` links redirect or stay protected as internal adapters. |
| Dashboards | `AdminController::dashboard` and the new championship dashboard have separate controllers/views and show only persisted scoped data. |
| Mega-screen removal | `admin/tournament-operations.php` is not a primary navigation target; every operation has a dedicated page link. |

## Stage 2 - Assisted management

- [ ] Rebuild championships, teams, athletes, staff, guardians, registrations and documents as dedicated list/detail/workflow pages.
- [ ] Replace raw relation IDs with scoped selectors/autocomplete.
- [ ] Add registration inbox, document review states and person/team detail history.
- [ ] Keep generic CRUD inaccessible from ordinary product menus.

Dependencies: Stage 1; `AssistedAdministrationService`, `RegistrationValidationService`, private file authorization.
Files affected: management controllers/views/presenters, scoped queries, document routes, components.
Acceptance: no raw technical input is visible; operations validate scope and reload persisted state.
Tests: service regressions plus browser create/edit/review and cross-team denial.
Risk: sensitive data exposure and stale relation selection.
Migration: new pages write current schema; generic pages become read-only/internal while records migrate naturally.

| Item | Current evidence / item acceptance |
|---|---|
| Dedicated management pages | `AssistedAdministrationService` and `RegistrationValidationService` are used by named list/detail/workflow controllers, not only `tournament-operations.php`. |
| Friendly relations | No visible form contains a free numeric `organization_id`, `project_id`, `tournament_id`, `team_id`, `person_id`, `stage_id` or `group_id` input. |
| Registration/document workspaces | A reviewer can approve/reject with reason, see missing documents and reload the persisted state through a browser flow. |
| Generic CRUD withdrawal | Core people/team/document pages in sidebar never route to `admin/crud.php`; the internal tool retains server scope checks. |

## Stage 3 - Competition

- [ ] Create dedicated pages for stages, groups, rounds, schedule generation, calendar, standings and bracket administration.
- [ ] Add generation preview/confirmation and clear lock state after start.
- [ ] Build standings and bracket presenters from the existing services, not duplicated calculation in templates.

Dependencies: Stage 2 roster/registration states; `ScheduleGenerationService`, `StandingsService`, `BracketService`, regulation versions.
Files affected: competition routes/controllers/views/query objects and scoped components.
Acceptance: organizer can perform group-to-fixture-to-standings journey without a database ID form.
Tests: service schedule/bracket regression and browser group/schedule/standings flows.
Risk: duplicate fixtures and incorrect status locks.
Migration: retain existing service contracts; add forward migration only for new public slugs/query indexes.

| Item | Current evidence / item acceptance |
|---|---|
| Competition pages | `ScheduleGenerationService`, `StandingsService` and `BracketService` feed named screens for stages, groups, fixtures, calendar, standings and bracket. |
| Generation confirmation | A schedule preview shows matches before persistence; a second explicit confirmation persists it and is audited. |
| Read-model boundary | No template calculates standings/pairings; presenter/query objects consume service output only. |

## Stage 4 - Match operation

- [ ] Create dedicated match detail, lineup preparation, live match center, event timeline, homologation, rectification and report pages.
- [ ] Support fast human event controls and clear team/player context; never make drag-and-drop mandatory.
- [ ] Show immutable report versions and rectification impact decisions before applying effects.
- [ ] Produce a print-quality report/PDF workflow based on persisted version data.

Dependencies: Stage 3, `TournamentOperationService`, `MatchEventService`, `RectificationService`, `SportsRulesService`, PDF storage.
Files affected: operation routes/controllers/views, report templates, focused operation CSS/JS, presenters.
Acceptance: assigned operator completes a match; organizer homologates; a rectification preserves history and shows impact.
Tests: sports/rectification service regression plus browser match-center, homologation and rectification scenarios.
Risk: live-operation usability and incorrect recalculation.
Migration: leave existing operation route available as fallback until dedicated pages pass regression; no sports-rule rewrite in UI stage.

| Item | Current evidence / item acceptance |
|---|---|
| Dedicated operation pages | Match actions currently nested in `admin/tournament-operations.php` move to named match detail, lineup and match-center routes. |
| Assisted live controls | `TournamentOperationService` receives validated selections from visible team/player controls; no player ID must be manually typed by an operator. |
| Version/impact review | `RectificationService` output is rendered as a before/after diff and requires an authorized, audited impact decision. |
| Report/PDF | `MatchReportService`/`PdfReportService` output is reviewable on screen and downloadable with version/status/verification information. |

## Stage 5 - Public portal

- [ ] Split generic `portal.php` into sports-specific pages and public presenters/DTOs.
- [ ] Render championship identity assets, fixtures, standings, bracket, teams, athletes, rankings and published content details.
- [ ] Add canonical public slugs, real metadata, sitemap expansion and public-safe 404 behavior.
- [ ] Preserve public/private field boundaries at query/presenter level.

Dependencies: Stages 2-4 data projections, `PublicPortalPresenter`, `ThemeService`.
Files affected: public routes/controller/presenters/templates/assets.
Acceptance: three fixture championships have distinct identities and no private fields are delivered to templates.
Tests: browser public flows, no-unpublished-content/privacy tests and responsive screenshots.
Risk: accidental privacy leak and generic query performance.
Migration: old generic page routes redirect progressively; no numeric detail URL remains canonical.

| Item | Current evidence / item acceptance |
|---|---|
| Page split | `PublicController` stops sending every page to generic `public/portal.php`; each sports page has its own view model/template. |
| Real identity/data | `PublicPortalPresenter` returns explicit public DTOs and rendered pages use tournament assets/theme rather than fixed branding. |
| Canonical routing | Public detail URLs use stable slugs and legacy numeric URLs issue safe redirects only after championship-scoped lookup. |
| Privacy boundary | `PublicPortalPresenter::content()` no longer uses `SELECT *`; automated tests prove private fields never enter public template data. |

## Stage 6 - Design system

- [ ] Consolidate tokens, themes, foundation, layout and component ownership per `CSS_REFACTOR_PLAN.md`.
- [ ] Remove legacy selectors only after migrated pages and visual regressions exist.
- [ ] Validate light/dark, championship themes, accessibility, keyboard behavior and responsive layouts.

Dependencies: page migrations from Stages 1-5.
Files affected: CSS layers, shared layouts, JS state helpers, screenshots.
Acceptance: one declared CSS owner per component; no duplicate global selector ownership; no critical overflow at target viewports.
Tests: browser visual/a11y regression.
Risk: cascade regressions.
Migration: delete legacy CSS in isolated commits with rollback-ready tag.

| Item | Current evidence / item acceptance |
|---|---|
| CSS ownership | The ten stylesheets currently linked by `base.php` have a selector map; each shared selector has exactly one active owner. |
| Safe removal | `app.css`, `management.css`, `operation.css` or `public-portal.css` are removed only after their pages pass browser regression with replacement styles enabled. |
| Usability baseline | Light/dark and championship color themes pass keyboard, contrast and target-viewport checks in a real browser. |

## Stage 7 - Real tests and release evidence

- [ ] Implement authenticated HTTP suite on disposable databases.
- [ ] Implement Playwright journey suite by role and viewport.
- [ ] Add CI artifacts and explicit test report evidence.
- [ ] Re-audit documentation; reopen unsupported claims rather than checking boxes.

Dependencies: all previous stages.
Files affected: `tests/`, browser tooling, CI config, documentation.
Acceptance: test report links each completed journey to service, HTTP and browser evidence.
Risk: fixture fragility and environment variance.
Migration: parallel test adoption; existing source checks may remain as fast structural checks but are renamed/reclassified.

| Item | Item acceptance |
|---|---|
| Authenticated HTTP suite | Uses real request/cookie/CSRF exchanges against a disposable database; it does not inspect source text as proof. |
| Browser suite | Playwright clicks/types through every role journey and captures trace/screenshots on failure. |
| CI artifacts | CI retains test report, failure traces and selected screenshots, all excluded from the repository. |
| Documentation audit | `TEST_REPORT.md` labels structural, integration, HTTP and browser evidence correctly; unsupported completion boxes are reopened. |

## Recommended order

1. Stage 0 and Stage 1 first. New UI on the current routing/CSS surface would compound the current failure mode.
2. Stage 2 before Stage 3 because competition needs trustworthy teams, people and registrations.
3. Stage 3 before Stage 4 because live operation needs fixtures and assigned scope.
4. Stage 4 before Stage 5 because public data must reflect real, reviewed match outcomes.
5. Stage 6 is continuous, but removals occur only after each page migration; Stage 7 runs alongside and is the final gate.

## Explicit non-goals for this preparation

- No deletion of legacy routes, CSS or migrations.
- No redesign/CSS implementation.
- No sports-rule modification.
- No claim that the current source-check tests are browser E2E.


Implementation evidence (2026-07-27): `ProductNavigationController`, `ProductNavigationService`, `app/Views/admin/product-page.php`, named `/admin/campeonatos/{championship}/{module}` routes, and `tests/navigation_http_e2e.php`. Temporary pages render scoped persisted summaries only; Stage 2 still owns internal forms and detail workflows.

## Review correction - 2026-07-27

This section supersedes completion implications in the earlier stage headings. The
navigation foundation is real, but it is deliberately not evidence that the product
screens have been rebuilt.

| Stage 1 deliverable | Status | Concrete evidence | Remaining acceptance evidence |
|---|---|---|---|
| [x] Role landing and server-side route gate | Completed foundation | `app/Services/ProductNavigationService.php`, `app/Controllers/ProductNavigationController.php`, `app/Controllers/AuthController.php`, routes 42-47 in `public/index.php`, and `tests/navigation_http_e2e.php` | HTTP suite proves redirects, 403/404 and championship scope. Browser navigation is still required by Stage 7. |
| [x] Legacy operation removed from normal product entry | Completed transition guard | `ProductNavigationController::legacyOperation()` and `::legacyAction()` require superadmin before delegating to `TournamentOperationController`; `admin/tournament-operations.php` displays the legacy notice | Keep the route until each replacement mutation has browser and authorization coverage. |
| [ ] Shared administrative shell with production account/context controls | Partial | `app/Views/admin/product-page.php` has a temporary shell only; `app/Views/layouts/base.php` still merely loads the old page views | A reusable shell/partials must wrap every administrative route, expose active organization/project/championship, and pass keyboard/mobile browser checks. |
| [ ] Named domain screens and compatibility adapters | Partial | `/admin/campeonatos/{championship}/{module}` exists in `public/index.php`, but it renders the generic `admin/product-page.php`; the entity loop at route 48 still exposes `AdminController::index/edit/save/delete` | Every named module must acquire a dedicated controller/presenter/view and old entity URLs must be protected internal adapters or redirects. |
| [ ] Global and championship dashboards | Partial | `ProductNavigationController::renderGlobal()` and `::renderTournament()` show scoped summaries; legacy `AdminController::dashboard()` and `TournamentOperationController::dashboard()` remain separate old screens | Action queues, persisted contextual metrics, permission-aware actions and browser acceptance are required before completion. |

### Dependency-ordered executable backlog

Every unchecked item below has an explicit acceptance condition, probable files and
test class. It is the authoritative execution order; it does not change any backend
service contract without a dedicated service-regression decision.

1. **[ ] Stage 0.1: baseline evidence and fixture contract**
   - Acceptance: all routes in `public/index.php` have a route inventory; a disposable database runs migrations plus `tests/demo_seed.php`; screenshots are generated outside Git.
   - Probable files: `bin/clean-install.ps1`, `tests/`, `docs/REAL_E2E_TEST_PLAN.md`, route inventory script.
   - Tests: clean-install execution, one authenticated HTTP exchange, browser startup smoke.
2. **[ ] Stage 1.1: finish the shell and context**
   - Acceptance: shared partials replace duplicated admin navigation in `admin/dashboard.php`, `admin/crud.php`, `admin/tournament-operations.php`, `admin/access-control.php` and `admin/accountability.php`; context comes from scoped persisted records.
   - Probable files: `app/Views/layouts/base.php`, new admin partials, `ProductNavigationController`, `ProductNavigationService`, `public/assets/css/layout.css`, `public/assets/js/app.js`.
   - Tests: role HTTP matrix plus Playwright desktop/mobile menu, focus and 403 checks.
3. **[ ] Stage 2: assisted management**
   - Acceptance: teams, athletes, staff, guardians, registrations and documents have dedicated list/detail/workflow routes; visible forms contain no free technical IDs or regulation JSON.
   - Probable files: new management controllers/presenters/views; `AssistedAdministrationService`, `RegistrationValidationService`, `ScopedRepository`, `UploadService`.
   - Tests: service regression; browser create/edit/review; cross-team and private-document denial.
4. **[ ] Stage 3: competition administration**
   - Acceptance: group, rounds, fixtures, standings and bracket pages consume `ScheduleGenerationService`, `StandingsService` and `BracketService` without template-side calculation or manual relation IDs.
   - Probable files: new competition controller/presenter/views; `TournamentOperationService`; scoped query helpers.
   - Tests: service schedule/bracket regression; browser preview/confirm; duplicate-generation and scope denial.
5. **[ ] Stage 4A: match center**
   - Acceptance: match detail, lineup, live event timeline and finish flow are dedicated routes; operators cannot select an unassigned match or manually type player IDs.
   - Probable files: new operation controller/presenters/views; `TournamentOperationService`, `MatchEventService`, `DisciplineService`.
   - Tests: browser lineup/event/finish; authorization; existing sports service regression.
6. **[ ] Stage 4B: homologation, rectification and report**
   - Acceptance: review checklist, immutable version comparison, authorized impact decision and version-labelled PDF work without the legacy mega screen.
   - Probable files: new review/report controllers/views; `RectificationService`, `MatchReportService`, `PdfReportService`, `BracketService`.
   - Tests: rectification transaction regression; browser homologation/rectification; private download checks.
7. **[ ] Stage 5: public portal**
   - Acceptance: public routes use separate presenters/templates for home, fixtures, standings, bracket, team, athlete and content detail; only explicit public DTO fields are delivered.
   - Probable files: `PublicController`, `PublicPortalPresenter`, `app/Views/public/*`, `ThemeService`.
   - Tests: browser public journeys, unpublished-content/IDOR/privacy tests and responsive screenshots.
8. **[ ] Stage 6: CSS ownership migration**
   - Acceptance: each selector has one loaded owner; legacy CSS is removed only after the migrated page's visual, keyboard and mobile checks pass.
   - Probable files: `public/assets/css/*.css`, `app/Views/layouts/base.php`, selector map artifact.
   - Tests: browser visual/a11y regression at 320, 375, 768, 1024, 1366, 1440 and 1920.
9. **[ ] Stage 7: release evidence**
   - Acceptance: `TEST_REPORT.md` distinguishes structural, service, HTTP and browser proof; CI retains failure artifacts outside Git; unsupported claims are reopened.
   - Probable files: `tests/`, CI configuration, `docs/TEST_REPORT.md`.
   - Tests: full clean installation, HTTP suite, Playwright suite, domain regression.

### Stage 1 route evidence - 2026-07-28

- [x] Add direct match detail and operation entry routes: `/admin/partidas/{match}` and `/admin/partidas/{match}/operar`.
  - Acceptance: both resolve the persisted match, re-check `view` or `operate_match` through `AuthPolicy`/`ScopeService`, hide numeric identifiers from rendered content, and retain the legacy operation route as a superadmin-only fallback.
  - Files: `public/index.php`, `ProductNavigationController`, `admin/product-page.php`, `tests/navigation_http_e2e.php`.
  - Tests: real HTTP for assigned operator detail/operation 200 and communication operation 403 under `/copa-online`; `database/seed.php --demo` provides the required persisted operator assignment.
# Atualizacao de execucao - Etapa 2

- [x] Fluxos assistidos por campeonato para equipes, atletas, comissao, responsaveis, inscricoes, documentos e regulamento. Aceite: respostas HTTP autenticadas, CSRF e escopo por slug verificados em `tests/management_http_e2e.php`.
- [ ] Criacao, edicao, ativacao e arquivamento globais de campeonatos. Aceite: substituir o acesso normal ao CRUD legado sem expor campos internos. Arquivos provaveis: `AssistedManagementController`, rotas administrativas e view de campeonatos. Esta pendencia impede considerar a Etapa 2 integralmente concluida.
