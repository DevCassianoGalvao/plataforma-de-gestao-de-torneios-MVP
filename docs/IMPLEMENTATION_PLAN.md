# Plano de Implementacao do MVP

Legenda: `[ ]` pendente, `[x]` concluido com evidencia. Nenhuma etapa futura deve ser marcada apenas por rota vazia ou tela estatica.

| Etapa | Objetivo | Estado | Evidencia |
|---|---|---|---|
| 1 | Fundacao tecnica | [x] | bootstrap, PDO, router, health, migration base, lint e HTTP |
| 2 | Autenticacao e acesso | [x] | migration 0002, login, sessao, recuperacao, perfis, permissoes, usuarios, auditoria, seed e testes |
| 3 | Campeonatos e regulamentos | [ ] | criar campeonato e regras sem JSON |
| 4 | Equipes e comissao | [ ] | equipes e vinculos autorizados |
| 5 | Atletas e documentos | [ ] | privacidade, posicoes e arquivos privados |
| 6 | Inscricoes | [ ] | envio, analise e aprovacao |
| 7 | Grupos, rodadas e tabela | [ ] | calendario e classificacao inicial |
| 8 | Formacoes e escalacoes | [ ] | campo visual e distribuicao automatica |
| 9 | Central da partida | [ ] | placar, gols, cartoes e ocorrencias |
| 10 | Disciplina | [ ] | cartoes, suspensoes e proximos confrontos |
| 11 | Classificacao e mata-mata | [ ] | criterios, cruzamentos e campeao |
| 12 | Sumula | [ ] | digital e PDF conforme planilha |
| 13 | Noticias | [ ] | rascunho e publicacao |
| 14 | Vai e Vem | [ ] | movimentacoes e historico |
| 15 | Portal publico | [ ] | dados publicados por slug |
| 16 | Preparacao para producao | [ ] | cPanel, instalacao limpa e observabilidade |
| 17 | UI/UX definitiva | [ ] | design system, temas, responsividade e acessibilidade |

## Estado da Etapa 2

Implementada em `feat/authentication-and-access`. O isolamento por campeonato/equipe, os modulos esportivos e o layout definitivo continuam pendentes.
