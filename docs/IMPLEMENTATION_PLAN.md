# Plano de Implementacao do MVP

Legenda: `[ ]` pendente, `[x]` concluido com evidencia. Nenhuma etapa futura deve ser marcada apenas por rota vazia ou tela estatica.

| Etapa | Objetivo | Estado | Evidencia |
|---|---|---|---|
| 1 | Fundacao tecnica | [x] | bootstrap, PDO, router, health, migration base, lint e HTTP |
| 2 | Autenticacao e acesso | [x] | login, sessao, recuperacao, perfis, permissoes, usuarios, auditoria, seed e testes |
| 3 | Campeonatos e regulamentos | [x] | catalogos, campeonato, identidade, escopo, editor estruturado, preset, versoes, uploads e testes |
| 4 | Equipes e comissao | [x] | equipes, vinculos autorizados, comissao, uploads, status, formacoes e testes |
| 5 | Atletas e documentos | [x] | cadastro, privacidade, posicoes, responsaveis e arquivos privados |
| 6 | Inscricoes e elenco oficial | [x] | envio, analise, correcoes, aprovacao e roster aprovado |
| 7 | Grupos, rodadas e tabela | [x] | grupos, locais, round-robin, agenda e proximos confrontos |
| 8 | Formacoes e escalacoes | [x] | campo funcional, distribuicao automatica, titulares, reservas e confirmacao |
| 9 | Central da partida | [x] | registros, placar derivado, arbitragem, finalizacao e homologacao basica |
| 10 | Disciplina | [x] | cartoes, suspensoes e proximos confrontos |
| 11 | Classificacao e mata-mata | [x] | criterios, cruzamentos, campeao e vice |
| 12 | Sumula | [ ] | digital e PDF conforme planilha |
| 13 | Noticias | [ ] | rascunho e publicacao |
| 14 | Vai e Vem | [ ] | movimentacoes e historico |
| 15 | Portal publico | [ ] | dados publicados por slug |
| 16 | Preparacao para producao | [ ] | cPanel, instalacao limpa e observabilidade |
| 17 | UI/UX definitiva | [ ] | design system, temas, responsividade e acessibilidade |

## Estado da Etapa 4

Implementada em `feat/teams-and-staff`, a partir do commit `1520839` da Etapa 3. A etapa entrega cadastro e escopo de equipes, responsaveis, comissao tecnica, identidade, status, nove formacoes taticas com slots estruturados, uploads privados e auditoria. Atletas, inscricoes, partidas e o campo visual definitivo continuam fora do escopo.

Evidencias: migration `0004_teams_staff_and_formations.sql`, seed idempotente, `TEAM_TESTS_OK unit=4 integration=4 http=4`, `REAL_HTTP_TESTS_OK checks=11` e `LINT_OK files=114`.

## Estado da Etapa 5

Implementada em `feat/athletes-and-documents`, partindo de `feat/teams-and-staff`. A etapa entrega cadastro independente de inscricao, catalogo de posicoes, responsaveis legais para menores, documentos privados com revisao, escopo por equipe/campeonato, exclusao logica, auditoria e seed idempotente para 10 equipes.

Evidencias: migration `0005_athletes_guardians_and_documents.sql`, `MVP_TESTS_OK unit=5 integration=5 http=5`, `REAL_HTTP_TESTS_OK checks=16` e `LINT_OK files=136`.

## Estado da Etapa 6

Implementada em `feat/registrations-and-rosters`, partindo de `feat/athletes-and-documents`. A etapa entrega inscricoes por campeonato, equipe e atleta, fluxo com historico, validacoes do regulamento, pendencias, correcao, elenco oficial formado somente por aprovados, escopo por perfil, auditoria e seed idempotente.

Evidencias: migration `0006_registration_roster_settings.sql`, `docs/REGISTRATIONS_AND_ROSTERS.md`, testes unitarios, de integracao e HTTP, lint PHP, banco descartavel e `APP_BASE_PATH=/copa-online`.

## Estado da Etapa 7

Implementada em `feat/groups-rounds-and-schedule`, partindo de `feat/registrations-and-rosters`. A etapa entrega locais, fases, grupos com distribuicao e bloqueio apos inicio, rodadas, partidas sem placar, agenda com historico, decisoes administrativas, proximos confrontos e assistente round-robin de turno unico ou ida e volta. O preset possui Grupo A e Grupo B com cinco equipes e quatro classificados por grupo.

Evidencias: migration `0007_groups_rounds_schedule.sql`, `docs/GROUPS_ROUNDS_AND_SCHEDULE.md`, seed idempotente, `MVP_TESTS_OK unit=7 integration=7 http=7`, `REAL_HTTP_TESTS_OK checks=23`, `LINT_OK files=165` e `APP_BASE_PATH=/copa-online`.

## Estado da Etapa 8

Implementada em `feat/tactical-lineups`, partindo de `feat/groups-rounds-and-schedule`. A etapa entrega escalacao por partida e equipe, formacao escolhida, titulares, reservas, slots coordenados, capitao, goleiro, comissao presente, rascunho, confirmacao, reabertura autorizada, historico minimo, distribuicao automatica por compatibilidade posicional e campo funcional responsivo com controles por selecao.

Nao foram implementados gols, operacao da partida, cartoes, classificacao ou portal publico.

Evidencias: migration `0008_tactical_lineups.sql`, `docs/TACTICAL_LINEUPS.md`, `MVP_TESTS_OK unit=8 integration=8 http=8`, lint PHP e `APP_BASE_PATH=/copa-online`.

## Estado da Etapa 9

## Estado da Etapa 10

Implementada nesta branch: disciplina, ledger de cartoes, acumulacao configuravel, pendurados, suspensoes automaticas/manuais, cumprimento idempotente, limpeza por fase, escopo e bloqueio de atleta suspenso na escalacao. Classificacao definitiva, mata-mata e retificacao avancada permanecem fora do escopo desta etapa.

Implementada em feat/match-operation-center, partindo de feat/tactical-lineups. A etapa entrega central operacional propria, registros de gols, gols contra, assistencias, cartoes, ocorrencias, substituicoes, penaltis separados, horarios, arbitragem, placar calculado ou administrativo, checklist, finalizacao pelo operador e homologacao separada pelo organizador.

Nao foram implementados acumulacao completa de suspensoes, classificacao definitiva, retificacao avancada, sumula PDF, noticias, Vai e Vem ou portal publico.

Evidencias: migration 0009_match_operation.sql, docs/MATCH_OPERATION.md, MVP_TESTS_OK unit=9 integration=9 http=9, lint PHP, banco descartavel e APP_BASE_PATH=/copa-online.

## Estado da Etapa 11

Implementada em `feat/standings-and-knockout`, partindo de `feat/discipline-and-suspensions`. A etapa entrega snapshots transacionais de classificacao por grupo usando partidas homologadas, pontuacao e desempates configuraveis, mini-tabela de confronto direto, registro do criterio separador, chave de quartas, semifinais e final, avancos apos homologacao, penaltis e resultados administrativos, campeao, vice, escopo e CSRF.

Evidencias: migration `0011_standings_and_knockout.sql`, `docs/STANDINGS_AND_KNOCKOUT.md`, `MVP_TESTS_OK unit=11 integration=11 http=11`, `LINT_OK files=206` e banco descartavel com `APP_BASE_PATH=/copa-online`.

## Estado da Etapa 3

Implementada em `feat/championships-and-regulations`. Equipes, partidas, portal e demais modulos esportivos continuam fora desta etapa.
