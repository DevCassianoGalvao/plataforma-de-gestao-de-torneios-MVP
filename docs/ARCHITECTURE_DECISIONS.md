# Decisões Arquiteturais em Evolução

## AD-021 - Design system em camadas e tema seguro

O CSS visual e separado em tokens, temas, layout, componentes e fundacao, carregados apos os estilos legados durante a migracao. O portal usa somente cores hexadecimais aprovadas por `ThemeService`; nenhuma configuracao do campeonato altera markup, JavaScript ou cores textuais globais. A preferencia local do usuario prevalece sobre o tema padrao apenas no navegador.

## AD-020 - Eventos esportivos avançados persistidos

Substituições, cobranças de pênalti, limpeza de cartões e autorizações excepcionais usam tabelas próprias. Gols de disputa não viram `match_events`, portanto não contaminam placar normal nem artilharia. Regras são lidas da versão ativa; não há constantes da Copa Brasil no serviço.

## AD-019 - Cadastro administrativo assistido

A URL do campeonato é a origem do escopo operacional. O cadastro de equipe deriva `project_id` do campeonato persistido e cria a inscrição transacionalmente. Atletas e comissão validam a participação da equipe no campeonato e criam vínculos/histórico. IDs submetidos escolhem registros; nunca definem escopo.

## AD-018 - Centralized scope authorization

`ScopeService` resolves ownership from persisted resource relations. `ScopedRepository` filters generic CRUD reads and validates scope before writes/deletes. `AuthPolicy` applies named operational permissions and logs denied critical attempts. Match operators require a persisted match assignment or persisted team authorization; request IDs never define scope.

## AD-016 - Serviço operacional transacional
`TournamentOperationService` concentra inscrição, grupos, agenda, escalação, eventos, homologação, estatísticas, classificação, chave e PDF. Controllers validam CSRF e autorização e não calculam resultado diretamente.

## AD-017 - Chave dirigida por regulamento
Quartas leem `knockout.pairings` da versão ativa do regulamento. Semifinal e final só nascem quando todos os resultados da fase anterior foram homologados.

## AD-001 — Backlog é contrato de progresso
`IMPLEMENTATION_PLAN.md` cobre o sistema inteiro. Tabela, rota ou serviço isolado não fecha uma etapa.

## AD-002 — Validação antes de persistência
Entrada administrativa precisa ser validada em servidor; o banco é segunda barreira.

## AD-003 — Recuperação de senha sem dependência externa
Tokens temporários são persistidos; desenvolvimento registra link e produção depende de SMTP configurado.

## AD-004 — Motor esportivo configurável
Regras devem ser dados versionáveis por campeonato, nunca constantes da Copa Brasil.

## AD-005 — Dados de menores
Dados e arquivos privados não entram em consultas públicas.

## AD-006 — Permissões persistidas
Permissões são atribuídas a roles. Policies e consultas devem aplicar escopo, não somente menus.

## AD-007 — Autorização por contexto persistido
Downloads privados usam `permission_key` e contexto resolvido no banco, nunca caminhos ou escopos enviados pelo cliente.

## AD-008 — Arquivos privados
Arquivos privados ficam em `storage/private`; o download usa ID, `realpath` e trilha de auditoria.

## AD-009 — Regulamento imutável por versão
Alterações preservam JSON anterior e exigem justificativa após partida iniciada.

## AD-010 — Perfil e documentos separados
Dados esportivos, responsáveis e documentos devem usar tabelas próprias vinculadas à pessoa.

## AD-011 — Geração de tabela configurável
Round-robin recebe equipes e parâmetros; persistência e publicação requerem prévia e confirmação.

## AD-012 — Eventos preservados
Eventos são anulados com motivo; placar considera apenas eventos válidos.

## AD-013 — Classificação idempotente
Snapshots devem ser reconstruídos transacionalmente a partir de partidas válidas, regulamento e punições.

## AD-014 — Exportações assíncronas simples
`export_jobs` registra solicitação, progresso, arquivo, expiração e falha; uma fila externa pode ser conectada depois.

## AD-015 — Estado auditado prevalece sobre roadmap
Checkboxes, migrations e serviços isolados não comprovam fluxo de produto. Um item só fica concluído com rota operacional, autorização no servidor, persistência, tratamento de falha e teste proporcional ao risco. A auditoria de 27/07/2026 reabriu itens entregues apenas como infraestrutura ou CRUD genérico.


## AD-022 - Product navigation is route and scope based

ProductNavigationController and ProductNavigationService own product navigation. Menus derive from persisted role assignments; direct requests are checked again by role/scope resolution. Championship context uses permitted persisted tournaments and friendly slugs. Legacy tournament operation, including action endpoints, is superadmin-only during migration.

## AD-023 - Direct match navigation remains policy guarded

`/admin/partidas/{match}` and `/admin/partidas/{match}/operar` are navigation
entry points, not authority grants. `ProductNavigationController` first uses
`AuthPolicy::requirePermission()` on the persisted match and then resolves its
permitted tournament context. The detail and operation pages expose names/statuses
but not the numeric match identifier. Legacy operation remains the temporary
superadmin fallback until the dedicated match-center workflow replaces it.

## AD-024 - Source files and repository root

The requested staging folder contains `docs/PRD_SIMPLIFICADO_PLATAFORMA_TORNEIOS_V2.md`, while the Git repository root contains `PRD_Plataforma_Gestao_Torneios.md`. Both source locations were checked before implementation, with the staging PRD and `docs/REFERENCIA_SUMULA.xlsx` treated as the requested round inputs. Work stays in the Git root so migrations, tests and the requested commit are auditable.

## AD-025 - Demo seed owns demo authorization fixtures

Migrations create permission definitions before demo roles exist, so a clean database cannot rely on migration-time role assignments. `database/seed.php --demo` creates roles, ensures baseline permissions and idempotently syncs role-permission assignments. Fresh install and repeated demo seed therefore use the same authorization fixtures.

## AD-026 - Match report mapping follows workbook structure

The workbook is treated as a reference for fields and visual grouping, not as a data import. The two team blocks, eight goal columns, AM/VM, period times, officials, signatures and verso occurrences are mapped in `docs/MATCH_REPORT_MAPPING.md`. Real personal data present in the source workbook is never copied into demo seed.

## AD-027 - Structural round stops before visual finalization

This round prioritizes persistence, server validation, route separation and tests. No final animations, dark-mode polish, tactical-field polish, bracket polish or pixel-faithful PDF styling is declared complete. Those changes require a later UI/UX acceptance pass.
