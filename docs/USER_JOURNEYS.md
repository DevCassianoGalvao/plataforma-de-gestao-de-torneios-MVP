# User Journeys

## Shared context model

Every administrative page must declare the active organization, project and championship. Team context is selected only where the user has a persisted team scope. URL IDs locate resources; they never grant scope. Server policy remains authoritative.

## Superadministrator

| Item | Definition |
|---|---|
| Objective | Govern platform tenants, users, permissions and exceptional decisions. |
| Landing page | Global dashboard with organization/project/championship health, approval queues and audit alerts. |
| Menu | Overview; Organizations; Projects; Championships; Users & access; Audit; Platform settings. |
| Main tasks | Create tenancy; assign roles/scopes; resolve cross-scope issues; review audit; manage system configuration. |
| Allowed data | Global, subject to private-file permission. |
| Forbidden actions | Silent data deletion, scope bypass without auditable action, unreviewed rule override. |
| Required pages | Tenant list/detail, user access assignment, audit log, championship setup and exception review. |
| Ideal flow | Choose tenant -> choose project/championship -> perform scoped action -> see confirmation/audit reference. |
| Errors | Duplicate slug, invalid scope combination, no permission, protected record, stale version. |
| Completion state | Clear result, linked audit event, next recommended action. |

## Project administrator

| Item | Definition |
|---|---|
| Objective | Run projects and their championships without seeing another project. |
| Landing page | Project dashboard with active championships and pending operational work. |
| Menu | Project overview; Championships; Teams; People; Documents; Reports. |
| Main tasks | Create/configure championships, review resources, assign project staff, export project reports. |
| Allowed data | Persisted project scope and child championships. |
| Forbidden actions | Platform-wide user management, other projects, unsupported sports-rule overrides. |
| Required pages | Project dashboard, championship list/detail, project users, report center. |
| Ideal flow | Open project -> select championship -> complete setup checklist -> delegate operations. |
| Errors | Incomplete championship configuration, scope denial, attempted cross-project relation. |
| Completion state | Championship ready/published or issue delegated. |

## Organizer

| Item | Definition |
|---|---|
| Objective | Configure and operate a championship from enrollment through champion. |
| Landing page | Championship dashboard with readiness, next fixtures and homologation queue. |
| Menu | Dashboard; Setup; Teams; People; Registrations; Competition; Matches; Reports; Content. |
| Main tasks | Configure rules; admit teams/registrations; create groups; generate schedule; homologate; publish results. |
| Allowed data | Assigned championship and child resources. |
| Forbidden actions | Other championships; global access management; unauthorized private documents. |
| Required pages | Setup, registrations inbox, groups, schedule, matches, homologation, standings, bracket, reports. |
| Ideal flow | Setup -> registration approval -> competition generation -> match review -> publish/homologate -> close. |
| Errors | Rules locked after start, invalid roster, schedule conflict, bracket impact from rectification. |
| Completion state | Published and internally consistent championship state. |

## Team manager

| Item | Definition |
|---|---|
| Objective | Manage own team eligibility and match preparation. |
| Landing page | Team workspace for current championship with pending documents, registrations and next match. |
| Menu | Team overview; Roster; Staff; Documents; Registrations; Fixtures; Reports. |
| Main tasks | Maintain roster, submit documents, correct pending registration, confirm lineup when permitted. |
| Allowed data | Assigned team and permitted championship context only. |
| Forbidden actions | Opponent roster/documents, homologation, regulation changes, other team access. |
| Required pages | Team profile, roster list/detail, document inbox, registration status, match preparation. |
| Ideal flow | Choose team -> resolve pending requirements -> submit lineup/document -> receive status. |
| Errors | Missing guardian consent, expired document, suspended athlete, duplicate shirt number. |
| Completion state | Roster/lineup marked ready with visible validation result. |

## Match operator

| Item | Definition |
|---|---|
| Objective | Record an assigned match safely during live play. |
| Landing page | Today/assigned matches, with explicit start operation button. |
| Menu | Assigned matches; Match center; Draft reports. |
| Main tasks | Confirm lineups, start/advance periods, record events, review timeline, finish report. |
| Allowed data | Assigned matches and public roster data needed for those matches. |
| Forbidden actions | Unassigned match operation, championship configuration, homologation, edit of unrelated roster. |
| Required pages | Match list; dedicated match center; event quick panels; report review. |
| Ideal flow | Open assigned match -> validate lineups -> operate events -> finish -> submit for homologation. |
| Errors | Invalid player/team, suspension, duplicate event, invalid period, lost connection. |
| Completion state | Report awaiting homologation, with immutable event history. |

## Communication

| Item | Definition |
|---|---|
| Objective | Publish authorized public information for assigned championships. |
| Landing page | Editorial calendar and published-content health. |
| Menu | Content overview; News; Galleries; Transfers; Sponsors; Public documents. |
| Main tasks | Draft, schedule, publish and update content; curate public assets. |
| Allowed data | Published-safe championship content and assigned assets. |
| Forbidden actions | Private documents, registration decisions, match operation, sensitive person fields. |
| Required pages | Editorial list/editor, gallery manager, public preview, sponsor/document library. |
| Ideal flow | Select championship -> draft -> preview public output -> publish/schedule -> review result. |
| Errors | Duplicate slug, unpublished relation, unsafe media, scope denial. |
| Completion state | Scheduled/published content with public URL and audit event. |

## Accountability / reporting

| Item | Definition |
|---|---|
| Objective | Produce scoped evidence and exports without altering operations. |
| Landing page | Reporting dashboard with filters, indicators and recent jobs. |
| Menu | Dashboard; Reports; Exports; Audit. |
| Main tasks | Filter indicators, generate reports, download authorized completed exports, inspect audit history. |
| Allowed data | Assigned organization/project/championship and authorized private exports. |
| Forbidden actions | Operational mutation, private-file download without explicit permission, cross-scope exports. |
| Required pages | Filtered dashboard, report catalog, export job detail, audit filters. |
| Ideal flow | Choose scope and period -> review counts -> request export -> wait -> download by ID. |
| Errors | No data, expired export, failed job, missing download permission. |
| Completion state | Completed/expired/failed status visible with retry when authorized. |

## Public portal visitor

| Item | Definition |
|---|---|
| Objective | Follow a published championship using only public information. |
| Landing page | Championship home by stable public slug. |
| Menu | Matches; Standings; Bracket; Teams; Athletes; Rankings; News; Galleries; Transfers; Rules; Documents; Champions. |
| Main tasks | Find fixture/result, understand standings, inspect team/athlete public profile, read published content. |
| Allowed data | Explicit public presenter fields only. |
| Forbidden actions | Admin operations; private documents; contact/documents/guardian details; unpublished content. |
| Required pages | Sports home, fixture list/detail, standings, bracket, team/athlete detail, editorial detail, 404. |
| Ideal flow | Open championship -> choose sport area -> navigate using stable names/slugs -> return to context. |
| Errors | Unknown/hidden resource returns 404, not a data leak; empty state explains no published data. |
| Completion state | Information found or accessible empty state shown. |

## Journey acceptance baseline

- Every journey has a dedicated landing page and only relevant navigation.
- Every state-changing form uses human labels, scoped selectors and server validation.
- Success, validation error, 403, 404, empty, loading and destructive confirmation states are designed and testable.
- Each role journey receives a browser test on desktop and mobile before its stage is checked complete.


## Implemented routing baseline (2026-07-27)

- Login now uses ProductNavigationService::landing() instead of sending every role to generic CRUD.
- Landing: superadmin global dashboard; project admin projects; organizer championship dashboard; team manager my team; operator assigned matches; communication content; auditor accountability.
- `tests/navigation_http_e2e.php` performs HTTP login, CSRF, regenerated cookie, role redirects, scope checks, legacy denial, 404 and subdirectory checks.
- Browser/mobile visual acceptance remains pending Stage 7.

## Implementation mapping and status - 2026-07-27

| Journey | Existing code support | Current gap before journey acceptance |
|---|---|---|
| Superadministrator | `ProductNavigationService::landing/menu`, `ProductNavigationController::global`, `AccessController`, `AuthPolicy::requireSuperAdmin` | Global routes render temporary summaries; named tenant/user/audit pages are not dedicated workflows. |
| Project administrator | `ProductNavigationService::tournaments`, `ScopeService`, `ScopedRepository` | Project dashboard and team/report pages are shell routes, not list/detail/task pages. |
| Organizer | `TournamentOperationService`, `ScheduleGenerationService`, `StandingsService`, `BracketService`, role modules | The menu reaches `admin/product-page.php`; teams through reports remain a legacy mega-screen or generic CRUD. |
| Team manager | team-scoped `ProductNavigationService::tournaments`, `ScopeService`, `RegistrationValidationService` | Own roster/document/lineup task routes remain to be built. |
| Match operator | `match_operator_assignments`, `ProductNavigationController::{assignedMatches,matchDetail,matchOperation}`, `TournamentOperationService`, `MatchEventService` | Assigned-match list, detail and guarded operation entry exist; dedicated live controls and offline/error UX do not. |
| Communication | role landing/menu plus editorial schema and `PublicPortalPresenter::content` | News/gallery/transfer editor and published preview are not dedicated routes. |
| Accountability | `AccountabilityController`, `AccountabilityService`, `ExportService` | Tournament export endpoint exists; global reporting workspace, filter journey and job detail remain incomplete. |
| Public visitor | `PublicController`, `PublicPortalPresenter`, `public/portal.php` | Generic page rendering, numeric detail IDs and `SELECT *` content query prevent public-portal completion. |

Each row remains unchecked in implementation planning until a real user can finish it
with server authorization, persisted data, failure states and browser evidence.
