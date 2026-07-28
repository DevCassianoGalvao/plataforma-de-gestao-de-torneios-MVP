# Plano Executavel do Sistema

## Rodada estrutural solicitada

Legenda: `[x]` = comportamento e teste comprovados; `[~]` = estrutura ou parte do fluxo comprovada; `[ ]` = pendente. Nenhuma tabela, rota vazia ou texto estatico fecha uma etapa.

| Etapa | Objetivo | Dependencias | Criterio de aceite | Teste necessario | Riscos |
|---|---|---|---|---|---|
| [x] 1. Fundacao tecnica | PHP 8.2, PDO, front controller, config, logs, storage, migrations | nenhuma | instala em subdiretorio e lint passa | lint + clean install | cPanel sem extensao PDO |
| [x] 2. Autenticacao e permissoes | sessao segura, CSRF, roles e escopos server-side | fundacao | cada perfil so alcanca seu escopo | authorization + HTTP | IDOR e seed de permissoes |
| [~] 3. Campeonatos e regulamentos | CRUD assistido, versoes e regras configuraveis | auth | criar/editar sem JSON e bloquear regra sensivel apos partida | service + HTTP | regra ainda tem campos nao expostos |
| [~] 4. Equipes, treinadores e comissao | vinculos por campeonato e equipe | campeonato | cadastro sem IDs digitados e escopo validado | service + HTTP | dados de equipe duplicados |
| [~] 5. Atletas, responsaveis e documentos | cadastro privado, documentos e situacao | equipes | atleta aprovado tem vinculo e documento controlado | integration + HTTP | LGPD e uploads de menores |
| [x] 6. Inscricoes | estados e aprovacao de atletas | atletas + regras | somente aprovado pode ser escalado | service | duplicidade e prazo |
| [x] 7. Grupos, rodadas e tabela | gerar confrontos idempotentes | equipes + regulamento | repetir geracao nao duplica partidas | service | folgas, turno/returno e datas |
| [~] 8. Formacoes taticas e escalacoes | posicoes, titulares, reservas e incompatibilidade | atletas + partidas | distribuicao valida e ajuste manual fora de posicao | unitario + service + UI | ainda sem campo visual final |
| [x] 9. Central da partida | placar, eventos, arbitragem e ocorrencias | escalacoes | finalizar cria relatorio e envia homologacao | service + HTTP | placar divergente de gols |
| [x] 10. Cartoes e suspensoes | ledger, acumulacao e cumprimento | homologacao + regras | suspenso bloqueia proxima escalação e depois e liberado | sports rules | limpeza por fase e casos especiais |
| [x] 11. Classificacao | tabela por grupo e desempate | resultados homologados | recalculo reproduzivel e ordenado pelo regulamento | tournament + unitario | mini-tabela de tres equipes |
| [~] 12. Mata-mata | quartas, semis, final, penalti, campeao | classificacao + regras | cruzamentos vem do regulamento e avancam apos homologacao | service | cruzamentos incompletos |
| [~] 13. Sumula | mapping, HTML e PDF versionado | partida + eventos | PDF comporta duas equipes, ocorrencias e versao | service + PDF | fidelidade visual da planilha |
| [~] 14. Noticias | rascunho, publicar, despublicar e portal | auth + campeonato | somente publicada aparece no slug publico | HTTP + presenter | editor e upload de capa |
| [~] 15. Vai e Vem | solicitar, aprovar, preservar historico e publicar | atleta + equipes | transferencia aprovada atualiza vinculo sem apagar historico | service + HTTP | janela e limite ainda incompletos |
| [~] 16. Rotas e paginas | separar painel e portal por tarefa | todos os modulos | paginas nao viram CRUD generico e protegem dados | HTTP + interface | telas legadas ainda coexistem |
| [x] 17. Seed de demonstracao | dados ficticios, perfis e campeonato completo | migrations | `database/seed.php --demo` idempotente e sem dados reais | integration + seed | volume alto torna seed lento |
| [~] 18. Testes e producao | suite, banco descartavel e checklist cPanel | todas as etapas | lint, migration, seed e testes passam; pendencias declaradas | suite completa | HTTP/browser depende de servidor |
| [ ] 19. Futura rodada UI/UX | design system, campo final, PDF fiel e refinamento | fluxo estavel | aprovacao visual em desktop/mobile | browser + screenshot | nao antecipar acabamento |

## UI/UX foundation - 27/07/2026

- [x] Tokens, temas light/dark, tipografia Bricolage Grotesk/Inter, shell responsivo, componentes e personalizacao segura de cores por campeonato; verificado por `tests/ui_foundation_e2e.php`.
- [ ] Aplicacao visual da base em todos os fluxos administrativos e paginas do portal, com validacao em navegador nos breakpoints previstos.

## Dashboard administrativo - 27/07/2026

- [x] Login, visão geral e central visual do campeonato redesenhados sobre dados e permissões existentes; verificado por `tests/dashboard_ui_e2e.php`.
- [ ] Perfil e configurações pessoais dependem de rota e persistência próprias; não foram simulados nesta etapa visual.

## Final release audit - 27/07/2026

- [ ] Production release. Reopened: authenticated HTTP proof, complete assisted administration, safe rectification/PDF chain, complete sports rules, public SEO/responsive evidence and infrastructure validation. See `docs/FINAL_RELEASE_AUDIT.md`.

## Production preparation - 27/07/2026

- [x] Disposable clean installation runner, security-control checks, deployment, LGPD, backup and observability documentation.
- [ ] Real authenticated HTTP integration, external backup restore, SMTP and cPanel production-host verification remain pending.

## Accountability and exports update - 27/07/2026

- [x] Real accountability indicators and scoped CSV/ZIP export jobs are implemented; verified by `tests/accountability_e2e.php`.
- [ ] Assisted editorial, gallery, transfer and document workflows, report PDF and grouped archive downloads remain pending.

## Public portal update - 27/07/2026

- [x] Public presenter, championship slug home, lists and public match/team/athlete details use explicit permitted fields and hide draft championships; verified by `tests/public_portal_e2e.php`.
- [ ] Sitemap, robots, complete social metadata, public content details, full bracket visual and responsive browser verification remain pending.

## Advanced sports rules update - 27/07/2026

- [x] Configurable points, W.O. score, active point penalties, basic direct head-to-head, card ledger, automatic suspension, fulfillment and lineup block are implemented and covered by `tests/sports_rules_e2e.php`.
- [ ] Full three-team mini-table ordering, card reset by phase, substitution windows, extra time and operational penalty shootout remain pending.
- [ ] Advanced rules execution is in progress: ordered mini-table, card cancellation/cleanup, substitution records, extra-time gate and shootout persistence were added in migration 019. Administrative controls and full scenario coverage remain pending.

## Administrative assisted workflow - 27/07/2026

- [x] Championship operational screen uses filtered selects and action forms for registrations, groups, schedule generation, visual lineups, match events, finish, homologation and PDF; no manual IDs or lineup JSON in primary flow.
- [x] Assisted team, athlete and technical-staff creation derives project/tournament from persisted context; athlete-team mismatch is blocked server-side.
- [ ] Photo/document upload, full athlete editing, operational match postponement/cancellation/W.O., schedule wizard with venues/days and bracket visualization remain pending.

## Authorization update - 27/07/2026

- [x] Granular permissions and organization/project/tournament/team/match isolation applied to generic CRUD and operational policies; verified by `tests/authorization_e2e.php`.
- [ ] Authenticated HTTP session, CSRF and response-code coverage remains pending.

## Atualização operacional - 27/07/2026

- [x] Fluxo mínimo comprovado por `tests/tournament_e2e.php`: inscrições aprovadas, grupos, agenda, escalações, eventos, homologação, classificação por grupo, quartas, semifinais, final, campeão, vice e PDF privado.
- [ ] O restante dos itens abaixo permanece aberto quando exigir interface assistida, controle completo de escopo em CRUD genérico ou regras esportivas ainda não implementadas.

Regra: um item só recebe `[x]` depois de comportamento real e verificação registrada em `docs/TEST_REPORT.md`.

## 1. Fundação e operação básica

- [x] Estrutura MVC, front controller, roteador e templates PHP.
- [x] Ambiente `.env`, PDO MySQL, migrations, seeds e runner PHP.
- [x] Erro centralizado, logs de exceção, CSRF, sessões seguras e soft delete base.
- [x] Timestamps automáticos em inserções de repositório.
- [ ] Validação e normalização de entrada por entidade no servidor. (Existe validador genérico; regras de domínio e relações ainda não são validadas.)
- [x] Paginação, filtros e busca reais nos CRUDs administrativos.
- [x] Upload seguro e armazenamento privado/público com validação de MIME e tamanho.
- [x] Download privado autorizado por permissão e escopo.
- [x] Documentação de instalação local e cPanel.

## 2. Autenticação, perfis e escopos

- [x] Login, logout, hash de senha, regeneração de sessão e rate limit.
- [x] Solicitação e redefinição de senha com token temporário.
- [x] Roles seedados: superadmin, projeto, organizador, time, operação, comunicação e auditoria.
- [ ] Permissões granulares por ação e perfil.
- [ ] Isolamento por organização, projeto, campeonato e equipe em consultas e rotas.
- [x] Auditoria de login, logout e mutações de CRUD.
- [ ] Auditoria de retificações, homologações e alterações de regulamento.

## 3. Multi-campeonato e regulamento

- [x] Organizações, projetos, campeonatos, slug, tema e configurações persistidas.
- [ ] Categorias, temporadas, patrocinadores, banners e logos com arquivos reais.
- [x] Preset configurável da Copa Brasil de Talentos persistido em JSON.
- [ ] Editor estruturado de grupos, pontuação, desempates, cartões, W.O., inscrição e mata-mata. (Há persistência JSON, mas não controles estruturados nem validação integral de todas as regras.)
- [x] Versionamento e bloqueio de regras após primeira partida.

## 4. Cadastros e inscrições

- [ ] CRUD de equipes, pessoas, vínculos e inscrições com regras de domínio. (Há CRUD genérico, sem fluxo de inscrição, relações assistidas ou validações exigidas.)
- [ ] Campos completos de atleta, responsável, comissão, posição, número, foto e documentos.
- [ ] Validação de duplicidade, faixa etária, limite de elenco e inscrição por categoria.
- [ ] Histórico de vínculo e movimentação de atleta.

## 5. Calendário e competição

- [x] Schema de fases, grupos, rodadas, locais, partidas e escalações.
- [ ] CRUD funcional de grupos, rodadas e distribuição de equipes.
- [ ] Geração configurável de fase de grupos, datas e horários.
- [ ] Adiamento, cancelamento, W.O. e decisões administrativas.
- [ ] Geração configurável de quartas, semifinais e final.
- [ ] Avanço automático de vencedor e tratamento de pênaltis.

## 6. Central de partida e súmula

- [x] Persistência de partidas, eventos, escalações e estados de súmula.
- [ ] Interface operacional da partida com cronologia, placar e correção de eventos.
- [ ] Titulares, reservas, comissão técnica e arbitragem validados.
- [ ] Homologação, retificação e histórico imutável de versões.
- [ ] Montagem de súmula digital completa.
- [ ] Geração de PDF individual, por rodada e por campeonato.

## 7. Estatísticas, classificação e disciplina

- [x] Serviço inicial de classificação por partidas homologadas.
- [ ] Pontuação e ordem de desempate lidas integralmente da configuração.
- [ ] Recálculo transacional após homologação e retificação.
- [ ] Artilharia, assistências, cartões, partidas e rankings automáticos.
- [ ] Cartões, pendurados, suspensões, cumprimento e bloqueio de escalação.
- [ ] Punições, perda de pontos e histórico administrativo.

## 8. Conteúdo e prestação de contas

- [x] Schema de notícias, galerias, transferências, documentos, premiações e export jobs.
- [ ] CRUD funcional com publicação, destaque, galerias e anexos.
- [ ] Fluxo completo de transferências, janela e publicação pública.
- [ ] Dashboard de prestação de contas e filtros por período.
- [ ] Exportação CSV, PDF, pacote de documentos e download agrupado.

## 9. Portal público

- [x] Home por slug, tema, jogos, classificação, equipes e notícias com dados reais.
- [ ] Página de detalhe de jogo, equipe e atleta.
- [ ] Grupos, chaveamento, artilharia, assistências, disciplina e rankings.
- [ ] Galerias, vai e vem, regulamento, documentos públicos e campeões.
- [ ] SEO, metadados sociais, estados de carregamento e acessibilidade revisada.

## 10. Qualidade e implantação

- [x] PHP lint, migrations, seed, smoke público e teste de CRUD de repositório.
- [ ] Testes de autenticação, CSRF, permissões e isolamento entre campeonatos.
- [ ] Testes de classificação, desempate, cartões, suspensão, mata-mata e retificação.
- [ ] Testes de interface responsiva e fluxo HTTP autenticado.
- [ ] CSP, headers de segurança, política LGPD, backup e observabilidade.
- [ ] Checklist de implantação cPanel validado em instalação limpa.
