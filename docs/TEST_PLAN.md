# Plano de Testes da Fundacao

## Classificacao

| Camada | Escopo | Arquivos |
|---|---|---|
| Unitario | Config, base path, escape e CSRF | `tests/Unit/FoundationTest.php` |
| Integracao | PDO descartavel, migration runner e tabelas da fundacao | `tests/Integration/MigrationTest.php` |
| HTTP | Request/Response, rotas, health, 404 e placeholder de login | `tests/Http/FoundationHttpTest.php` |
| Lint | Sintaxe de todos os PHP | `bin/lint.php` |

Nao existe teste chamado E2E nesta etapa. Teste que apenas inspeciona arquivo nao sera tratado como navegador ou E2E.

## Criterios

- Configuracao carrega `.env` sem versionar segredo.
- Base path `/copa-online` nao altera o roteamento interno.
- PDO conecta usando prepared statements e migrations sao idempotentes.
- Health retorna 200 quando a persistencia esta disponivel.
- Rota inexistente retorna 404.
- CSRF rejeita token ausente ou incorreto.
- Saida HTML escapa valores.
- Producao nao exibe stack trace.
- Banco de teste tem nome explicitamente seguro e e removido ao final.

## Comandos

```text
php bin/lint.php
php bin/console.php migrate:status
php bin/console.php migrate
php bin/test.php
php -S localhost:8000 -t public public/index.php
```
