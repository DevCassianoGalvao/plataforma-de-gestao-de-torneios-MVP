# Rotas e Paginas

Todas as URLs respeitam `APP_BASE_PATH=/copa-online`.

## Catalogos

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/temporadas` | listar temporadas |
| GET/POST | `/admin/temporadas/nova`, `/admin/temporadas` | criar temporada |
| GET/POST | `/admin/temporadas/{id}/editar`, `/admin/temporadas/{id}` | editar temporada |
| GET | `/admin/categorias` | listar categorias |
| GET/POST | `/admin/categorias/nova`, `/admin/categorias` | criar categoria |
| GET/POST | `/admin/categorias/{id}/editar`, `/admin/categorias/{id}` | editar categoria |

## Campeonamentos

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/campeonatos` | listar, buscar e filtrar |
| GET/POST | `/admin/campeonatos/novo`, `/admin/campeonatos` | criar rascunho |
| GET | `/admin/campeonatos/{slug}` | dashboard simples |
| GET/POST | `/admin/campeonatos/{slug}/editar`, `/admin/campeonatos/{slug}` | editar gerais |
| GET/POST | `/admin/campeonatos/{slug}/identidade` | cores, tema e uploads |
| GET | `/admin/campeonatos/{slug}/assets/{field}` | asset privado autorizado |
| POST | `/admin/campeonatos/{slug}/status` | transicao validada |
| POST | `/admin/campeonatos/{slug}/arquivar` | arquivamento |
| GET/POST | `/admin/campeonatos/{slug}/organizadores` | listar e vincular organizadores |
| POST | `/admin/campeonatos/{slug}/organizadores/{userId}/remover` | remover vinculo |

## Regulamentos

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/campeonatos/{slug}/regulamento` | resumo e versoes |
| GET/POST | `/admin/campeonatos/{slug}/regulamento/editar`, `/admin/campeonatos/{slug}/regulamento` | editor estruturado |
| POST | `/admin/campeonatos/{slug}/regulamento/preset` | aplicar preset sem duplicar |
| POST | `/admin/campeonatos/{slug}/regulamento/documento` | anexar PDF privado |
| GET | `/admin/campeonatos/{slug}/regulamento/documentos/{id}` | consultar PDF autorizado |
| GET | `/admin/campeonatos/{slug}/regulamento/revisar` | revisao antes de publicar |
| POST | `/admin/campeonatos/{slug}/regulamento/publicar` | publicar versao |
| GET | `/admin/campeonatos/{slug}/regulamento/versoes` | historico |
| GET | `/admin/campeonatos/{slug}/regulamento/versoes/{version}` | consultar versao |

Nenhum formulario solicita JSON, nome de tabela ou ID digitado livremente. Selects exibem nomes e os IDs ficam apenas como valores internos.

## Equipes

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/equipes` | listar, buscar e filtrar equipes |
| GET/POST | `/admin/equipes/nova`, `/admin/equipes` | criar equipe |
| GET | `/admin/equipes/{slug}` | visao geral da equipe |
| GET/POST | `/admin/equipes/{slug}/editar`, `/admin/equipes/{slug}` | editar dados gerais |
| GET/POST | `/admin/equipes/{slug}/identidade` | cores e escudo |
| GET | `/admin/equipes/{slug}/assets/{field}` | asset privado autorizado |
| POST | `/admin/equipes/{slug}/status` | alterar status com transicao validada |
| POST | `/admin/equipes/{slug}/restaurar` | restaurar equipe arquivada |

## Responsaveis e comissao

| Metodo | Rota | Funcao |
|---|---|---|
| GET/POST | `/admin/equipes/{slug}/responsaveis` | consultar e atribuir usuarios |
| POST | `/admin/equipes/{slug}/responsaveis/{assignment}/encerrar` | encerrar vinculo |
| GET | `/admin/equipes/{slug}/comissao` | listar membros |
| GET | `/admin/equipes/{slug}/comissao/nova` | formulario de membro |
| POST | `/admin/equipes/{slug}/comissao` | cadastrar membro |
| GET/POST | `/admin/equipes/{slug}/comissao/{staff}/editar`, `/admin/equipes/{slug}/comissao/{staff}` | editar membro |
| POST | `/admin/equipes/{slug}/comissao/{staff}/status` | inativar ou reativar membro |

## Formacao padrao

| Metodo | Rota | Funcao |
|---|---|---|
| GET/POST | `/admin/equipes/{slug}/formacao` | consultar slots e selecionar formacao padrao |

## Atletas

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/atletas` | listar, buscar e filtrar por equipe, posicao, idade e status |
| GET/POST | `/admin/atletas/nova`, `/admin/atletas` | cadastrar atleta e responsavel de menor |
| GET/POST | `/admin/atletas/{id}/editar`, `/admin/atletas/{id}` | editar dados esportivos |
| GET | `/admin/atletas/{id}` | detalhe com dados esportivos, equipe, posicoes, responsavel e documentos |
| POST | `/admin/atletas/{id}/status` | alterar status validado |
| POST | `/admin/atletas/{id}/excluir` | exclusao logica |
| GET | `/admin/posicoes` | consultar catalogo estruturado de posicoes |

## Responsaveis e documentos de atletas

| Metodo | Rota | Funcao |
|---|---|---|
| GET/POST | `/admin/atletas/{id}/responsaveis` | listar e vincular responsavel legal |
| GET/POST | `/admin/atletas/{id}/documentos` | listar e enviar documento privado |
| GET | `/admin/atletas/{id}/documentos/{documentId}` | servir arquivo somente apos autorizacao |
| POST | `/admin/atletas/{id}/documentos/{documentId}/status` | revisar documento |
| GET | `/admin/atletas/{id}/assets/photo` | servir foto privada autorizada |

As telas exibem nomes, funcoes e formacoes; IDs permanecem apenas nos valores internos dos formularios. Inscricoes, partidas e disciplina aparecem como modulos futuros na pagina do atleta.

## Inscricoes e elenco oficial

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/admin/inscricoes` | central com filtros e abas por status |
| GET/POST | `/admin/inscricoes/nova`, `/admin/inscricoes` | criar rascunho com selects de campeonato, equipe e atleta |
| GET | `/admin/inscricoes/{id}` | detalhe, pendencias e historico |
| POST | `/admin/inscricoes/{id}` | corrigir rascunho ou pendencia |
| POST | `/admin/inscricoes/{id}/enviar` | enviar ou reenviar |
| POST | `/admin/inscricoes/{id}/iniciar-analise` | iniciar analise do organizador |
| POST | `/admin/inscricoes/{id}/pendencia` | solicitar correcao |
| POST | `/admin/inscricoes/{id}/aprovar` | aprovar e incluir no elenco oficial |
| POST | `/admin/inscricoes/{id}/rejeitar` | rejeitar com motivo |
| POST | `/admin/inscricoes/{id}/cancelar` | cancelar conforme transicao valida |
| GET | `/admin/inscricoes/elenco` | listar somente atletas aprovados |

Nao existem ainda rotas de grupos, partidas, escalacoes, cartoes, classificacao ou portal publico.
