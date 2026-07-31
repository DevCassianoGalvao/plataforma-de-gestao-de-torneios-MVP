# Arquitetura do MVP

## Stack e fluxo

- PHP 8.2 sem framework obrigatório.
- MySQL via PDO com prepared statements.
- MVC simples, autoload PSR-4 mínimo e front controller em `public/index.php`.
- Rotas declaradas em `routes/web.php` e URLs montadas por `Config::url`.
- `APP_BASE_PATH` continua compatível com `/torneio-online`.
- Views PHP estruturadas por tarefa; o portal público por campeonato usa read models explicitos e a UI/UX definitiva fica para a Etapa 17.

## Escopo por campeonato

Os papeis continuam globais, mas campeonamentos agora possuem `championship_user_assignments`. Administradores acessam todos os campeonatos. Organizadores precisam de permissão esportiva e vínculo `organizer` para listar, abrir ou alterar um campeonato. A busca já aplica o vínculo no SQL, antes de exibir dados.

Treinadores e gestores recebem escopo explícito por equipe quando possuem vínculo ativo. Operadores e comunicação continuam sem acesso administrativo a equipes, salvo permissão de visualização adicionada futuramente.

## Equipes e comissão

`TeamRepository` aplica o escopo na consulta: administrador vê todas as equipes, organizador vê apenas equipes de campeonamentos aos quais está vinculado e treinador/gestor vê somente equipes com vínculo ativo. `TeamAccessService` centraliza essas decisões; as views recebem capacidades já calculadas e não verificam perfis diretamente.

`TeamStatusService` valida transições entre `draft`, `active`, `inactive`, `withdrawn` e `archived`. Responsáveis são registrados em `team_user_assignments` com início, fim e histórico; um treinador pode ter mais de uma equipe porque o acesso é concedido por vínculo explícito. A comissão usa `staff_roles` e `team_staff`, permitindo membros sem login.

Escudos de equipes e fotos de comissão passam pelo `StorageService`, ficam fora da área pública, usam nome aleatório e são servidos somente depois de autorização. O campo `document_number` existe na estrutura para uma futura implementação protegida, mas não e coletado nem armazenado nesta etapa.

## Atletas, responsáveis e documentos

`AthleteAccessService` e `AthleteRepository` aplicam o mesmo escopo no SQL: administrador vê todos, organizador vê equipes de campeonamentos autorizados e treinador/gestor vê somente equipes com vínculo ativo. Operador e comunicação recebem `403`. O cadastro do atleta não depende de inscrição.

`AthleteRules` calcula idade por aniversário, valida categoria e gênero contra a equipe/campeonato, limita posições ao catálogo ativo, impede duplicidade suficiente por equipe, nome e nascimento e exige responsável para menor. A exclusão e lógica por `deleted_at` e status `archived`.

Dados de documento do responsável são cifrados com AES-256-GCM em `SensitiveData`; a interface exibe apenas uma mascara. Documentos usam `UploadRules` com MIME real via `finfo`, extensao coerente, limite de 10 MB e bloqueio de executáveis. O `StorageService` mantém os arquivos fora da área pública; cada leitura exige autenticação, permissão e escopo do atleta.

## Formações táticas

`TacticalFormationRepository` e `TacticalFormationService` carregam formações e slots estruturados. Cada formação ativa possui exatamente 11 slots, um goleiro e coordenadas normalizadas de 0 a 100. A equipe guarda uma formação padrão e registra autor e data da alteração.

`LineupRepository`, `LineupAccessService` e `LineupService` persistem uma escalação por partida e equipe, com titulares, reservas, comissão presente, capitão, goleiro, status, versão e histórico. A sugestao automática usa posição principal, secundária e grupo posicional; a incompatibilidade gera aviso, mas não impede o ajuste manual. Confirmação exige onze titulares, capitão titular e goleiro válido. Operadores veem apenas confirmadas e dados privados não saem por rotas públicas.

## Campeonamentos e regulamentos

`ChampionshipRepository` concentra cadastro e filtros. `ChampionshipAccessService` valida permissão, vínculo e status. `ChampionshipStatusService` limita transições e impede `configured`/`in_progress` sem regulamento publicado.

`RegulationService` mantém configuração estruturada em tabelas especificas. Uma publicação transforma o rascunho em versão ativa e marca a anterior como `superseded`. Alterações posteriores criam nova versão; nenhuma versão publicada e sobrescrita silenciosamente.

O campo `metadata` de auditoria continua sendo JSON por conter atributos pequenos e variáveis por evento. Ele nunca e usado como formulário e não e exibido bruto.

## Grupos, rodadas e agenda

`ScheduleRepository` separa fases, grupos, vínculos de equipes, rodadas, locais e partidas. `RoundRobinGenerator` usa algoritmo de círculo, remove folgas de grupos ímpares e gera segundo turno com mandante/visitante invertidos. `ScheduleService` valida limites, conflito de equipe/local, transições de status, bloqueio após início e gera `fixture_key` para idempotência.

O assistente recebe grupos, turno, período, dias, horários e locais em campos estruturados. A prévia mostra confrontos e conflitos antes da confirmação. Alterações de agenda entram em `match_schedule_changes`; decisões administrativas entram em `administrative_decisions`. O escopo de leitura de partidas e administrador, organizador vinculado ou treinador com vínculo ativo a uma das equipes.

## Classificação e mata-mata

`StandingsRepository` le somente partidas homologadas, eventos válidos, resultados administrativos e dados disciplinares necessários aos desempates. `StandingsService` calcula cada grupo dentro de transação, grava snapshot e hash de fonte e ordena pelos critérios habilitados do regulamento. Confronto direto usa mini-tabela dos times empatados; o último desempate e deterministico para permitir reprodução.

`knockout_rounds` e `knockout_ties` representam a chave sem misturar a operação da partida. A geração usa fontes A1/B4 etc. e chaves de fixture para não duplicar partidas. Apenas uma partida homologada pode promover vencedor; resultado administrativo e pênaltis definem a tie sem entrar nos gols da classificação. A final grava campeão e vice em `competition_results`.

## Súmula digital

`MatchReportRepository` monta payload de partida homologada a partir das tabelas reais de operação, escalação, arbitragem e campeonato. `MatchReportHtmlRenderer` preserva a organização estrutural da planilha em preview; `MatchReportPdf` desenha A4 com tabela de duas equipes, página principal e verso de ocorrências. `MatchReportService` calcula hash da fonte, grava arquivo privado e cria versão imutável; nova fonte cria nova versão sem alterar as anteriores.

`MatchReportAccessService` reaproveita escopo da central de partida para bloquear IDOR. Downloads usam `StorageService`, nunca caminho público. Pacotes ZIP são compostos apenas de versoes atuais autorizadas.

## Notícias e blog

`NewsRepository` separa consulta editorial de consulta pública. `NewsAccessService` exige `content.manage`/`content.publish` e valida atribuição de comunicação ou organizador ao campeonato. `NewsService` controla status, slug, publicação, exclusão lógica e auditoria. `NewsImageService` usa a otimização central: valida MIME real, corrige EXIF, reduz proporcionalmente e grava WebP; o portal só resolve capas por notícias publicadas.

## Operação e homologação

MatchOperationRepository concentra a operação, eventos, substituições, arbitragem, placar derivado e histórico. MatchOperationService valida os registros contra as duas escalações confirmadas e contra as regras de substituição do regulamento. MatchOperationAccessService separa administrador, organizador, operador atribuido, treinador por equipe e perfis negados.

A operação usa open, awaiting_homologation e homologated. O operador finaliza com checklist; o organizador homologa em ação separada. Eventos de gol e gol contra calculam o placar normal; pênaltis ficam separados e resultado administrativo possui precedência explícita. Não ha retificação avançada nem acumulação completa de suspensões nesta etapa.

## Uploads

`StorageService` grava arquivos fora de `public/storage/private`, com nome aleatório, MIME detectado por `finfo`, limite de tamanho e extensoes derivadas do MIME. `storeOptimizedImage` trata todos os assets visuais, limita a 12 MP, respeita orientação EXIF, redimensiona sem corte e converte para WebP. SVG não e aceito nesta etapa. Assets de identidade e PDFs de regulamento só são servidos depois de autenticação e autorização do campeonato.

## Limites atuais

Inscrições usam `RegistrationAccessService`, `RegistrationRules` e `RegistrationService`. O repositório aplica escopo por administrador, organizador vinculado ou equipe do treinador. O regulamento define tamanho de elenco, goleiros mínimos, documentos obrigatórios e inscrição por múltiplas equipes em tabelas normalizadas. Somente `approved` aparece no elenco oficial; toda transição e correção gera histórico e auditoria.

Não existem assinatura digital oficial, retificação completa ou portal público completo. Equipes, atletas, documentos, inscrições, elenco, grupos, tabela, escalações, operação, classificação, mata-mata, súmula digital, notícias e Vai e Vem existem na estrutura administrativa; notícias e movimentações possuem portal público básico, enquanto a home pública completa permanece para etapa posterior.
