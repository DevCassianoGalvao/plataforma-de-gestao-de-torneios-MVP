# Plataforma de Gestao de Torneios MVP

## Objetivo

Construir, por etapas, uma plataforma para criar, organizar, operar e publicar campeonatos de futebol.

## Stack

PHP 8.2, MySQL, HTML, CSS e JavaScript, com Apache/cPanel. A fundacao usa MVC simples, PDO e front controller, sem framework obrigatorio.

## Requisitos

- PHP 8.2 com PDO MySQL;
- MySQL 8 ou compativel;
- extensao OpenSSL para CSRF e sessoes seguras;
- Apache/cPanel ou servidor PHP embutido para desenvolvimento.

## Instalacao local

1. Copie `.env.example` para `.env`.
2. Ajuste `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` no `.env` local.
3. Mantenha `APP_BASE_PATH=/copa-online` para simular o subdiretorio de producao.
4. Crie o banco informado em `DB_NAME`.
5. Execute `php bin/console.php migrate`.
6. Inicie `php -S localhost:8000 -t public public/index.php`.
7. Acesse `http://localhost:8000/copa-online/`.

## Comandos disponiveis

```text
php bin/console.php migrate
php bin/console.php migrate:status
php bin/lint.php
APP_ENV=test DB_NAME=torneios_mvp_test php bin/test.php
php -S localhost:8000 -t public public/index.php
```

No PowerShell, defina as variaveis de teste antes de executar `php bin/test.php`:

```powershell
$env:APP_ENV='test'
$env:DB_NAME='torneios_mvp_test'
php bin/test.php
```

O runner de testes cria e remove um banco com nome explicitamente identificado como teste.

## Estrutura

```text
app/Core/              nucleo tecnico
app/Http/Controllers/  endpoints finos
app/Views/             views minimas
config/                configuracoes futuras
database/migrations/   migrations versionadas
docs/                  PRD e contratos tecnicos
public/                document root
routes/                rotas tecnicas
storage/               logs, cache e arquivos privados
tests/Unit/            testes unitarios
tests/Integration/     testes de banco
tests/Http/            contratos HTTP
```

## Situacao atual

Etapa 1: fundacao tecnica. Existe apenas bootstrap, configuracao, PDO, migration runner, health check, rotas tecnicas, sessao, CSRF, logger e testes da fundacao. Autenticacao completa e todos os modulos esportivos ainda estao pendentes.

## Proximas etapas

Autenticacao e usuarios; campeonatos e regulamentos; equipes e pessoas; inscricoes; competencia; partida; disciplina; sumula; noticias; Vai e Vem; portal publico; producao e UI/UX definitiva. O detalhamento esta em `docs/IMPLEMENTATION_PLAN.md`.

## Fonte do produto

Leia `docs/PRD_PLATAFORMA_TORNEIOS.md` antes de implementar qualquer modulo. O mapeamento da planilha oficial esta em `docs/MATCH_REPORT_MAPPING.md` e o arquivo original em `docs/REFERENCIA_SUMULA.xlsx`.
