# Plano de Implementacao do MVP

Legenda: `[ ]` pendente, `[~]` em andamento, `[x]` concluido com evidencia. Nenhuma etapa futura pode ser marcada antes de possuir fluxo real, validacao e teste.

| Etapa | Objetivo | Dependencias | Entregas | Criterios de aceite | Testes obrigatorios | Riscos |
|---|---|---|---|---|---|---|
| [x] 1. Fundacao tecnica | Criar base PHP executavel em subdiretorio | nenhuma | bootstrap, config, PDO, router, sessao, CSRF, logger, health, migrations base | `GET /health` responde sem stack trace; migration status funciona; base path preservado | unitarios, integracao, contrato HTTP, lint | extensoes PHP/PDO ausentes no cPanel |
| [ ] 2. Autenticacao e usuarios | Login, logout, recuperacao e perfis | fundacao | usuarios, sessoes e permissoes server-side | credenciais, sessao e escopos validados no servidor | unitarios, integracao, HTTP autenticado | IDOR e vazamento de sessao |
| [ ] 3. Campeonatos e regulamentos | Criar campeonato e regras configuraveis | autenticacao | campeonato, temporada, categoria, identidade e versoes de regulamento | regras editaveis antes da primeira partida e bloqueadas depois | dominio, integracao, HTTP | regras fixas em codigo |
| [ ] 4. Equipes, treinadores e comissao | Cadastrar equipes e equipe tecnica | campeonatos | equipes, vinculos, treinadores e comissao | contexto escolhido sem IDs digitados | dominio, integracao, HTTP | duplicidade e escopo |
| [ ] 5. Atletas, responsaveis e documentos | Cadastrar pessoas e documentos privados | equipes | atletas, responsaveis, posicoes e arquivos | privacidade, MIME, permissao e vinculo validados | dominio, upload, HTTP | LGPD e documentos de menores |
| [ ] 6. Inscricoes | Controlar envio, analise e aprovacao | atletas, regras | estados, pendencias, bloqueios e historico | somente inscrito aprovado pode ser escalado | dominio, integracao, HTTP | prazo e duplicidade |
| [ ] 7. Grupos, rodadas e tabela | Gerar calendario e classificacao inicial | equipes, regulamento | grupos, rodadas, locais, datas e confrontos | geracao idempotente e auditavel | dominio, integracao, HTTP | datas, folgas e W.O. |
| [ ] 8. Formacoes taticas e escalacoes | Escalar titulares, reservas e comissao | atletas, partidas | presets, campo, distribuicao automatica e ajuste manual | suspenso bloqueado; fora de posicao sinalizado | dominio, integracao, HTTP | UX do campo e regras de elenco |
| [ ] 9. Central da partida | Registrar partida sem cronologia obrigatoria | escalacao, agenda | placar, gols, assistencias, cartoes, substituicoes, arbitragem e ocorrencias | partida encerrada gera submissao para homologacao | dominio, integracao, HTTP | retificacao e consistencia do placar |
| [ ] 10. Cartoes e suspensoes | Aplicar disciplina configuravel | central, regulamento | acumulacao, vermelho, cumprimento e liberacao | regra por campeonato e bloqueio no proximo confronto | dominio, integracao, HTTP | limpeza por fase |
| [ ] 11. Classificacao e mata-mata | Calcular grupos e eliminatorias | resultados homologados | tabela, criterios, quartas, semis, final, penaltis, campeao e vice | recalculo reproduzivel conforme regulamento | dominio, integracao, HTTP | desempate com tres equipes |
| [ ] 12. Sumula | Gerar sumula baseada na planilha | partida homologada | dados, ocorrencias, assinaturas e PDF | mapeamento completo da planilha e versao rastreavel | integracao, PDF, HTTP | fidelidade visual e dados pessoais |
| [ ] 13. Noticias | Publicar noticias por campeonato | autenticacao, campeonato | rascunho, publicacao, despublicacao, capa e detalhe publico | somente publicada aparece no portal | dominio, HTTP | moderacao e uploads |
| [ ] 14. Vai e Vem | Registrar movimentacoes de atletas | atletas, equipes | solicitacao, aprovacao, historico e publicacao | vinculo anterior preservado e novo vinculo validado | dominio, HTTP | janela e limite |
| [ ] 15. Portal publico | Expor dados permitidos por slug | modulos publicados | home, jogos, tabela, mata-mata, equipes, noticias e Vai e Vem | nenhum dado privado ou rascunho exposto | HTTP, navegador | SEO e responsividade |
| [ ] 16. Preparacao para producao | Validar cPanel e operacao | fluxo MVP | instalacao limpa, backup, logs, headers e observabilidade | deploy reproduzivel sem segredos no repositorio | integracao, smoke, browser | ambiente divergente |
| [ ] 17. UI/UX definitiva | Refinar visual depois do fluxo estavel | todas as etapas anteriores | design system, identidade, temas, acessibilidade e mobile | aprovacao visual em breakpoints definidos | navegador, screenshot | polir antes de validar fluxo |

## Estado desta etapa

A etapa 1 foi concluida com lint, banco descartavel, migration/status e HTTP real. Nenhuma entidade esportiva ou modulo de negocio foi criado nesta rodada.
