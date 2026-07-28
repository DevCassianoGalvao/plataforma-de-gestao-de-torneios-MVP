# Real E2E Test Plan

## Honest classification of current tests

The files below are useful regressions, but names ending in `_e2e.php` are not proof of end-to-end browser behavior unless stated otherwise.

| File | Actual test type | Why |
|---|---|---|
| `tests/dashboard_ui_e2e.php` | Structural source test | Reads login/dashboard/CSS/JS files and uses `str_contains`. |
| `tests/management_ui_e2e.php` | Structural source test | Checks markup/CSS/JS fragments. |
| `tests/match_center_ui_e2e.php` | Structural source test | Checks strings/classes in template and CSS. |
| `tests/public_ui_e2e.php` | Structural source test | Checks template/CSS fragments. |
| `tests/ui_foundation_e2e.php` | Structural source test | Checks files and required text fragments. |
| `tests/ui_ux_audit_e2e.php` | Structural source test | Checks source fragments and breakpoint strings. |
| `tests/http_authenticated_e2e.php` | Structural security/source test | Reads auth/session source; sends no HTTP request. |
| `tests/security_e2e.php` | Unit/source test | Executes CSRF helper and searches source for hardening tokens. |
| `tests/clean_install_e2e.php` | Structural script test | Reads PowerShell source; does not install. |
| `tests/public_portal_e2e.php` | In-process controller integration | Calls `PublicController` directly; no server/browser. |
| `tests/accountability_e2e.php` | Database/service integration | Inserts records and calls services directly. |
| `tests/admin_workflow_e2e.php` | Mixed structural/service integration | Some file assertions plus direct services. |
| `tests/authorization_e2e.php` | Database/service integration | Calls scope/repository services directly. |
| `tests/rectification_e2e.php` | Database/service integration | Calls service inside transaction. |
| `tests/sports_rules_e2e.php` | Database/service integration | Calls rules/standings services directly. |
| `tests/tournament_e2e.php` | Database/service integration | Builds tournament through services; direct controller rendering once. |

## Test stack to add

- Keep PHP unit/service/integration tests for domain rules and scoped persistence.
- Add an HTTP suite against a disposable database that uses real cookies, redirects, CSRF tokens, multipart upload and download headers.
- Add Playwright browser tests for journeys. It must start the PHP server, create disposable data, navigate/click/type/assert visible UI, and record trace/screenshots on failure.
- Add Playwright visual/responsive checks for target viewports: 320, 375, 768, 1024, 1366, 1440 and 1920.
- Treat browser testing as mandatory acceptance evidence. Text searches remain lint-like structural checks only.

## Required browser acceptance scenarios

| Journey | Browser proof |
|---|---|
| Login and account | Valid login, invalid login, CSRF failure, logout, accessible error/focus and session redirect. |
| Role navigation | One test per role verifies visible menu, denied route behavior and no cross-scope data. |
| Championship setup | Create/select championship, configure identity/rules through human fields, persist and reload. |
| Team/athlete/staff | Scoped list, create/edit, field error, document state and no raw IDs/JSON visible. |
| Registration | Submit, review, reject with reason, approve and verify roster status. |
| Groups/table | Create groups, distribute teams, preview generation, confirm schedule and inspect fixtures. |
| Match center | Open assigned match, prepare lineup, record goal/card/substitution, see timeline/score, finish. |
| Homologation/rectification | Review checklist, homologate, request rectification, show version diff and explicit impact decision. |
| Public portal | Published championship home, matches, team, athlete, standings, bracket, content detail and 404. |
| Privacy/security | Other-team IDOR, private document download denial, hidden resource 404, invalid upload, path traversal denial. |
| Mobile/accessibility | Drawer/menu keyboard behavior, focus visibility, no overflow, readable tables/bracket at mobile widths. |

## Acceptance infrastructure

1. A disposable DB name/prefix is generated per run and removed in `finally`.
2. Migrations and dedicated visual fixtures run before tests; production/staging data is never touched.
3. Test users have each real role and explicit scopes; IDs are discovered from fixture API/database only, never typed into user UI.
4. Playwright outputs trace, screenshot and video on failure; artifact paths are excluded from Git.
5. CI has separate jobs: PHP lint, service integration, HTTP integration and browser acceptance.

## Completion gate

No reconstructed page is complete until a browser test exercises its primary task, an authorization test covers its mutation, and at least desktop/mobile screenshots show no critical overflow, focus or contrast issue.

## Current proof boundary - 2026-07-27

| Test | Verified mechanism | Correct label |
|---|---|---|
| `tests/navigation_http_e2e.php` | Opens a TCP socket with `stream_socket_client`, exchanges login cookies/CSRF, follows role redirects and checks 403/404 under `NAVIGATION_TEST_URL` | HTTP integration; no browser rendering proof. |
| `tests/public_portal_e2e.php` | Instantiates `PublicController` and examines returned HTML | In-process controller integration. |
| `tests/tournament_e2e.php`, `sports_rules_e2e.php`, `rectification_e2e.php`, `accountability_e2e.php`, `authorization_e2e.php` | Uses database records and services directly | Domain/service integration. |
| `tests/dashboard_ui_e2e.php`, `management_ui_e2e.php`, `match_center_ui_e2e.php`, `public_ui_e2e.php`, `ui_foundation_e2e.php`, `ui_ux_audit_e2e.php` | Uses `file_get_contents()` and `str_contains()` on source files | Structural source checks. |
| `tests/http_authenticated_e2e.php`, `security_e2e.php`, `clean_install_e2e.php` | Checks source/script fragments and helper behavior | Structural/security checks, not authenticated HTTP or clean installation execution. |

### Test dependency order

1. Disposable database/migration/fixture runner must exist before browser work.
2. HTTP mutation/authorization tests must protect every new route before UI acceptance.
3. Playwright workflow tests then prove the human flow by role and viewport.
4. Visual/a11y screenshots become release evidence only after functional HTTP/browser assertions pass.

Generated screenshots, videos, traces, PDFs, database dumps, uploads and test logs
are CI artifacts or local evidence. They must remain excluded from Git.
