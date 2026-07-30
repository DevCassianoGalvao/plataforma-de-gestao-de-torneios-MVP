# Plataforma de Gestao de Torneios MVP

Sistema web planejado para administrar campeonatos de futebol e publicar informacoes esportivas.

URL configurada: https://www.cassianogalvao.com.br/torneio-online

## Estado atual

As Etapas 1 a 17 estao implementadas nesta linha de desenvolvimento. O projeto possui fundacao tecnica, autenticacao, acesso por campeonato, competicao completa, sumula, conteudo editorial, portal publico, hardening de seguranca, instalacao limpa, backup, preparacao para cPanel e a UI/UX definitiva da aplicacao.

A UI/UX da Etapa 17 foi centralizada em um design system com tema escuro e claro, responsividade e acessibilidade basica. A auditoria permanece aprovada para homologacao, nao para producao, porque cPanel, HTTPS, SMTP, cron, backup externo e restauracao ainda precisam de evidencia no ambiente real.

## Stack prevista

- PHP 8.2;
- MySQL;
- HTML, CSS e JavaScript;
- PDO com prepared statements;
- hospedagem alvo em cPanel.

## Configuracao

Copie `.env.example` para `.env` e preencha apenas valores do ambiente local. Nunca versione `.env` ou credenciais reais.

Para cPanel, copie `config/cpanel.env.example` para `.env`. O template ja possui dominio, base path e banco configurados; preencha somente `DB_PASS` e `APP_KEY` no servidor.

## Comandos disponiveis

```text
php bin/lint.php
php bin/console.php migrate
php bin/console.php migrate:status
SEED_DEMO_PASSWORD=... php bin/console.php db:seed
ALLOW_DEMO_SIMULATION=1 php bin/console.php db:seed:simulation
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/torneio-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows PowerShell, defina variaveis com `$env:NOME='valor'` e use `C:\xampp\php\php.exe` se necessario. O seed exige senha ficticia no ambiente e e bloqueado em producao.

## Documentacao

- [PRD](docs/PRD_PLATAFORMA_TORNEIOS.md)
- [Autenticacao](docs/AUTHENTICATION.md)
- [Campeonamentos e regulamentos](docs/CHAMPIONSHIPS_AND_REGULATIONS.md)
- [Equipes e comissao tecnica](docs/TEAMS_AND_STAFF.md)
- [Formacoes taticas](docs/TACTICAL_FORMATIONS.md)
- [Atletas, responsaveis e documentos](docs/ATHLETES_AND_DOCUMENTS.md)
- [Inscricoes e elenco oficial](docs/REGISTRATIONS_AND_ROSTERS.md)
- [Grupos, rodadas e tabela](docs/GROUPS_ROUNDS_AND_SCHEDULE.md)
- [Formacoes e escalacoes taticas](docs/TACTICAL_LINEUPS.md)
- [Central operacional e homologacao](docs/MATCH_OPERATION.md)
- [Operacao administrativa e notificacoes](docs/ADMIN_OPERATIONS.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Schema](docs/DATABASE_SCHEMA.md)
- [Rotas](docs/ROUTES_AND_PAGES.md)
- [Perfis, permissoes e escopo](docs/ROLES_AND_PERMISSIONS.md)
- [Plano de implementacao](docs/IMPLEMENTATION_PLAN.md)
- [Plano de testes](docs/TEST_PLAN.md)
- [Preparacao para producao](docs/PRODUCTION_READINESS.md)
- [Implantacao em cPanel](docs/CPANEL_DEPLOYMENT.md)
- [Auditoria final do MVP](docs/FINAL_MVP_AUDIT.md)
- [Referencia visual do Stitch](docs/STITCH_DESIGN_REFERENCE.md)
- [Design system da UI](docs/UI_DESIGN_SYSTEM.md)
- [Auditoria final de UI/UX](docs/UI_UX_FINAL_AUDIT.md)
- [Simulacao de campeonato em andamento](docs/TOURNAMENT_PROGRESS_SIMULATION.md)
- [Referencia da sumula](docs/REFERENCIA_SUMULA.xlsx)

## Proxima etapa

A proxima etapa e a homologacao operacional em ambiente semelhante ao cPanel de destino. Melhorias de produto, refinamento visual adicional e retificacao avancada devem ser priorizados somente apos essa verificacao real.
