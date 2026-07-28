# Arquitetura do MVP

## Stack e fluxo

- PHP 8.2 sem framework obrigatorio.
- MySQL via PDO com prepared statements.
- MVC simples, autoload PSR-4 minimo e front controller em `public/index.php`.
- Rotas declaradas em `routes/web.php` e URLs montadas por `Config::url`.
- `APP_BASE_PATH` continua compativel com `/copa-online`.
- Views PHP estruturadas por tarefa; UI/UX definitiva fica para rodada posterior.

## Escopo por campeonato

Os papeis continuam globais, mas campeonamentos agora possuem `championship_user_assignments`. Administradores acessam todos os campeonatos. Organizadores precisam de permissao esportiva e vinculo `organizer` para listar, abrir ou alterar um campeonato. A busca ja aplica o vinculo no SQL, antes de exibir dados.

Treinadores, operadores e comunicacao ainda nao recebem acesso funcional aos modulos esportivos desta etapa.

## Campeonamentos e regulamentos

`ChampionshipRepository` concentra cadastro e filtros. `ChampionshipAccessService` valida permissao, vinculo e status. `ChampionshipStatusService` limita transicoes e impede `configured`/`in_progress` sem regulamento publicado.

`RegulationService` mantem configuracao estruturada em tabelas especificas. Uma publicacao transforma o rascunho em versao ativa e marca a anterior como `superseded`. Alteracoes posteriores criam nova versao; nenhuma versao publicada e sobrescrita silenciosamente.

O campo `metadata` de auditoria continua sendo JSON por conter atributos pequenos e variaveis por evento. Ele nunca e usado como formulario e nao e exibido bruto.

## Uploads

`StorageService` grava arquivos fora de `public/storage/private`, com nome aleatorio, MIME detectado por `finfo`, limite de tamanho e extensoes derivadas do MIME. SVG nao e aceito nesta etapa. Assets de identidade e PDFs de regulamento so sao servidos depois de autenticacao e autorizacao do campeonato.

## Limites atuais

Nao existem equipes, atletas, inscricoes, grupos, rodadas, partidas, escalacoes, cartoes operacionais, suspensoes, classificacao, mata-mata, sumula, noticias, Vai e Vem ou portal publico. Identidade e regulamento existem apenas no painel administrativo.
