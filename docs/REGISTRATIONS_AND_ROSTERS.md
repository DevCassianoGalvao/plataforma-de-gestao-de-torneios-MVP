# Inscrições e Elenco Oficial

## Escopo

Etapa 6 cobre inscrição de atleta por equipe e campeonato, análise, correções, decisões e consulta do elenco oficial. Não cobre grupos, partidas, escalações, cartões, classificação ou portal público.

O cadastro do atleta continua independente de inscrição. A inscrição referência campeonato, equipe, atleta e categoria, sem solicitar IDs manualmente: a interface usa selects por nome.

## Modelo

`athlete_registrations` armazena campeonato, equipe, atleta, categoria, número pretendido, datas, status, pendências, motivo de rejeição, analista, observações e timestamps. A chave de negócio impede duas inscrições do mesmo atleta na mesma equipe e campeonato.

`athlete_registration_history` registra criação, envio, análise, pendência, correção, aprovação, rejeição, suspensão e cancelamento. Auditoria também registra eventos de negócio.

`regulation_roster_settings` configura `minimum_roster_size`, `maximum_roster_size`, `minimum_goalkeepers` e `allow_multiple_team_registration`. `regulation_required_documents` relaciona tipos de documento obrigatórios a uma versão de regulamento. Nenhuma regra de elenco fica fixada apenas no código e formulários não usam JSON.

## Fluxo

1. Treinador cria rascunho.
2. Treinador envia.
3. Organizador inicia análise.
4. Organizador aprova, rejeita ou solicita correção.
5. Treinador corrige pendências e reenvia.
6. Sistema registra cada transição e decisão.
7. Aprovação torna a inscrição parte do elenco oficial.

Estados: `draft`, `submitted`, `under_review`, `pending_correction`, `approved`, `rejected`, `suspended`, `cancelled`.

Somente inscrições `approved` entram no elenco oficial. A Etapa 8 poderá usar essa consulta para escalações, sem permitir atletas pendentes ou rejeitados.

## Validações no servidor

- campeonato em status `registration` ou `configured`;
- período de inscrição inclusivo, conforme datas do campeonato;
- equipe ativa e pertencente ao campeonato;
- atleta ativo, pertencente a equipe e dentro do escopo do usuário;
- categoria por idade calculada com aniversário e regra de gênero;
- número entre 1 e 99, sem duplicidade na equipe;
- duplicidade de inscrição por outra equipe bloqueada quando regulamento não permite;
- documentos obrigatórios aprovados e dentro da validade;
- limite máximo de elenco e mínimos avaliados conforme regulamento;
- aprovação apenas em `under_review`;
- CSRF e autorização aplicados em todas as alterações.

Falta de inscrição não bloqueia cadastro do atleta. Documento e dados de responsável permanecem privados e não são servidos por rotas públicas.

## Escopo e páginas

Administrador acessa tudo. Organizador analisa somente campeonamentos autorizados. Treinador gerência somente própria equipe e nunca aprova. Operador e comunicação recebem `403`.

O painel possui central por status, filtros por campeonato/equipe/status, formulário, detalhe com histórico e central de elenco oficial. A página do atleta oferece atalhos para inscrições, partidas e disciplina, que usam as mesmas regras de escopo do painel.

## Seed e testes

`RegistrationSeed` cria dez inscrições ficticias em estados diferentes, configura regras de elenco e exige autorização do responsável. Execução repetida é idempotente.

Testes cobrem migration, seed duplo, fluxo completo, correções, prazo fechado, idade, documento ausente/vencido, limite, duplicidade, escopo, IDOR, CSRF, histórico, elenco aprovado, PHP lint e `APP_BASE_PATH=/torneio-online`.

Etapa 7 fica reservada para grupos, rodadas e tabela.
