# Authorization Matrix

Server authorization is centralized in `ScopeService`, `ScopedRepository` and `AuthPolicy`. Out-of-scope generic CRUD records are hidden as 404; denied known operational actions return 403 and are audited.

| Method | Route/action | Resource | Permission | Scope | Roles | IDOR |
|---|---|---|---|---|---|---|
| GET/POST | `/admin/organizations` | organization | view/create/update/delete | organization | superadmin | hidden outside scope |
| GET/POST | `/admin/projects`, `/admin/tournaments` | project/championship | view/create/update/delete | org/project/tournament | project admin, organizer | scoped repository |
| GET/POST | configuration/rules/assets | regulation | manage_regulation/create_regulation_version | tournament | project admin, organizer | policy before write |
| GET/POST | teams, people, memberships | team/athlete | view/create/update/delete/manage_roster | project/tournament/team | organizer, team manager | ownership chain |
| GET/POST | registrations | registration | approve_registration/reject_registration/manage_roster | tournament/team | organizer, project admin | scope verified |
| GET/POST | groups, rounds, schedule, knockout | bracket | manage_bracket | tournament | organizer, project admin | tournament verified |
| POST | lineups | lineup | manage_lineup | match/team | organizer, manager, operator | match/team verified |
| POST | events/finish | match | operate_match/finish_match | match/team | organizer, operator | match assignment/team authorization |
| POST | homologate/rectify | match | homologate_match/approve_rectification | match/tournament | organizer, project admin | denied to operator/manager |
| GET | report PDF, document download | report/document | download_private_file | tournament/team | scoped roles | persisted ID and realpath |
| GET/POST | news, galleries, awards | editorial | view/create/update/delete/publish | tournament | communication, organizer | scoped repository |
| GET | access/audit | users/permissions/audit | manage_users/manage_permissions/view_audit_logs | global | superadmin | global endpoint |

Permission keys: `view`, `create`, `update`, `delete`, `restore`, `publish`, `approve_registration`, `reject_registration`, `manage_roster`, `manage_lineup`, `operate_match`, `finish_match`, `homologate_match`, `request_rectification`, `approve_rectification`, `manage_regulation`, `create_regulation_version`, `manage_bracket`, `apply_penalty`, `export`, `download_private_file`, `manage_users`, `manage_permissions`, `view_audit_logs`.

`superadmin` is global. Other roles require both a role permission and matching organization, project, tournament, team and, for operators, explicit match or team authorization.
