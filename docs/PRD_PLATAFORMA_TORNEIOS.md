# PRD Simplificado — Plataforma de Gestão de Torneios

## 1. Visão do produto

A plataforma será um sistema web para criar, organizar, operar e publicar campeonatos de futebol.

Ela terá dois ambientes principais:

1. **Painel administrativo**, usado pela organização, treinadores, gestores de equipe, operadores de partida e responsáveis pela prestação de contas.
2. **Portal público**, usado por atletas, familiares, torcedores, patrocinadores e público geral.

A mesma aplicação poderá gerenciar vários campeonatos. Cada campeonato terá seu próprio:

- nome;
- logo;
- cores;
- categoria;
- temporada;
- regulamento;
- equipes;
- atletas inscritos;
- jogos;
- classificação;
- mata-mata;
- estatísticas;
- notícias;
- transferências;
- endereço público por slug.

Exemplos:

```text
/campeonatos/copa-brasil-de-talentos-2026
/campeonatos/copa-serra-sub-15-2026
```

---

## 2. Objetivo principal

Permitir que a organização consiga operar um campeonato completo sem editar banco de dados, informar IDs ou preencher JSON.

O sistema deve permitir:

1. criar o campeonato;
2. configurar seu regulamento;
3. cadastrar equipes;
4. cadastrar atletas e comissão técnica;
5. aprovar inscrições;
6. definir a formação padrão das equipes;
7. criar grupos;
8. gerar rodadas e partidas;
9. montar escalações visualmente em um campo de futebol;
10. registrar o resultado e as informações da partida;
11. homologar resultados;
12. atualizar classificação, cartões e estatísticas;
13. calcular suspensões e próximos confrontos;
14. gerar mata-mata;
15. definir campeão e vice-campeão;
16. gerar súmula em PDF conforme a planilha oficial utilizada pela organização;
17. publicar notícias e movimentações do “Vai e Vem”;
18. publicar as informações no portal público.

---

## 3. Escopo do MVP

O MVP deve entregar os módulos necessários para realizar um campeonato completo e publicar as principais informações para o público.

### Incluído no MVP

- autenticação;
- perfis e permissões;
- múltiplos campeonatos;
- identidade visual por campeonato;
- regulamento configurável;
- equipes;
- atletas;
- comissão técnica;
- responsáveis legais;
- inscrições;
- formação tática da equipe;
- escalação visual em campo;
- grupos;
- rodadas;
- geração da tabela;
- partidas;
- próximos confrontos;
- central da partida simplificada;
- gols;
- assistências;
- cartões;
- substituições;
- homologação;
- classificação;
- suspensões;
- mata-mata;
- campeão e vice-campeão;
- súmula digital;
- PDF da súmula;
- notícias e blog;
- “Vai e Vem” de transferências;
- portal público;
- relatórios básicos;
- tema light e dark.

### Fora do MVP

Os itens abaixo ficam para fases posteriores:

- chat ou notificações em tempo real;
- aplicativo mobile nativo;
- pagamentos;
- integração com federações externas;
- relatórios financeiros;
- recursos disciplinares jurídicos complexos;
- assinatura digital oficial;
- automações com serviços externos;
- transmissão ao vivo;
- métricas avançadas de posse, finalizações e mapas de calor;
- cronologia minuto a minuto da partida.

---

## 4. Perfis de acesso

### 4.1 Superadministrador

Pode:

- gerenciar toda a plataforma;
- criar organizações e projetos;
- criar campeonatos;
- gerenciar usuários;
- visualizar todos os dados;
- configurar permissões.

### 4.2 Organizador

Pode:

- gerenciar campeonatos autorizados;
- cadastrar equipes;
- analisar inscrições;
- criar grupos;
- gerar partidas;
- configurar regulamento;
- homologar resultados;
- gerar mata-mata;
- publicar notícias e transferências;
- baixar súmulas e relatórios.

### 4.3 Gestor ou treinador da equipe

Pode:

- acessar apenas sua equipe;
- cadastrar atletas;
- cadastrar comissão técnica;
- enviar inscrições;
- escolher a formação padrão da equipe;
- montar escalações;
- ajustar os atletas no campo;
- consultar jogos, cartões, suspensões e próximos confrontos.

### 4.4 Operador de partida

Pode:

- acessar somente partidas autorizadas;
- consultar escalações;
- preencher placar e dados da partida;
- registrar gols, assistências, cartões e substituições;
- preencher arbitragem e ocorrências;
- finalizar a súmula e enviar para homologação.

### 4.5 Consulta e prestação de contas

Pode:

- visualizar dados;
- consultar indicadores;
- baixar súmulas;
- baixar relatórios básicos;
- não pode alterar resultados esportivos.

---

## 5. Regras obrigatórias de interface

A interface final não pode expor conceitos técnicos do banco.

### Não permitir

- campo `team_id`;
- campo `tournament_id`;
- campo `project_id`;
- campo `settings_json`;
- digitação manual de IDs;
- edição de JSON;
- edição de nomes de tabelas;
- mega tela com todas as funções misturadas;
- CRUD genérico como interface final.

### Usar

- selects com nomes;
- busca por equipe e atleta;
- formulários específicos;
- cards;
- tabelas claras;
- filtros;
- etapas guiadas;
- páginas separadas por tarefa;
- mensagens de erro simples;
- confirmação para ações críticas;
- visualização do campo de futebol para escalação;
- ações alternativas ao arrastar e soltar, para funcionar também no celular.

---

## 6. Estrutura administrativa

### 6.1 Dashboard

Exibir:

- campeonatos ativos;
- equipes;
- atletas inscritos;
- próximos confrontos;
- últimos resultados;
- partidas aguardando homologação;
- pendências de inscrição;
- atletas pendurados;
- atletas suspensos;
- classificação resumida;
- atalhos principais.

### 6.2 Campeonatos

Permitir:

- criar;
- editar;
- ativar;
- encerrar;
- arquivar.

Campos principais:

- nome;
- slug;
- projeto;
- categoria;
- temporada;
- data inicial;
- data final;
- status;
- logo;
- banner;
- cores;
- tema padrão.

### 6.3 Regulamento configurável

O regulamento será definido separadamente em cada campeonato.

Configurações mínimas:

- quantidade de grupos;
- equipes por grupo;
- classificados por grupo;
- pontos por vitória;
- pontos por empate;
- pontos por derrota;
- ordem dos critérios de desempate;
- quantidade de cartões amarelos que gera suspensão;
- quantidade de partidas da suspensão por acúmulo;
- suspensão por cartão vermelho;
- tratamento do segundo cartão amarelo;
- limpeza de cartões entre fases, quando aplicável;
- manutenção ou não da suspensão já gerada após a limpeza;
- resultado de W.O.;
- quantidade de substituições;
- jogo único ou ida e volta;
- prorrogação;
- disputa de pênaltis;
- fases do mata-mata;
- cruzamentos.

Exemplo de regra configurável:

```text
3 cartões amarelos = suspensão de 1 partida
```

Outro campeonato poderá utilizar:

```text
2 cartões amarelos = suspensão de 1 partida
```

O sistema não pode fixar esse limite diretamente no código.

### 6.4 Equipes

Campos:

- nome;
- nome curto;
- sigla;
- escudo;
- cores;
- cidade;
- responsável;
- telefone;
- e-mail;
- campeonato;
- categoria;
- formação padrão;
- status.

### 6.5 Atletas

Campos:

- nome completo;
- nome esportivo;
- foto;
- data de nascimento;
- posição principal;
- posições secundárias opcionais;
- número;
- pé dominante;
- equipe;
- responsável legal, quando necessário;
- documento;
- contato;
- status.

Posições padronizadas:

- goleiro;
- zagueiro;
- lateral direito;
- lateral esquerdo;
- volante;
- meio-campista central;
- meia ofensivo;
- meia direita;
- meia esquerda;
- ponta direita;
- ponta esquerda;
- atacante.

### 6.6 Comissão técnica

Campos:

- nome;
- foto;
- função;
- equipe;
- telefone;
- e-mail;
- documento;
- status.

### 6.7 Inscrições

Estados:

- rascunho;
- enviada;
- em análise;
- pendente;
- aprovada;
- rejeitada;
- suspensa;
- cancelada.

Somente atletas aprovados podem ser escalados.

### 6.8 Grupos

Permitir:

- criar grupos;
- adicionar equipes;
- mover equipes;
- validar quantidade;
- publicar grupos;
- bloquear alterações após o início.

### 6.9 Tabela e rodadas

Permitir:

- gerar confrontos automaticamente;
- turno único;
- turno e returno;
- tratar folgas;
- editar datas;
- editar horários;
- definir local;
- publicar partidas.

### 6.10 Partidas

Estados mínimos:

- agendada;
- confirmada;
- em andamento;
- encerrada;
- aguardando homologação;
- homologada;
- adiada;
- cancelada;
- W.O.

---

## 7. Formações táticas e escalação visual

### 7.1 Formação padrão da equipe

O treinador escolherá uma formação padrão durante a configuração da equipe.

Essa formação poderá ser alterada em uma partida específica antes da confirmação da escalação.

Formações iniciais disponíveis no MVP:

- 4-4-2;
- 4-3-3;
- 4-2-3-1;
- 4-1-4-1;
- 4-1-2-1-2;
- 4-3-2-1;
- 4-5-1;
- 4-4-1-1;
- 3-5-2;
- 3-4-3;
- 3-4-2-1;
- 5-4-1;
- 5-3-2;
- 5-2-3;
- 5-2-1-2.

A lista deve ser armazenada como configuração e poderá receber novas formações futuramente.

### 7.2 Campo de futebol

A escalação deve ser exibida sobre uma representação visual de um campo de futebol.

Cada atleta escalado deve aparecer como um marcador contendo:

- foto;
- nome esportivo;
- número;
- posição.

### 7.3 Distribuição automática

Ao selecionar os titulares, o sistema deve tentar posicioná-los automaticamente nos espaços da formação escolhida.

Prioridade de posicionamento:

1. posição principal compatível com o espaço;
2. posição secundária compatível;
3. grupo de posição semelhante;
4. qualquer atleta ainda não distribuído.

Exemplo:

- um zagueiro deve ser priorizado em uma vaga de zagueiro;
- um lateral deve ser priorizado em uma lateral;
- um ponta pode ocupar uma faixa ofensiva;
- se a formação tiver três zagueiros e o treinador selecionar laterais para essas vagas, o sistema deve preencher os espaços disponíveis e sinalizar que os atletas estão fora de sua posição principal.

### 7.4 Ajuste manual

Depois da distribuição automática, o treinador poderá:

- arrastar jogadores entre posições;
- selecionar um jogador e escolher outro espaço;
- trocar dois jogadores de lugar;
- remover um atleta dos titulares;
- enviar um atleta para o banco;
- substituir por outro atleta elegível.

O arrastar e soltar não pode ser a única forma de ajuste.

### 7.5 Validações

Antes de confirmar a escalação, validar:

- exatamente um goleiro, salvo regra especial;
- quantidade de titulares;
- atleta inscrito e aprovado;
- atleta pertencente à equipe;
- atleta não suspenso;
- atleta não duplicado;
- número de camisa;
- capitão definido;
- reservas dentro do limite.

---

## 8. Central da partida simplificada

A central da partida deve ser uma página própria, mas não terá cronologia minuto a minuto no MVP.

### Informações exibidas

- equipes;
- escudos;
- placar;
- status;
- data;
- horário;
- local;
- escalações;
- campo com a formação das equipes;
- arbitragem;
- ocorrências.

### Dados preenchidos por atleta

Para cada atleta relacionado, permitir registrar:

- número da camisa;
- quantidade de gols;
- assistência, quando utilizada;
- cartão amarelo;
- cartão vermelho;
- se iniciou como titular;
- se entrou como reserva;
- substituição, sem obrigatoriedade de minuto.

### Dados gerais da partida

- placar final;
- resultado do primeiro tempo, quando informado;
- resultado do segundo tempo, quando informado;
- horário de início e término;
- resultado da disputa de pênaltis;
- observações;
- ocorrências;
- árbitro;
- árbitros assistentes;
- mesário;
- organização responsável.

O placar final deve ser validado contra a soma dos gols registrados, considerando também gol contra e resultado administrativo quando aplicável.

---

## 9. Cartões, suspensões e próximos confrontos

### 9.1 Controle disciplinar

O sistema deve somar os cartões de cada atleta dentro do campeonato.

Deve identificar:

- atleta sem cartões;
- atleta pendurado;
- atleta suspenso;
- motivo da suspensão;
- quantidade de partidas a cumprir;
- partida em que a suspensão foi cumprida;
- data de liberação.

### 9.2 Regra por campeonato

A quantidade de cartões necessária para suspensão será configurada no regulamento.

Ao homologar uma partida, o sistema deve:

1. atualizar a contagem de cartões;
2. verificar se o limite foi atingido;
3. criar a suspensão;
4. bloquear o atleta na próxima escalação aplicável;
5. mostrar o atleta como suspenso para o próximo confronto;
6. registrar o cumprimento da suspensão após a partida correspondente.

### 9.3 Próximos confrontos

O painel e o portal público devem exibir:

- próxima partida;
- data;
- horário;
- local;
- fase;
- rodada;
- equipe adversária;
- atletas suspensos;
- atletas pendurados, quando a informação for pública;
- botão para abrir os detalhes da partida.

---

## 10. Homologação

Antes de homologar, o sistema deve revisar:

- escalações;
- formação;
- placar;
- gols;
- assistências;
- cartões;
- substituições;
- ocorrências;
- arbitragem;
- inconsistências.

Ao homologar:

- atualizar classificação;
- atualizar estatísticas;
- atualizar cartões;
- gerar suspensões;
- atualizar próximos confrontos;
- atualizar mata-mata;
- gerar versão da súmula;
- registrar auditoria.

---

## 11. Classificação

Calcular automaticamente:

- jogos;
- vitórias;
- empates;
- derrotas;
- gols pró;
- gols contra;
- saldo;
- pontos;
- aproveitamento;
- posição.

Os critérios de desempate devem seguir a ordem definida no regulamento.

---

## 12. Mata-mata

O sistema deve:

- gerar quartas de final;
- gerar semifinais;
- gerar final;
- avançar vencedores;
- registrar decisão por pênaltis;
- definir campeão;
- definir vice-campeão.

Os cruzamentos devem ser configuráveis.

Não fixar cruzamentos diretamente no código.

---

## 13. Preset — Copa Brasil de Talentos

Configuração inicial:

- 10 equipes;
- 2 grupos;
- 5 equipes por grupo;
- 4 classificados por grupo;
- 8 classificados;
- quartas de final;
- semifinais;
- final;
- partidas eliminatórias em jogo único.

Cruzamentos iniciais:

```text
1º Grupo A x 4º Grupo B
2º Grupo A x 3º Grupo B
1º Grupo B x 4º Grupo A
2º Grupo B x 3º Grupo A
```

Esses cruzamentos devem ser carregados da configuração do campeonato.

---

## 14. Súmula digital e PDF

A súmula do sistema deve seguir como referência principal a planilha fornecida:

```text
COPA BRASIL DE TALENTOS(1).xlsx
```

A estrutura visual e os campos do PDF devem preservar o modelo atualmente utilizado pela organização.

### 14.1 Cabeçalho

- título “Súmula Oficial”;
- nome do campeonato;
- temporada ou edição;
- equipes;
- placar final;
- horário;
- início e término do primeiro tempo;
- início e término do segundo tempo;
- contagem por período, quando utilizada;
- desempate por pênaltis.

### 14.2 Relação das equipes

As duas equipes devem aparecer lado a lado, como no modelo atual.

Para cada atleta:

- nome;
- número;
- cartão amarelo — AM;
- cartão vermelho — VM;
- gols.

A interface digital poderá utilizar campos numéricos. No PDF, a seção de gols deve reproduzir visualmente a lógica do modelo atual, com marcação compatível com as colunas de gols.

### 14.3 Responsáveis das equipes

Para cada equipe:

- assinatura ou confirmação do técnico;
- auxiliar ou capitão.

### 14.4 Arbitragem e organização

- árbitro;
- primeiro árbitro assistente;
- segundo árbitro assistente;
- mesário;
- organização.

### 14.5 Ocorrências

A planilha atual informa que as ocorrências são registradas no verso.

No sistema, o PDF deve possuir:

- primeira página com a súmula principal;
- segunda página para ocorrências e observações, quando existirem;
- versão da súmula;
- data de geração;
- responsável pela homologação.

### 14.6 Capacidade da relação

O PDF deve comportar a quantidade de atletas prevista no regulamento.

Quando a quantidade ultrapassar o espaço da primeira página, gerar continuação organizada sem quebrar o documento.

---

## 15. Notícias e blog

O MVP terá um módulo de notícias para cada campeonato.

### Painel administrativo

Permitir:

- criar notícia;
- editar;
- salvar rascunho;
- publicar;
- despublicar;
- destacar na home;
- agendar publicação, se simples de implementar.

Campos:

- título;
- slug;
- resumo;
- conteúdo;
- imagem de capa;
- autor;
- campeonato;
- equipe ou partida relacionada, opcional;
- status;
- data de publicação.

### Portal público

Páginas:

- lista de notícias;
- detalhe da notícia;
- notícias em destaque;
- últimas notícias.

---

## 16. “Vai e Vem” de transferências

O MVP terá uma área pública de movimentações de atletas.

### Tipos de movimentação

- transferência definitiva;
- empréstimo;
- retorno de empréstimo;
- liberação;
- nova inscrição;
- troca de equipe dentro do campeonato, quando permitida.

### Dados

- atleta;
- foto;
- equipe anterior;
- nova equipe;
- tipo;
- data;
- campeonato;
- status;
- observação pública opcional.

### Fluxo

1. criar solicitação;
2. analisar regras do campeonato;
3. aprovar ou rejeitar;
4. preservar histórico do vínculo anterior;
5. atualizar a equipe atual quando aprovado;
6. publicar no “Vai e Vem”.

O sistema deve respeitar:

- janela de transferências;
- limite de movimentações;
- situação da inscrição;
- permissões;
- escopo do campeonato.

---

## 17. Portal público

Cada campeonato terá um portal próprio por slug.

### Páginas mínimas

- home;
- jogos;
- detalhe do jogo;
- próximos confrontos;
- classificação;
- grupos;
- mata-mata;
- equipes;
- equipe;
- atletas;
- atleta;
- artilharia;
- assistências;
- cartões;
- suspensões públicas permitidas;
- notícias;
- detalhe da notícia;
- “Vai e Vem”;
- regulamento;
- campeões.

### Home pública

Exibir:

- logo e identidade;
- próximos confrontos;
- últimos resultados;
- classificação resumida;
- mata-mata;
- artilheiros;
- notícias;
- “Vai e Vem”;
- patrocinadores.

### Privacidade

Nunca exibir no portal:

- documentos;
- CPF;
- telefone;
- e-mail;
- endereço;
- responsável legal;
- anexos privados;
- dados sensíveis.

---

## 18. UI/UX — primeira rodada estrutural

Nesta primeira rodada de desenvolvimento, o foco será estrutura, funcionamento e usabilidade.

### Objetivos da primeira rodada

- páginas separadas por tarefa;
- navegação clara;
- formulários amigáveis;
- nenhuma entrada de ID ou JSON;
- fluxo completo do campeonato;
- campo visual de escalação funcional;
- interface básica responsiva;
- estados de erro e sucesso;
- permissões aplicadas;
- dados reais.

### Não priorizar nesta primeira rodada

- acabamento visual premium;
- animações avançadas;
- efeitos decorativos;
- redesign detalhado de cada componente;
- refinamento editorial do portal.

A segunda rodada será dedicada ao layout, design system, temas e identidade visual.

### Fontes

- **Hanken Grotesk** para títulos, placares, números e destaques;
- **Inter** para textos, menus, tabelas, formulários e informações secundárias.

### Direção futura do layout

- interface clean;
- branco, azul e verde como base;
- tema light;
- tema dark;
- personalização por campeonato;
- navegação lateral no painel;
- cards esportivos;
- tabelas claras;
- escudos e fotos visíveis;
- responsividade.

---

## 19. Tecnologia

### Stack obrigatória

- PHP 8.2;
- MySQL;
- HTML;
- CSS;
- JavaScript;
- hospedagem em cPanel.

### Requisitos técnicos

- arquitetura organizada;
- PDO e prepared statements;
- migrations;
- seeds;
- CSRF;
- autenticação;
- permissões;
- escopos;
- logs;
- auditoria;
- uploads seguros;
- arquivos privados fora do diretório público;
- compatibilidade com subdiretório;
- `.env` fora do repositório.

---

## 20. Estrutura mínima de banco

Tabelas ou entidades principais:

- users;
- roles;
- permissions;
- user_scopes;
- projects;
- tournaments;
- tournament_rules;
- categories;
- seasons;
- teams;
- people;
- athletes;
- staff_members;
- guardians;
- registrations;
- tactical_formations;
- formation_slots;
- team_default_formations;
- groups;
- group_teams;
- rounds;
- venues;
- matches;
- lineups;
- lineup_players;
- match_player_stats;
- match_reports;
- match_report_versions;
- standings;
- cards;
- suspensions;
- transfers;
- news_posts;
- files;
- audit_logs.

Os nomes podem ser adaptados à arquitetura escolhida.

---

## 21. Critérios de aceite do MVP

O MVP só pode ser considerado concluído quando for possível:

1. criar um campeonato pela interface;
2. configurar seu regulamento sem JSON;
3. definir a regra de cartões e suspensões;
4. cadastrar dez equipes sem digitar IDs;
5. escolher a formação padrão de cada equipe;
6. cadastrar atletas e comissão;
7. aprovar inscrições;
8. criar dois grupos de cinco equipes;
9. gerar as partidas;
10. montar escalações visualmente no campo;
11. distribuir jogadores automaticamente conforme a formação;
12. ajustar manualmente jogadores fora de posição;
13. registrar placar, gols, assistências, cartões e substituições sem cronologia minuto a minuto;
14. homologar resultados;
15. atualizar classificação automaticamente;
16. gerar suspensões segundo a regra do campeonato;
17. bloquear atleta suspenso no próximo confronto;
18. exibir os próximos confrontos;
19. gerar quartas, semifinais e final;
20. definir campeão e vice-campeão;
21. gerar súmula em PDF seguindo a planilha oficial;
22. publicar notícia no blog;
23. registrar e publicar uma transferência no “Vai e Vem”;
24. visualizar os dados no portal público;
25. impedir acesso fora da permissão e do escopo;
26. funcionar em desktop, tablet e celular;
27. funcionar em subdiretório no cPanel;
28. passar por teste completo sem edição manual no banco.

O acabamento visual premium, os temas finais e o refinamento detalhado de UI serão validados na segunda rodada.

---

## 22. Ordem de desenvolvimento

### Fase 1 — Fundação estrutural

- autenticação;
- permissões;
- escopos;
- multi-campeonato;
- rotas;
- estrutura administrativa básica;
- dashboard funcional.

### Fase 2 — Cadastros

- campeonatos;
- regulamento;
- equipes;
- formações;
- atletas;
- comissão;
- responsáveis;
- inscrições.

### Fase 3 — Competição

- grupos;
- rodadas;
- tabela;
- próximos confrontos;
- classificação;
- cartões;
- suspensões;
- mata-mata.

### Fase 4 — Partida e súmula

- escalação visual;
- distribuição automática no campo;
- central simplificada;
- dados por atleta;
- homologação;
- súmula;
- PDF conforme a planilha.

### Fase 5 — Conteúdo público

- notícias;
- blog;
- “Vai e Vem”;
- páginas públicas;
- jogos;
- classificação;
- equipes;
- atletas;
- mata-mata;
- campeão.

### Fase 6 — Qualidade estrutural

- testes funcionais;
- segurança;
- responsividade básica;
- instalação limpa;
- cPanel;
- auditoria da primeira rodada.

### Segunda rodada — Layout e UI/UX

- design system;
- acabamento visual;
- light e dark mode;
- identidade por campeonato;
- componentes esportivos;
- refinamento mobile;
- acessibilidade visual;
- auditoria final de interface.

---

## 23. Regra de simplicidade

A prioridade é concluir um campeonato completo com boa usabilidade.

Não criar módulos secundários além do que está explicitamente incluído no MVP antes de o fluxo principal estar funcional.

Não considerar uma funcionalidade concluída somente porque existe:

- tabela no banco;
- rota vazia;
- botão sem comportamento;
- tela estática;
- mock;
- array hardcoded;
- teste que apenas procura texto em arquivos.

Uma funcionalidade só está concluída quando possui:

- interface utilizável;
- validação no servidor;
- persistência real;
- permissão;
- teste funcional;
- integração com o restante do campeonato.
