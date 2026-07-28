# Generic CRUD Replacement Map

Current generic CRUD is `AdminController` plus `app/Views/admin/crud.php`. It is an internal migration utility, not a final interface. The following replacements are required before it can leave primary navigation.

| Current technical field/screen | Friendly field/component | Required relationship and validation | Permission | Destination page |
|---|---|---|---|---|
| `organization_id` | Organization selector showing name and status | Filtered to assignable organizations; persisted scope validation | create/update resource | Context switcher or scoped setup page |
| `project_id` | Project selector filtered by organization | Project belongs to active organization | create/update resource | Championship setup |
| `tournament_id` | Championship selector or inherited active context | Never accepted as authority from form; resolve scope server-side | scoped action | Workspace context |
| `team_id` | Team search/select with crest/name/category | Team participates in current championship | roster/match action | Team, registration, lineup pages |
| `person_id` | Athlete/staff autocomplete with public name, team and eligibility badge | Person role, team membership, registration status and scope | roster/match action | Athlete/staff/lineup pages |
| `stage_id`, `group_id`, `round_id` | Dependent selectors with names/orders | Must belong to current championship; locked state respected | manage competition | Competition pages |
| `match_id` | Current match context, not free numeric input | Match belongs to championship and assigned operator when applicable | operate/view match | Match detail/center |
| `category_id`, `season_id` | Named tags/selectors | Category/season active and compatible with championship | setup/roster | Championship setup, team profile |
| `settings_json` | Structured regulation editor: sections, switches, numeric fields, sortable tie-break criteria | Schema validation, version reason and lock-after-start rule | manage regulation | Regulation setup tab |
| `before_json`, `after_json`, `snapshot_json` | Read-only diff/timeline | Immutable history, masked sensitive fields | audit/rectify | Rectification and audit detail |
| `file_path` | File name, type, validity and private/public badge | File selected by persisted document ID; path never editable | document permission | Document library/detail |
| `visibility`, `status` raw values | Localized status badge and permitted transition menu | Server transition rules and explanation/reason | domain-specific action | Detail/review pages |
| raw timestamps | Locale date/time with timezone and status context | Parse/validate dates and interval constraints | relevant action | All lists/details |
| `home_team_id`, `away_team_id` | Match pairing builder with team names/crests | Same championship, no duplicate pairing; schedule constraints | manage competition | Fixture wizard/match editor |
| card/event enums | Quick event controls with labels/icons | Event type validity, lineup eligibility, period/minute | operate match | Match center |
| `export_type`, job fields | Report catalog and job status card | Scope, expiry, selected filters and requester | export/download | Reports/export center |

## Screen replacements

| Generic entity | Current state | Replacement |
|---|---|---|
| `organizations`, `projects` | Dynamic table/form | Tenant/project list and detail with member tabs. |
| `tournaments`, settings, themes | Dynamic table plus configuration page | Championship list, setup wizard and identity/rules tabs. |
| `teams`, entries, memberships | Dynamic table plus mega screen | Team directory/detail, participation status and roster tabs. |
| `people`, registrations, documents | Dynamic table plus mega screen | Athlete/staff detail, registration inbox and document review workflow. |
| `stages`, venues, matches, events, reports | Dynamic table plus mega screen | Competition workspace, fixtures, match detail/center and report archive. |
| discipline, suspensions | Dynamic table | Discipline inbox, athlete/team history and authorized decision flow. |
| news, galleries, transfers, awards | Dynamic table | Editorial calendar/list/editor and publication preview. |
| exports/accountability | Narrow dashboard | Report catalog, filters, async job detail and download history. |

## Form implementation constraints

- IDs may exist only in hidden, server-resolved internal fields; never as user-entered inputs.
- Option lists are scoped on server and show human context (name, crest, category, current status).
- Relations use dependent selects/autocomplete; an invalid or stale relation returns a validation error without data leak.
- Destructive and status-changing actions require explicit confirmation and a reason when domain policy requires it.
- Form errors remain beside the relevant field and preserve non-sensitive user input.

## Current route-to-replacement evidence - 2026-07-27

| Existing route and implementation | Technical leak or workflow problem | Gradual replacement boundary |
|---|---|---|
| `public/index.php` entity loop -> `AdminController::index/edit/save/delete` -> `admin/crud.php` | Dynamic metadata renders database-oriented fields and relations. | Add named list/detail/workflow routes first; leave the generic route server-scoped and out of product menus until no workflow points to it. |
| `/admin/tournaments/{id}/configuration` -> `TournamentConfigurationController::saveRules()` -> `admin/tournament-configuration.php` | `settings_json` is decoded from a raw textarea. | Structured regulation tabs call the same `RuleConfigurationService::save()` contract and keep the old route as a protected fallback. |
| `/admin/access` -> `AccessController::index()` -> `admin/access-control.php` | Assignment uses organization/project/tournament/team numeric IDs. | User-access workspace resolves named, scoped selectors before posting an internal relation value. |
| `/admin/documents/upload` -> `AdminController::uploadDocumentForm()` -> `admin/upload-document.php` | User enters tournament relation context directly. | Document library inherits tournament/team context and uses `UploadService` plus existing private download policy. |
| `/admin/tournaments/{id}/operation` -> `TournamentOperationController::dashboard()` -> `admin/tournament-operations.php` | Teams, athletes, registrations, groups, schedule, lineups, events, homologation and reports mix in one page. | Replace one task route at a time; preserve guarded legacy POST adapters until each replacement has mutation and regression evidence. |

The source string in `tests/admin_workflow_e2e.php` only proves that guided hidden
fields occur in the template. It is not acceptance that a person can safely complete
the workflow; Stage 2 onward requires browser interaction evidence.
# Etapa 2 - Cadastros assistidos

- [x] `teams` no fluxo do campeonato: `/admin/campeonatos/{slug}/equipes` usa `AssistedManagementController`, nomes e selects de categoria; o CRUD genérico continua apenas como legado administrativo.
- [x] `people`, `team_memberships` e `legal_guardians`: telas `/atletas`, `/comissao` e `/responsaveis` resolvem equipe e atleta pelo escopo do campeonato, sem IDs livres fora dos valores persistidos em selects.
- [x] `registrations` e `documents`: telas `/inscricoes` e `/documentos` usam serviços existentes, CSRF e persistência real. `020_assisted_document_metadata.sql` completa a validade documental sem apagar dados.
- [ ] Listagens globais de campeonatos e a edição completa da identidade continuam na Etapa 2.1: a configuração estruturada por campeonato já está disponível em `/configuracoes`, mas a tela global de ciclo de vida ainda usa o legado.
