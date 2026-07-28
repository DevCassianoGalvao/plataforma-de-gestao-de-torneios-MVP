# Perfis, Permissoes e Escopo

Papeis sao globais. Acesso esportivo exige tambem vinculo ao campeonato.

## Matriz das Etapas 3, 4 e 5

| Perfil | Modulo | Acao | Permitido | Negado | Escopo | Observacao |
|---|---|---|---|---|---|---|
| Administrador | campeonamentos | view/create/update/archive/manage_identity/manage_assignments | sim | - | todos | acesso global |
| Administrador | temporadas/categorias | view/manage | sim | - | global | catalogos |
| Administrador | regulamentos | view/create/update/publish/version_history | sim | - | todos | pode administrar versoes |
| Organizador | campeonamentos | view/create/update/manage_identity | sim | archive/manage_assignments | vinculados como organizer | nao acessa campeonato alheio |
| Organizador | temporadas/categorias | view | sim | manage | catalogo existente | seleciona no formulario |
| Organizador | regulamentos | view/create/update/publish/version_history | sim | - | campeonato vinculado | nao usa JSON |
| Treinador/gestor | equipes | view/update/manage_own | sim | outras equipes, campeonato e regulamento | equipes vinculadas | nao troca o campeonato |
| Operador | campeonamentos | modulo esportivo | nao nesta etapa | sim | futuro | partidas entram depois |
| Comunicacao | campeonamentos/regulamentos | configuracao | nao nesta etapa | sim | futuro | conteudo entra depois |
| Administrador | atletas, responsaveis e documentos | todas as operacoes | sim | - | global | documentos privados autorizados |
| Organizador | atletas, responsaveis e documentos | operacoes autorizadas | sim | equipes fora dos campeonamentos vinculados | campeonamentos autorizados | sem acesso publico |
| Treinador/gestor | atletas, responsaveis e documentos | leitura e gestao propria | sim | outra equipe | equipe vinculada | dados privados exigem sessao |
| Operador | atletas e documentos | nenhuma | nao | sim | - | `403` |
| Comunicacao | atletas e documentos | nenhuma | nao | sim | - | `403` |

## Permissoes adicionadas

`championships.view`, `championships.create`, `championships.update`, `championships.archive`, `championships.manage_identity`, `championships.manage_assignments`, `seasons.view`, `seasons.manage`, `categories.view`, `categories.manage`, `regulations.view`, `regulations.create`, `regulations.update`, `regulations.publish`, `regulations.version_history`.

## Permissoes da Etapa 4

`teams.view`, `teams.create`, `teams.update`, `teams.deactivate`, `teams.restore`, `teams.manage_assignments`, `teams.manage_identity`, `teams.manage_own`, `team_staff.view`, `team_staff.create`, `team_staff.update`, `team_staff.deactivate`, `team_staff.manage_own`, `tactical_formations.view`, `tactical_formations.manage`, `teams.select_default_formation`.

Administrador recebe escopo global. Organizador recebe operacoes de equipes nos campeonatos vinculados como `organizer`. Treinador ou gestor recebe leitura e operacoes permitidas apenas nas equipes com vinculo ativo; nao pode trocar o campeonato nem atribuir responsaveis administrativos. O vinculo e explicito, portanto um treinador pode estar em mais de uma equipe sem ganhar acesso automatico as demais.

Operador e comunicacao recebem `403` por padrao neste modulo. O service nunca usa somente o papel global para autorizar uma equipe ou campeonato. Permissoes de atletas, partidas, conteudo e transferencias continuam fora do escopo.

## Permissoes da Etapa 5

`athletes.view`, `athletes.create`, `athletes.update`, `athletes.deactivate`, `athletes.restore`, `athletes.manage_own`, `positions.view`, `positions.manage`, `athlete_guardians.view`, `athlete_guardians.create`, `athlete_guardians.update`, `athlete_guardians.manage_own`, `athlete_documents.view`, `athlete_documents.create`, `athlete_documents.update`, `athlete_documents.review`, `athlete_documents.manage_own`.

Nenhum documento, guardian, telefone, e-mail, endereco ou observacao privada e exposto por rota publica nesta etapa.
