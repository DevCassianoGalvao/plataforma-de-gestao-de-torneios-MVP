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

Treinadores e gestores recebem escopo explicito por equipe quando possuem vinculo ativo. Operadores e comunicacao continuam sem acesso administrativo a equipes, salvo permissao de visualizacao adicionada futuramente.

## Equipes e comissao

`TeamRepository` aplica o escopo na consulta: administrador ve todas as equipes, organizador ve apenas equipes de campeonamentos aos quais esta vinculado e treinador/gestor ve somente equipes com vinculo ativo. `TeamAccessService` centraliza essas decisoes; as views recebem capacidades ja calculadas e nao verificam perfis diretamente.

`TeamStatusService` valida transicoes entre `draft`, `active`, `inactive`, `withdrawn` e `archived`. Responsaveis sao registrados em `team_user_assignments` com inicio, fim e historico; um treinador pode ter mais de uma equipe porque o acesso e concedido por vinculo explicito. A comissao usa `staff_roles` e `team_staff`, permitindo membros sem login.

Escudos de equipes e fotos de comissao passam pelo `StorageService`, ficam fora da area publica, usam nome aleatorio e sao servidos somente depois de autorizacao. O campo `document_number` existe na estrutura para uma futura implementacao protegida, mas nao e coletado nem armazenado nesta etapa.

## Formacoes taticas

`TacticalFormationRepository` e `TacticalFormationService` carregam formacoes e slots estruturados. Cada formacao ativa possui exatamente 11 slots, um goleiro e coordenadas normalizadas de 0 a 100. A equipe guarda uma formacao padrao e registra autor e data da alteracao. Nao ha atletas, escalaacoes por partida, arrastar-e-soltar ou campo visual definitivo.

## Campeonamentos e regulamentos

`ChampionshipRepository` concentra cadastro e filtros. `ChampionshipAccessService` valida permissao, vinculo e status. `ChampionshipStatusService` limita transicoes e impede `configured`/`in_progress` sem regulamento publicado.

`RegulationService` mantem configuracao estruturada em tabelas especificas. Uma publicacao transforma o rascunho em versao ativa e marca a anterior como `superseded`. Alteracoes posteriores criam nova versao; nenhuma versao publicada e sobrescrita silenciosamente.

O campo `metadata` de auditoria continua sendo JSON por conter atributos pequenos e variaveis por evento. Ele nunca e usado como formulario e nao e exibido bruto.

## Uploads

`StorageService` grava arquivos fora de `public/storage/private`, com nome aleatorio, MIME detectado por `finfo`, limite de tamanho e extensoes derivadas do MIME. SVG nao e aceito nesta etapa. Assets de identidade e PDFs de regulamento so sao servidos depois de autenticacao e autorizacao do campeonato.

## Limites atuais

Nao existem atletas, inscricoes, grupos, rodadas, partidas, escalacoes, cartoes operacionais, suspensoes, classificacao, mata-mata, sumula operacional, noticias, Vai e Vem ou portal publico. Equipes, identidade, comissao e formacao padrao existem apenas no painel administrativo; a UI/UX definitiva continua para uma rodada posterior.
