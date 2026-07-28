# Plataforma de Gestao de Torneios MVP

Sistema web planejado para administrar campeonatos de futebol e publicar informacoes esportivas.

## Estado atual

A Etapa 1 (fundacao tecnica) e a Etapa 2 (autenticacao e acesso) estao implementadas na branch `feat/authentication-and-access`. O projeto ainda esta em desenvolvimento estrutural. Campeonatos, equipes, atletas, partidas, sumula, noticias, Vai e Vem e portal publico ainda nao foram implementados.

Entregas atuais:

- login, logout e sessao com timeout;
- recuperacao de senha com token hash e expiracao;
- cinco perfis globais e permissoes verificadas no servidor;
- gestao administrativa de usuarios e perfis;
- perfil pessoal e alteracao de senha;
- auditoria de eventos importantes;
- seed ficticio idempotente para desenvolvimento;
- base path `/copa-online`.

## Stack prevista

- PHP 8.2;
- MySQL;
- HTML, CSS e JavaScript;
- PDO com prepared statements;
- hospedagem alvo em cPanel.

## Requisitos locais

- PHP 8.2 com PDO MySQL;
- MySQL;
- servidor web apontando para `public/`.

## Configuracao

Copie `.env.example` para `.env` e preencha apenas os valores do ambiente local. Nunca versione `.env` ou credenciais reais.

## Comandos disponiveis

```text
php bin/lint.php
php bin/console.php migrate
php bin/console.php migrate:status
SEED_DEMO_PASSWORD=... php bin/console.php db:seed
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
HTTP_TEST_BASE_URL=http://127.0.0.1:18080/copa-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows PowerShell, defina as variaveis com `$env:NOME='valor'` e use `C:\xampp\php\php.exe` se necessario. O comando de seed exige uma senha ficticia definida no ambiente e e bloqueado em producao.

## Documentacao

- [PRD](docs/PRD_PLATAFORMA_TORNEIOS.md)
- [Autenticacao](docs/AUTHENTICATION.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Schema](docs/DATABASE_SCHEMA.md)
- [Rotas](docs/ROUTES_AND_PAGES.md)
- [Perfis e permissoes](docs/ROLES_AND_PERMISSIONS.md)
- [Plano de implementacao](docs/IMPLEMENTATION_PLAN.md)
- [Plano de testes](docs/TEST_PLAN.md)
- [Referencia da sumula](docs/REFERENCIA_SUMULA.xlsx)

## Proximas etapas

A Etapa 3 deve implementar campeonamentos e regulamentos configuraveis, depois equipes e atletas. A separacao entre estrutura funcional e a segunda rodada de UI/UX sera mantida.
