# Schema de Banco

## Migrations

### Etapa 10

0010_discipline_and_suspensions.sql cria discipline_ledger, discipline_processing_runs, discipline_suspensions, discipline_suspension_fulfillments, discipline_card_resets e discipline_history, além de ampliar eventos da partida para comissão e anulação auditável.

| Migration | Conteudo | Estado |
|---|---|---|
| `0001_foundation.sql` | controle de migrations e health | implementada |
| `0002_authentication.sql` | usuarios, papeis, permissoes, tokens e auditoria | implementada |
| `0003_championships_and_regulations.sql` | catalogos, campeonatos, escopo e regulamentos | implementada |
| `0004_teams_staff_and_formations.sql` | equipes, responsaveis, comissao e formacoes taticas | implementada |
| `0005_athletes_guardians_and_documents.sql` | atletas, posicoes, responsaveis legais e documentos privados | implementada |
| `0006_registration_roster_settings.sql` | regras de elenco, documentos obrigatorios, inscricoes e historico | implementada |
| `0007_groups_rounds_schedule.sql` | locais, fases, grupos, rodadas, partidas, agenda e decisoes | implementada |
| `0008_tactical_lineups.sql` | escalacoes, titulares, reservas, comissao e historico | implementada |
| `0009_match_operation.sql` | operacao, eventos, substituicoes, arbitragem e homologacao | implementada |
| `0010_discipline_and_suspensions.sql` | ledger disciplinar, suspensoes, cumprimentos e historico | implementada |
| `0011_standings_and_knockout.sql` | snapshots de classificacao, calculos e chave eliminatoria | implementada |
| `0012_digital_match_reports.sql` | sumulas, versoes imutaveis, hashes e arquivos privados | implementada |
| `0013_news_blog.sql` | noticias, blog, estados editoriais, destaque e exclusao logica | implementada |

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

As coordenadas de `tactical_formation_slots` usam `DECIMAL(5,2)` entre 0 e 100: `horizontal_position` cresce da esquerda para a direita e `vertical_position` cresce da defesa para o ataque. A Etapa 8 usa essas coordenadas no campo funcional, sem alterar a formacao padrao da equipe.

## Tabelas da Etapa 5

| Tabela | Finalidade |
|---|---|
| `positions` | catalogo de 13 posicoes, grupo, ordem e status |
| `athletes` | cadastro esportivo, equipe, categoria derivada, status e exclusao logica |
| `athlete_secondary_positions` | varias posicoes alternativas por atleta |
| `legal_guardians` | dados privados cifrados de responsaveis legais |
| `athlete_guardians` | vinculo, parentesco, autorizacao e primariedade |
| `athlete_document_types` | tipos configuraveis de documento |
| `athlete_documents` | arquivo privado, validade, status e revisao |

`athletes.team_id` mantem o atleta dentro do escopo da equipe. `athletes.primary_position_id` e `athlete_secondary_positions.position_id` referenciam o catalogo ativo. Documentos podem apontar para um responsavel quando aplicavel; `reviewed_by` e `reviewed_at` preservam a analise.

## Tabelas da Etapa 6

| Tabela | Finalidade |
|---|---|
| `regulation_roster_settings` | tamanho minimo/maximo, goleiros minimos e inscricao por multiplas equipes |
| `regulation_required_documents` | documentos obrigatorios por versao de regulamento |
| `athlete_registrations` | inscricao do atleta no campeonato por equipe, status, analise e decisoes |
| `athlete_registration_history` | transicoes, correcoes, pendencias, decisoes e usuario responsavel |

`athlete_registrations` possui unicidade de campeonato, equipe e atleta. O elenco oficial e uma consulta de inscricoes com `status = approved`; nao existe tabela paralela nem limite fixado no codigo.

## Tabelas da Etapa 7

| Tabela | Finalidade |
|---|---|
| `venues` | locais vinculados ao campeonato, capacidade e status |
| `competition_phases` | fases, quantidade de grupos/equipes e publicacao |
| `competition_groups` | grupos, limites, classificados e bloqueio |
| `group_teams` | vinculo de equipe com grupo, posicao e retirada |
| `competition_rounds` | rodadas por fase/grupo e periodo |
| `matches` | confrontos, agenda, local e status sem placar nesta etapa |
| `match_schedule_changes` | historico de adiamentos, cancelamentos e alteracoes |
| `administrative_decisions` | decisoes administrativas ligadas ao calendario |

`matches.fixture_key` garante geracao idempotente. Nao ha colunas de placar, gols ou cartoes. O round-robin cria folga para grupos impares e pode gerar turno unico ou ida e volta.

## Tabelas da Etapa 8

| Tabela | Finalidade |
|---|---|
| `match_lineups` | uma escalacao por partida e equipe, status, formacao, capitao, goleiro e versao |
| `match_lineup_players` | titulares e reservas, slots, numero e alertas posicionais |
| `match_lineup_staff` | comissao presente vinculada a equipe |
| `match_lineup_history` | criacao, salvamento, confirmacao e reabertura com motivo |

`match_lineups` nao armazena placar, gols, cartoes ou classificacao. A confirmacao exige onze titulares, capitao titular e goleiro com posicao valida; alteracoes comuns ficam bloqueadas depois da confirmacao.

## Tabelas da Etapa 9

| Tabela | Finalidade |
|---|---|
| match_operations | estado operacional, horarios, resultado administrativo e finalizacao/homologacao |
| match_operator_assignments | atribuicao explicita de operador por partida |
| match_officials | arbitragem e demais funcoes da sumula |
| match_operation_events | gols, gols contra, assistencias, cartoes, ocorrencias e penaltis |
| match_substitutions | atleta que sai, atleta que entra, janela, periodo e minuto |
| match_operation_history | transicoes de operacao e homologacao |

O placar normal e uma consulta sobre eventos validos de gol e gol contra. Penaltis ficam em colunas de consulta separadas. Resultado administrativo, quando informado, substitui o placar calculado e preserva motivo, usuario e horario.

## Tabelas da Etapa 11

| Tabela | Finalidade |
|---|---|
| `competition_standings` | snapshot atual por equipe e grupo, com estatisticas, posicao e criterio separador |
| `standings_calculation_runs` | hash da fonte de cada recalculo transacional |
| `knockout_rounds` | quartas, semifinais e final por fase eliminatoria |
| `knockout_ties` | cruzamentos, equipes, partida, vencedor, vice e criterio de decisao |
| `competition_results` | campeao e vice homologados da fase |

Classificacao consulta somente partidas homologadas e gols/eventos validos ou resultado administrativo. Penaltis ficam fora dos gols da classificacao e apenas decidem a tie eliminatoria.

## Tabelas da Etapa 12

| Tabela | Finalidade |
|---|---|
| `match_reports` | ponte da partida homologada para versao atual e homologacao |
| `match_report_versions` | historico imutavel, hash, codigo, HTML e PDF privado |

O PDF nao fica em coluna binaria. `storage_path` aponta para armazenamento privado fora de `public`; a aplicacao controla download por partida e versao.

## Tabelas da Etapa 13

| Tabela | Finalidade |
|---|---|
| `news_articles` | noticias por campeonato, autor, conteudo, capa, status, publicacao, destaque e exclusao logica |

Capas sao reprocessadas para WebP e ficam fora de `public`. O portal consulta apenas campeonatos publicos e noticias publicadas cuja data ja chegou.

## Tabelas da Etapa 14

| Tabela | Finalidade |
|---|---|
| `regulation_transfer_settings` | janela e limite opcional de movimentacoes por regulamento |
| `transfer_movements` | registro editorial de atleta, equipes, tipo, data, publicacao e notas privadas |
| `transfer_movement_history` | transicoes, decisoes, motivos e usuario responsavel |

Vai e Vem nao altera automaticamente `athletes.team_id`. A movimentacao publicada e um registro publico separado do vinculo oficial.

## Regras

- slugs de categorias e campeonamentos sao unicos;
- um campeonato referencia temporada e categoria reais;
- um vinculo de usuario usa `championship_id`, `user_id` e `assignment_type` como chave de negocio;
- somente uma versao de regulamento pode ficar `published`, garantido pelo service em transacao;
- rascunhos, versoes superseded e versoes anteriores nao sao excluidos;
- uploads usam caminho privado e nome aleatorio;
- nenhuma regra e editada por JSON.
## Migrations 0036 a 0038

| Migration | Conteúdo |
|---|---|
| `0036_accountability_completion.sql` | configuração documental por campeonato, assinatura de súmula e logs completos de exportação |
| `0037_advanced_rectification.sql` | campos de correção, histórico de diferenças e segunda aprovação |
| `0038_retention_policies.sql` | políticas centrais e histórico de arquivamento/restauração |

Tabelas novas: `championship_accountability_settings`, `match_rectification_changes`, `championship_rectification_settings`, `retention_policies` e `retention_actions`. Nenhuma delas mistura simulações com registros oficiais.

As migrations `0048_event_day_evidence.sql` e `0049_round_labels.sql` adicionam evidências por dia de evento e rótulos administrativos de rodada. São alterações aditivas; dias, locais e cidades reais continuam sendo configurados pelo administrador.

# Read model publico

A Etapa 15 nao cria migration ou tabela nova. `PublicPortalRepository` compoe o portal a partir de `championships`, fases, grupos, partidas, equipes, elenco aprovado, classificacao, mata-mata, eventos, disciplina, noticias e transferencias existentes. As consultas usam listas explicitas de colunas e filtros de visibilidade/status para manter a fronteira de privacidade.
