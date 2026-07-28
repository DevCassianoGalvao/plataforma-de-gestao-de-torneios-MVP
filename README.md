# Plataforma de Gestao de Torneios MVP

Sistema web planejado para administrar campeonatos de futebol e publicar informacoes esportivas.

## Estado atual

As Etapas 1, 2, 3, 4 e 5 estao implementadas nesta branch de trabalho. O projeto possui fundacao tecnica, autenticacao, acesso por campeonato, temporadas, categorias, campeonamentos, identidade basica, regulamentos configuraveis, equipes, responsaveis, comissao tecnica, formacao tatica padrao, atletas, posicoes e documentos privados.

Ainda nao existem inscricoes, grupos, rodadas, partidas, escalacoes, classificacao, mata-mata, sumula operacional, noticias, Vai e Vem ou portal publico. O layout definitivo e o campo visual completo continuam planejados para rodadas posteriores.

## Stack prevista

- PHP 8.2;
- MySQL;
- HTML, CSS e JavaScript;
- PDO com prepared statements;
- hospedagem alvo em cPanel.

## Configuracao

Copie `.env.example` para `.env` e preencha apenas valores do ambiente local. Nunca versione `.env` ou credenciais reais.

## Comandos disponiveis

```text
php bin/lint.php
php bin/console.php migrate
php bin/console.php migrate:status
SEED_DEMO_PASSWORD=... php bin/console.php db:seed
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/copa-online TEST_PASSWORD=... php bin/http-test.php
```

No Windows PowerShell, defina variaveis com `$env:NOME='valor'` e use `C:\xampp\php\php.exe` se necessario. O seed exige senha ficticia no ambiente e e bloqueado em producao.

## Documentacao

- [PRD](docs/PRD_PLATAFORMA_TORNEIOS.md)
- [Autenticacao](docs/AUTHENTICATION.md)
- [Campeonamentos e regulamentos](docs/CHAMPIONSHIPS_AND_REGULATIONS.md)
- [Equipes e comissao tecnica](docs/TEAMS_AND_STAFF.md)
- [Formacoes taticas](docs/TACTICAL_FORMATIONS.md)
- [Atletas, responsaveis e documentos](docs/ATHLETES_AND_DOCUMENTS.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Schema](docs/DATABASE_SCHEMA.md)
- [Rotas](docs/ROUTES_AND_PAGES.md)
- [Perfis, permissoes e escopo](docs/ROLES_AND_PERMISSIONS.md)
- [Plano de implementacao](docs/IMPLEMENTATION_PLAN.md)
- [Plano de testes](docs/TEST_PLAN.md)
- [Referencia da sumula](docs/REFERENCIA_SUMULA.xlsx)

## Proxima etapa

A Etapa 6 deve tratar inscricoes em campeonamentos. O layout definitivo continua separado da rodada estrutural.
