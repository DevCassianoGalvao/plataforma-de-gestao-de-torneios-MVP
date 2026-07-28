# Changelog

## Nao lancado - Dashboards administrativos

- Redesenhados login, painel global, navegação administrativa e resumo da central do campeonato.
- Adicionado `tests/dashboard_ui_e2e.php`.

## Nao lancado - Sistema de design UI/UX

- Adicionados tokens, tema claro/escuro, shell responsivo e componentes reutilizaveis.
- Portal agora aplica as tres cores seguras do campeonato com fallback da plataforma.
- Adicionado `tests/ui_foundation_e2e.php` e documentacao do sistema de design.

## 2026-07-27 - Versioned homologation snapshots

- Normal homologation now writes integrity-hashed operational snapshots with active rules and PDF linkage.
- Rectification comparison exposes score and event deltas; started knockout/champion impact remains explicitly blocked.

## 2026-07-27 - Advanced competition rules foundation

- Added persisted substitution, shootout, card-cleanup and exceptional-lineup records.
- Expanded configurable standings tie-breaks to rank tied groups through a mini-table.
- Added card cancellation, card cleanup, substitution validation, extra-time gate and shootout winner calculation services.

## 2026-07-27 - Assisted administration expansion

- Added championship dashboard shortcuts, assisted team, athlete, guardian and technical-staff creation.
- Added visual lineup controls, HTML match-report review and guided group membership removal.
- Blocked cross-team athlete registration at service level and expanded administrative workflow verification.

## 2026-07-27 - Final release audit

- Added definitive release audit and reopened production-release claims unsupported by HTTP/UI/infrastructure evidence.

## 2026-07-27 - Production preparation

- Added disposable database installer, cPanel rewrite config, security checks and production operations documentation.
- Added clean-install, security and authentication-control tests.

## 2026-07-27 - Accountability and exports

- Added accountability metrics service and scoped administrative dashboard.
- Expanded export jobs with CSV categories, ZIP output, expiration, progress and failure state.
- Added `tests/accountability_e2e.php`.

## 2026-07-27 - Public portal expansion

- Added public presenter with explicit permitted fields and public match, team and athlete detail routes.
- Added public lists for standings, rankings, content, champions and sponsors.
- Added `tests/public_portal_e2e.php`.

## 2026-07-27 - Rectification workflow

- Added immutable homologation versions, rectification requests, integrity hashes and impact detection.
- Added protected transactional rectification application and `tests/rectification_e2e.php`.

## 2026-07-27 - Advanced sports rules base

- Added sports rules persistence, discipline ledger, suspension fulfillments and penalty revocation metadata.
- Added configurable W.O., point penalties, configurable standings values and direct head-to-head support.
- Added `tests/sports_rules_e2e.php`.

## 2026-07-27 - Assisted tournament workflow

- Replaced the technical tournament operation screen with filtered selects, status actions and guided group/schedule/match forms.
- Added controller-side relationship checks for registration, group and stage IDs.
- Added `tests/admin_workflow_e2e.php`.

## 2026-07-27 - Granular authorization

- Added named role permissions, scoped repository, expanded scope resolver, match operator assignments and team-scoped documents.
- Added authorization E2E coverage and authorization matrix.
- Reconciled final audit report with tournament E2E evidence.

## Não lançado - Base fictícia de demonstração

- Adicionado `database/seed.php --demo`, bloqueado em produção e idempotente.
- Criados 3 campeonatos fictícios, usuários por perfil, 16 equipes, 288 atletas, comissão, conteúdo, documentos e placeholders locais.
- Adicionado teste `tests/demo_seed.php` e documentação de dados de teste.
- Corrigido round-robin para grupos com quantidade ímpar de equipes.

## Não lançado - Fluxo operacional mínimo

- Migration 012 para escalação, rodada e PDF de partida.
- Serviço transacional para inscrições, grupos, agenda, escalação, eventos, homologação, classificação, chave e súmula PDF.
- Rotas administrativas de operação e teste E2E de campeonato completo.

## Não lançado — Auditoria técnica

- Home pública passou a consultar e exibir partidas, classificação, equipes e notícias do campeonato.
- Rankings, galerias, documentos públicos e transferências receberam renderização; rota pública desconhecida agora retorna 404.
- Corrigido o fluxo de texto do PDF (`BT`/`ET`) e reforçado o teste estrutural do gerador.
- Plano, README e relatórios foram corrigidos para separar infraestrutura existente de fluxos ainda não implementados.

## 0.1.0 — Fundação

- Estrutura MVC PHP sem framework de execução.
- Migration e seed da base organizacional, autenticação e tema.
- Login, sessões, CSRF, autorização inicial e auditoria.
- CRUD administrativo central e portal público por campeonato.

## 0.2.0 — Operação e portal ampliado

- Schema de partidas, eventos, súmulas, disciplina, classificação e conteúdo.
- Serviços de classificação, eventos, disciplina, suspensões, inscrições, chaveamento, tema e exportação.
- Portal público com jogos, classificação, equipes e notícias.
- CRUD administrativo ampliado.

## 0.2.1 — Correções de operação

- Inserções genéricas passam a preencher timestamps obrigatórios.
- Tela administrativa suporta edição de registros.
- Teste de integração verifica CRUD e exclusão lógica.

## 0.2.2 — Validação e listagem administrativa

- Validação e normalização de IDs, datas, cores, slugs, JSON e números em servidor.
- Busca e paginação preparadas por consultas PDO nos CRUDs.

## 0.2.3 — Uploads seguros

- Upload administrativo de PDF, JPG, PNG e WEBP.
- Validação de MIME, tamanho máximo, nome aleatório e armazenamento fora de `public` por padrão.

## 0.2.4 — Recuperação de senha

- Tokens com hash, expiração de 30 minutos e uso único.
- Fluxo de solicitação/redefinição disponível sem revelar se o e-mail existe.

## 0.2.5 — Base de permissões

- Relação persistida entre roles e permissões.
- Seed define permissões para todos os perfis previstos.

## 0.3.0 — Downloads privados e auditoria contextual

- Contexto persistido de organização, projeto, campeonato e equipe na auditoria.
- Download privado por ID persistido, permission key, escopo, `realpath` e headers seguros.
- Log dedicado de acesso a arquivo privado e interface de atribuições/auditoria para superadministrador.

## 0.4.0 — Configuração de campeonato

- Schema para categorias, temporadas, ativos visuais, patrocinadores e versões do regulamento.
- Editor de regulamento com preset Copa Brasil, histórico e bloqueio após início.
- Upload de logos, favicon, banners e imagem de compartilhamento por campeonato.

## 0.5.0 — Domínio de inscrições

- Schema para perfil esportivo, responsáveis legais, tipos/documentos e histórico de vínculos.
- Serviço inicial de validação de inscrição baseado no regulamento ativo.

## 0.6.0 — Base de calendário

- Schema para distribuição de grupos, decisões administrativas e vínculos de chave.
- Serviço configurável de round-robin com folga e returno.

## 0.7.0 — Núcleo de súmula

- Versionamento de eventos, arbitragem e versões de súmula.
- Placar derivado de eventos válidos e gerador PDF A4 binário.

## 0.8.0 — Classificação configurável

- Recalculo transacional e idempotente de classificação.
- Pontuação e desempates lidos do regulamento ativo; perdas de pontos persistidas.

## 0.9.0 — Editorial e exportações

- Campos operacionais para notícias, galerias, transferências e documentos.
- Exportações CSV persistidas com status, progresso e expiração.

## 0.10.0 — Hardening e implantação

- Headers de segurança, HSTS condicional e logs JSON com correlação.
- Documentação de instalação, backup, segurança e LGPD.


## Nao lancado - Product navigation foundation

- Added role-based landing redirects and named administrative product routes.
- Added championship context, breadcrumbs, module shells and scoped menu generation.
- Removed legacy tournament operation from primary navigation and restricted its endpoints to superadministrator.
- Added real HTTP navigation coverage with CSRF, sessions, roles, scopes, 403, 404 and /copa-online checks.
- Added policy-guarded direct match detail and operation entry routes, with links that do not display match IDs.
- Demo seed now includes a persisted operator-to-match assignment for navigation coverage.
