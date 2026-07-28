# Schema de Banco

## Migrations

| Migration | Conteudo | Estado |
|---|---|---|
| `0001_foundation.sql` | controle de migrations e health | implementada |
| `0002_authentication.sql` | usuarios, papeis, permissoes, tokens e auditoria | implementada |
| `0003_championships_and_regulations.sql` | catalogos, campeonatos, escopo e regulamentos | implementada |

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

## Regras

- slugs de categorias e campeonamentos sao unicos;
- um campeonato referencia temporada e categoria reais;
- um vinculo de usuario usa `championship_id`, `user_id` e `assignment_type` como chave de negocio;
- somente uma versao de regulamento pode ficar `published`, garantido pelo service em transacao;
- rascunhos, versoes superseded e versoes anteriores nao sao excluidos;
- uploads usam caminho privado e nome aleatorio;
- nenhuma regra e editada por JSON.
