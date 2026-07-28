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
