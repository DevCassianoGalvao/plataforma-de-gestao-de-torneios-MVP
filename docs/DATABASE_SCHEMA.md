# Schema inicial

Migration `001_foundation.sql` cria:

`organizations`, `projects`, `tournaments`, `tournament_settings`, `tournament_themes`, `users`, `roles`, `permissions`, `user_role_assignments`, `people`, `teams`, `team_tournament_entries`, `team_memberships`, `registrations`, `audit_logs`, `login_attempts`.

Todas as tabelas de negócio usam `status`, timestamps e exclusão lógica quando aplicável. Chaves estrangeiras impedem referências inválidas. Índices cobrem slug, e-mail, escopo e relações de campeonato.

`registrations` distingue `athlete` e `staff`; `people` guarda dados restritos e nunca é consultada diretamente pelo portal. `tournament_settings.settings_json` guarda formato, pontuação, desempates, mata-mata, cartões, W.O., elenco e publicação. `tournament_themes` guarda tokens permitidos light/dark.

Migration `002_competition_content.sql` adiciona `stages`, `groups_competition`, `rounds`, `venues`, `matches`, `match_lineups`, `match_events`, `match_reports`, `disciplinary_records`, `suspensions`, `standings_snapshots`, `news_posts`, `galleries`, `gallery_items`, `transfers`, `documents`, `awards`, `notifications` e `export_jobs`.

## Mapa do MVP desta rodada

| Conceito do PRD | Tabela(s) persistida(s) | Observacao |
|---|---|---|
| usuarios, perfis, permissoes e escopos | `users`, `roles`, `permissions`, `user_role_assignments`, `role_permission_assignments` | escopo por organizacao, projeto, campeonato ou equipe |
| organizacao/projeto | `organizations`, `projects` | campeonato pertence a projeto |
| campeonato, temporada e categoria | `tournaments`, `seasons`, `categories`, `tournament_categories` | identidade em `tournament_themes` e `tournament_assets` |
| regulamento e versoes | `tournament_rule_versions`, `tournament_settings` | JSON versionado e validado por service |
| equipes e comissao | `teams`, `team_tournament_entries`, `team_memberships`, `team_membership_history` | comissao usa `people.person_type` e cargo do vinculo |
| atletas e posicoes | `people`, `person_profiles` | posicoes secundarias em JSON controlado do perfil |
| responsavel legal | `legal_guardians` | dado privado |
| documentos | `document_types`, `person_documents`, `documents`, `private_file_access_logs` | privado fora de `public/` |
| inscricoes | `registrations` | status e camisa por campeonato/equipe |
| locais | `venues` | pertence ao projeto |
| grupos e rodadas | `stages`, `groups_competition`, `group_team_assignments`, `rounds` | atribuicao por tabela propria |
| partidas | `matches`, `match_decisions` | inclui adiamento, cancelamento e W.O. |
| formacao e escalação | `tactical_formations`, `formation_slots`, `team_default_formations`, `match_lineups`, `match_lineup_positions` | distribuicao automatica e override manual persistivel |
| gols, assistencias, cartoes e ocorrencias | `match_events`, `discipline_ledger`, `player_statistics` | derivados de eventos homologados |
| substituicoes e penaltis | `match_substitutions`, `match_shootout_attempts` | regras vem do regulamento |
| suspensoes | `suspensions`, `suspension_fulfillments`, `disciplinary_records` | bloqueio antes da confirmacao |
| classificacao | `standings_snapshots`, `team_penalties` | reconstruivel |
| mata-mata | `bracket_links`, `stages`, `rounds`, `matches` | cruzamentos configurados |
| sumula | `match_reports`, `match_report_versions`, `match_homologation_versions`, `match_officials` | PDF privado e snapshot |
| noticias | `news_posts` | publicacao por campeonato |
| Vai e Vem | `transfers`, `team_membership_history` | historico preservado |
| auditoria | `audit_logs` | inclui escopo e negacoes |

## JSON permitido

JSON e aceito somente para regras versionadas, posicoes secundarias, metadados de evento, snapshots imutaveis de homologacao e justificativas de retificacao. Relacionamentos de pessoas, equipes, partidas, eventos e permissoes permanecem em tabelas com FK.

## Lacunas declaradas

O campo tatico visual definitivo, drag-and-drop refinado e a fidelidade final do PDF continuam pendentes. A camada estrutural de formacoes, slots, defaults e distribuicao automatica ja esta coberta pela migration 021 e `FormationService`.
