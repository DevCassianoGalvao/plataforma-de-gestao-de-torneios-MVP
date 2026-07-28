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

## Atletas, responsaveis e documentos

`AthleteAccessService` e `AthleteRepository` aplicam o mesmo escopo no SQL: administrador ve todos, organizador ve equipes de campeonamentos autorizados e treinador/gestor ve somente equipes com vinculo ativo. Operador e comunicacao recebem `403`. O cadastro do atleta nao depende de inscricao.

`AthleteRules` calcula idade por aniversario, valida categoria e genero contra a equipe/campeonato, limita posicoes ao catalogo ativo, impede duplicidade suficiente por equipe, nome e nascimento e exige responsavel para menor. A exclusao e logica por `deleted_at` e status `archived`.

Dados de documento do responsavel sao cifrados com AES-256-GCM em `SensitiveData`; a interface exibe apenas uma mascara. Documentos usam `UploadRules` com MIME real via `finfo`, extensao coerente, limite de 10 MB e bloqueio de executaveis. O `StorageService` mantem os arquivos fora da area publica; cada leitura exige autenticacao, permissao e escopo do atleta.

## Formacoes taticas

`TacticalFormationRepository` e `TacticalFormationService` carregam formacoes e slots estruturados. Cada formacao ativa possui exatamente 11 slots, um goleiro e coordenadas normalizadas de 0 a 100. A equipe guarda uma formacao padrao e registra autor e data da alteracao. Nao ha atletas, escalaacoes por partida, arrastar-e-soltar ou campo visual definitivo.

## Campeonamentos e regulamentos

`ChampionshipRepository` concentra cadastro e filtros. `ChampionshipAccessService` valida permissao, vinculo e status. `ChampionshipStatusService` limita transicoes e impede `configured`/`in_progress` sem regulamento publicado.

`RegulationService` mantem configuracao estruturada em tabelas especificas. Uma publicacao transforma o rascunho em versao ativa e marca a anterior como `superseded`. Alteracoes posteriores criam nova versao; nenhuma versao publicada e sobrescrita silenciosamente.

O campo `metadata` de auditoria continua sendo JSON por conter atributos pequenos e variaveis por evento. Ele nunca e usado como formulario e nao e exibido bruto.

## Grupos, rodadas e agenda

`ScheduleRepository` separa fases, grupos, vinculos de equipes, rodadas, locais e partidas. `RoundRobinGenerator` usa algoritmo de circulo, remove folgas de grupos impares e gera segundo turno com mandante/visitante invertidos. `ScheduleService` valida limites, conflito de equipe/local, transicoes de status, bloqueio apos inicio e gera `fixture_key` para idempotencia.

O assistente recebe grupos, turno, periodo, dias, horarios e locais em campos estruturados. A previa mostra confrontos e conflitos antes da confirmacao. Alteracoes de agenda entram em `match_schedule_changes`; decisoes administrativas entram em `administrative_decisions`. O escopo de leitura de partidas e administrador, organizador vinculado ou treinador com vinculo ativo a uma das equipes.

## Uploads

`StorageService` grava arquivos fora de `public/storage/private`, com nome aleatorio, MIME detectado por `finfo`, limite de tamanho e extensoes derivadas do MIME. SVG nao e aceito nesta etapa. Assets de identidade e PDFs de regulamento so sao servidos depois de autenticacao e autorizacao do campeonato.

## Limites atuais

Inscricoes usam `RegistrationAccessService`, `RegistrationRules` e `RegistrationService`. O repositorio aplica escopo por administrador, organizador vinculado ou equipe do treinador. O regulamento define tamanho de elenco, goleiros minimos, documentos obrigatorios e inscricao por multiplas equipes em tabelas normalizadas. Somente `approved` aparece no elenco oficial; toda transicao e correcao gera historico e auditoria.

Nao existem escalacoes, operacao de partida, cartoes operacionais, suspensoes disciplinares, classificacao, mata-mata, sumula operacional, noticias, Vai e Vem ou portal publico. Equipes, atletas, documentos, inscricoes, elenco, grupos e tabela existem apenas no painel administrativo; a UI/UX definitiva continua para uma rodada posterior.
