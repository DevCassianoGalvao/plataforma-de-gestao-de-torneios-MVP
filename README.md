# Plataforma de Gestão de Torneios

## UI/UX foundation

Tokens, temas light/dark, componentes e shell responsivo estao documentados em `docs/UI_DESIGN_SYSTEM.md`. A identidade do portal recebe cores persistidas do campeonato somente apos validacao hexadecimal. Esta entrega e a base reutilizavel; a aplicacao visual completa das telas permanece planejada.

## URL de teste cPanel

Use `APP_URL=https://www.cassianogalvao.com.br/copa-online` and `APP_BASE_PATH=/copa-online`. See `docs/CPANEL_DEPLOYMENT.md` for deployment layout.

## Authorization

Granular permissions and scope isolation are enforced server-side through `ScopeService`, `ScopedRepository` and `AuthPolicy`. See `docs/AUTHORIZATION_MATRIX.md`; run `php tests/authorization_e2e.php` after migrations.

## Base fictícia de demonstração

Em ambiente de desenvolvimento, execute `php database/seed.php --demo`. O seed cria dados totalmente fictícios e idempotentes. Consulte `database/README.md` e `docs/TEST_DATA.md`; o comando é bloqueado em produção.

## Fluxo operacional mínimo

O painel inclui a rota `/admin/tournaments/{id}/operation` para cadastro assistido de equipes, atletas, responsáveis e comissão, inscrições, grupos, geração de agenda, escalações visuais, eventos, revisão de súmula HTML, finalização, homologação, quartas e PDFs. O fluxo é validado por `php tests/admin_workflow_e2e.php` e `php tests/tournament_e2e.php`.

Base PHP 8.2 + MySQL 8 para Apache/cPanel. Não usa framework de execução nem exige build.

## Local

1. `Copy-Item .env.example .env` e ajuste a conexão MySQL.
2. `composer install` se Composer estiver disponível.
3. `php bin/migrate.php`
4. `php bin/seed.php`
5. `php -S localhost:8080 -t public public/index.php`
6. Abra `http://localhost:8080/login`.

Credenciais de desenvolvimento: `admin@torneios.local` / `Admin@12345`.
Portal: `/campeonatos/copa-brasil-de-talentos`.

Páginas públicas disponíveis: `/jogos`, `/classificacao`, `/equipes`, `/noticias`, `/rankings`, `/galerias`, `/documentos` e `/transferencias` sob o slug do campeonato.

## cPanel

Crie banco e usuário MySQL, envie o projeto, aponte o document root para `public/`, crie `.env`, habilite PHP 8.2 com PDO MySQL e execute `php bin/migrate.php` e `php bin/seed.php` no Terminal cPanel. Proteja `storage/` e mantenha `.env` fora do acesso público. Use HTTPS.

## Estado funcional

O projeto ainda não opera um campeonato completo de ponta a ponta. A área administrativa contém CRUD genérico para parte das entidades, não os fluxos esportivos completos do PRD. Faltam inscrição validada, geração persistida de grupos e rodadas, escalação, homologação, retificação, mata-mata, PDFs publicados, exportações, estatísticas e permissões granulares aplicadas aos CRUDs. Consulte `docs/FINAL_AUDIT_REPORT.md` e `docs/IMPLEMENTATION_PLAN.md` antes de implantar ou apresentar o sistema como concluído.
