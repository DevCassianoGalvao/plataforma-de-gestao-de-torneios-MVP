# Plano de Implementação do MVP

Legenda: `[ ]` pendente, `[x]` concluído com evidência. Nenhuma etapa futura deve ser marcada apenas por rota vazia ou tela estática.

| Etapa | Objetivo | Estado | Evidência |
|---|---|---|---|
| 1 | Fundação técnica | [x] | bootstrap, PDO, router, health, migration base, lint e HTTP |
| 2 | Autenticação e acesso | [x] | login, sessão, recuperação, perfis, permissões, usuários, auditoria, seed e testes |
| 3 | Campeonatos e regulamentos | [x] | catalogos, campeonato, identidade, escopo, editor estruturado, preset, versoes, uploads e testes |
| 4 | Equipes e comissão | [x] | equipes, vínculos autorizados, comissão, uploads, status, formações e testes |
| 5 | Atletas e documentos | [x] | cadastro, privacidade, posições, responsáveis e arquivos privados |
| 6 | Inscrições e elenco oficial | [x] | envio, análise, correções, aprovação e roster aprovado |
| 7 | Grupos, rodadas e tabela | [x] | grupos, locais, round-robin, agenda e próximos confrontos |
| 8 | Formações e escalações | [x] | campo funcional, distribuição automática, titulares, reservas e confirmação |
| 9 | Central da partida | [x] | registros, placar derivado, arbitragem, finalização e homologação básica |
| 10 | Disciplina | [x] | cartões, suspensões e próximos confrontos |
| 11 | Classificação e mata-mata | [x] | critérios, cruzamentos, campeão e vice |
| 12 | Súmula | [x] | preview HTML, PDF A4, versoes e pacotes conforme planilha |
| 13 | Notícias | [x] | rascunho, agendamento e publicação |
| 14 | Vai e Vem | [x] | movimentações, publicação e histórico |
| 15 | Portal público | [x] | portal completo por slug, read model público, SEO e privacidade |
| 16 | Preparação para produção | [x] | instalação limpa, hardening, backup, cPanel e auditoria |
| 17 | UI/UX definitiva | [x] | design system, temas, responsividade e acessibilidade |

## Estado da Etapa 3

Implementada em `feat/championships-and-regulations`. Equipes, partidas, portal e demais módulos esportivos continuam fora desta etapa.

## Estado da Etapa 4

Implementada em `feat/teams-and-staff`, a partir do commit `1520839` da Etapa 3. A etapa entrega cadastro e escopo de equipes, responsáveis, comissão técnica, identidade, status, nove formações táticas com slots estruturados, uploads privados e auditoria. Atletas, inscrições, partidas e o campo visual definitivo continuam fora do escopo.

Evidências: migration `0004_teams_staff_and_formations.sql`, seed idempotente, `TEAM_TESTS_OK unit=4 integration=4 http=4`, `REAL_HTTP_TESTS_OK checks=11` e `LINT_OK files=114`.

## Estado da Etapa 5

Implementada em `feat/athletes-and-documents`, partindo de `feat/teams-and-staff`. A etapa entrega cadastro independente de inscrição, catálogo de posições, responsáveis legais para menores, documentos privados com revisão, escopo por equipe/campeonato, exclusão lógica, auditoria e seed idempotente para 10 equipes.

Evidências: migration `0005_athletes_guardians_and_documents.sql`, `MVP_TESTS_OK unit=5 integration=5 http=5`, `REAL_HTTP_TESTS_OK checks=16` e `LINT_OK files=136`.

## Estado da Etapa 6

Implementada em `feat/registrations-and-rosters`, partindo de `feat/athletes-and-documents`. A etapa entrega inscrições por campeonato, equipe e atleta, fluxo com histórico, validações do regulamento, pendências, correção, elenco oficial formado somente por aprovados, escopo por perfil, auditoria e seed idempotente.

Evidências: migration `0006_registration_roster_settings.sql`, `docs/REGISTRATIONS_AND_ROSTERS.md`, testes unitários, de integração e HTTP, lint PHP, banco descartável e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 7

Implementada em `feat/groups-rounds-and-schedule`, partindo de `feat/registrations-and-rosters`. A etapa entrega locais, fases, grupos com distribuição e bloqueio após início, rodadas, partidas sem placar, agenda com histórico, decisões administrativas, próximos confrontos e assistente round-robin de turno único ou ida e volta. O preset possui Grupo A e Grupo B com cinco equipes e quatro classificados por grupo.

Evidências: migration `0007_groups_rounds_schedule.sql`, `docs/GROUPS_ROUNDS_AND_SCHEDULE.md`, seed idempotente, `MVP_TESTS_OK unit=7 integration=7 http=7`, `REAL_HTTP_TESTS_OK checks=23`, `LINT_OK files=165` e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 8

Implementada em `feat/tactical-lineups`, partindo de `feat/groups-rounds-and-schedule`. A etapa entrega escalação por partida e equipe, formação escolhida, titulares, reservas, slots coordenados, capitão, goleiro, comissão presente, rascunho, confirmação, reabertura autorizada, histórico mínimo, distribuição automática por compatibilidade posicional e campo funcional responsivo com controles por seleção.

Não foram implementados gols, operação da partida, cartões, classificação ou portal público.

Evidências: migration `0008_tactical_lineups.sql`, `docs/TACTICAL_LINEUPS.md`, `MVP_TESTS_OK unit=8 integration=8 http=8`, lint PHP e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 9

Implementada em `feat/match-operation-center`, partindo de `feat/tactical-lineups`. A etapa entrega central operacional própria, registros de gols, gols contra, assistências, cartões, ocorrências, substituições, pênaltis separados, horários, arbitragem, placar calculado ou administrativo, checklist, finalização pelo operador e homologação separada pelo organizador.

Não foram implementados acumulação completa de suspensões, retificação avançada, notícias, Vai e Vem ou portal público.

Evidências: migration `0009_match_operation.sql`, `docs/MATCH_OPERATION.md`, `MVP_TESTS_OK unit=9 integration=9 http=9`, lint PHP, banco descartável e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 10

Implementada nesta branch: disciplina, ledger de cartões, acumulação configurável, pendurados, suspensões automáticas/manuais, cumprimento idempotente, limpeza por fase, escopo e bloqueio de atleta suspenso na escalação. Classificação definitiva, mata-mata e retificação avançada permanecem fora do escopo desta etapa.

Evidências: migration `0010_discipline_and_suspensions.sql`, `docs/DISCIPLINE_AND_SUSPENSIONS.md`, `MVP_TESTS_OK unit=10 integration=10 http=10` e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 11

Implementada em `feat/standings-and-knockout`, partindo de `feat/discipline-and-suspensions`. A etapa entrega snapshots transacionais de classificação por grupo usando partidas homologadas, pontuação e desempates configuráveis, mini-tabela de confronto direto, registro do critério separador, chave de quartas, semifinais e final, avanços após homologação, pênaltis e resultados administrativos, campeão, vice, escopo e CSRF.

Evidências: migration `0011_standings_and_knockout.sql`, `docs/STANDINGS_AND_KNOCKOUT.md`, `MVP_TESTS_OK unit=11 integration=11 http=11`, `LINT_OK files=206` e banco descartável com `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 12

Implementada em `feat/digital-match-report`, partindo de `feat/standings-and-knockout`. A etapa entrega súmula HTML e PDF A4 baseado na planilha enviada, relação das duas equipes, atletas, números, titulares, reservas, cartões, gols, placar, horários, arbitragem, mesário, pênaltis, ocorrências, confirmações, página de verso, código de verificação, armazenamento privado, histórico imutável, downloads autorizados e pacotes ZIP.

Evidências: migration `0012_digital_match_reports.sql`, `docs/DIGITAL_MATCH_REPORT.md`, `MVP_TESTS_OK unit=12 integration=12 http=12`, `LINT_OK files=216` e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 13

Implementada em `feat/news-blog`, partindo de `feat/digital-match-report`. A etapa entrega notícias por campeonato, CRUD editorial, rascunho, agendamento, publicação, despublicação, destaque, prévia, exclusão lógica, capas otimizadas, portal público paginado, busca, recentes, escopo para comunicação/organizador, XSS escapado e CSRF.

Evidências: migration `0013_news_blog.sql`, `docs/NEWS_BLOG.md`, `MVP_TESTS_OK unit=13 integration=13 http=13`, `LINT_OK files=230` e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 14

Implementada em `feat/transfers-market`, partindo de `feat/news-blog`. A etapa entrega fluxo assistido de movimentações, tipos configuráveis no domínio, janela e limite opcionais, escopo por campeonato atribuido, histórico, publicação no Vai e Vem, filtros, privacidade de notas internas e seed idempotente.

O registro permanece editorial/administrativo e não altera automaticamente `athletes.team_id`; aplicação do vínculo oficial exige decisão explícita em etapa posterior.

Evidências: migration `0014_transfers_market.sql`, `docs/TRANSFERS_MARKET.md`, `MVP_TESTS_OK unit=14 integration=14 http=14`, lint PHP e `APP_BASE_PATH=/torneio-online`.

## Estado da Etapa 15

Implementada em `feat/public-portal`, partindo de `feat/transfers-market`. A etapa entrega home e páginas públicas por campeonato para agenda, resultados, partida, classificação, grupos, mata-mata, equipes, atletas, rankings, notícias, Vai e Vem, regulamento e campeão/vice. A identidade pública usa logo, cores, banner, favicon, imagem social e tema básico cadastrados no campeonato.

O read model público seleciona somente dados esportivos e editoriais publicados. Documentos, dados pessoais, responsáveis, observações privadas e arquivos privados permanecem fora das consultas e views públicas. O portal inclui canonical, Open Graph, Twitter card, sitemap, robots, 404, isolamento por slug e funcionamento com `APP_BASE_PATH=/torneio-online`.

Evidências: `docs/PUBLIC_PORTAL.md`, `MVP_TESTS_OK unit=15 integration=15 http=15`, lint PHP, banco descartável e testes de privacidade/SEO.

## Estado da Etapa 16

Implementada em `feat/production-readiness`, partindo de `feat/public-portal`. A etapa entrega instalador limpo com criação de banco, migrations, seed opcional e smoke HTTP; hardening de headers, sessão, CSRF, open redirect e storage; `.htaccess` para cPanel; scripts de backup, verificação, rotação e restauração; documentação operacional e auditoria final.

O veredito e `APROVADO PARA HOMOLOGACAO`. Aprovação para produção depende de executar no cPanel real HTTPS, SMTP, cron, backup externo e restauração.

## Estado da Etapa 17

Implementada em `feat/final-ui-ux`, partindo de `feat/production-readiness`. A etapa aplica a referência visual do projeto privado `Football Management Login Interface`, do Google Stitch, sem importar código proprietário ou alterar a stack PHP/HTML/CSS/JavaScript.

O produto agora possui tokens visuais centralizados, Hanken Grotesk para títulos e números, Inter para textos e formulários, shell administrativo, login, portal público, campo tático, central da partida, súmula, notícias, temas claro/escuro, foco visivel, drawer mobile, navegação responsiva e proteções basicas contra overflow. O dashboard administrativo passou a exibir métricas reais do banco em vez de números demonstrativos.

Evidências e decisões visuais: `docs/STITCH_DESIGN_REFERENCE.md`, `docs/UI_DESIGN_SYSTEM.md` e `docs/UI_UX_FINAL_AUDIT.md`. A UI/UX foi aprovada para homologação; a aprovação para produção continua condicionada a validação operacional no ambiente de destino.
