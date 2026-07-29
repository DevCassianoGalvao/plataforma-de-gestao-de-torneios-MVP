# Arquitetura do MVP

## Stack e fluxo

- PHP 8.2 sem framework obrigatorio.
- MySQL via PDO com prepared statements.
- MVC simples, autoload PSR-4 minimo e front controller em `public/index.php`.
- Rotas declaradas em `routes/web.php` e URLs montadas por `Config::url`.
- `APP_BASE_PATH` continua compativel com `/copa-online`.
- Views PHP estruturadas por tarefa; o portal publico por campeonato usa read models explicitos e a UI/UX definitiva fica para a Etapa 17.

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

`TacticalFormationRepository` e `TacticalFormationService` carregam formacoes e slots estruturados. Cada formacao ativa possui exatamente 11 slots, um goleiro e coordenadas normalizadas de 0 a 100. A equipe guarda uma formacao padrao e registra autor e data da alteracao.

`LineupRepository`, `LineupAccessService` e `LineupService` persistem uma escalacao por partida e equipe, com titulares, reservas, comissao presente, capitao, goleiro, status, versao e historico. A sugestao automatica usa posicao principal, secundaria e grupo posicional; a incompatibilidade gera aviso, mas nao impede o ajuste manual. Confirmacao exige onze titulares, capitao titular e goleiro valido. Operadores veem apenas confirmadas e dados privados nao saem por rotas publicas.

## Campeonamentos e regulamentos

`ChampionshipRepository` concentra cadastro e filtros. `ChampionshipAccessService` valida permissao, vinculo e status. `ChampionshipStatusService` limita transicoes e impede `configured`/`in_progress` sem regulamento publicado.

`RegulationService` mantem configuracao estruturada em tabelas especificas. Uma publicacao transforma o rascunho em versao ativa e marca a anterior como `superseded`. Alteracoes posteriores criam nova versao; nenhuma versao publicada e sobrescrita silenciosamente.

O campo `metadata` de auditoria continua sendo JSON por conter atributos pequenos e variaveis por evento. Ele nunca e usado como formulario e nao e exibido bruto.

## Grupos, rodadas e agenda

`ScheduleRepository` separa fases, grupos, vinculos de equipes, rodadas, locais e partidas. `RoundRobinGenerator` usa algoritmo de circulo, remove folgas de grupos impares e gera segundo turno com mandante/visitante invertidos. `ScheduleService` valida limites, conflito de equipe/local, transicoes de status, bloqueio apos inicio e gera `fixture_key` para idempotencia.

O assistente recebe grupos, turno, periodo, dias, horarios e locais em campos estruturados. A previa mostra confrontos e conflitos antes da confirmacao. Alteracoes de agenda entram em `match_schedule_changes`; decisoes administrativas entram em `administrative_decisions`. O escopo de leitura de partidas e administrador, organizador vinculado ou treinador com vinculo ativo a uma das equipes.

## Classificacao e mata-mata

`StandingsRepository` le somente partidas homologadas, eventos validos, resultados administrativos e dados disciplinares necessarios aos desempates. `StandingsService` calcula cada grupo dentro de transacao, grava snapshot e hash de fonte e ordena pelos criterios habilitados do regulamento. Confronto direto usa mini-tabela dos times empatados; o ultimo desempate e deterministico para permitir reproducao.

`knockout_rounds` e `knockout_ties` representam a chave sem misturar a operacao da partida. A geracao usa fontes A1/B4 etc. e chaves de fixture para nao duplicar partidas. Apenas uma partida homologada pode promover vencedor; resultado administrativo e penaltis definem a tie sem entrar nos gols da classificacao. A final grava campeao e vice em `competition_results`.

## Sumula digital

`MatchReportRepository` monta payload de partida homologada a partir das tabelas reais de operacao, escalacao, arbitragem e campeonato. `MatchReportHtmlRenderer` preserva a organizacao estrutural da planilha em preview; `MatchReportPdf` desenha A4 com tabela de duas equipes, pagina principal e verso de ocorrencias. `MatchReportService` calcula hash da fonte, grava arquivo privado e cria versao imutavel; nova fonte cria nova versao sem alterar as anteriores.

`MatchReportAccessService` reaproveita escopo da central de partida para bloquear IDOR. Downloads usam `StorageService`, nunca caminho publico. Pacotes ZIP sao compostos apenas de versoes atuais autorizadas.

## Noticias e blog

`NewsRepository` separa consulta editorial de consulta publica. `NewsAccessService` exige `content.manage`/`content.publish` e valida atribuicao de comunicacao ou organizador ao campeonato. `NewsService` controla status, slug, publicacao, exclusao logica e auditoria. `NewsImageService` valida MIME real, dimensoes e reprocessa capas com GD; o portal so resolve capas por noticias publicadas.

## Operacao e homologacao

MatchOperationRepository concentra a operacao, eventos, substituicoes, arbitragem, placar derivado e historico. MatchOperationService valida os registros contra as duas escalacoes confirmadas e contra as regras de substituicao do regulamento. MatchOperationAccessService separa administrador, organizador, operador atribuido, treinador por equipe e perfis negados.

A operacao usa open, awaiting_homologation e homologated. O operador finaliza com checklist; o organizador homologa em acao separada. Eventos de gol e gol contra calculam o placar normal; penaltis ficam separados e resultado administrativo possui precedencia explicita. Nao ha retificacao avancada nem acumulacao completa de suspensoes nesta etapa.

## Uploads

`StorageService` grava arquivos fora de `public/storage/private`, com nome aleatorio, MIME detectado por `finfo`, limite de tamanho e extensoes derivadas do MIME. SVG nao e aceito nesta etapa. Assets de identidade e PDFs de regulamento so sao servidos depois de autenticacao e autorizacao do campeonato.

## Limites atuais

Inscricoes usam `RegistrationAccessService`, `RegistrationRules` e `RegistrationService`. O repositorio aplica escopo por administrador, organizador vinculado ou equipe do treinador. O regulamento define tamanho de elenco, goleiros minimos, documentos obrigatorios e inscricao por multiplas equipes em tabelas normalizadas. Somente `approved` aparece no elenco oficial; toda transicao e correcao gera historico e auditoria.

Nao existem assinatura digital oficial, retificacao completa ou portal publico completo. Equipes, atletas, documentos, inscricoes, elenco, grupos, tabela, escalacoes, operacao, classificacao, mata-mata, sumula digital, noticias e Vai e Vem existem na estrutura administrativa; noticias e movimentacoes possuem portal publico basico, enquanto a home publica completa permanece para etapa posterior.
