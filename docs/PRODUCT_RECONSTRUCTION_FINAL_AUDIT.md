# Auditoria Final da Reconstrucao

Data: 2026-07-28

## Veredito

**NAO APROVADO**

## Evidencia executada

- Lint PHP de `app`, `public` e `tests`: aprovado.
- `tests/integration.php`: aprovado.
- `tests/authorization_e2e.php`: aprovado.
- `tests/sports_rules_e2e.php`: aprovado.
- `tests/rectification_e2e.php`: aprovado.
- `tests/accountability_e2e.php`: aprovado.
- `tests/public_portal_e2e.php`: falhou. A suite espera a antiga renderizacao generica e nao aceita a separacao parcial de views publicas. A falha foi preservada como regressao aberta, nao mascarada.

## Correcao realizada

`PublicPortalPresenter::content()` deixou de usar `SELECT *`. Cada tipo publico possui agora uma projecao explicita. Durante a regressao, a projecao de noticias foi corrigida de colunas inexistentes (`summary`, `image_path`) para o schema real (`excerpt`, `cover_path`).

## Jornadas verificadas

Servicos e autorizacao: CRUD basico, escopo, regras esportivas, retificacao e exportacoes possuem evidencia local. A navegacao HTTP por perfil tem evidencia anterior em `navigation_http_e2e.php`, mas a auditoria atual nao provou o fluxo completo solicitado por todos os perfis.

## Bloqueadores

1. Etapas 2, 3, 4 e 5 permanecem incompletas pelos seus proprios criterios de aceite.
2. Nao existem `competition_http_e2e.php`, `match_operation_http_e2e.php`, `public_portal_http_e2e.php`, `security_http_e2e.php` com a cobertura solicitada.
3. O fluxo esportivo completo por rotas sem apoio de banco manual nao foi demonstrado.
4. Validacao visual em navegador, responsividade e contraste nos viewports obrigatorios nao foi executada.
5. Instalacao descartavel, upload hostil, rate limit HTTP e downloads privados autenticados nao foram repetidos nesta auditoria.

## Risco de liberacao

Liberar para producao agora criaria risco de regressao no portal, interfaces administrativas incompletas e ausencia de cobertura HTTP para operacao da partida. A recomendacao e nao abrir Pull Request para merge ate que os bloqueadores sejam resolvidos e a suite HTTP seja reconstruida contra as rotas finais.
