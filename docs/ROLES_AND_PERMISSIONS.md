# Perfis, Permissoes e Escopo

Papeis sao globais. Acesso esportivo exige tambem vinculo ao campeonato.

## Matriz da Etapa 3

| Perfil | Modulo | Acao | Permitido | Negado | Escopo | Observacao |
|---|---|---|---|---|---|---|
| Administrador | campeonamentos | view/create/update/archive/manage_identity/manage_assignments | sim | - | todos | acesso global |
| Administrador | temporadas/categorias | view/manage | sim | - | global | catalogos |
| Administrador | regulamentos | view/create/update/publish/version_history | sim | - | todos | pode administrar versoes |
| Organizador | campeonamentos | view/create/update/manage_identity | sim | archive/manage_assignments | vinculados como organizer | nao acessa campeonato alheio |
| Organizador | temporadas/categorias | view | sim | manage | catalogo existente | seleciona no formulario |
| Organizador | regulamentos | view/create/update/publish/version_history | sim | - | campeonato vinculado | nao usa JSON |
| Treinador/gestor | campeonamentos | modulo esportivo | nao nesta etapa | sim | futuro | equipes entram depois |
| Operador | campeonamentos | modulo esportivo | nao nesta etapa | sim | futuro | partidas entram depois |
| Comunicacao | campeonamentos/regulamentos | configuracao | nao nesta etapa | sim | futuro | conteudo entra depois |

## Permissoes adicionadas

`championships.view`, `championships.create`, `championships.update`, `championships.archive`, `championships.manage_identity`, `championships.manage_assignments`, `seasons.view`, `seasons.manage`, `categories.view`, `categories.manage`, `regulations.view`, `regulations.create`, `regulations.update`, `regulations.publish`, `regulations.version_history`.

Permissoes de equipes, atletas, partidas, conteudo e transferencias continuam preparadas para etapas futuras. O service nunca usa somente o papel global para autorizar um campeonato.
