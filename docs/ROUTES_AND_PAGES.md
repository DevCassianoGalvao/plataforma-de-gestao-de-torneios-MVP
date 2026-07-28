# Rotas e Paginas

## Rotas tecnicas implementadas

| Metodo | Rota | Funcao | Estado |
|---|---|---|---|
| GET | `/` | Mensagem da fundacao | implementada |
| GET | `/health` | Health JSON da aplicacao e persistencia | implementada |
| GET | `/login` | Placeholder sem autenticacao | implementada |
| POST | `/login` | Placeholder protegido por CSRF | retorna 501 |
| qualquer | demais rotas | 404 sem stack trace | implementada |

Todas as rotas respeitam `APP_BASE_PATH`, por exemplo `/copa-online/health`.

## Rotas futuras

Rotas de campeonato, equipes, atletas, inscricoes, partidas, noticias, Vai e Vem e portal so serao adicionadas nas etapas correspondentes do plano. Nenhuma rota vazia deve ser criada para aparentar implementacao.

## Convencoes

- Controllers nao calculam regra esportiva.
- Views nao recebem IDs sensiveis por texto livre.
- Endpoints de mutacao exigem CSRF e permissao server-side quando existirem.
- Respostas de erro usam status HTTP correto e mensagem publica generica.
