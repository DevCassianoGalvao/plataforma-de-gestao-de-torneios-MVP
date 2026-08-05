# Perfis, Permissões e Escopo

Existem quatro perfis: **Administrador**, **Treinador/Gestor**, **Operador de partida** e **Prestação de Contas**. Não há mais um perfil "Organizador" ou "Comunicação" — o Administrador é o único perfil de escopo global e assume toda gestão de campeonato, regulamento e conteúdo editorial que antes era dividida com esses papéis.

Treinador/Gestor e Operador de partida têm escopo restrito: o primeiro por vínculo explícito à equipe (`team_user_assignments`), o segundo por atribuição explícita à partida (`match_operator_assignments`). Prestação de Contas tem escopo por vínculo ao campeonato (`championship_user_assignments`, `assignment_type = 'accountability'`).

## Permissões adicionadas nesta rodada

- `accountability.detail`, `accountability.export_pdf`, `accountability.export_xlsx`, `accountability.export_zip` e `match_reports.signed_upload` controlam a prestação completa.
- `match_operation.rectify.edit`, `match_operation.rectify.complete` e `match_operation.rectify.approve` controlam a retificação avançada.
- `retention.view`, `retention.manage`, `retention.archive` e `retention.restore` controlam a central de retenção.

O administrador recebe todas. O perfil de prestação recebe consulta detalhada e exportações documentais, mas não recebe operação esportiva, retificação ou retenção administrativa.

## Matriz por módulo

| Módulo | Administrador | Treinador/Gestor | Operador de partida | Prestação de Contas |
|---|---|---|---|---|
| Campeonatos, temporadas, categorias, regulamentos | tudo, escopo global | `403` | `403` | `403` |
| Equipes e comissão técnica | tudo, escopo global | leitura e gestão da própria equipe | `403` | `403` |
| Formações táticas | gerencia o catálogo | seleciona formação padrão da própria equipe | `403` | `403` |
| Atletas, responsáveis e documentos | tudo, escopo global | leitura e gestão da própria equipe | `403` | `403` |
| Inscrições e elenco oficial | cria, analisa, aprova, rejeita e cancela | cria, edita, envia e corrige as da própria equipe; nunca aprova | `403` | `403` |
| Grupos, rodadas e tabela | tudo, escopo global | leitura da tabela e próximos jogos da própria equipe | `403` | `403` |
| Escalações | cria, edita, confirma, visualiza e reabre | cria, edita e confirma a da própria equipe | visualiza confirmadas nas partidas atribuídas | `403` |
| Central da partida e homologação | opera, finaliza e homologa qualquer partida | visualiza conforme a própria equipe | visualiza e opera partidas atribuídas | `403` |
| Disciplina e suspensões | gerencia e processa | leitura da própria equipe | leitura no contexto da partida | `403` |
| Classificação e mata-mata | recalcula, gera e avança | leitura da fase da própria equipe | `403` | `403` |
| Súmula digital | gera, baixa e empacota | baixa as da própria equipe | baixa as das partidas atribuídas | baixa (leitura consolidada) |
| Notícias, Vai e Vem (gestão) | cria, edita, publica e arquiva | `403` no painel editorial | `403` | `403` |
| Vai e Vem (solicitação) | aprova, publica, cancela qualquer registro e aplica o vínculo oficial | cria, visualiza e cancela solicitações `transferencia` da própria equipe (`transfers.request`) | `403` | `403` |
| Prestação de contas | tudo | `403` | `403` | consulta e exporta evidências autorizadas |

O portal público por campeonato não exige sessão em nenhum caso: leitura de dados esportivos e editoriais publicados, sem documentos, dados pessoais, responsáveis ou observações privadas.

## Permissões por perfil (chaves reais no banco)

- **Administrador**: recebe automaticamente toda permissão cadastrada no sistema (`array_keys($permissionIds)` no seed) — não há lista fixa a manter.
- **Treinador/Gestor**: `teams.view`, `teams.manage_own`, `teams.select_default_formation`, `team_staff.*` (view/create/update/deactivate/manage_own), `tactical_formations.view`, `athletes.view`, `athletes.create`, `athletes.manage_own`, `positions.view`, `athlete_guardians.*` (view/create/update/manage_own), `athlete_documents.*` (view/create/update/manage_own), `registrations.view/create/update/submit/correct/cancel/manage_own`, `rosters.view`, `matches.view`, `schedule.view`, `lineups.view/create/update/confirm/manage_own`, `match_operation.view`, `discipline.view`, `suspensions.view`, `standings.view`, `match_reports.view/download`, `transfers.request`.
- **Operador de partida**: `matches.view`, `matches.operate`, `lineups.view`, `match_operation.view/operate`, `discipline.view`, `match_reports.view/download`.
- **Prestação de Contas**: `championships.view`, `matches.view`, `match_reports.view/download`, `accountability.view`, `accountability.export`.

## Vai e Vem: solicitação vs. gestão

O ledger de transferências (`transfer_movements`) segue o mesmo estado (`draft → pending → approved → published/cancelled`) para qualquer autor, mas duas permissões distintas controlam quem pode fazer o quê:

- `transfers.manage` (só Administrador): cria/edita para qualquer equipe e tipo, aprova, publica, cancela qualquer registro e aplica o vínculo oficial (`athletes.team_id`).
- `transfers.request` (Treinador/Gestor): cria e edita somente solicitações do tipo `transferencia`, com a equipe de origem sob sua própria gestão (verificado via `team_user_assignments`, mesma convenção usada em atletas/inscrições/escalações/tabela). Só visualiza e cancela as solicitações de sua própria autoria; nunca aprova, publica ou aplica o vínculo oficial.

Um treinador que tenta usar a equipe de outro responsável como origem, ou tenta aprovar/publicar/aplicar o vínculo, recebe `403` — a validação acontece tanto no controller quanto em `TransferService::save()`.
