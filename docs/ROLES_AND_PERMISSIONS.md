# Perfis e Permissoes

Os perfis sao globais nesta etapa. Escopo por campeonato, equipe ou partida sera adicionado quando essas entidades existirem.

## Perfis

| Perfil | Chave | Escopo futuro | Observacao |
|---|---|---|---|
| Administrador | `administrator` | global | acesso total da plataforma |
| Organizador | `organizer` | campeonamentos autorizados | opera competicoes |
| Treinador ou gestor de equipe | `team_manager` | equipe e campeonato autorizados | opera somente a equipe |
| Operador de partida | `match_operator` | partidas atribuidas | nao homologa a propria partida |
| Comunicacao | `communication` | conteudo autorizado | noticias e Vai e Vem |

## Matriz

| Perfil | Modulo | Acao | Permitido | Negado | Escopo futuro | Observacoes |
|---|---|---|---|---|---|---|
| Administrador | sistema | `system.access`, `system.configure` | sim | - | global | acesso global |
| Administrador | usuarios | `users.view/create/update/deactivate/manage_roles` | sim | - | global | somente administrador |
| Administrador | auditoria | `audit.view` | sim | - | global | sem JSON bruto |
| Administrador | todos | permissoes esportivas | sim | - | global | preparacao administrativa |
| Organizador | campeonamentos | `championships.view/manage` | sim | `system.configure` | campeonamentos autorizados | modulo futuro |
| Organizador | equipes | `teams.view/manage` | sim | `teams.manage_own` | campeonamentos autorizados | modulo futuro |
| Organizador | inscricoes | `registrations.review` | sim | - | campeonamentos autorizados | modulo futuro |
| Organizador | partidas | `matches.view/homologate` | sim | `matches.operate` | campeonamentos autorizados | nao opera como operador |
| Organizador | conteudo | `content.manage/publish`, `transfers.manage` | sim | - | campeonamentos autorizados | modulo futuro |
| Treinador/gestor | equipe | `teams.view/manage_own` | sim | `teams.manage` | propria equipe | modulo futuro |
| Treinador/gestor | atletas | `athletes.view/manage_own` | sim | `athletes.manage` | propria equipe | modulo futuro |
| Treinador/gestor | partidas | `matches.view` | sim | `matches.operate` | equipe autorizada | modulo futuro |
| Operador | partidas | `matches.view/operate` | sim | `matches.homologate` | partidas atribuidas | nao homologa propria partida |
| Comunicacao | conteudo | `content.manage/publish` | sim | `users.view` | campeonamentos autorizados | modulo futuro |
| Comunicacao | Vai e Vem | `transfers.manage` | sim | alteracao esportiva direta | campeonamentos autorizados | aprovacao futura |

Permissoes nomeadas implementadas no seed: `system.access`, `system.configure`, `users.view`, `users.create`, `users.update`, `users.deactivate`, `users.manage_roles`, `audit.view`, `championships.view`, `championships.manage`, `teams.view`, `teams.manage`, `teams.manage_own`, `athletes.view`, `athletes.manage`, `athletes.manage_own`, `registrations.review`, `matches.view`, `matches.operate`, `matches.homologate`, `content.manage`, `content.publish`, `transfers.manage`.
