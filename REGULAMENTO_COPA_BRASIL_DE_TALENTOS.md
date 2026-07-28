# REGULAMENTO DA COPA BRASIL DE TALENTOS

> Documento funcional e normativo para implementação no sistema de gestão de torneios.
>
> **Versão:** 1.0  
> **Status:** Regulamento-base configurável  
> **Base esportiva:** Regras do Jogo da IFAB vigentes na temporada 2026/27  
> **Abrangência:** Copa Brasil de Talentos

---

## 1. OBJETIVO DO DOCUMENTO

Este documento define o regulamento-base da **Copa Brasil de Talentos** e os comportamentos que deverão ser aplicados pelo sistema de gerenciamento da competição.

O sistema deverá permitir que as regras sejam configuradas separadamente para cada campeonato, sem necessidade de alterar o código-fonte.

As regras configuráveis deverão possuir:

- valor atual;
- data de início da vigência;
- usuário responsável pela alteração;
- histórico de versões;
- justificativa da alteração;
- bloqueio de alterações sensíveis após o início da competição;
- possibilidade de publicação de uma nova versão do regulamento.

Nenhuma mudança de regra poderá alterar retroativamente partidas já homologadas, salvo decisão administrativa registrada e auditável.

---

## 2. BASE NORMATIVA

As partidas deverão seguir as **Regras do Jogo da IFAB vigentes**, exceto nos pontos organizacionais que podem ser definidos pelo regulamento da competição, como:

- duração das partidas;
- quantidade de jogadores relacionados;
- quantidade e modelo de substituições;
- critérios de classificação e desempate;
- formato da competição;
- regras de inscrição;
- controle de cartões e suspensões;
- procedimentos de W.O.;
- critérios de homologação dos resultados;
- prazos para recursos e retificações.

Em caso de conflito entre este regulamento e uma regra técnica da IFAB que não possa ser modificada pela competição, deverá prevalecer a regra da IFAB.

---

## 3. IDENTIFICAÇÃO DA COMPETIÇÃO

- **Nome:** Copa Brasil de Talentos
- **Modalidade:** Futebol de campo
- **Número de equipes:** 10
- **Número de grupos:** 2
- **Equipes por grupo:** 5
- **Grupos:** Grupo A e Grupo B
- **Classificados por grupo:** 4
- **Total de classificados para o mata-mata:** 8
- **Fases eliminatórias:** Quartas de final, semifinal e final
- **Modelo das eliminatórias:** Jogo único
- **Disputa de terceiro lugar:** Não prevista por padrão

A organização poderá configurar categorias independentes dentro da competição, como Sub-11, Sub-13, Sub-15, Sub-17, adulto, feminino ou outras.

Cada categoria deverá possuir tabela, inscrições, estatísticas, classificação, súmulas e regras próprias.

---

## 4. FORMATO DA COMPETIÇÃO

### 4.1. Fase de grupos

As 10 equipes serão distribuídas em dois grupos de cinco equipes:

- Grupo A: 5 equipes;
- Grupo B: 5 equipes.

Na configuração padrão, as equipes se enfrentarão em turno único dentro do próprio grupo.

Cada equipe disputará quatro partidas na fase de grupos.

Cada grupo terá dez partidas, totalizando vinte partidas na primeira fase.

O sistema deverá permitir configurar:

- turno único;
- turno e returno;
- tabela manual;
- tabela gerada automaticamente;
- partidas em campo neutro;
- restrições de datas, locais e horários.

### 4.2. Classificação para o mata-mata

Classificam-se para as quartas de final as quatro equipes mais bem colocadas de cada grupo.

- Grupo A: 1º, 2º, 3º e 4º colocados;
- Grupo B: 1º, 2º, 3º e 4º colocados.

A equipe que terminar na quinta colocação de cada grupo será eliminada.

### 4.3. Cruzamentos das quartas de final

O cruzamento padrão será:

| Jogo | Confronto |
|---|---|
| QF1 | 1º do Grupo A x 4º do Grupo B |
| QF2 | 2º do Grupo A x 3º do Grupo B |
| QF3 | 1º do Grupo B x 4º do Grupo A |
| QF4 | 2º do Grupo B x 3º do Grupo A |

Os mandos de campo, locais e horários serão definidos pela organização.

O sistema deverá permitir que os cruzamentos sejam alterados antes do início da competição.

### 4.4. Semifinais

O cruzamento padrão será:

| Jogo | Confronto |
|---|---|
| SF1 | Vencedor da QF1 x Vencedor da QF2 |
| SF2 | Vencedor da QF3 x Vencedor da QF4 |

### 4.5. Final

A final será disputada em partida única entre:

- vencedor da SF1;
- vencedor da SF2.

O vencedor será declarado campeão da Copa Brasil de Talentos.

O perdedor será declarado vice-campeão.

### 4.6. Empate nas fases eliminatórias

Na configuração-base desta competição, não haverá prorrogação.

Em caso de empate ao final do tempo regulamentar nas quartas de final, semifinais ou final, a equipe vencedora será definida por disputa de pênaltis, conforme as Regras do Jogo da IFAB.

O sistema deverá permitir configurar, por fase:

- disputa direta de pênaltis;
- prorrogação seguida de pênaltis;
- vantagem esportiva;
- partida de volta;
- gol qualificado, somente quando expressamente permitido pela organização;
- outro critério aprovado e publicado no regulamento.

---

## 5. SISTEMA DE PONTUAÇÃO

Na fase de grupos, será aplicado o seguinte sistema:

- vitória: 3 pontos;
- empate: 1 ponto para cada equipe;
- derrota: 0 ponto;
- vitória por W.O.: 3 pontos e placar administrativo definido neste regulamento.

Não haverá disputa de pênaltis em partidas da fase de grupos.

O sistema deverá calcular automaticamente:

- pontos;
- jogos disputados;
- vitórias;
- empates;
- derrotas;
- gols marcados;
- gols sofridos;
- saldo de gols;
- aproveitamento;
- posição no grupo;
- sequência recente de resultados.

---

## 6. CRITÉRIOS DE DESEMPATE NA FASE DE GRUPOS

Em caso de empate em pontos entre duas ou mais equipes, serão aplicados, sucessivamente, os seguintes critérios:

1. maior número de vitórias;
2. maior saldo de gols;
3. maior número de gols marcados;
4. maior número de pontos obtidos nos confrontos entre as equipes empatadas;
5. maior saldo de gols nos confrontos entre as equipes empatadas;
6. maior número de gols marcados nos confrontos entre as equipes empatadas;
7. menor número de cartões vermelhos;
8. menor número de cartões amarelos;
9. sorteio realizado pela organização.

Quando o empate envolver mais de duas equipes, os critérios de confronto direto deverão ser calculados por uma minitabela composta apenas pelas partidas disputadas entre as equipes empatadas.

Se, após a aplicação de um critério, apenas parte das equipes continuar empatada, a análise deverá reiniciar no primeiro critério entre as equipes que permanecerem empatadas.

O sorteio deverá ser registrado no sistema com:

- data e horário;
- responsáveis presentes;
- método utilizado;
- resultado;
- arquivo, foto, vídeo ou documento comprobatório, quando houver.

---

## 7. DURAÇÃO DAS PARTIDAS

A configuração-base será:

- dois tempos de 45 minutos;
- intervalo de até 15 minutos;
- acréscimos determinados exclusivamente pela arbitragem.

Para categorias de base, a organização poderá estabelecer tempos reduzidos antes do início da competição.

Exemplos de parâmetros configuráveis:

- duração de cada tempo;
- duração do intervalo;
- duração de eventual prorrogação;
- existência de parada técnica;
- existência de pausa para hidratação;
- regras específicas da categoria.

A duração não poderá ser alterada durante uma partida, salvo decisão da arbitragem por motivo de segurança, força maior ou condição excepcional prevista nas Regras do Jogo.

---

## 8. JOGADORES E QUANTIDADE MÍNIMA

Cada partida será disputada por duas equipes, com no máximo 11 jogadores em campo por equipe, sendo um deles o goleiro.

Uma partida não poderá começar ou continuar se uma equipe tiver menos de sete jogadores aptos em campo.

Caso uma equipe fique com menos de sete jogadores por expulsões, abandono voluntário ou outra causa atribuível à própria equipe, a partida poderá ser encerrada pela arbitragem e encaminhada para decisão da organização.

Na configuração-base, a equipe responsável será considerada derrotada por W.O., sem prejuízo de outras sanções.

---

## 9. INSCRIÇÃO DE EQUIPES

Para participar da competição, a equipe deverá possuir cadastro aprovado pela organização.

O cadastro deverá conter, no mínimo:

- nome oficial;
- nome de exibição;
- escudo;
- cidade e estado;
- responsável legal;
- treinador;
- comissão técnica;
- contatos oficiais;
- cores dos uniformes;
- documentos exigidos;
- aceite do regulamento.

A organização poderá definir:

- prazo de inscrição;
- limite de equipes;
- documentos obrigatórios;
- taxa de inscrição, quando houver;
- critérios de aprovação;
- critérios de substituição ou desistência.

---

## 10. INSCRIÇÃO DE ATLETAS

Somente poderão participar atletas regularmente cadastrados, aprovados e vinculados à equipe na competição.

O cadastro do atleta deverá conter, conforme a categoria e a exigência da organização:

- nome completo;
- nome esportivo;
- fotografia;
- data de nascimento;
- documento de identificação;
- CPF, quando aplicável;
- posição;
- número da camisa;
- informações médicas autorizadas;
- termo de responsabilidade;
- autorização do responsável legal, quando menor de idade;
- situação da inscrição.

Cada atleta poderá representar apenas uma equipe na mesma edição e categoria, salvo se o regulamento permitir transferência.

O sistema deverá impedir automaticamente:

- escalação de atleta não inscrito;
- escalação de atleta suspenso;
- escalação de atleta vinculado a outra equipe na mesma categoria;
- escalação fora da faixa etária;
- escalação após encerramento do prazo de inscrição;
- uso de documento duplicado por cadastros diferentes.

---

## 11. ELENCO E RELAÇÃO PARA A PARTIDA

Na configuração-base, cada equipe poderá cadastrar até 30 atletas na competição e relacionar até 26 atletas por partida, sendo:

- até 11 titulares;
- até 15 suplentes.

Esses limites deverão ser configuráveis por campeonato e categoria.

A relação da partida deverá ser enviada no sistema até o prazo definido pela organização.

A súmula deverá identificar:

- titulares;
- suplentes;
- capitão;
- goleiros;
- treinador;
- membros da comissão técnica;
- números das camisas;
- atletas suspensos ou indisponíveis.

Após o início da partida, a relação somente poderá ser corrigida pela organização mediante registro de auditoria e justificativa.

---

## 12. SUBSTITUIÇÕES

Na configuração-base, cada equipe poderá realizar até cinco substituições em até três oportunidades durante o tempo regulamentar.

Substituições realizadas no intervalo não contarão como uma das três oportunidades.

Caso duas ou mais substituições sejam realizadas pela mesma equipe na mesma paralisação, elas contarão como uma única oportunidade.

O sistema deverá permitir configurar:

- quantidade máxima de substituições;
- quantidade máxima de oportunidades;
- quantidade de suplentes relacionados;
- substituição adicional na prorrogação;
- substituição adicional permanente por concussão;
- substituições ilimitadas ou de retorno em categorias autorizadas;
- regras específicas para competições de base.

Toda substituição deverá registrar:

- atleta que saiu;
- atleta que entrou;
- minuto;
- período da partida;
- responsável pelo lançamento;
- eventual correção posterior.

---

## 13. UNIFORMES E EQUIPAMENTOS

Os atletas deverão utilizar os equipamentos obrigatórios previstos nas Regras do Jogo.

Não será permitido o uso de objetos perigosos ou joias durante a partida.

As equipes deverão possuir uniformes com cores suficientemente distintas entre si e da equipe de arbitragem.

Em caso de conflito de cores, a organização ou a arbitragem determinará qual equipe deverá utilizar o uniforme alternativo ou coletes.

Cada atleta deverá utilizar número visível e compatível com o informado na súmula.

O goleiro deverá utilizar cores que o diferenciem dos demais jogadores e da arbitragem.

---

## 14. ARBITRAGEM

A arbitragem será designada pela organização da competição.

As decisões técnicas tomadas pela arbitragem durante a partida deverão seguir as Regras do Jogo e serão soberanas no campo de jogo.

A equipe de arbitragem deverá registrar na súmula:

- resultado;
- gols;
- cartões;
- substituições, quando controladas;
- acréscimos;
- interrupções;
- ocorrências disciplinares;
- incidentes;
- abandono ou suspensão;
- observações relevantes.

O sistema poderá permitir que um operador autorizado auxilie no lançamento dos eventos, mas a homologação dependerá da confirmação da arbitragem ou da organização.

---

## 15. SÚMULA OFICIAL

Cada partida deverá possuir uma súmula digital vinculada ao confronto.

A súmula deverá conter:

- identificação da competição;
- categoria;
- fase;
- rodada;
- grupo;
- data, horário e local;
- equipes e escudos;
- arbitragem;
- relação de atletas;
- comissão técnica;
- escalações;
- substituições;
- gols;
- cartões;
- placar;
- disputa de pênaltis, quando houver;
- ocorrências;
- assinaturas ou confirmações digitais;
- situação da homologação;
- histórico de alterações.

### 15.1. Estados da súmula

A súmula poderá assumir os seguintes estados:

1. rascunho;
2. aguardando escalações;
3. pronta para a partida;
4. em andamento;
5. aguardando conferência;
6. homologada;
7. retificada;
8. anulada.

### 15.2. Homologação

Somente partidas homologadas deverão atualizar definitivamente:

- classificação;
- artilharia;
- cartões acumulados;
- suspensões;
- chaveamento;
- rankings;
- histórico oficial.

Resultados provisórios poderão ser exibidos no portal com identificação clara de que ainda aguardam homologação.

### 15.3. Retificação

Uma súmula homologada somente poderá ser alterada por usuário autorizado.

Toda retificação deverá registrar:

- dado anterior;
- dado novo;
- motivo;
- responsável;
- data e horário;
- documento de suporte, quando necessário.

Após uma retificação, todas as estatísticas derivadas deverão ser recalculadas automaticamente.

---

## 16. GOLS E ESTATÍSTICAS

Cada gol deverá registrar:

- equipe;
- autor;
- minuto;
- período;
- tipo do gol;
- assistência, quando informada;
- indicação de gol contra;
- indicação de cobrança de pênalti;
- indicação de correção ou anulação.

Tipos de gol suportados:

- jogada normal;
- pênalti;
- falta direta;
- gol contra;
- outro tipo definido pela organização.

O sistema deverá atualizar automaticamente:

- placar;
- artilharia;
- assistências;
- gols por equipe;
- saldo de gols;
- classificação;
- estatísticas individuais;
- estatísticas da competição.

A artilharia deverá considerar apenas gols homologados.

Gols marcados em disputa de pênaltis após o término da partida não deverão ser contabilizados na artilharia nem no placar estatístico do tempo regulamentar.

---

## 17. CARTÕES, PENDURADOS E SUSPENSÕES

### 17.1. Cartões amarelos

Na configuração-base, o atleta que acumular três cartões amarelos em partidas diferentes cumprirá suspensão automática de uma partida.

Após o cumprimento da suspensão, o contador será reiniciado.

O sistema deverá identificar automaticamente:

- atletas com um cartão amarelo;
- atletas pendurados com dois cartões amarelos;
- atletas suspensos ao receber o terceiro cartão;
- partida na qual a suspensão deverá ser cumprida;
- situação de cumprimento da suspensão.

### 17.2. Dois cartões amarelos na mesma partida

Dois cartões amarelos recebidos pelo mesmo atleta na mesma partida resultarão em cartão vermelho e expulsão.

Na configuração-base, os dois cartões amarelos que originarem a expulsão não serão adicionados ao controle de acúmulo de cartões amarelos.

A expulsão gerará suspensão automática de pelo menos uma partida.

### 17.3. Cartão vermelho direto

O atleta ou membro da comissão técnica expulso com cartão vermelho direto cumprirá suspensão automática de pelo menos uma partida, sem prejuízo de análise e ampliação da pena pela comissão disciplinar.

### 17.4. Zerar cartões

Na configuração-base, os cartões amarelos não serão zerados durante a competição.

O sistema deverá permitir configurar uma fase para zerar os cartões, preservando suspensões já geradas.

Exemplo de regra configurável:

- zerar cartões após as quartas de final;
- não cancelar suspensão já confirmada;
- manter histórico completo dos cartões anteriores.

### 17.5. Cartões da comissão técnica

Cartões aplicados a membros da comissão técnica deverão ser registrados separadamente e poderão gerar suspensões conforme o regulamento disciplinar.

### 17.6. Cumprimento de suspensão

A suspensão será considerada cumprida apenas em partida oficial da mesma competição e categoria para a qual o atleta estava regularmente inscrito e apto, salvo decisão da organização.

Partidas canceladas, anuladas ou não iniciadas não contarão para cumprimento de suspensão, salvo decisão expressa da organização.

---

## 18. JOGADOR IRREGULAR

Será considerado irregular o atleta que:

- não estiver inscrito;
- estiver suspenso;
- estiver fora da faixa etária;
- utilizar documento de terceiro;
- estiver inscrito por outra equipe na mesma categoria;
- tiver inscrição recusada ou cancelada;
- não constar na relação oficial da partida;
- descumprir requisito obrigatório do regulamento.

Na configuração-base, a utilização comprovada de atleta irregular resultará em:

- derrota administrativa da equipe infratora por 3 a 0;
- manutenção do placar de campo quando ele for mais desfavorável à equipe infratora;
- atribuição de três pontos ao adversário;
- retirada dos pontos obtidos irregularmente;
- possibilidade de sanções adicionais.

Os eventos individuais da partida poderão ser mantidos ou anulados conforme decisão da comissão organizadora.

A decisão deverá ser registrada no sistema e vinculada à partida.

---

## 19. W.O. — AUSÊNCIA OU IMPOSSIBILIDADE DE JOGAR

Será caracterizado W.O. quando uma equipe:

- não comparecer;
- não se apresentar dentro da tolerância definida;
- não possuir o mínimo de sete jogadores aptos;
- se recusar a iniciar ou continuar a partida;
- abandonar a competição ou a partida sem autorização;
- for responsabilizada administrativamente pela impossibilidade de realização do jogo.

### 19.1. Tolerância

A tolerância padrão será de 15 minutos após o horário oficial da partida.

A organização poderá alterar esse prazo por competição.

### 19.2. Placar do W.O.

O placar administrativo padrão será de 3 a 0 para a equipe não infratora.

Se a partida já estiver em andamento e a equipe não infratora possuir vantagem superior, poderá ser mantido o placar de campo, conforme decisão da organização.

### 19.3. Estatísticas do W.O.

Os gols administrativos do W.O. não serão atribuídos a atletas e não contarão para a artilharia.

A partida contará para a classificação e para o total de jogos das equipes.

A organização poderá decidir se a partida contará como participação individual dos atletas relacionados.

### 19.4. W.O. reincidente

A equipe que sofrer dois W.O.s na mesma competição poderá ser eliminada.

Em caso de eliminação, a organização deverá escolher e publicar um dos seguintes tratamentos:

- manutenção de todos os resultados anteriores;
- anulação de todos os resultados da equipe;
- conversão das partidas restantes em W.O.;
- outra solução que preserve a igualdade esportiva.

A regra escolhida deverá ser aplicada de forma uniforme a todas as equipes.

---

## 20. PARTIDA INTERROMPIDA, SUSPENSA OU ABANDONADA

A arbitragem poderá interromper, suspender ou abandonar uma partida por motivos como:

- condições climáticas;
- falta de segurança;
- invasão de campo;
- violência;
- falha de iluminação;
- condições inadequadas do gramado;
- ausência de condições médicas;
- número insuficiente de jogadores;
- força maior.

A organização decidirá, conforme a súmula e os fatos registrados, se a partida será:

- retomada do minuto da interrupção;
- reiniciada integralmente;
- homologada com o placar existente;
- declarada W.O.;
- anulada;
- remarcada.

Quando a responsabilidade pelo abandono for atribuída a uma equipe, ela poderá perder a partida administrativamente, sem prejuízo de outras sanções.

---

## 21. DESISTÊNCIA E ELIMINAÇÃO DE EQUIPE

A desistência deverá ser comunicada formalmente à organização.

A equipe desistente poderá sofrer:

- eliminação;
- perda dos jogos restantes por W.O.;
- anulação de resultados;
- impedimento de participação em futuras edições;
- outras sanções previstas no termo de participação.

A forma de tratamento dos resultados deverá considerar a fase da competição e a igualdade entre os participantes.

Toda decisão deverá ser registrada no sistema.

---

## 22. TRANSFERÊNCIAS E “VAI E VEM”

A competição poderá possuir janela de transferências.

Cada movimentação deverá registrar:

- atleta;
- equipe de origem;
- equipe de destino;
- data da solicitação;
- data da aprovação;
- motivo;
- documentos;
- responsável pela aprovação;
- situação.

Situações possíveis:

- solicitada;
- em análise;
- aprovada;
- recusada;
- cancelada.

O atleta somente poderá atuar pela nova equipe após a aprovação da transferência.

O sistema deverá impedir que o atleta fique elegível simultaneamente por duas equipes da mesma categoria.

A organização poderá configurar:

- data de abertura da janela;
- data de fechamento;
- número máximo de transferências por equipe;
- número máximo de inscrições tardias;
- prazo mínimo para o atleta atuar;
- possibilidade de substituição por lesão;
- proibição de transferência após determinada fase.

As transferências aprovadas poderão ser exibidas na área pública “Vai e Vem”.

---

## 23. CRAQUE DA RODADA E PREMIAÇÕES

A organização poderá escolher um craque da rodada com base em:

- votação popular;
- votação técnica;
- estatísticas;
- indicação da organização;
- modelo misto.

O critério deverá ser publicado previamente.

O sistema deverá impedir múltiplos votos indevidos quando houver votação pública e deverá armazenar o resultado final.

Premiações possíveis:

- campeão;
- vice-campeão;
- artilheiro;
- melhor goleiro;
- melhor jogador;
- craque da rodada;
- equipe mais disciplinada;
- seleção do campeonato;
- outras categorias definidas pela organização.

---

## 24. COMISSÃO DISCIPLINAR

A organização poderá instituir uma comissão disciplinar para analisar fatos não resolvidos automaticamente pelo regulamento.

A comissão poderá analisar:

- expulsões graves;
- agressões;
- ofensas;
- discriminação;
- invasão de campo;
- fraude documental;
- escalação irregular;
- abandono;
- danos ao patrimônio;
- condutas de torcedores vinculadas a uma equipe;
- descumprimento de decisões.

As decisões deverão conter:

- número do processo;
- envolvidos;
- descrição dos fatos;
- documentos e provas;
- decisão;
- sanção;
- duração da sanção;
- data de início;
- possibilidade de recurso;
- responsáveis pela decisão.

---

## 25. RECURSOS E CONTESTAÇÕES

A equipe poderá apresentar recurso ou contestação no prazo padrão de 24 horas após a publicação da súmula ou decisão.

O prazo será configurável por competição.

O recurso deverá conter:

- identificação da equipe;
- partida ou decisão contestada;
- descrição objetiva;
- fundamento;
- documentos e provas;
- responsável pela solicitação.

O sistema deverá registrar:

- protocolo;
- data e horário;
- situação;
- responsável pela análise;
- decisão;
- resposta;
- anexos.

A apresentação de recurso não suspenderá automaticamente a competição, salvo decisão da organização.

---

## 26. HOMOLOGAÇÃO DA CLASSIFICAÇÃO E DO CAMPEÃO

A classificação deverá ser calculada automaticamente, mas sua publicação definitiva dependerá da homologação das partidas.

Ao término da competição, a organização deverá homologar:

- resultados;
- classificação final;
- campeão;
- vice-campeão;
- artilharia;
- premiações;
- suspensões pendentes;
- decisões disciplinares.

Após a homologação final, alterações somente poderão ocorrer mediante processo administrativo registrado.

---

## 27. DADOS OFICIAIS E PRESTAÇÃO DE CONTAS

Serão considerados dados oficiais aqueles originados de:

- súmulas homologadas;
- decisões administrativas;
- decisões disciplinares;
- cadastros aprovados;
- documentos anexados pela organização.

O sistema deverá gerar relatórios com:

- equipes participantes;
- atletas inscritos;
- comissão técnica;
- jogos previstos e realizados;
- resultados;
- classificação;
- gols;
- cartões;
- suspensões;
- locais;
- datas;
- fotografias;
- súmulas em PDF;
- decisões e retificações.

Os documentos deverão preservar histórico e rastreabilidade para prestação de contas ao Ministério ou a outros órgãos responsáveis.

---

## 28. PUBLICAÇÃO NO SITE

O portal público poderá exibir:

- regulamento vigente;
- tabela;
- classificação;
- próximos jogos;
- resultados;
- chaveamento;
- artilharia;
- cartões;
- pendurados;
- suspensos, respeitando regras de privacidade;
- equipes;
- atletas;
- craque da rodada;
- vai e vem;
- notícias;
- galerias;
- documentos públicos.

O sistema deverá identificar claramente informações provisórias e informações homologadas.

Dados pessoais e documentos internos não deverão ser publicados sem autorização.

---

## 29. AUTORIDADE DA ORGANIZAÇÃO

Compete à organização:

- interpretar este regulamento;
- resolver casos omissos;
- organizar tabela e horários;
- homologar resultados;
- aplicar sanções administrativas;
- nomear comissão disciplinar;
- publicar decisões;
- alterar regras antes do início da competição;
- realizar ajustes emergenciais devidamente justificados.

Casos omissos deverão ser decididos com base em:

1. Regras do Jogo da IFAB;
2. princípios de igualdade esportiva;
3. segurança dos participantes;
4. integridade da competição;
5. decisões oficiais da organização.

---

## 30. REGRAS DE CONFIGURAÇÃO NO SISTEMA

Cada campeonato deverá possuir uma configuração própria e independente.

### 30.1. Parâmetros obrigatórios

Antes de publicar um campeonato, o sistema deverá exigir a definição de:

- formato da competição;
- número de grupos;
- quantidade de equipes por grupo;
- quantidade de classificados;
- modelo de confrontos;
- quantidade de partidas por eliminatória;
- duração dos jogos;
- intervalo;
- quantidade de substituições;
- oportunidades de substituição;
- limite de atletas inscritos;
- limite de atletas relacionados;
- pontuação por resultado;
- critérios de desempate;
- regra de cartões;
- regra de suspensão;
- regra de W.O.;
- tolerância de atraso;
- modelo de decisão em empate eliminatório;
- existência de prorrogação;
- regras de transferência;
- prazo de recursos;
- regras de homologação.

### 30.2. Bloqueio após o início

Após a primeira partida iniciada, os seguintes campos deverão ficar bloqueados por padrão:

- formato da competição;
- grupos;
- pontuação;
- classificados;
- critérios de desempate;
- cruzamentos;
- regras de cartões;
- regras de W.O.;
- modelo das eliminatórias.

Alterações excepcionais exigirão permissão de superadministrador, justificativa e criação de nova versão do regulamento.

### 30.3. Reprocessamento

Quando uma alteração autorizada afetar cálculos esportivos, o sistema deverá:

1. criar uma versão da configuração anterior;
2. registrar o responsável;
3. recalcular partidas e estatísticas afetadas;
4. mostrar uma prévia das mudanças;
5. exigir confirmação;
6. publicar a nova classificação;
7. manter histórico consultável.

---

## 31. CONFIGURAÇÃO-BASE PARA O CODEX

O bloco abaixo representa a configuração inicial da Copa Brasil de Talentos. Ele é uma referência funcional e poderá ser convertido para JSON, array PHP, tabela MySQL ou formulário administrativo.

```yaml
competition:
  name: "Copa Brasil de Talentos"
  slug: "copa-brasil-de-talentos"
  sport: "football"
  rules_reference: "IFAB 2026/27"
  status: "draft"

format:
  teams_total: 10
  group_stage_enabled: true
  groups_count: 2
  teams_per_group: 5
  group_names:
    - "Grupo A"
    - "Grupo B"
  group_round_robin: "single"
  qualifiers_per_group: 4
  qualified_teams_total: 8

points:
  win: 3
  draw: 1
  loss: 0

standings_tiebreakers:
  - "points"
  - "wins"
  - "goal_difference"
  - "goals_for"
  - "head_to_head_points"
  - "head_to_head_goal_difference"
  - "head_to_head_goals_for"
  - "fewest_red_cards"
  - "fewest_yellow_cards"
  - "draw"

knockout:
  enabled: true
  default_legs: 1
  stages:
    quarterfinal:
      enabled: true
      legs: 1
      extra_time: false
      tie_decision: "penalties"
    semifinal:
      enabled: true
      legs: 1
      extra_time: false
      tie_decision: "penalties"
    final:
      enabled: true
      legs: 1
      extra_time: false
      tie_decision: "penalties"
    third_place:
      enabled: false

quarterfinal_pairings:
  - "A1-B4"
  - "A2-B3"
  - "B1-A4"
  - "B2-A3"

semifinal_pairings:
  - "QF1-QF2"
  - "QF3-QF4"

match:
  players_on_field: 11
  minimum_players: 7
  half_duration_minutes: 45
  halftime_minutes: 15
  stoppage_time_controlled_by_referee: true
  knockout_draw_allowed: false

roster:
  max_registered_players: 30
  max_match_squad: 26
  max_starters: 11
  max_named_substitutes: 15
  one_team_per_player_per_category: true

substitutions:
  max_used: 5
  max_opportunities: 3
  halftime_counts_as_opportunity: false
  return_substitutions: false
  extra_substitution_in_extra_time: false
  concussion_substitution_enabled: false

cards:
  yellow_cards_for_suspension: 3
  automatic_red_suspension_matches: 1
  second_yellow_suspension_matches: 1
  yellows_from_second_yellow_red_count_in_accumulation: false
  reset_stage: null
  preserve_generated_suspensions_on_reset: true

walkover:
  tolerance_minutes: 15
  score_for_winner: 3
  score_for_loser: 0
  goals_count_for_top_scorer: false
  repeated_walkovers_for_disqualification: 2

appeals:
  deadline_hours: 24
  suspensive_effect: false

publication:
  show_provisional_results: true
  provisional_label_required: true
  only_homologated_data_is_official: true

versioning:
  lock_sensitive_rules_after_first_match: true
  require_reason_for_change: true
  keep_full_history: true
  recalculate_impacted_statistics: true
```

---

## 32. REGRAS QUE DEVEM SER PERSONALIZÁVEIS EM OUTROS CAMPEONATOS

O mesmo sistema deverá permitir criar campeonatos diferentes, alterando apenas configurações como:

- pontos corridos;
- grupos e mata-mata;
- mata-mata desde a primeira fase;
- turno único;
- turno e returno;
- melhor de duas partidas;
- final em jogo único;
- final em ida e volta;
- número variável de equipes;
- melhores terceiros colocados;
- classificação por índice técnico;
- vantagem de empate;
- prorrogação;
- pênaltis;
- duração reduzida;
- substituições ilimitadas;
- regras específicas de categorias de base;
- quantidade diferente de cartões para suspensão;
- limpeza de cartões por fase;
- critérios de desempate personalizados;
- janela de transferências;
- limite de inscrições;
- W.O. com placar diferente;
- disputa de terceiro lugar.

Nenhum desses formatos deverá depender de alteração manual no código-fonte.

---

## 33. DISPOSIÇÕES FINAIS

A inscrição e participação na competição representarão concordância com este regulamento.

A organização deverá publicar a versão vigente antes do início da competição.

Equipes, treinadores, atletas e membros da comissão técnica serão responsáveis por acompanhar:

- regulamento;
- tabela;
- decisões;
- suspensões;
- comunicados oficiais.

Este documento deverá ser revisado pela organização antes da publicação oficial, especialmente nos pontos relacionados a:

- categorias e faixas etárias;
- duração das partidas;
- quantidade de atletas;
- substituições;
- documentos obrigatórios;
- transferências;
- sanções disciplinares;
- prazos administrativos;
- exigências do Ministério.

---

**Fim do regulamento.**
