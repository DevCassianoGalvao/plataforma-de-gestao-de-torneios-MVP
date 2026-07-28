# Schema de Banco

## Migrations

| Migration | Conteudo | Estado |
|---|---|---|
| `0001_foundation.sql` | controle de migrations e health | implementada |
| `0002_authentication.sql` | usuarios, papeis, permissoes, tokens e auditoria | implementada |
| `0003_championships_and_regulations.sql` | catalogos, campeonatos, escopo e regulamentos | implementada |
| `0004_teams_staff_and_formations.sql` | equipes, responsaveis, comissao e formacoes taticas | implementada |

## Tabelas da Etapa 3

| Tabela | Finalidade |
|---|---|
| `seasons` | temporadas e status do catalogo |
| `categories` | categorias, slug, idades e regra de genero |
| `championships` | dados gerais, identidade, datas, status e visibilidade |
| `championship_user_assignments` | vinculo de usuario ao campeonato, sem duplicidade |
| `regulations` | versoes, autor, status e publicacao |
| `regulation_format_settings` | grupos, classificados, fases e formato |
| `regulation_points_settings` | pontuacao e W.O. |
| `regulation_tiebreakers` | criterios ordenados e habilitados |
| `regulation_discipline_settings` | amarelos, vermelhos e limpeza de cartoes |
| `regulation_match_settings` | duracao, substituicoes, prorrogacao e penaltis |
| `regulation_documents` | PDFs privados ligados a uma versao |

## Tabelas da Etapa 4

| Tabela | Finalidade |
|---|---|
| `teams` | equipes, identidade, status, campeonato e formacao padrao |
| `team_user_assignments` | vinculos historicos de gestor, treinador, auxiliar ou visualizador |
| `staff_roles` | catalogo de funcoes da comissao tecnica |
| `team_staff` | membros da comissao, com ou sem usuario do sistema |
| `tactical_formations` | catalogo das formacoes estruturadas |
| `tactical_formation_slots` | onze posicoes e coordenadas de cada formacao |

`teams` referencia `championships`, `users` e, opcionalmente, `tactical_formations`. Slug e nome sao unicos dentro do campeonato. `team_user_assignments` preserva datas e encerramento do vinculo, evitando duplicidade do mesmo historico. `team_staff` referencia uma funcao do catalogo e aceita `user_id` nulo.

As coordenadas de `tactical_formation_slots` usam `DECIMAL(5,2)` entre 0 e 100: `horizontal_position` cresce da esquerda para a direita e `vertical_position` cresce da defesa para o ataque. Elas servem para uma futura representacao de campo, sem armazenar atletas ou escalacoes nesta etapa.

## Regras

- slugs de categorias e campeonamentos sao unicos;
- um campeonato referencia temporada e categoria reais;
- um vinculo de usuario usa `championship_id`, `user_id` e `assignment_type` como chave de negocio;
- somente uma versao de regulamento pode ficar `published`, garantido pelo service em transacao;
- rascunhos, versoes superseded e versoes anteriores nao sao excluidos;
- uploads usam caminho privado e nome aleatorio;
- nenhuma regra e editada por JSON.
