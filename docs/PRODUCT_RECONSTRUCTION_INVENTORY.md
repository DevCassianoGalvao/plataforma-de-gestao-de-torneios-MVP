# Product Reconstruction Inventory

Date: 2026-07-27
Branch: `refactor/product-reconstruction`
Scope: codebase inspection only. No claim in this document is browser acceptance evidence.

## Classification legend

- **Reusable**: persisted and exercised by service/integration code; can remain behind a new UI.
- **Functional, inadequate UI**: server behavior exists, but the current route/view is not a usable product workflow.
- **Partial**: schema or service covers only a subset of the required journey.
- **Visual/static**: markup/CSS exists without proof of usable behavior.
- **Schema only**: migration exists without a supported product route.
- **Superficial test**: a source-string check or direct service call labelled as E2E.
- **Legacy/duplicate**: overlapping implementation that must not be extended.
- **Blocker**: prevents a credible user journey or release claim.

## Runtime and platform

| Area | Evidence | Classification | Reconstruction decision |
|---|---|---|---|
| PHP MVC runtime | `app/bootstrap.php`, `Support/Router.php`, `Support/View.php` | Reusable | Keep. Refactor route registration into grouped route files only after coverage exists. |
| MySQL migrations | `database/migrations/001` through `019` | Reusable | Immutable historical migrations. Add only forward migrations when a data change is genuinely required. |
| Seed data | `database/seed.php`, `tests/demo_seed.php` | Partial | Keep demo data, but seed must become a visual fixture with named journeys and stable slugs. |
| Authentication/session/CSRF | `AuthController`, `Session`, `Security` | Reusable, with unproven HTTP behavior | Preserve implementation; add real authenticated browser and HTTP tests before changing auth UI. |
| Logs/audit | `AuditService`, `audit_logs`, file-access logs | Reusable | Preserve and expose through task-oriented audit views. |
| Private storage/download | `UploadService`, `AdminController::downloadDocument` | Reusable, narrow UI | Preserve ID-based access model; replace numeric upload/download entry points. |

## Server-side domain inventory

| Domain | Evidence | Classification | Product issue / next action |
|---|---|---|---|
| Organizations, projects, championships | migrations 001/006; `AdminController`; `ScopedRepository` | Functional, inadequate UI | Current generic CRUD shows technical fields. Replace with scoped list/detail/setup flow. |
| Roles, permissions and scopes | migrations 004/005/013-015; `AuthPolicy`, `ScopeService` | Reusable | Keep policy boundary. Audit every new route before exposure. |
| Regulations and identity | `TournamentConfigurationController`, `RuleConfigurationService`, migrations 006 | Functional, inadequate UI | Persisted versions/assets exist, but structured editor and asset preview are not a product journey. |
| Teams, athletes, staff, guardians | migrations 007/018; `AssistedAdministrationService` | Partial | Assisted creation exists only inside tournament operations; records/edit/history/document journeys are not separate pages. |
| Registrations and validation | `RegistrationService`, `RegistrationValidationService`, `TournamentOperationService` | Partial | Approval workflow exists in the mega screen; no work queue, document review workspace or clear exception path. |
| Groups, rounds and schedule | migrations 008/012; `ScheduleGenerationService`, `TournamentOperationService` | Functional, inadequate UI | Generation and persistence exist; setup, preview, edit and publication are mixed into one page. |
| Standings, bracket, sports rules | `StandingsService`, `BracketService`, `SportsRulesService` | Reusable/partial | Services cover important paths. Direct head-to-head, cleanup, shootout and exceptions need true scenario coverage and dedicated UI. |
| Match operation, lineups, events | migrations 009/012/019; `TournamentOperationService`, `MatchEventService` | Functional, inadequate UI; blocker | Live workflow is nested in a `<details>` element in a table. Separate route and dedicated operator shell are mandatory. |
| Homologation and rectification | `RectificationService`, `MatchReportService`, migration 017 | Partial; blocker | Snapshots/version records exist. Guided impact choice, bracket rebuilding and version comparison are incomplete. |
| Match reports/PDF | `MatchReportService`, `PdfReportService` | Partial; blocker | PDF is generated, but not an official report-quality document or versioned, printable workflow. |
| Discipline and penalties | `DisciplineService`, `SportsRulesService`, migrations 010/016/019 | Reusable/partial | Persisted concepts exist; no safe operational screens for cards, suspensions, appeals or exceptions. |
| Editorial, galleries, transfers | migration 011; generic CRUD; `PublicPortalPresenter::content` | Schema/partial | Tables are present; CRUD and public detail routes are generic/incomplete. |
| Documents and exports | `ExportService`, `AccountabilityService`, `AccountabilityController` | Partial | Basic export job and download exist. Product-level report selection, package composition and progress workflow are absent. |

## Administrative interface inventory

| Current route/view | Classification | Evidence | Required replacement |
|---|---|---|---|
| `/admin` / `admin/dashboard.php` | Visual/static plus basic counts | Count cards and activity query; UI test only searches strings | Scoped global dashboard with actionable queues and championship switcher. |
| `/admin/{entity}` / `admin/crud.php` | Legacy/duplicate; blocker | One route loop serves 23 entities; dynamic column rendering | Keep only as internal migration tool, protected and excluded from primary navigation. |
| `/admin/tournaments/{id}/operation` / `tournament-operations.php` | Functional, inadequate UI; blocker | Teams, athletes, registrations, groups, schedule, lineups, events and homologation rendered together | Split into dashboard, roster, registrations, competition, matches, match center, review and reports. |
| `/admin/tournaments/{id}/configuration` | Functional, inadequate UI | Configuration and rules controller/view | Championship setup with tabs: identity, categories, format, rules, publishing. |
| `/admin/access` | Functional, inadequate UI | Numeric scope assignment noted in divergence audit | User/role/scope assignment with named scoped selectors. |
| `/admin/documents/upload` | Functional, inadequate UI | `tournament_id` is numeric input | Contextual document library and upload modal bound to current tournament/person/team. |
| `/admin/tournaments/{id}/accountability` | Partial | Metrics/export list exists | Filtered accountability workspace and report/export center. |
| Match report route | Partial | HTML report plus PDF endpoint | Version-aware report detail, review checklist and print view. |

## Public portal inventory

| Area | Classification | Evidence | Required replacement |
|---|---|---|---|
| Routes | Partial | `PublicController` allows a broad list on generic route patterns | Retain public slug root; define page-specific route contracts and public slugs for detail pages. |
| Data projection/privacy | Reusable, needs review | `PublicPortalPresenter` uses selected athlete fields for some methods | Keep presenter boundary; remove `SELECT *` in `content()` and define DTOs for every public page. |
| Home and sections | Functional, inadequate UI | One `portal.php` receives all page data | Separate home, fixtures, standings, bracket, team, athlete and content templates. |
| Match detail | Partial | Events and lineups projected | Add officials, timeline, shootout, public report authorization, gallery and clear empty states. |
| Team and athlete detail | Partial | Team roster and athlete statistics exist | Add public team context, fixtures, rankings; keep sensitive data out of query results. |
| Standings/rankings | Functional, inadequate UI | `StandingsService::forPublic`, `rankings()` | Sport-specific tables with criteria/explanations and proper sorting/filtering. |
| Bracket | Blocker | `mata-mata` is a generic section/data list | Dedicated bracket projection and responsive visual component. |
| Editorial details | Blocker | Detail endpoint only supports `jogo`, `equipe`, `atleta` | Add published news/gallery/transfer/document detail presenters and routes. |
| Tournament branding | Partial | theme colors selected; assets stored in `tournament_assets` | Use logo variants, banner, favicon and social image in layout and metadata. |

## CSS and JavaScript inventory

| Asset | Classification | Notes |
|---|---|---|
| `public/assets/css/app.css` | Legacy | Base/older rules overlap newer layers. Do not extend. |
| `tokens.css`, `themes.css` | Reusable foundation | Keep as token/theme source after selector/value audit. |
| `layout.css`, `components.css`, `foundation.css` | Partial reusable | Useful intent, but loaded together with overlapping legacy rules. Consolidate ownership before new pages. |
| `dashboard.css`, `management.css`, `operation.css`, `public-portal.css` | Legacy/duplicate candidates | Page layers added over legacy CSS; migrate page-by-page then remove old selectors. |
| `public/assets/js/app.js` | Partial reusable | Theme, drawer and small UI hooks exist; no component/module boundary and confirmations are inconsistent. |

## Test inventory

| Test group | Classification | Evidence |
|---|---|---|
| `integration.php`, `tournament_e2e.php`, `sports_rules_e2e.php`, `authorization_e2e.php` | Service/integration tests | Use database/services directly; valuable for regression, not browser E2E. |
| `accountability_e2e.php`, `rectification_e2e.php`, `demo_seed.php` | Integration tests | Direct DB setup and service invocation. |
| `dashboard_ui_e2e.php`, `management_ui_e2e.php`, `match_center_ui_e2e.php`, `public_ui_e2e.php`, `ui_foundation_e2e.php`, `ui_ux_audit_e2e.php` | Structural source tests | Read files and assert text/classes/breakpoints. |
| `http_authenticated_e2e.php`, `security_e2e.php`, `clean_install_e2e.php` | Structural/security checks | Read source/script and assert fragments; no authenticated HTTP exchange or disposable installation run. |
| `public_portal_e2e.php` | Controller integration | Calls controller in process, not HTTP/browser. |

## Immediate blockers

1. Generic CRUD remains a user-facing route for core domain records.
2. Tournament operation is a single oversized page and its live match center is embedded in a table row.
3. Public portal uses one generic template and does not render a real sports bracket or complete published content details.
4. Browser interaction, responsive layout, accessibility and real authenticated HTTP flows are not covered.
5. CSS ownership is undefined because nine stylesheets are loaded simultaneously.

## Preservation rules

- Do not modify executed migrations; add forward migrations only.
- Do not remove generic CRUD or legacy CSS until replacement routes have acceptance and regression coverage.
- Do not change sports rules while service regression is failing or absent.
- Do not pass raw public/private records to templates; use explicit presenters/view models.

## Evidence cross-check - 2026-07-27

This review was made against the checked-out branch, not prior checkbox claims.

| Evidence category | Verified source | Finding and reconstruction implication |
|---|---|---|
| Registered routes | `public/index.php:14-52` | Auth, document, access, configuration, accountability, legacy operation, role-navigation, generic entity and public routes coexist. The `foreach` at line 48 still maps 23 entities to `AdminController`; it is compatibility surface, not product navigation. |
| Controllers/templates | `app/Controllers/{Admin,Access,Accountability,Auth,Public,TournamentConfiguration,TournamentOperation,ProductNavigation}Controller.php`; `app/Views/admin/*`; `app/Views/public/portal.php` | `ProductNavigationController` is a role/scope gate and summary-shell adapter. `crud.php`, `tournament-operations.php` and `portal.php` remain the main generic/mega-screen templates to replace. |
| Repositories | `app/Support/Repository.php`, `app/Support/ScopedRepository.php` | No domain-specific repositories exist. New read models need scoped query/presenter classes, not template SQL or a second generic repository. |
| Reusable services | `TournamentOperationService`, `ScheduleGenerationService`, `StandingsService`, `BracketService`, `RegistrationValidationService`, `MatchEventService`, `RectificationService`, `MatchReportService`, `PdfReportService`, `ScopeService`, `UploadService`, `PublicPortalPresenter` | Keep their persisted contracts behind new UI controllers. `PublicPortalPresenter::content()` still has `SELECT *`, so it is not a complete public DTO boundary. |
| CSS loaded | `app/Views/layouts/base.php` | Ten files load: `app.css`, `tokens.css`, `themes.css`, `layout.css`, `components.css`, `dashboard.css`, `management.css`, `public-portal.css`, `foundation.css`, `operation.css`. `:root` occurs in 3, `body` in 6, `.button` in 4 and `.panel` in 4. |
| JavaScript loaded | `public/assets/js/app.js` | Theme, drawer, confirmation, password, loading, view toggle and team filter exist. Confirmation listens to form `data-confirm`, while a legacy status action puts it on a nested button in `tournament-operations.php`. |
| Exposed technical UI | `admin/crud.php`, `admin/upload-document.php`, `admin/access-control.php`, `admin/tournament-configuration.php`, `admin/tournament-operations.php` | Free relation IDs and `settings_json` remain. Hidden IDs are acceptable only after a scoped human selector resolves the relation. |
| Tests named E2E | `tests/*_e2e.php` | UI, clean-install and security files named in `REAL_E2E_TEST_PLAN.md` are structural/source checks; `navigation_http_e2e.php` is the current genuine socket HTTP suite. |

No migration, service, seed, permission policy or portal data contract is changed by
this documentation review.
