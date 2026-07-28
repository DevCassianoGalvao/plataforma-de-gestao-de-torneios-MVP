# Schema de Banco

## Fundacao implementada nesta etapa

Migration inicial: `database/migrations/0001_foundation.sql`.

| Tabela | Finalidade | Estado |
|---|---|---|
| `schema_migrations` | Controlar arquivos SQL aplicados | implementada |
| `foundation_health` | Provar persistencia e health check | implementada |

Nao ha tabelas de negocio neste commit.

## Entidades planejadas do MVP

Estas entidades sao contrato futuro, nao implementacao atual:

- `organizations`, `projects`, `tournaments`, `tournament_settings`, `tournament_versions`;
- `users`, `roles`, `permissions`, `user_role_assignments`, `audit_logs`;
- `teams`, `team_tournament_entries`, `people`, `team_memberships`;
- `athlete_profiles`, `guardians`, `private_documents`, `registrations`;
- `stages`, `groups`, `rounds`, `venues`, `matches`;
- `tactical_formations`, `formation_slots`, `match_lineups`, `lineup_positions`;
- `match_events`, `discipline_records`, `suspensions`, `standings`, `bracket_nodes`;
- `match_reports`, `match_report_versions`, `news_posts`, `galleries`, `transfers`.

## Regras de modelagem futuras

- IDs internos nunca serao digitados em formularios operacionais.
- Relacoes serao validadas pelo escopo organization/project/tournament/team.
- Regras esportivas serao dados versionados por campeonato, nao constantes espalhadas no codigo.
- Documentos pessoais e sumulas privadas terao caminho privado e autorizacao server-side.
- Resultados homologados terao historico imutavel e retificacao versionada.
