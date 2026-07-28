# Information Architecture

## Administrative shell

The new shell has four persistent elements: product mark, context switcher, role-aware navigation, and account controls. The active championship is explicit on every child page. Navigation is grouped by work, not database table.

### Global routes

| Route target | Purpose |
|---|---|
| `/admin` | Global dashboard for the signed-in scope. |
| `/admin/organizations` | Organizations list/detail for superadmin. |
| `/admin/projects` | Projects list/detail for permitted scope. |
| `/admin/championships` | Championship discovery and creation. |
| `/admin/access` | Users, roles and scope assignment. |
| `/admin/audit` | Searchable audit log. |
| `/admin/settings/profile` | Profile and basic preferences. |

### Championship workspace

Use a canonical route prefix such as `/admin/championships/{championshipSlug}`. Existing numeric routes remain as compatibility redirects only after the new route is accepted.

| Area | Pages |
|---|---|
| Dashboard | Overview, readiness checklist, actions, next matches, pending work, activity. |
| Setup | General identity; categories/seasons; regulation; venues; sponsors; publication. |
| People | Teams; team detail; athletes; athlete detail; staff; guardians; documents; registrations inbox. |
| Competition | Stages; groups; group detail; rounds; schedule/calendar; standings; bracket. |
| Matches | Match list; match detail; lineup preparation; dedicated match center; homologation queue; rectification requests; report archive. |
| Content | News; galleries; transfers; public documents; champions. |
| Accountability | Dashboard; report catalog; export jobs; audit slice. |

### Administrative page rules

1. A list page owns discovery, filters, bulk-safe actions and creation entry point.
2. A detail page owns a record context and subordinate tabs.
3. A wizard owns an irreversible or multi-step action, such as schedule generation.
4. A live operation owns one match only. It does not share the generic list/table layout.
5. A review page owns approval/rejection/homologation with visible validation and impact.
6. No user-facing page renders column names, relationship IDs or JSON.

## Public portal architecture

Use canonical stable URLs under `/campeonatos/{championshipSlug}`. Detail resources gain public slugs where available; numeric legacy URLs redirect after a public-safe lookup.

| Route target | Page contents |
|---|---|
| `/campeonatos/{slug}` | Championship home: hero, live/upcoming/results, standing summary, bracket summary, leaders, editorial and sponsors. |
| `/campeonatos/{slug}/jogos` | Filters by phase, group, round, team, status and period. |
| `/campeonatos/{slug}/jogos/{matchSlug}` | Score, status, events, lineups, officials, shootout, gallery, public report when authorized. |
| `/campeonatos/{slug}/classificacao` | Group tabs, table, classification status and tie-break explanation. |
| `/campeonatos/{slug}/mata-mata` | Responsive bracket and champion/vice outcome. |
| `/campeonatos/{slug}/equipes` | Team directory and search. |
| `/campeonatos/{slug}/equipes/{teamSlug}` | Identity, public roster, fixtures, results, statistics and rankings. |
| `/campeonatos/{slug}/atletas` | Athlete directory and filters. |
| `/campeonatos/{slug}/atletas/{athleteSlug}` | Public profile and permitted statistics only. |
| `/campeonatos/{slug}/rankings/{metric}` | Goals, assists, cards, appearances, awards and configured public metrics. |
| `/campeonatos/{slug}/noticias` and `/{newsSlug}` | Published editorial list/detail. |
| `/campeonatos/{slug}/galerias` and `/{gallerySlug}` | Published galleries and items. |
| `/campeonatos/{slug}/transferencias` | Published transfer movement. |
| `/campeonatos/{slug}/regulamento` | Active public regulation version. |
| `/campeonatos/{slug}/documentos` | Public documents only. |
| `/campeonatos/{slug}/campeoes` | Published historical awards/champions. |

## Public data boundary

- Public routes call dedicated presenters/query objects, never a generic repository or `SELECT *`.
- Presenters enumerate allowed fields per page. They do not pass private fields for CSS hiding.
- A resource outside its championship, unpublished, deleted or private returns 404.
- Public download only uses persisted public document IDs and approved file paths.

## Migration from current routes

| Current route | Migration target | Compatibility action |
|---|---|---|
| `/admin/{entity}` | Named domain list routes | Remove from menu first; retain protected internal route during migration. |
| `/admin/tournaments/{id}/operation` | Championship workspace pages | Redirect to new dashboard after all child routes work. |
| `/admin/tournaments/{id}/configuration` | Championship setup tabs | Redirect to setup page. |
| `/campeonatos/{slug}/{page}` | Named public routes | Keep routing adapter; redirect known legacy pages. |
| `/campeonatos/{slug}/{page}/{id}` | Public slug detail routes | Add lookup/redirect only when public slug exists. |


## Implemented navigation foundation (2026-07-27)

- ProductNavigationController owns global landing, championship modules, assigned matches and legacy-operation guard.
- ProductNavigationService resolves persisted roles and permitted championships; menu visibility is not authorization.
- public/index.php adds named product routes, including /admin/campeonatos/{championship}/{module}.
- `app/Views/admin/product-page.php` renders breadcrumb, active championship context, responsive drawer markup and scoped summaries.
- /admin/tournaments/{id}/operation and all action/report endpoints are temporary superadmin-only legacy routes.

## Reality check - 2026-07-27

A route is not proof that its destination is a finished product page.

| Route family | Current controller/view | Product status |
|---|---|---|
| `/admin` and `/admin/{area}` | `ProductNavigationController::{home,global}` -> `admin/product-page.php` | Role-aware navigation foundation; generic summary shell only. |
| `/admin/campeonatos/{championship}[/{module}[/{resource}]]` | `ProductNavigationController::{tournament,tournamentModule}` -> `admin/product-page.php` | Scoped route shell; module action/detail workflow remains absent. |
| `/admin/{entity}` and mutations | `AdminController::{index,edit,save,delete}` -> `admin/crud.php` | Legacy generic CRUD; internal compatibility tool during replacement. |
| `/admin/tournaments/{id}/operation/*` | guarded adapter -> `TournamentOperationController` -> `admin/tournament-operations.php` | Legacy mega-screen, not a finished operational UX. |
| `/admin/tournaments/{id}/configuration` | `TournamentConfigurationController` -> `admin/tournament-configuration.php` | Persistence exists, but raw JSON configuration remains exposed. |
| `/admin/access` | `AccessController` -> `admin/access-control.php` | Scope persistence exists, but numeric-ID workflow remains exposed. |
| `/admin/partidas/{match}` and `/admin/partidas/{match}/operar` | `ProductNavigationController::{matchDetail,matchOperation}` -> `admin/product-page.php` | Direct navigation entry with `AuthPolicy` resource permission and scoped match lookup; live controls remain a later match-center module. |
| `/campeonatos/{slug}[/{page}[/{id}]]` | `PublicController` + `PublicPortalPresenter` -> `public/portal.php` | Public route exists, but one generic template and numeric detail identifiers remain. |

### Required split points and dependencies

- **Assisted management before competition:** `AssistedAdministrationService` and `RegistrationValidationService` back roster/document workflows before schedule and lineup work.
- **Competition before live operation:** `ScheduleGenerationService`, `StandingsService` and `BracketService` establish fixture context before `TournamentOperationService` moves into a match center.
- **Match center and report remain separate:** live events use `MatchEventService`; homologation, rectification and versioned reports use `RectificationService`, `MatchReportService` and `PdfReportService`.
- **Public portal follows reviewed state:** `PublicPortalPresenter` needs page-specific public DTOs after management, competition and operation replacements; it must not copy private administration queries.
