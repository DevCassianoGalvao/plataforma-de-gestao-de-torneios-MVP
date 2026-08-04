# Torneio Online Web App

Backup operacional: consulte [Backups da aplicacao](docs/APPLICATION_BACKUPS.md). Agendamento: `php bin/console.php backup:run`.

Sistema web planejado para administrar campeonatos de futebol e publicar informações esportivas.

URL configurada: https://www.cassianogalvao.com.br/torneio-online

## Estado atual

As Etapas 1 a 17 estão implementadas nesta linha de desenvolvimento. O projeto possui fundação técnica, autenticação, acesso por campeonato, competição completa, súmula, conteúdo editorial, portal público, hardening de segurança, instalação limpa, backup, preparação para cPanel e a UI/UX definitiva da aplicação.

A UI/UX da Etapa 17 foi centralizada em um design system com tema escuro e claro, responsividade e acessibilidade básica. A auditoria permanece aprovada para homologação, não para produção, porque cPanel, HTTPS, SMTP, cron, backup externo e restauração ainda precisam de evidência no ambiente real.

## Stack prevista

- PHP 8.2;
- MySQL;
- HTML, CSS e JavaScript;
- PDO com prepared statements;
- hospedagem alvo em cPanel.

## Configuração

Copie `.env.example` para `.env` e preencha apenas valores do ambiente local. Nunca versione `.env` ou credenciais reais.

Para cPanel, copie `config/cpanel.env.example` para `.env`. O template já possui domínio, base path e banco configurados; preencha somente `DB_PASS` e `APP_KEY` no servidor.

## Comandos disponíveis

```text
php bin/lint.php
php bin/console.php migrate
php bin/console.php migrate:status
SEED_DEMO_PASSWORD=... php bin/console.php db:seed
ALLOW_DEMO_SIMULATION=1 php bin/console.php db:seed:simulation
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/torneio-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows PowerShell, defina variáveis com `$env:NOME='valor'` e use `C:\xampp\php\php.exe` se necessário. O seed exige senha fictícia no ambiente e é bloqueado em produção.

## Documentação

- [PRD](docs/PRD_PLATAFORMA_TORNEIOS.md)
- [Autenticação](docs/AUTHENTICATION.md)
- [Campeonamentos e regulamentos](docs/CHAMPIONSHIPS_AND_REGULATIONS.md)
- [Regulamento avancado e elegibilidade](docs/ADVANCED_REGULATIONS_AND_ELIGIBILITY.md)
- [Equipes e comissão técnica](docs/TEAMS_AND_STAFF.md)
- [Formações táticas](docs/TACTICAL_FORMATIONS.md)
- [Atletas, responsáveis e documentos](docs/ATHLETES_AND_DOCUMENTS.md)
- [Inscrições e elenco oficial](docs/REGISTRATIONS_AND_ROSTERS.md)
- [Grupos, rodadas e tabela](docs/GROUPS_ROUNDS_AND_SCHEDULE.md)
- [Formações e escalações táticas](docs/TACTICAL_LINEUPS.md)
- [Central operacional e homologação](docs/MATCH_OPERATION.md)
- [Checklist configurável de evidências](docs/CONFIGURABLE_MATCH_EVIDENCE_CHECKLIST.md)
- [Acompanhamento por rodada](docs/ROUND_COVERAGE_MONITORING.md)
- [Simulador interno de partidas e classificação](docs/INTERNAL_TOURNAMENT_SIMULATOR.md)
- [Operação administrativa e notificações](docs/ADMIN_OPERATIONS.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Schema](docs/DATABASE_SCHEMA.md)
- [Rotas](docs/ROUTES_AND_PAGES.md)
- [Perfis, permissões e escopo](docs/ROLES_AND_PERMISSIONS.md)
- [Plano de implementação](docs/IMPLEMENTATION_PLAN.md)
- [Plano de testes](docs/TEST_PLAN.md)
- [Preparação para produção](docs/PRODUCTION_READINESS.md)
- [Implantação em cPanel](docs/CPANEL_DEPLOYMENT.md)
- [Auditoria final do MVP](docs/FINAL_MVP_AUDIT.md)
- [Referência visual do Stitch](docs/STITCH_DESIGN_REFERENCE.md)
- [Design system da UI](docs/UI_DESIGN_SYSTEM.md)
- [Auditoria final de UI/UX](docs/UI_UX_FINAL_AUDIT.md)
- [Simulação de campeonato em andamento](docs/TOURNAMENT_PROGRESS_SIMULATION.md)
- [Referência da súmula](docs/REFERENCIA_SUMULA.xlsx)

## Próxima etapa

A próxima etapa e a homologação operacional em ambiente semelhante ao cPanel de destino. Melhorias de produto, refinamento visual adicional e retificação avançada devem ser priorizados somente após essa verificação real.
