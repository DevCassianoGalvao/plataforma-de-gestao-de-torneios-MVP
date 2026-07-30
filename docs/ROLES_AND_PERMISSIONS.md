# Perfis, Permissoes e Escopo

Papeis sao globais. Acesso esportivo exige tambem vinculo ao campeonato.

## Matriz das Etapas 3 a 8

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

Operador e comunicacao recebem `403` por padrao neste modulo. O service nunca usa somente o papel global para autorizar uma equipe ou campeonato. As permissoes de atletas, partidas e conteudo sao tratadas nas secoes especificas de cada modulo abaixo.

## Permissoes da Etapa 5

`athletes.view`, `athletes.create`, `athletes.update`, `athletes.deactivate`, `athletes.restore`, `athletes.manage_own`, `positions.view`, `positions.manage`, `athlete_guardians.view`, `athlete_guardians.create`, `athlete_guardians.update`, `athlete_guardians.manage_own`, `athlete_documents.view`, `athlete_documents.create`, `athlete_documents.update`, `athlete_documents.review`, `athlete_documents.manage_own`.

Nenhum documento, guardian, telefone, e-mail, endereco ou observacao privada e exposto por rota publica nesta etapa.

## Etapa 6: inscricoes e elenco

| Perfil | Inscricoes | Elenco oficial | Escopo |
|---|---|---|---|
| Administrador | cria, edita, envia, analisa, aprova, rejeita e cancela | consulta todos | global |
| Organizador | analisa, solicita correcao, aprova, rejeita e cancela | consulta campeonatos autorizados | campeonatos vinculados |
| Treinador/gestor | cria, edita, envia, corrige e cancela | consulta propria equipe | equipe vinculada |
| Operador/comunicacao | `403` | `403` | sem permissao |

Treinador nunca aprova. O servidor revalida equipe, atleta, categoria, periodo, documentos, numero, duplicidade e limites configurados. Documento e dado privado continuam fora de rotas publicas.

Permissoes: `registrations.view`, `registrations.create`, `registrations.update`, `registrations.submit`, `registrations.correct`, `registrations.approve`, `registrations.reject`, `registrations.cancel`, `registrations.manage_own`, `registrations.review`, `rosters.view`.

## Etapa 7: grupos, rodadas e tabela

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | fases, grupos, locais, geracao, agenda, decisoes e leitura de partidas | todos os campeonatos |
| Organizador | mesmas operacoes do calendario | campeonatos autorizados |
| Treinador/gestor | leitura de tabela e proximos jogos | propria equipe |
| Operador | `403` nesta etapa | sem acesso |
| Comunicacao | `403` nesta etapa | sem acesso |

Permissoes adicionadas: `venues.view`, `venues.create`, `venues.update`, `phases.view`, `phases.create`, `phases.update`, `phases.publish`, `groups.view`, `groups.create`, `groups.update`, `groups.distribute`, `groups.publish`, `schedule.view`, `schedule.generate`, `schedule.update`, `schedule.postpone`, `schedule.cancel`. Acoes de agenda exigem CSRF, permissao de escrita e escopo do campeonato; consulta de partida de treinador aplica escopo por equipe no SQL.

## Etapa 8: escalacoes

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | cria, edita, confirma, visualiza e reabre | todos |
| Organizador | visualiza escalacoes | campeonatos autorizados |
| Treinador/gestor | cria, edita e confirma | propria equipe em partida autorizada |
| Operador | visualiza escalacoes confirmadas | partidas autorizadas pelo modulo |
| Comunicacao | `403` | sem acesso |

Permissoes adicionadas: `lineups.view`, `lineups.create`, `lineups.update`, `lineups.confirm`, `lineups.reopen`, `lineups.manage_own`. A confirmacao revalida equipe, elenco aprovado e ativo, onze titulares, capitao titular e goleiro com posicao valida. A incompatibilidade posicional gera apenas alerta. Depois da confirmacao, edicao comum fica bloqueada; reabertura exige administrador, permissao e motivo.

## Etapa 9: operacao e homologacao

## Etapa 10: disciplina

Permissoes adicionadas: discipline.view, discipline.manage, discipline.process, suspensions.view, suspensions.create_manual, suspensions.revoke e cards.cancel. Organizador recebe as decisoes do campeonato autorizado; treinador recebe leitura da propria equipe; operador recebe leitura no contexto da partida; comunicacao nao recebe acesso.

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | visualiza, opera, finaliza e homologa | todos |
| Organizador | visualiza e homologa | campeonatos autorizados |
| Operador | visualiza e opera partidas atribuidas | atribuicao explicita por partida |
| Treinador/gestor | visualiza conforme equipe | partidas da propria equipe |
| Comunicacao | 403 | sem acesso |

Permissoes adicionadas: match_operation.view, match_operation.operate, match_operation.homologate. Acoes de escrita exigem CSRF. O servidor bloqueia mutacoes depois da finalizacao e separa a homologacao do operador. Nenhuma rota publica expoe documentos, dados privados ou operacao.

## Etapa 11: classificacao e mata-mata

| Perfil | Classificacao | Mata-mata | Escopo |
|---|---|---|---|
| Administrador | consulta e recalcula | gera, consulta e avanca | todos |
| Organizador | consulta e recalcula | gera, consulta e avanca | campeonatos autorizados |
| Treinador/gestor | consulta | consulta | fase do campeonato da propria equipe |
| Operador | `403` | `403` | sem acesso |
| Comunicacao | `403` | `403` | sem acesso |

Permissoes adicionadas: `standings.view`, `standings.recalculate`, `knockout.generate`, `knockout.advance`. O servidor valida fase, campeonato, vinculo, CSRF e status homologado antes de qualquer alteracao da chave.

## Etapa 12: sumula digital

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | gerar, visualizar, baixar e empacotar | todos |
| Organizador | gerar, visualizar, baixar e empacotar | campeonatos autorizados |
| Operador | visualizar e baixar | partidas atribuidas |
| Treinador/gestor | visualizar e baixar | partidas da propria equipe |
| Comunicacao | `403` | sem acesso |

Permissoes adicionadas: `match_reports.view`, `match_reports.download`, `match_reports.generate`, `match_reports.package`. PDFs e dados privados nunca sao servidos por rota publica.

## Etapa 13: noticias e blog

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | criar, editar, publicar, despublicar e arquivar | todos os campeonatos |
| Organizador | criar, editar, publicar, despublicar e arquivar | campeonatos atribuidos |
| Comunicacao | criar, editar, publicar, despublicar e arquivar | campeonatos atribuidos |
| Treinador/gestor | `403` no painel editorial | sem acesso |
| Operador | `403` no painel editorial | sem acesso |

Noticias publicadas ficam disponiveis no portal somente quando o campeonato esta publico. O servidor nao confia em links ou menus para aplicar o escopo; cada noticia e capa e revalidada antes da resposta.

## Etapa 14: Vai e Vem

| Perfil | Acesso | Escopo |
|---|---|---|
| Administrador | cria, aprova, publica, cancela e consulta historico | todos os campeonatos |
| Organizador | cria, aprova, publica, cancela e consulta historico | campeonatos atribuidos |
| Comunicacao | cria, aprova, publica, cancela e consulta historico | campeonatos atribuidos |
| Treinador/gestor | `403` | sem acesso |
| Operador | `403` | sem acesso |

Permissao: `transfers.manage`. O registro editorial e separado do vinculo oficial; nenhuma transicao troca automaticamente a equipe do atleta.
# Portal publico

O portal por campeonato nao exige sessao. Ele permite somente leitura de dados publicos filtrados por campeonato visivel e nao carrega documentos, dados de contato, responsaveis legais, observacoes privadas ou arquivos privados. Gestao de conteudo continua protegida pela matriz administrativa; treinador, operador e comunicacao nao recebem permissoes administrativas novas nesta etapa.
