# Rotas e paginas

## Convencoes

- `APP_BASE_PATH` e removido do caminho antes do dispatch. A mesma aplicacao funciona na raiz ou em subdiretorio cPanel.
- URLs administrativas exigem sessao e permissao resolvida a partir do recurso persistido.
- URLs publicas usam slug do campeonato; dados provisoriais, privados ou sensiveis ficam fora do presenter publico.
- IDs existem apenas como parte interna de URLs e selects. Nenhum formulario pede que a pessoa digite ID.

## Autenticacao

| Metodo | Rota | Pagina/acao | Perfil |
|---|---|---|---|
| GET/POST | `/login` | entrada e autenticacao | publico |
| GET | `/logout` | encerramento de sessao | autenticado |
| GET/POST | `/senha/esqueci` | pedido de redefinicao | publico |
| GET/POST | `/senha/redefinir/{token}` | redefinicao | publico com token |

## Painel administrativo

| Rota | Funcao | Estado |
|---|---|---|
| `/admin` | landing por perfil | funcional |
| `/admin/visao-geral` | dashboard global | funcional |
| `/admin/campeonatos/{slug}` | dashboard do campeonato | funcional |
| `/admin/campeonatos/{slug}/configuracoes` | identidade, temporada e regras amigaveis | parcial, sem todos os campos do PRD |
| `/admin/campeonatos/{slug}/equipes` | equipes do campeonato | funcional assistido |
| `/admin/campeonatos/{slug}/equipe/{resource}` | detalhe de equipe | estrutura de navegacao |
| `/admin/campeonatos/{slug}/atletas` | atletas e inscricoes | funcional assistido |
| `/admin/campeonatos/{slug}/atleta/{resource}` | detalhe do atleta | estrutura de navegacao |
| `/admin/campeonatos/{slug}/comissao` | comissao tecnica | funcional assistido |
| `/admin/campeonatos/{slug}/responsaveis` | responsaveis legais | funcional assistido |
| `/admin/campeonatos/{slug}/inscricoes` | analise e aprovacao | funcional assistido |
| `/admin/campeonatos/{slug}/grupos` | grupos e distribuicao | funcional |
| `/admin/campeonatos/{slug}/rodadas` | rodadas e agenda | funcional parcial |
| `/admin/campeonatos/{slug}/partidas` | lista de partidas | funcional |
| `/admin/partidas/{match}` | detalhe de partida | funcional e protegido |
| `/admin/partidas/{match}/operar` | central da partida | funcional parcial |
| `/admin/campeonatos/{slug}/escalacoes` | escalações e validacoes | persistencia funcional; campo visual final pendente |
| `/admin/campeonatos/{slug}/homologacoes` | resultados aguardando homologacao | estrutura; fluxo legado cobre homologacao |
| `/admin/campeonatos/{slug}/classificacao` | tabela por grupo | funcional |
| `/admin/campeonatos/{slug}/mata-mata` | fases eliminatorias | funcional parcial; cruzamentos basicos |
| `/admin/campeonatos/{slug}/sumulas` | sumulas e downloads privados | HTML/PDF estrutural parcial |
| `/admin/campeonatos/{slug}/noticias` | noticias | persistencia/publicacao parcial |
| `/admin/campeonatos/{slug}/transferencias` | Vai e Vem | persistencia/publicacao parcial |
| `/admin/access` | perfis, permissoes e auditoria | superadmin |
| `/admin/documents/upload` | upload privado/publico validado | funcional |

## Portal publico

| Rota | Funcao | Regra |
|---|---|---|
| `/campeonatos/{slug}` | home do campeonato | somente campeonato publicado/ativo |
| `/campeonatos/{slug}/jogos` | jogos e proximos confrontos | dados publicos |
| `/campeonatos/{slug}/jogo/{id}` | detalhe do jogo | resultado homologado quando oficial |
| `/campeonatos/{slug}/classificacao` | classificacao | calculada a partir de resultados validos |
| `/campeonatos/{slug}/grupos` | grupos | equipes publicadas |
| `/campeonatos/{slug}/mata-mata` | chaveamento | fase publicada |
| `/campeonatos/{slug}/equipes` | lista de equipes | dados publicos |
| `/campeonatos/{slug}/equipe/{id}` | equipe | dados publicos |
| `/campeonatos/{slug}/atletas` | atletas | apenas nome esportivo e dados publicos |
| `/campeonatos/{slug}/atleta/{id}` | atleta | sem documentos ou contato |
| `/campeonatos/{slug}/noticias` | noticias | publicadas |
| `/campeonatos/{slug}/noticia/{id}` | noticia | publicada e dentro do slug |
| `/campeonatos/{slug}/vai-e-vem` | transferencias | aprovadas e publicadas |
| `/campeonatos/{slug}/regulamento` | regulamento | versao publicada |
| `/campeonatos/{slug}/campeoes` | campeao e vice | dados homologados |
| `/sitemap.xml` | sitemap publico | slugs publicados |
| `/robots.txt` | politica de crawling | sem dados privados |

## Rotas de compatibilidade

`/admin/tournaments/{id}/operation` e suas acoes permanecem durante a migracao do fluxo legado. Exigem superadmin. Novas telas devem usar contexto por slug e endpoints assistidos; a rota legada nao deve ser ampliada.
