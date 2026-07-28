# Arquitetura da Fundacao

## Decisoes

- PHP 8.2 sem framework obrigatorio.
- MySQL via PDO com prepared statements.
- MVC simples, com responsabilidades separadas por camada.
- Autoload PSR-4 minimo em `app/bootstrap.php`.
- Front controller em `public/index.php`.
- Rotas declaradas em `routes/web.php`.
- Configuracao somente por ambiente e `.env` local, nunca por segredo versionado.
- `APP_BASE_PATH` controla URLs quando a aplicacao roda em `/copa-online`.
- Views PHP sem design system nesta etapa.
- Arquivos privados ficam fora de `public/` nas etapas futuras.

## Estrutura

```text
app/
  Core/                 # ambiente, config, request, response, router, PDO, sessao, seguranca
  Http/Controllers/     # endpoints finos
  Views/                # templates sem logica de dominio
config/                 # contratos de configuracao futuros
database/
  migrations/           # SQL versionado; somente fundacao nesta etapa
docs/                   # PRD, arquitetura e planos
public/                 # document root e assets minimos
routes/                 # mapa de rotas
storage/                # logs/cache/private, ignorados quando gerados
tests/
  Unit/
  Integration/
  Http/
```

## Fluxo de requisicao

1. `public/index.php` carrega o bootstrap.
2. O bootstrap registra autoload, ambiente, timezone e sessao segura.
3. `Request` captura metodo, URI, query e corpo.
4. `Router` remove `APP_BASE_PATH` e resolve somente rotas conhecidas.
5. Controller chama Core/Services quando existirem e devolve `Response`.
6. Excecoes sao registradas em `storage/logs` e ocultadas em producao.

## Limites desta etapa

Nao existem ainda autenticacao real, entidades de campeonato, migrations de dominio, controllers de negocio, dashboard, portal, PDF ou CSS final. O migration runner apenas prova o mecanismo com uma tabela de controle e um health check persistido.
