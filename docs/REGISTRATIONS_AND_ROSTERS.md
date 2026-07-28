# Inscricoes e Elenco Oficial

## Escopo

Etapa 6 cobre inscricao de atleta por equipe e campeonato, analise, correcoes, decisoes e consulta do elenco oficial. Nao cobre grupos, partidas, escalacoes, cartoes, classificacao ou portal publico.

O cadastro do atleta continua independente de inscricao. A inscricao referencia campeonato, equipe, atleta e categoria, sem solicitar IDs manualmente: a interface usa selects por nome.

## Modelo

`athlete_registrations` armazena campeonato, equipe, atleta, categoria, numero pretendido, datas, status, pendencias, motivo de rejeicao, analista, observacoes e timestamps. A chave de negocio impede duas inscricoes do mesmo atleta na mesma equipe e campeonato.

`athlete_registration_history` registra criacao, envio, analise, pendencia, correcao, aprovacao, rejeicao, suspensao e cancelamento. Auditoria tambem registra eventos de negocio.

`regulation_roster_settings` configura `minimum_roster_size`, `maximum_roster_size`, `minimum_goalkeepers` e `allow_multiple_team_registration`. `regulation_required_documents` relaciona tipos de documento obrigatorios a uma versao de regulamento. Nenhuma regra de elenco fica fixada apenas no codigo e formularios nao usam JSON.

## Fluxo

1. Treinador cria rascunho.
2. Treinador envia.
3. Organizador inicia analise.
4. Organizador aprova, rejeita ou solicita correcao.
5. Treinador corrige pendencias e reenvia.
6. Sistema registra cada transicao e decisao.
7. Aprovacao torna a inscricao parte do elenco oficial.

Estados: `draft`, `submitted`, `under_review`, `pending_correction`, `approved`, `rejected`, `suspended`, `cancelled`.

Somente inscricoes `approved` entram no elenco oficial. A Etapa 8 podera usar essa consulta para escalacoes, sem permitir atletas pendentes ou rejeitados.

## Validacoes no servidor

- campeonato em status `registration` ou `configured`;
- periodo de inscricao inclusivo, conforme datas do campeonato;
- equipe ativa e pertencente ao campeonato;
- atleta ativo, pertencente a equipe e dentro do escopo do usuario;
- categoria por idade calculada com aniversario e regra de genero;
- numero entre 1 e 99, sem duplicidade na equipe;
- duplicidade de inscricao por outra equipe bloqueada quando regulamento nao permite;
- documentos obrigatorios aprovados e dentro da validade;
- limite maximo de elenco e minimos avaliados conforme regulamento;
- aprovacao apenas em `under_review`;
- CSRF e autorizacao aplicados em todas as alteracoes.

Falta de inscricao nao bloqueia cadastro do atleta. Documento e dados de responsavel permanecem privados e nao sao servidos por rotas publicas.

## Escopo e paginas

Administrador acessa tudo. Organizador analisa somente campeonamentos autorizados. Treinador gerencia somente propria equipe e nunca aprova. Operador e comunicacao recebem `403`.

O painel possui central por status, filtros por campeonato/equipe/status, formulario, detalhe com historico e central de elenco oficial. A pagina do atleta continua reservando inscricoes, partidas e disciplina como modulos futuros.

## Seed e testes

`RegistrationSeed` cria dez inscricoes ficticias em estados diferentes, configura regras de elenco e exige autorizacao do responsavel. Execucao repetida e idempotente.

Testes cobrem migration, seed duplo, fluxo completo, correcoes, prazo fechado, idade, documento ausente/vencido, limite, duplicidade, escopo, IDOR, CSRF, historico, elenco aprovado, PHP lint e `APP_BASE_PATH=/copa-online`.

Etapa 7 fica reservada para grupos, rodadas e tabela.
