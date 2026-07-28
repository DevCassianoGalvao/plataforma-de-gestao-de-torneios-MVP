# PRD — Plataforma de Gestão de Torneios de Futebol

**Versão:** 1.0  
**Status:** Documento-base para planejamento, prototipação e desenvolvimento  
**Data:** 27/07/2026  
**Responsável pelo projeto:** Cassiano Galvão  
**Stack definida:** HTML, CSS, JavaScript, PHP e MySQL em hospedagem cPanel  
**Fontes:** Bricolage Grotesk para títulos e destaques; Inter para textos, tabelas e interface

---

## 1. Resumo executivo

A Plataforma de Gestão de Torneios será um sistema web completo para criar, operar, divulgar e prestar contas de campeonatos de futebol.

O produto terá dois grandes ambientes integrados:

1. **Portal público de cada campeonato**, com identidade visual própria, classificação, partidas, resultados, mata-mata, estatísticas, equipes, jogadores, artilharia, cartões, craque da rodada, notícias, galerias e vai e vem do mercado.
2. **Sistema administrativo**, utilizado por organizadores, treinadores, operadores de partida, equipe de comunicação e responsáveis pela prestação de contas.

A proposta é utilizar **uma única base de código**, mas permitir que cada campeonato funcione visualmente como um site próprio. Cada torneio poderá ter:

- logo;
- nome;
- slug ou subdomínio;
- cores primária, secundária e de destaque;
- imagens de capa;
- patrocinadores;
- regulamento;
- configurações esportivas;
- conteúdo e dados independentes.

A interface manterá uma base visual consistente, clean e responsiva, com temas **light e dark**, predominância de branco, azul e verde e possibilidade de adaptação controlada às cores de cada competição.

---

## 2. Contexto e problema

Atualmente, informações importantes do campeonato são controladas por planilhas e documentos separados. A planilha enviada como referência possui três áreas principais:

- súmula da partida;
- cadastro de atletas;
- artilharia, cartões, suspensões e ocorrências.

Ela também contém dados pessoais como CPF, data de nascimento e WhatsApp. Isso mostra que o sistema precisará unir gestão esportiva, controle documental, segurança de dados e prestação de contas.

Os principais problemas a resolver são:

- retrabalho ao lançar a mesma informação em vários lugares;
- risco de erro na classificação, artilharia e controle de cartões;
- dificuldade para acompanhar atletas pendurados e suspensos;
- dificuldade para montar e armazenar súmulas;
- falta de histórico confiável das alterações;
- dificuldade para reunir documentos e fotografias para o Ministério;
- ausência de uma central pública moderna para divulgar o torneio;
- dependência de planilhas com marcações manuais como `x`, `xx` e campos fixos;
- risco de exposição indevida de dados pessoais dos participantes.

---

## 3. Visão do produto

Criar uma plataforma centralizada em que a organização consiga administrar toda a competição e, a partir dos mesmos dados, atualizar automaticamente o site público e gerar os documentos necessários para prestação de contas.

### 3.1 Proposta de valor

- Uma única fonte de dados para toda a competição.
- Classificação e rankings calculados automaticamente.
- Súmula digital preenchida com dados já cadastrados.
- Controle automático de cartões, pendurados e suspensões.
- Site público atualizado rapidamente após cada partida.
- Relatórios e pacotes de documentos prontos para prestação de contas.
- Estrutura reutilizável para vários projetos, copas e categorias.
- Identidade visual personalizada por campeonato sem duplicar o sistema.

### 3.2 Princípio central

O sistema deve ser orientado a eventos da partida.

Em vez de registrar apenas números finais em campos isolados, o operador registrará acontecimentos como:

- gol;
- assistência;
- cartão amarelo;
- cartão vermelho;
- substituição;
- gol contra;
- pênalti convertido;
- pênalti perdido;
- início e fim dos períodos;
- ocorrências disciplinares.

A partir desses eventos, o sistema atualiza placar, classificação, artilharia, cartões, suspensões, timeline e súmula.

---

## 4. Objetivos do produto

### 4.1 Objetivos principais

1. Digitalizar integralmente a operação dos torneios.
2. Reduzir erros e trabalho manual.
3. Publicar resultados e estatísticas com rapidez.
4. Garantir rastreabilidade das informações oficiais.
5. Facilitar a prestação de contas para o Ministério.
6. Permitir a criação de novos campeonatos sem desenvolver um novo sistema.
7. Proteger dados pessoais de atletas, responsáveis e profissionais.

### 4.2 Indicadores de sucesso

- 100% das partidas cadastradas no sistema.
- 100% das partidas concluídas com súmula digital ou PDF anexado.
- Resultado publicado em até 5 minutos após a finalização da súmula.
- Classificação atualizada automaticamente, sem cálculo manual em situações normais.
- Alertas de suspensão gerados automaticamente.
- Redução significativa do uso de planilhas paralelas.
- Relatório de prestação de contas gerado em poucos cliques.
- Nenhum dado sensível exibido no portal público.
- Histórico de auditoria disponível para alterações críticas.

---

## 5. Estrutura organizacional do sistema

A plataforma será multi-campeonato e deverá seguir a seguinte hierarquia:

```text
Organização
└── Projeto esportivo
    ├── Campeonato A
    ├── Campeonato B
    └── Campeonato C
```

### 5.1 Organização

Entidade responsável pela plataforma e pelos usuários administrativos.

Exemplo:

```text
Organização responsável pelos projetos esportivos
```

### 5.2 Projeto esportivo

Agrupa uma iniciativa, contrato, convênio ou conjunto de ações que pode possuir um ou mais torneios.

Exemplo:

```text
Projeto Brasil de Talentos
```

### 5.3 Campeonato

É a unidade esportiva e pública principal. Cada campeonato terá seu próprio site, participantes, jogos, estatísticas, documentos e configurações.

Exemplos:

```text
Copa Brasil de Talentos Sub-15 2026
Copa Brasil de Talentos Sub-17 2026
Torneio de Verão 2027
```

### 5.4 Independência dos campeonatos

Todos os registros esportivos deverão ser vinculados ao campeonato correto. Um dado de um torneio não poderá aparecer em outro.

Cada campeonato terá:

- identidade visual;
- equipes inscritas;
- atletas inscritos;
- comissão técnica;
- formato esportivo;
- regulamento;
- fases;
- grupos;
- rodadas;
- partidas;
- estatísticas;
- notícias;
- galerias;
- documentos;
- usuários autorizados;
- relatórios.

---

## 6. Premissa da Copa Brasil de Talentos

A configuração inicial informada é:

- 10 equipes;
- 2 grupos com 5 equipes;
- fase de grupos;
- 8 classificados;
- quartas de final;
- semifinal;
- final em jogo único.

### 6.1 Premissa provisória

O documento considera, provisoriamente, que os quatro melhores de cada grupo avançam às quartas de final.

Essa regra deverá ser confirmada antes da implementação definitiva.

### 6.2 Estrutura esperada

```text
Grupo A: 5 equipes
Grupo B: 5 equipes

Classificam-se 4 equipes de cada grupo

Quartas de final: 8 equipes
Semifinais: 4 equipes
Final: 2 equipes
Campeão
```

### 6.3 Regras configuráveis

O sistema não deverá deixar essas regras fixas no código. A organização deverá configurar:

- quantidade de grupos;
- quantidade de equipes por grupo;
- número de classificados;
- pontos por vitória, empate e derrota;
- ordem dos critérios de desempate;
- cruzamento do mata-mata;
- jogo único ou ida e volta;
- decisão por pênaltis;
- regra para W.O.;
- limpeza ou manutenção de cartões entre fases;
- quantidade de amarelos para suspensão.

---

## 7. Escopo do MVP

O MVP deverá permitir operar um campeonato completo do início ao fim.

### 7.1 Incluído no MVP

- autenticação e recuperação de senha;
- controle de usuários, perfis e permissões;
- criação de projetos e campeonatos;
- personalização visual por campeonato;
- cadastro de equipes;
- cadastro de atletas e comissão técnica;
- upload de escudos, fotos e documentos;
- inscrição e aprovação de atletas;
- importação inicial de planilha;
- criação de grupos, fases, rodadas e partidas;
- tabela da fase de grupos;
- chaveamento de mata-mata;
- página de informações da partida;
- súmula digital;
- registro de gols, cartões, substituições e ocorrências;
- placar e timeline;
- cálculo automático da classificação;
- artilharia;
- controle de cartões e suspensões;
- próximos confrontos e últimos resultados;
- página de equipes e atletas;
- notícias;
- craque da rodada;
- vai e vem do mercado;
- galerias e fotografias das partidas;
- geração de PDF da súmula;
- relatórios e downloads para prestação de contas;
- trilha de auditoria;
- temas light e dark;
- design responsivo;
- implantação em cPanel.

### 7.2 Fora do MVP

- aplicativo nativo para Android ou iOS;
- venda de ingressos;
- transmissão de vídeo;
- reconhecimento facial ou biometria;
- integração automática com federações;
- estatísticas avançadas por GPS ou sensores;
- scout profissional com mapas de calor;
- fantasy game;
- sistema financeiro completo;
- geração automática de vídeos;
- domínio personalizado totalmente automatizado por campeonato;
- assinatura eletrônica com validade jurídica avançada.

Esses recursos poderão ser avaliados em fases futuras.

---

## 8. Perfis de usuário e permissões

### 8.1 Superadministrador

Acesso completo à plataforma.

Pode:

- criar organizações, projetos e campeonatos;
- acessar qualquer módulo;
- gerenciar usuários e permissões;
- alterar configurações globais;
- visualizar auditoria;
- corrigir dados críticos;
- restaurar ou reabrir registros, sempre com justificativa.

### 8.2 Administrador do projeto

Gerencia os campeonatos vinculados ao projeto autorizado.

Pode:

- criar e configurar campeonatos;
- cadastrar e aprovar equipes;
- controlar usuários do projeto;
- acessar relatórios e prestação de contas;
- acompanhar pendências operacionais.

### 8.3 Organizador do campeonato

Gerencia a operação esportiva de um campeonato específico.

Pode:

- cadastrar equipes e atletas;
- aprovar inscrições;
- criar grupos, rodadas e partidas;
- definir locais e arbitragem;
- preencher ou revisar súmulas;
- publicar resultados;
- gerenciar cartões e suspensões;
- cadastrar notícias, fotos e premiações;
- gerar documentos.

### 8.4 Treinador ou responsável pelo time

Acesso restrito ao próprio time e aos campeonatos em que ele estiver inscrito.

Pode:

- completar dados do time;
- cadastrar comissão técnica;
- cadastrar e editar atletas antes da aprovação;
- enviar fotos e documentos;
- solicitar inscrições;
- consultar jogadores aprovados, pendurados e suspensos;
- consultar partidas e súmulas;
- solicitar movimentações no elenco;
- indicar escalação ou relação de atletas, caso essa permissão seja habilitada.

Não pode:

- editar adversários;
- alterar resultados oficiais;
- remover cartões;
- publicar súmulas definitivas;
- acessar dados privados de outras equipes.

### 8.5 Operador de partida

Acesso apenas às partidas atribuídas.

Pode:

- abrir a central da partida;
- confirmar atletas relacionados;
- preencher escalações;
- registrar eventos;
- informar horários;
- registrar ocorrências;
- encaminhar súmula para revisão.

### 8.6 Comunicação ou fotógrafo

Pode:

- enviar fotos;
- criar galerias;
- publicar notícias;
- cadastrar craque da rodada;
- atualizar destaques do portal.

Não acessa CPF, documentos ou dados sensíveis.

### 8.7 Prestação de contas ou auditor

Perfil de leitura e exportação.

Pode:

- consultar indicadores;
- visualizar documentos;
- baixar súmulas;
- baixar fotos e relatórios;
- consultar histórico de alterações.

Não pode editar dados esportivos.

### 8.8 Público

Não necessita login.

Pode acessar apenas informações publicadas e autorizadas para exibição pública.

---

## 9. Arquitetura de sites por campeonato

### 9.1 Conceito

Cada campeonato será percebido como um site próprio, mas todos utilizarão a mesma aplicação, banco de dados e componentes.

Exemplos de endereço:

```text
plataforma.com/copa-brasil-de-talentos
plataforma.com/torneio-de-verao
```

Alternativa futura:

```text
copabrasil.plataforma.com
verao.plataforma.com
```

### 9.2 Configurações visuais por campeonato

- logo principal;
- versão clara e escura do logo;
- favicon;
- cor primária;
- cor secundária;
- cor de destaque;
- cor de fundo;
- imagem de capa;
- imagem de compartilhamento;
- patrocinadores;
- nome curto;
- nome completo;
- slogan;
- links sociais;
- tema padrão light ou dark.

### 9.3 Regras de consistência

A personalização não poderá quebrar o sistema visual.

O sistema deverá:

- validar contraste mínimo;
- aplicar cores em componentes predefinidos;
- manter tipografia e espaçamentos globais;
- usar cores de estado independentes da marca;
- possuir cores de fallback;
- impedir combinações ilegíveis.

### 9.4 Temas light e dark

O usuário poderá alternar o tema. A preferência será salva no navegador e, quando logado, poderá ser salva no perfil.

O tema do campeonato deverá possuir tokens próprios para os dois modos.

---

## 10. Arquitetura da informação — Portal público

### 10.1 Menu principal

- Início
- Campeonato
- Classificação
- Jogos
- Mata-mata
- Equipes
- Atletas
- Estatísticas
- Disciplina
- Vai e vem
- Notícias
- Galerias
- Regulamento

### 10.2 Página inicial do campeonato

Deverá apresentar:

- identidade do campeonato;
- banner principal;
- próximo confronto em destaque;
- últimos resultados;
- classificação resumida dos grupos;
- artilheiros;
- craque da rodada;
- atletas suspensos ou pendurados, conforme política pública;
- últimas notícias;
- vai e vem recente;
- galeria recente;
- patrocinadores;
- chamada para consultar todos os jogos.

### 10.3 Página do campeonato

- apresentação;
- formato da disputa;
- período de realização;
- organização;
- categorias;
- regulamento;
- documentos públicos;
- patrocinadores.

### 10.4 Classificação

Exibir por grupo:

- posição;
- equipe;
- pontos;
- jogos;
- vitórias;
- empates;
- derrotas;
- gols pró;
- gols contra;
- saldo de gols;
- aproveitamento;
- forma nos últimos jogos;
- zona de classificação.

A interface deverá explicar os critérios de desempate.

### 10.5 Jogos

Filtros por:

- fase;
- grupo;
- rodada;
- equipe;
- data;
- status.

Status possíveis:

- agendado;
- pré-jogo;
- em andamento;
- intervalo;
- encerrado;
- adiado;
- cancelado;
- W.O.;
- aguardando revisão.

### 10.6 Página de informações do jogo

#### Antes do jogo

- fase, grupo e rodada;
- data e horário;
- local;
- equipes e escudos;
- arbitragem, quando publicada;
- escalações, quando liberadas;
- desfalques;
- pendurados;
- informações gerais.

#### Durante o jogo

- placar;
- minuto e período;
- status ao vivo;
- timeline de eventos;
- gols;
- cartões;
- substituições;
- atualização por requisição periódica.

#### Depois do jogo

- placar final;
- resultado dos pênaltis, quando aplicável;
- autores dos gols;
- assistências;
- cartões;
- escalações;
- substituições;
- melhor jogador;
- galeria;
- súmula pública, caso autorizada;
- confronto seguinte no mata-mata.

### 10.7 Mata-mata

- visualização por chave;
- quartas de final;
- semifinais;
- final;
- placar agregado, quando houver ida e volta;
- pênaltis;
- destaque do classificado;
- navegação mobile horizontal controlada.

### 10.8 Equipes

Listagem com:

- escudo;
- nome;
- grupo;
- cidade ou bairro;
- posição;
- desempenho.

### 10.9 Página da equipe

- escudo e capa;
- dados públicos;
- comissão técnica;
- elenco;
- próximos jogos;
- resultados;
- classificação;
- gols marcados e sofridos;
- artilheiros do time;
- cartões;
- fotos;
- movimentações.

### 10.10 Atletas

Listagem pública somente com dados autorizados:

- foto;
- nome esportivo;
- equipe;
- posição;
- número;
- gols;
- assistências;
- jogos;
- cartões.

CPF, telefone, data completa de nascimento, documentos e endereço nunca serão públicos.

### 10.11 Página do atleta

- foto;
- nome público;
- equipe;
- posição;
- número;
- estatísticas;
- gols por partida;
- cartões;
- histórico público de equipes;
- premiações.

### 10.12 Estatísticas e rankings

- artilharia;
- assistências;
- goleiros menos vazados, se houver dados confiáveis;
- mais partidas;
- melhores ataques;
- melhores defesas;
- fair play;
- forma recente;
- craques da rodada.

### 10.13 Disciplina

- cartões amarelos;
- cartões vermelhos;
- suspensos;
- punições públicas, quando permitido;
- regulamento disciplinar.

### 10.14 Vai e vem

- atleta;
- foto;
- equipe de origem;
- equipe de destino;
- tipo de movimentação;
- data;
- situação;
- campeonato.

### 10.15 Notícias

- listagem;
- categorias;
- notícia individual;
- autor;
- data;
- compartilhamento;
- imagens;
- conteúdos relacionados.

### 10.16 Galerias

- galeria por partida;
- galeria por rodada;
- créditos do fotógrafo;
- capa;
- visualização em lightbox;
- download somente quando autorizado.

---

## 11. Arquitetura da informação — Painel administrativo

### 11.1 Menu principal

- Visão geral
- Projetos
- Campeonatos
- Equipes
- Pessoas e atletas
- Inscrições
- Jogos
- Central da partida
- Súmulas
- Classificação
- Disciplina
- Transferências
- Comunicação
- Galerias
- Prestação de contas
- Relatórios
- Usuários
- Configurações
- Auditoria

### 11.2 Dashboard geral

Indicadores:

- campeonatos ativos;
- equipes inscritas;
- atletas cadastrados;
- inscrições pendentes;
- partidas agendadas;
- partidas concluídas;
- súmulas pendentes;
- atletas suspensos;
- documentos pendentes;
- fotos pendentes;
- relatórios recentes.

### 11.3 Dashboard do campeonato

- próximo jogo;
- jogos da rodada;
- pendências;
- classificação resumida;
- artilharia;
- cartões;
- alertas de suspensão;
- status da documentação;
- atalhos operacionais.

---

## 12. Requisitos funcionais

Os requisitos abaixo recebem identificadores para facilitar desenvolvimento, testes e aceite.

### 12.1 Autenticação e usuários

**RF-001** — Permitir login por e-mail e senha.  
**RF-002** — Permitir recuperação segura de senha.  
**RF-003** — Permitir ativação, bloqueio e desativação de usuários.  
**RF-004** — Permitir múltiplos perfis e permissões.  
**RF-005** — Restringir cada usuário aos projetos, campeonatos e equipes autorizados.  
**RF-006** — Registrar último acesso e tentativas de login.  
**RF-007** — Encerrar sessões após período de inatividade configurável.  
**RF-008** — Permitir autenticação em dois fatores em fase posterior ou para perfis críticos.

### 12.2 Projetos e campeonatos

**RF-010** — Criar, editar, arquivar e duplicar projetos.  
**RF-011** — Criar campeonatos vinculados a um projeto.  
**RF-012** — Definir nome, slug, período, descrição e status.  
**RF-013** — Configurar identidade visual.  
**RF-014** — Configurar regras esportivas.  
**RF-015** — Definir campeonato como rascunho, publicado, encerrado ou arquivado.  
**RF-016** — Duplicar configurações de um campeonato para uma nova edição sem copiar resultados.  
**RF-017** — Definir quais módulos ficam visíveis no portal público.

### 12.3 Equipes

**RF-020** — Cadastrar equipe com nome, nome curto, escudo e informações institucionais.  
**RF-021** — Vincular equipe a um ou mais campeonatos.  
**RF-022** — Definir responsável principal.  
**RF-023** — Aprovar ou rejeitar inscrição da equipe.  
**RF-024** — Manter histórico de participações.  
**RF-025** — Impedir duplicidade indevida de equipe no mesmo campeonato.

### 12.4 Pessoas, atletas e comissão técnica

**RF-030** — Manter cadastro central de pessoas.  
**RF-031** — Permitir que uma pessoa assuma funções diferentes em campeonatos diferentes.  
**RF-032** — Cadastrar nome completo, nome público, foto, documento, nascimento, telefone e contato do responsável quando necessário.  
**RF-033** — Cadastrar posição e número preferencial.  
**RF-034** — Cadastrar funções de comissão técnica.  
**RF-035** — Armazenar documentos obrigatórios.  
**RF-036** — Registrar autorização de uso de imagem.  
**RF-037** — Registrar consentimento ou fundamento legal aplicável.  
**RF-038** — Mascarar dados sensíveis na interface.  
**RF-039** — Detectar possível duplicidade por CPF, documento, nome e nascimento.

### 12.5 Inscrições

**RF-040** — Inscrever atleta em equipe e campeonato.  
**RF-041** — Trabalhar com status rascunho, enviada, em análise, aprovada, rejeitada, suspensa e cancelada.  
**RF-042** — Validar campos e documentos obrigatórios.  
**RF-043** — Registrar justificativa de aprovação ou rejeição.  
**RF-044** — Controlar limite de atletas por equipe.  
**RF-045** — Bloquear inscrição fora da janela, salvo permissão especial.  
**RF-046** — Gerar lista oficial de inscritos.  
**RF-047** — Manter histórico de alterações do vínculo.

### 12.6 Importação de planilhas

**RF-050** — Importar dados de XLSX ou CSV.  
**RF-051** — Permitir mapear colunas da planilha para campos do sistema.  
**RF-052** — Exibir prévia antes de confirmar.  
**RF-053** — Validar CPF, telefone, datas e campos obrigatórios.  
**RF-054** — Identificar duplicidades.  
**RF-055** — Gerar relatório de linhas importadas, rejeitadas e pendentes.  
**RF-056** — Normalizar marcações históricas como `x` e `xx`, mediante regra de migração confirmada.  
**RF-057** — Converter datas numéricas de planilha para datas válidas.  
**RF-058** — Importar histórico de gols, cartões, suspensões e ocorrências com revisão manual.

### 12.7 Estrutura esportiva

**RF-060** — Criar fases.  
**RF-061** — Criar grupos.  
**RF-062** — Criar rodadas.  
**RF-063** — Distribuir equipes em grupos.  
**RF-064** — Gerar confrontos automaticamente em turno único ou ida e volta.  
**RF-065** — Permitir ajuste manual de confrontos.  
**RF-066** — Configurar classificados por grupo.  
**RF-067** — Gerar chave de mata-mata.  
**RF-068** — Atualizar automaticamente o próximo confronto após resultado definitivo.  
**RF-069** — Permitir avanço manual somente com permissão e auditoria.

### 12.8 Locais e agenda

**RF-070** — Cadastrar campos, estádios e locais.  
**RF-071** — Armazenar endereço, mapa, instruções e fotos.  
**RF-072** — Agendar partida com data, horário e local.  
**RF-073** — Detectar conflito de horário de equipe, campo ou arbitragem.  
**RF-074** — Reagendar partida com motivo.  
**RF-075** — Notificar os usuários afetados.  
**RF-076** — Exibir histórico de mudanças.

### 12.9 Partidas

**RF-080** — Cadastrar partida vinculada a fase, grupo e rodada.  
**RF-081** — Definir mandante e visitante.  
**RF-082** — Definir status.  
**RF-083** — Atribuir operador, árbitro, assistentes e mesário.  
**RF-084** — Definir regulamento específico da partida.  
**RF-085** — Registrar W.O., adiamento ou cancelamento.  
**RF-086** — Registrar placar normal e placar de pênaltis separadamente.  
**RF-087** — Bloquear publicação enquanto houver inconsistências críticas.

### 12.10 Escalações e relacionados

**RF-090** — Selecionar atletas relacionados a partir dos inscritos aprovados.  
**RF-091** — Marcar titulares, reservas, capitão e goleiro.  
**RF-092** — Informar número da camisa na partida.  
**RF-093** — Bloquear atleta suspenso, salvo autorização formal.  
**RF-094** — Alertar atleta irregular ou sem documento aprovado.  
**RF-095** — Permitir confirmação pelo treinador antes da partida, caso habilitado.  
**RF-096** — Manter snapshot da relação usada na súmula.

### 12.11 Central da partida

**RF-100** — Exibir as duas equipes lado a lado.  
**RF-101** — Exibir placar, período, minuto e status.  
**RF-102** — Registrar eventos rapidamente.  
**RF-103** — Pesquisar atleta por nome ou número.  
**RF-104** — Desfazer ou corrigir evento antes da finalização.  
**RF-105** — Exigir justificativa para alterações após envio à revisão.  
**RF-106** — Atualizar o portal público por consulta periódica.  
**RF-107** — Salvar automaticamente o rascunho.  
**RF-108** — Manter cópia local temporária no navegador em caso de conexão instável.  
**RF-109** — Evitar edição simultânea conflitante por bloqueio ou controle de versão.

### 12.12 Eventos da partida

**RF-110** — Registrar gol.  
**RF-111** — Registrar assistência opcional.  
**RF-112** — Registrar gol contra.  
**RF-113** — Registrar cartão amarelo.  
**RF-114** — Registrar segundo amarelo.  
**RF-115** — Registrar cartão vermelho direto.  
**RF-116** — Registrar substituição.  
**RF-117** — Registrar pênalti convertido ou perdido.  
**RF-118** — Registrar ocorrências textuais.  
**RF-119** — Registrar período e minuto.  
**RF-120** — Permitir evento vinculado à comissão técnica.  
**RF-121** — Recalcular placar e estatísticas após correção autorizada.

### 12.13 Súmula digital

**RF-130** — Gerar súmula pré-preenchida com dados da partida.  
**RF-131** — Incluir equipes, atletas, números, cartões, gols, horários e arbitragem.  
**RF-132** — Incluir placar final e pênaltis.  
**RF-133** — Incluir campos de técnico, auxiliar, capitão, árbitros e mesário.  
**RF-134** — Incluir ocorrências.  
**RF-135** — Gerar PDF padronizado.  
**RF-136** — Numerar versões da súmula.  
**RF-137** — Registrar data, hora e usuário responsável pela finalização.  
**RF-138** — Bloquear edição direta após finalização.  
**RF-139** — Permitir retificação por fluxo formal.  
**RF-140** — Gerar código ou QR de verificação da versão.  
**RF-141** — Permitir download individual ou em lote.  
**RF-142** — Permitir anexar súmula física digitalizada, quando necessário.

### 12.14 Classificação

**RF-150** — Calcular classificação automaticamente.  
**RF-151** — Permitir pontos configuráveis por resultado.  
**RF-152** — Aplicar critérios de desempate na ordem configurada.  
**RF-153** — Tratar W.O. conforme regulamento.  
**RF-154** — Mostrar zona de classificação.  
**RF-155** — Recalcular após retificação.  
**RF-156** — Manter histórico ou snapshot por rodada.  
**RF-157** — Permitir ajuste administrativo excepcional com justificativa e auditoria.

### 12.15 Disciplina, pendurados e suspensões

**RF-160** — Contabilizar cartões por atleta e comissão técnica.  
**RF-161** — Configurar quantidade de amarelos que gera suspensão.  
**RF-162** — Configurar suspensão por vermelho.  
**RF-163** — Configurar limpeza de cartões por fase.  
**RF-164** — Gerar suspensão automaticamente.  
**RF-165** — Identificar atletas pendurados.  
**RF-166** — Marcar suspensão como cumprida após partida válida.  
**RF-167** — Permitir punição manual por decisão disciplinar.  
**RF-168** — Registrar documento, motivo, duração e autoridade responsável.  
**RF-169** — Alertar treinador e organização.  
**RF-170** — Impedir escalação irregular.  
**RF-171** — Manter histórico completo.

### 12.16 Estatísticas

**RF-180** — Atualizar artilharia.  
**RF-181** — Atualizar assistências, quando usadas.  
**RF-182** — Atualizar partidas e participações.  
**RF-183** — Calcular gols marcados e sofridos por equipe.  
**RF-184** — Calcular melhores ataques e defesas.  
**RF-185** — Atualizar rankings após finalização da partida.  
**RF-186** — Permitir correção por retificação da súmula, nunca por edição isolada sem origem.

### 12.17 Transferências e vai e vem

**RF-190** — Criar janela de movimentações.  
**RF-191** — Permitir solicitação de transferência.  
**RF-192** — Registrar origem, destino, tipo e data.  
**RF-193** — Aprovar, rejeitar ou cancelar movimentação.  
**RF-194** — Bloquear conflito de inscrição.  
**RF-195** — Manter histórico do atleta.  
**RF-196** — Publicar no vai e vem somente após aprovação.  
**RF-197** — Permitir ocultar movimentações administrativas.

### 12.18 Craque da rodada e premiações

**RF-200** — Cadastrar rodadas e categorias de prêmio.  
**RF-201** — Selecionar atleta.  
**RF-202** — Adicionar foto, texto e estatísticas.  
**RF-203** — Publicar no portal.  
**RF-204** — Manter histórico de premiados.

### 12.19 Notícias e conteúdo

**RF-210** — Criar, editar, agendar e publicar notícias.  
**RF-211** — Adicionar imagem de capa e galeria.  
**RF-212** — Relacionar notícia a campeonato, equipe, atleta ou partida.  
**RF-213** — Gerenciar categorias e autores.  
**RF-214** — Gerar URL amigável e metadados de compartilhamento.

### 12.20 Fotos e galerias

**RF-220** — Fazer upload múltiplo.  
**RF-221** — Vincular fotos a partida e rodada.  
**RF-222** — Gerar miniaturas.  
**RF-223** — Informar fotógrafo e legenda.  
**RF-224** — Aprovar antes de publicar.  
**RF-225** — Marcar foto como evidência de prestação de contas.  
**RF-226** — Controlar autorização de uso de imagem.  
**RF-227** — Permitir download em lote para usuários autorizados.

### 12.21 Prestação de contas

**RF-230** — Exibir painel por projeto e campeonato.  
**RF-231** — Mostrar equipes, atletas, partidas, locais e documentos.  
**RF-232** — Exibir pendências por partida.  
**RF-233** — Baixar súmulas por partida, rodada ou período.  
**RF-234** — Baixar fotografias de comprovação.  
**RF-235** — Gerar relação de atletas.  
**RF-236** — Gerar relação de equipes e comissão técnica.  
**RF-237** — Gerar relatório de partidas.  
**RF-238** — Gerar relatório consolidado do campeonato.  
**RF-239** — Gerar pacote compactado com documentos.  
**RF-240** — Registrar quem gerou e baixou cada pacote.  
**RF-241** — Permitir filtros por data, local, rodada e status.

### 12.22 Auditoria

**RF-250** — Registrar criação, edição, exclusão lógica, aprovação e publicação.  
**RF-251** — Armazenar usuário, data, IP, ação, entidade e identificador.  
**RF-252** — Armazenar antes e depois de alterações críticas.  
**RF-253** — Impedir alteração do histórico por usuários comuns.  
**RF-254** — Permitir busca e filtros.  
**RF-255** — Registrar retificações de súmula e classificação.

### 12.23 Notificações

**RF-260** — Notificar inscrição aprovada ou rejeitada.  
**RF-261** — Notificar alteração de partida.  
**RF-262** — Notificar suspensão.  
**RF-263** — Notificar súmula pendente.  
**RF-264** — Notificar documento ausente.  
**RF-265** — Permitir notificação interna e por e-mail.  
**RF-266** — Permitir ao usuário configurar preferências não obrigatórias.

---

## 13. Fluxo da súmula digital

### 13.1 Estados da súmula

```text
Rascunho
↓
Preparada
↓
Em andamento
↓
Aguardando revisão
↓
Finalizada
↓
Retificada, quando necessário
```

### 13.2 Pré-jogo

O sistema preencherá automaticamente:

- campeonato;
- fase;
- grupo;
- rodada;
- data;
- horário;
- local;
- equipes;
- atletas aprovados;
- comissão técnica;
- arbitragem.

O operador confirmará:

- relacionados;
- titulares;
- reservas;
- números;
- capitão;
- responsáveis técnicos.

### 13.3 Durante a partida

A tela deverá priorizar rapidez e uso em tablet ou celular.

Ações rápidas:

- gol;
- amarelo;
- vermelho;
- substituição;
- ocorrência;
- início ou fim do período;
- correção do cronômetro;
- encerramento.

### 13.4 Pós-jogo

O operador deverá:

- confirmar placar;
- confirmar pênaltis;
- revisar eventos;
- registrar horários de início e término;
- informar ocorrências;
- confirmar arbitragem;
- encaminhar para revisão.

### 13.5 Finalização

Ao finalizar:

- o placar passa a ser oficial;
- a classificação é recalculada;
- a artilharia é atualizada;
- os cartões são processados;
- suspensões são geradas;
- o mata-mata é atualizado;
- o PDF é gerado;
- a versão é bloqueada;
- o portal público é atualizado.

### 13.6 Retificação

Uma súmula finalizada não poderá ser simplesmente editada.

O usuário autorizado deverá:

1. solicitar retificação;
2. informar justificativa;
3. alterar os dados;
4. gerar nova versão;
5. manter a versão anterior arquivada;
6. recalcular os dados afetados;
7. registrar tudo na auditoria.

### 13.7 Evolução em relação à planilha

A planilha atual possui campos fixos para marcações de gols e cartões. O novo sistema deverá trabalhar com uma lista ilimitada de eventos, evitando a limitação de quantidade de gols e permitindo registrar minuto, período e autoria corretamente.

---

## 14. Motor de classificação

### 14.1 Dados calculados

- jogos;
- pontos;
- vitórias;
- empates;
- derrotas;
- gols pró;
- gols contra;
- saldo;
- aproveitamento;
- sequência recente;
- posição.

### 14.2 Critérios de desempate configuráveis

Exemplos:

1. maior número de vitórias;
2. maior saldo de gols;
3. maior número de gols marcados;
4. confronto direto;
5. menor número de cartões;
6. sorteio ou decisão administrativa.

A ordem deve ser configurável por campeonato.

### 14.3 Situações especiais

- W.O.;
- desistência;
- equipe excluída;
- resultado anulado;
- perda de pontos;
- partida interrompida;
- decisão disciplinar;
- empate absoluto.

Toda intervenção manual deverá possuir justificativa e auditoria.

---

## 15. Sistema de cartões e suspensões

### 15.1 Regras configuráveis

- amarelos acumulados para suspensão;
- suspensão por segundo amarelo;
- suspensão por vermelho direto;
- número de jogos de suspensão;
- limpeza de cartões ao mudar de fase;
- manutenção de suspensão mesmo após limpeza;
- punição administrativa adicional.

### 15.2 Estados do atleta

- regular;
- pendurado;
- suspenso;
- cumprindo suspensão;
- liberado;
- sob análise.

### 15.3 Cumprimento

A suspensão será cumprida em uma partida elegível, conforme regulamento. O sistema não deverá considerar automaticamente qualquer partida cancelada ou sem validade.

### 15.4 Alertas

- alerta no dashboard;
- alerta na escalação;
- aviso ao treinador;
- aviso ao organizador;
- lista de pendurados e suspensos.

---

## 16. Prestação de contas

### 16.1 Objetivo

Centralizar provas, documentos e indicadores exigidos pelo Ministério ou órgão financiador.

### 16.2 Indicadores do dashboard

- projetos;
- campeonatos;
- equipes participantes;
- atletas inscritos;
- atletas aprovados;
- partidas previstas;
- partidas realizadas;
- partidas pendentes;
- locais utilizados;
- gols;
- cartões;
- súmulas disponíveis;
- súmulas pendentes;
- fotos enviadas;
- documentos faltantes;
- período de execução.

### 16.3 Relatórios

- relatório geral do projeto;
- relatório por campeonato;
- relatório por período;
- relatório por rodada;
- relatório por local;
- relação de equipes;
- relação de atletas;
- relação de comissão técnica;
- partidas realizadas;
- resultados;
- estatísticas consolidadas;
- ocorrências;
- arquivos de súmula;
- pacote de fotos.

### 16.4 Pacote por partida

Cada partida poderá possuir um pacote com:

- súmula oficial;
- fotos selecionadas;
- identificação do local;
- dados das equipes;
- arbitragem;
- ocorrências;
- documentos adicionais.

### 16.5 Pendências

O sistema deverá indicar claramente quando uma partida estiver sem:

- súmula finalizada;
- foto de comprovação;
- arbitragem cadastrada;
- ocorrência revisada;
- documento obrigatório.

---

## 17. Design e experiência do usuário

### 17.1 Direção visual

- interface clean;
- alta legibilidade;
- uso equilibrado de espaços;
- cards discretos;
- tabelas claras;
- azul e verde como base;
- branco predominante no light mode;
- azul-marinho e superfícies neutras no dark mode;
- cores do campeonato aplicadas de forma controlada;
- visual esportivo moderno, sem excesso de elementos decorativos.

### 17.2 Tipografia

**Bricolage Grotesk**

- títulos principais;
- números de destaque;
- placares;
- chamadas;
- headings de cards.

**Inter**

- textos;
- menus;
- formulários;
- tabelas;
- legendas;
- dados operacionais.

### 17.3 Tokens de design sugeridos

```css
--brand-primary: cor configurável do campeonato;
--brand-secondary: cor configurável do campeonato;
--brand-accent: cor configurável do campeonato;

--success: verde de confirmação;
--warning: amarelo de atenção;
--danger: vermelho de erro ou expulsão;
--info: azul informativo;

--surface-0: fundo principal;
--surface-1: card;
--surface-2: card elevado;
--text-primary: texto principal;
--text-secondary: texto secundário;
--border: borda neutra;
```

### 17.4 Componentes principais

- header público;
- sidebar administrativa;
- topbar;
- seletor de campeonato;
- seletor light/dark;
- cards de indicadores;
- card de partida;
- placar;
- tabela de classificação;
- tabela administrativa;
- filtros;
- badges de status;
- timeline;
- chave de mata-mata;
- modal;
- drawer mobile;
- uploader;
- visualizador de documentos;
- notificações;
- skeleton de carregamento;
- estados vazios;
- alertas de pendência.

### 17.5 Responsividade

A interface deverá funcionar em:

- desktop;
- notebook;
- tablet;
- celular.

A central da partida deve ser especialmente otimizada para tablet e celular.

### 17.6 Acessibilidade

- contraste compatível com WCAG AA;
- navegação por teclado;
- foco visível;
- labels em formulários;
- textos alternativos em imagens;
- tabelas com cabeçalhos semânticos;
- uso de ícones acompanhado de texto quando a ação não for óbvia;
- estados não comunicados apenas por cor.

---

## 18. Modelo de dados proposto

A nomenclatura final poderá mudar durante a modelagem técnica.

### 18.1 Núcleo organizacional

#### `organizations`

- id
- name
- status
- created_at
- updated_at

#### `projects`

- id
- organization_id
- name
- description
- start_date
- end_date
- status

#### `tournaments`

- id
- project_id
- name
- short_name
- slug
- description
- start_date
- end_date
- status
- public_status
- default_theme
- created_at
- updated_at

#### `tournament_settings`

- tournament_id
- points_win
- points_draw
- points_loss
- walkover_home_score
- walkover_away_score
- yellow_limit
- cards_reset_rule
- public_lineups
- public_reports
- live_updates
- rules_json

#### `tournament_themes`

- tournament_id
- logo_light
- logo_dark
- favicon
- hero_image
- primary_color
- secondary_color
- accent_color
- light_tokens_json
- dark_tokens_json

### 18.2 Usuários e permissões

#### `users`

- id
- name
- email
- password_hash
- status
- last_login_at
- created_at
- updated_at

#### `roles`

- id
- name
- scope_type

#### `permissions`

- id
- key
- description

#### `user_role_assignments`

- id
- user_id
- role_id
- organization_id
- project_id
- tournament_id
- team_id

### 18.3 Pessoas e equipes

#### `people`

- id
- full_name
- public_name
- birth_date
- cpf_encrypted
- cpf_hash
- phone_encrypted
- email
- photo
- image_consent_status
- status

#### `teams`

- id
- organization_id
- name
- short_name
- slug
- crest
- city
- neighborhood
- description
- status

#### `team_tournament_entries`

- id
- tournament_id
- team_id
- group_id
- seed
- status
- approved_at

#### `team_memberships`

- id
- team_id
- person_id
- role_type
- position
- start_date
- end_date

#### `registrations`

- id
- tournament_id
- team_id
- person_id
- registration_type
- shirt_number
- position
- status
- submitted_at
- approved_at
- rejection_reason

#### `person_documents`

- id
- person_id
- tournament_id
- type
- file_path
- status
- expires_at
- reviewed_by

### 18.4 Competição

#### `stages`

- id
- tournament_id
- name
- type
- order_index
- status

Tipos:

- group;
- knockout;
- final;
- custom.

#### `groups`

- id
- stage_id
- name
- order_index

#### `rounds`

- id
- stage_id
- group_id
- name
- number
- start_date
- end_date
- status

#### `venues`

- id
- name
- address
- city
- latitude
- longitude
- instructions
- status

#### `matches`

- id
- tournament_id
- stage_id
- group_id
- round_id
- venue_id
- home_entry_id
- away_entry_id
- scheduled_at
- status
- home_score
- away_score
- home_penalties
- away_penalties
- started_at
- ended_at
- published_at
- version

#### `match_officials`

- id
- match_id
- person_id
- role_type

#### `match_lineups`

- id
- match_id
- registration_id
- team_entry_id
- lineup_type
- shirt_number
- is_captain
- is_goalkeeper

#### `match_events`

- id
- match_id
- team_entry_id
- registration_id
- related_registration_id
- event_type
- period
- minute
- extra_minute
- event_order
- notes
- status
- created_by
- created_at

#### `match_reports`

- id
- match_id
- version
- status
- report_number
- pdf_path
- verification_hash
- finalized_by
- finalized_at
- amendment_reason

#### `match_occurrences`

- id
- match_id
- person_id
- category
- description
- visibility

#### `match_photos`

- id
- match_id
- gallery_id
- file_path
- thumbnail_path
- caption
- photographer
- evidence_flag
- status

### 18.5 Disciplina e estatísticas

#### `disciplinary_records`

- id
- tournament_id
- person_id
- team_entry_id
- match_id
- type
- quantity
- source_event_id
- status

#### `suspensions`

- id
- tournament_id
- person_id
- team_entry_id
- source_type
- source_id
- matches_total
- matches_served
- start_date
- end_date
- status
- notes

#### `standings_snapshots`

- id
- tournament_id
- stage_id
- group_id
- round_id
- data_json
- generated_at

#### `awards`

- id
- tournament_id
- round_id
- person_id
- team_entry_id
- type
- title
- description
- image
- published_at

### 18.6 Conteúdo

#### `news_posts`

- id
- tournament_id
- author_id
- title
- slug
- excerpt
- content
- cover_image
- status
- published_at

#### `galleries`

- id
- tournament_id
- match_id
- round_id
- title
- slug
- cover_image
- status
- published_at

#### `transfers`

- id
- tournament_id
- person_id
- from_team_entry_id
- to_team_entry_id
- transfer_type
- requested_by
- status
- effective_date
- published_at

#### `documents`

- id
- project_id
- tournament_id
- match_id
- type
- title
- file_path
- visibility
- status

### 18.7 Sistema

#### `notifications`

- id
- user_id
- type
- title
- message
- link
- read_at

#### `audit_logs`

- id
- user_id
- action
- entity_type
- entity_id
- before_json
- after_json
- ip_address
- created_at

#### `export_jobs`

- id
- user_id
- tournament_id
- export_type
- filters_json
- status
- file_path
- created_at
- completed_at

---

## 19. Regras de integridade de dados

- Toda partida pertence a um campeonato.
- Toda equipe participante deve possuir inscrição válida no campeonato.
- Todo atleta escalado deve possuir inscrição aprovada.
- Atleta suspenso não poderá ser escalado sem autorização formal.
- Gols e cartões devem possuir origem em evento de partida ou decisão disciplinar.
- A classificação não deve ser editada diretamente em fluxo normal.
- Uma súmula finalizada deve ser imutável; correções criam nova versão.
- Dados sensíveis nunca devem ser retornados em endpoints públicos.
- Exclusões importantes serão lógicas, preservando histórico.
- Toda alteração crítica deverá gerar auditoria.

---

## 20. Arquitetura técnica

### 20.1 Abordagem

Aplicação web monolítica modular, adequada a cPanel, com separação clara entre domínio, controladores, serviços, acesso a dados, templates e assets.

### 20.2 Tecnologias

- HTML5 semântico;
- CSS3 com custom properties;
- JavaScript modular;
- PHP 8.2 ou versão estável compatível com o servidor;
- MySQL 8 ou versão disponível no cPanel;
- PDO com prepared statements;
- Composer para autoload e dependências;
- servidor Apache;
- cron jobs do cPanel;
- SMTP autenticado para e-mails;
- biblioteca PHP de PDF a definir;
- GD ou Imagick para imagens, conforme disponibilidade.

### 20.3 Estrutura sugerida

```text
/app
  /Controllers
  /Services
  /Repositories
  /Models
  /Policies
  /Validators
  /Jobs
  /Support
  /Views
/config
/database
  /migrations
  /seeds
/public
  index.php
  /assets
  /uploads-public
/routes
/storage
  /private
  /exports
  /logs
  /cache
/vendor
```

### 20.4 Padrões técnicos

- front controller;
- roteamento centralizado;
- arquitetura MVC leve;
- serviços para regras esportivas;
- repositórios para acesso ao banco;
- políticas para autorização;
- validação no servidor;
- templates PHP renderizados no servidor;
- endpoints JSON para interações dinâmicas;
- transações de banco em operações críticas;
- migrations versionadas;
- variáveis de ambiente fora do repositório.

### 20.5 Serviços de domínio essenciais

- `StandingsService`;
- `MatchEventService`;
- `DisciplineService`;
- `SuspensionService`;
- `BracketService`;
- `RegistrationService`;
- `MatchReportService`;
- `ExportService`;
- `ThemeService`;
- `AuditService`.

### 20.6 Atualizações ao vivo

Para compatibilidade com cPanel, o MVP poderá utilizar requisições AJAX periódicas.

Sugestão:

- atualização a cada 15 ou 30 segundos no portal público;
- atualização imediata no painel do operador;
- cache curto para dados públicos;
- possibilidade futura de tecnologia em tempo real.

### 20.7 Armazenamento de arquivos

No MVP:

- arquivos privados fora da pasta pública;
- acesso por controlador autenticado;
- imagens públicas em pasta controlada;
- nomes aleatórios;
- metadados no banco;
- limites de tamanho;
- geração de miniaturas.

Fase futura:

- armazenamento externo compatível com objetos, caso o volume de fotos cresça.

---

## 21. Rotas sugeridas

### 21.1 Portal público

```text
/{campeonato}
/{campeonato}/classificacao
/{campeonato}/jogos
/{campeonato}/jogos/{id-ou-slug}
/{campeonato}/mata-mata
/{campeonato}/equipes
/{campeonato}/equipes/{slug}
/{campeonato}/atletas
/{campeonato}/atletas/{slug}
/{campeonato}/estatisticas
/{campeonato}/disciplina
/{campeonato}/vai-e-vem
/{campeonato}/noticias
/{campeonato}/noticias/{slug}
/{campeonato}/galerias
/{campeonato}/galerias/{slug}
/{campeonato}/regulamento
```

### 21.2 Painel

```text
/painel
/painel/projetos
/painel/campeonatos
/painel/campeonatos/{id}
/painel/campeonatos/{id}/equipes
/painel/campeonatos/{id}/inscricoes
/painel/campeonatos/{id}/jogos
/painel/jogos/{id}/central
/painel/jogos/{id}/sumula
/painel/campeonatos/{id}/disciplina
/painel/campeonatos/{id}/transferencias
/painel/campeonatos/{id}/prestacao-de-contas
/painel/usuarios
/painel/auditoria
```

### 21.3 Endpoints JSON internos

```text
GET    /api/v1/tournaments/{id}/standings
GET    /api/v1/matches/{id}
GET    /api/v1/matches/{id}/events
POST   /api/v1/matches/{id}/events
PATCH  /api/v1/match-events/{id}
DELETE /api/v1/match-events/{id}
POST   /api/v1/matches/{id}/submit
POST   /api/v1/matches/{id}/finalize
POST   /api/v1/matches/{id}/amend
GET    /api/v1/tournaments/{id}/suspensions
POST   /api/v1/registrations/{id}/approve
POST   /api/v1/registrations/{id}/reject
```

Todos os endpoints de escrita deverão exigir autenticação, autorização e proteção CSRF.

---

## 22. Segurança

### 22.1 Autenticação

- senhas com algoritmo seguro oferecido pelo PHP;
- política de senha;
- recuperação com token de uso único;
- expiração de token;
- sessão regenerada após login;
- cookies `HttpOnly`, `Secure` e `SameSite`;
- bloqueio progressivo após tentativas inválidas.

### 22.2 Autorização

- RBAC;
- verificação por escopo;
- políticas em todas as ações;
- nunca confiar apenas em controles visuais do front-end.

### 22.3 Banco de dados

- PDO;
- prepared statements;
- transações;
- usuário de banco com privilégios mínimos;
- backup regular;
- migrations versionadas.

### 22.4 Formulários

- CSRF;
- validação do lado do servidor;
- sanitização de saída;
- proteção contra XSS;
- limites de requisição;
- proteção contra mass assignment.

### 22.5 Uploads

- validação real de MIME;
- extensão permitida;
- limite de tamanho;
- nome aleatório;
- proibição de execução;
- reprocessamento de imagens;
- armazenamento privado para documentos;
- controle de acesso no download.

### 22.6 Auditoria e logs

- logs de erro sem dados sensíveis;
- logs de autenticação;
- trilha de alterações;
- monitoramento de falhas de geração de PDF e exportações.

---

## 23. LGPD e privacidade

O sistema tratará dados de atletas e poderá envolver menores de idade. Privacidade deve ser requisito estrutural, não uma etapa posterior.

### 23.1 Dados sensíveis ou restritos

- CPF;
- data de nascimento completa;
- telefone;
- documentos;
- dados do responsável;
- ocorrências disciplinares detalhadas;
- autorizações de imagem.

### 23.2 Regras

- coletar somente dados necessários;
- informar finalidade;
- controlar base legal e autorização aplicável;
- restringir visualização por perfil;
- mascarar CPF e telefone;
- criptografar campos críticos na aplicação;
- armazenar hash normalizado do CPF para detectar duplicidades sem pesquisar texto puro;
- nunca expor dados pessoais em HTML público, APIs públicas ou URLs;
- registrar acesso a documentos privados;
- possuir política de retenção;
- permitir correção de dados;
- permitir anonimização ou exclusão quando legalmente aplicável;
- manter autorização de uso de imagem vinculada à pessoa e ao projeto.

### 23.3 Menores de idade

Quando houver menores:

- cadastrar responsável legal;
- exigir documentação ou autorização definida pela organização;
- limitar dados públicos;
- controlar uso de imagem;
- evitar publicação de informações desnecessárias.

### 23.4 Ocorrências disciplinares

Detalhes sensíveis não devem ser automaticamente públicos. O sistema deverá separar:

- descrição interna;
- resumo público;
- documento restrito;
- decisão final.

---

## 24. Requisitos não funcionais

### 24.1 Desempenho

- páginas públicas principais com carregamento rápido em rede móvel;
- consultas paginadas;
- índices adequados no MySQL;
- cache para classificação, rankings e página inicial;
- imagens otimizadas;
- lazy loading;
- geração assíncrona de pacotes grandes quando possível.

### 24.2 Disponibilidade

- backups automáticos;
- recuperação documentada;
- página de manutenção;
- monitoramento básico;
- ambiente de homologação separado da produção.

### 24.3 Escalabilidade

- todas as tabelas esportivas com escopo de campeonato;
- paginação;
- armazenamento de fotos preparado para migração futura;
- serviços de domínio independentes do layout.

### 24.4 Compatibilidade

- versões atuais de Chrome, Edge, Firefox e Safari;
- Android e iOS em navegadores modernos;
- interface funcional sem depender de hover.

### 24.5 SEO

- URLs amigáveis;
- title e description por página;
- Open Graph;
- sitemap por campeonato;
- canonical;
- dados estruturados quando adequados;
- páginas públicas renderizadas no servidor.

### 24.6 Observabilidade

- log de erros;
- log de ações críticas;
- identificação de requisição;
- status de tarefas de exportação;
- painel simples de falhas administrativas.

---

## 25. Importação e migração da planilha atual

### 25.1 Dados identificados

A planilha de referência possui:

- uma aba de súmula;
- uma aba com cadastro de atletas e dados pessoais;
- uma aba com artilharia, cartões, suspensões e ocorrências.

### 25.2 Estratégia

1. Fazer cópia de segurança da planilha original.
2. Criar mapeamento de equipes.
3. Normalizar nomes das equipes.
4. Normalizar nomes das pessoas.
5. Converter datas.
6. Validar CPF sem expor o valor nos relatórios.
7. Normalizar telefones.
8. Identificar duplicidades.
9. Converter gols.
10. Converter marcações de cartões.
11. Revisar suspensões e ocorrências.
12. Importar em ambiente de homologação.
13. Gerar relatório de inconsistências.
14. Fazer validação humana antes da produção.

### 25.3 Pontos de atenção

- Há variações de maiúsculas, minúsculas e grafia nos nomes.
- Há telefones com formatos diferentes.
- Datas podem estar armazenadas como número serial do Excel.
- Marcações `x`, `X`, `xx` e `Xx` precisam de uma regra confirmada.
- Algumas pessoas podem estar duplicadas com pequenas diferenças de nome.
- Ocorrências devem ser tratadas como informação restrita.
- O novo sistema não deve reproduzir automaticamente dados pessoais no portal.

---

## 26. Fluxos principais

### 26.1 Criação de campeonato

```text
Administrador cria projeto
↓
Cria campeonato
↓
Define identidade visual
↓
Configura regulamento
↓
Configura fases e grupos
↓
Publica página inicial
```

### 26.2 Inscrição de equipe

```text
Organização cria ou convida equipe
↓
Responsável completa cadastro
↓
Cadastra comissão e atletas
↓
Envia documentos
↓
Submete inscrição
↓
Organização analisa
↓
Aprova ou rejeita
```

### 26.3 Operação de partida

```text
Partida é agendada
↓
Operador e arbitragem são definidos
↓
Relacionados são confirmados
↓
Súmula é preparada
↓
Eventos são registrados
↓
Súmula é enviada para revisão
↓
Organização finaliza
↓
Estatísticas e portal são atualizados
```

### 26.4 Suspensão

```text
Cartão é registrado
↓
Regra disciplinar é aplicada
↓
Atleta fica pendurado ou suspenso
↓
Treinador recebe alerta
↓
Sistema bloqueia escalação irregular
↓
Suspensão é cumprida
↓
Atleta volta à condição regular
```

### 26.5 Prestação de contas

```text
Responsável seleciona projeto e período
↓
Sistema mostra pendências
↓
Usuário corrige documentos ausentes
↓
Gera relatório
↓
Gera pacote com súmulas e fotos
↓
Download é registrado
```

---

## 27. Critérios de aceite do MVP

### 27.1 Campeonato e identidade

- É possível criar um campeonato com logo e cores próprias.
- O campeonato possui URL pública própria.
- O tema light e dark funciona.
- A troca de cores não compromete contraste e legibilidade.

### 27.2 Equipes e atletas

- Um treinador gerencia somente seu time.
- A organização aprova inscrições.
- Dados sensíveis não aparecem no portal.
- O sistema detecta duplicidades prováveis.
- É possível importar a base inicial com relatório de erros.

### 27.3 Competição

- É possível criar dois grupos de cinco equipes.
- É possível gerar a fase de grupos.
- A classificação é calculada após os resultados.
- Os classificados alimentam o mata-mata.
- Quartas, semifinais e final são exibidas publicamente.

### 27.4 Partida e súmula

- O operador acessa apenas partidas autorizadas.
- A súmula vem pré-preenchida.
- É possível registrar gols, cartões, substituições e ocorrências.
- O placar é calculado pelos eventos.
- A partida pode ser finalizada.
- O sistema gera PDF.
- A súmula finalizada fica bloqueada.
- Retificação gera uma nova versão e auditoria.

### 27.5 Estatísticas e disciplina

- Artilharia é atualizada automaticamente.
- Classificação é atualizada automaticamente.
- Cartões são contabilizados.
- Pendurados são identificados.
- Suspensões são criadas conforme configuração.
- Atleta suspenso não pode ser escalado normalmente.

### 27.6 Portal público

- Exibe próximos jogos e resultados.
- Exibe classificação.
- Exibe mata-mata.
- Exibe equipes e atletas.
- Exibe estatísticas.
- Exibe notícias, craque da rodada, galerias e vai e vem.
- Funciona em celular, tablet e desktop.

### 27.7 Prestação de contas

- O dashboard mostra dados consolidados.
- O sistema identifica partidas com documentação pendente.
- É possível baixar súmulas em lote.
- É possível gerar relatório do campeonato.
- É possível gerar pacote de fotos e documentos.

### 27.8 Segurança

- Permissões são validadas no servidor.
- CPF, telefone e documentos são restritos.
- Uploads são validados.
- Formulários possuem CSRF.
- Alterações críticas são auditadas.

---

## 28. Priorização sugerida

### Fase 0 — Descoberta e regras

- validar regulamento;
- validar modelo de súmula;
- validar exigências do Ministério;
- definir dados públicos e privados;
- mapear perfis;
- revisar planilha;
- definir identidade base.

### Fase 1 — Fundação

- autenticação;
- usuários e permissões;
- projetos e campeonatos;
- tema por campeonato;
- equipes;
- pessoas;
- inscrições;
- importação.

### Fase 2 — Operação esportiva

- fases;
- grupos;
- rodadas;
- partidas;
- escalações;
- central da partida;
- eventos;
- súmula;
- PDF;
- classificação;
- cartões;
- suspensões;
- mata-mata.

### Fase 3 — Portal público

- home;
- jogos;
- classificação;
- mata-mata;
- equipes;
- atletas;
- estatísticas;
- disciplina;
- responsividade;
- light e dark.

### Fase 4 — Conteúdo e prestação de contas

- notícias;
- galerias;
- craque da rodada;
- vai e vem;
- relatórios;
- pacotes;
- pendências;
- auditoria ampliada.

### Fase 5 — Qualidade e implantação

- testes;
- segurança;
- otimização;
- migração;
- homologação;
- treinamento;
- produção;
- documentação.

---

## 29. Estratégia de testes

### 29.1 Testes unitários prioritários

- cálculo de classificação;
- critérios de desempate;
- W.O.;
- cartões acumulados;
- suspensão;
- limpeza de cartões;
- chaveamento;
- avanço de fase;
- placar por eventos;
- retificação;
- permissões.

### 29.2 Testes de integração

- finalizar súmula e atualizar classificação;
- finalizar partida e gerar suspensão;
- finalizar quartas e criar semifinal;
- retificar resultado e recalcular tudo;
- aprovar inscrição e liberar escalação;
- gerar PDF e pacote de documentos.

### 29.3 Testes de interface

- celular em campo;
- tablet;
- desktop;
- tema dark;
- tema light;
- contraste com diferentes cores de campeonato;
- tabelas grandes;
- chave de mata-mata;
- conexão instável.

### 29.4 Testes de segurança

- acesso indevido entre equipes;
- alteração de IDs na URL;
- CSRF;
- XSS;
- SQL injection;
- upload malicioso;
- download de documento privado sem permissão;
- exposição de CPF em resposta JSON;
- sessão expirada.

---

## 30. Riscos e mitigação

### 30.1 Regulamento incompleto ou variável

**Risco:** regras mudarem durante o desenvolvimento.  
**Mitigação:** tornar regras configuráveis e aprovar documento esportivo antes do motor de classificação.

### 30.2 Dados antigos inconsistentes

**Risco:** duplicidades, grafias e marcações ambíguas.  
**Mitigação:** importação com prévia, validação e relatório de pendências.

### 30.3 Conexão ruim no local da partida

**Risco:** perda de dados durante a súmula.  
**Mitigação:** autosave, cache local temporário, indicador de conexão e sincronização segura.

### 30.4 Edição simultânea

**Risco:** dois operadores alterarem a mesma partida.  
**Mitigação:** bloqueio de edição, versão do registro e aviso de conflito.

### 30.5 Crescimento do volume de fotos

**Risco:** limite de armazenamento da hospedagem.  
**Mitigação:** compressão, miniaturas, política de retenção e opção futura de storage externo.

### 30.6 Dados de menores

**Risco:** uso indevido ou exposição.  
**Mitigação:** consentimento, acesso restrito, dados públicos mínimos e trilha de auditoria.

### 30.7 Correção de resultado oficial

**Risco:** inconsistência entre súmula, classificação e mata-mata.  
**Mitigação:** fluxo formal de retificação com recálculo transacional.

### 30.8 Limitações do cPanel

**Risco:** tarefas longas, tempo real e armazenamento.  
**Mitigação:** arquitetura compatível, cron, geração em lotes, cache e evolução planejada.

---

## 31. Questões que precisam ser validadas com o cliente

1. Passam realmente quatro equipes de cada grupo?
2. Todos jogam contra todos dentro do grupo em turno único?
3. Quais são os critérios de desempate e sua ordem?
4. Qual placar deve ser aplicado em W.O.?
5. Quantos cartões amarelos geram suspensão?
6. Os cartões são zerados antes do mata-mata?
7. Cartão vermelho gera quantas partidas automaticamente?
8. Quem pode preencher a súmula: treinador, mesário, árbitro ou organização?
9. Quem pode finalizar e tornar o resultado oficial?
10. Haverá escalação pública antes do jogo?
11. Assistências e substituições serão obrigatórias?
12. Será necessário registrar minuto de todos os eventos?
13. Haverá tempo normal, prorrogação e pênaltis?
14. Qual é o modelo exato de relatório exigido pelo Ministério?
15. Quais fotos e documentos são obrigatórios por partida?
16. A súmula precisa de assinatura digital ou apenas campo para assinatura impressa?
17. Quais documentos são obrigatórios para atletas?
18. Haverá atletas menores de idade?
19. Quais dados do atleta podem aparecer publicamente?
20. O vai e vem será operado por solicitação dos times ou somente pela organização?
21. Existe janela de transferências?
22. Um atleta poderá trocar de equipe durante a mesma competição?
23. Haverá limite de atletas por equipe?
24. O treinador poderá cadastrar livremente ou dependerá de aprovação a cada alteração?
25. O portal mostrará atualizações ao vivo ou apenas placar final?
26. A súmula pública poderá ser baixada por qualquer pessoa?
27. Cada campeonato usará caminho, subdomínio ou domínio próprio?
28. Quantos campeonatos simultâneos são esperados?
29. Qual é o volume estimado de fotos por jogo?
30. Quem ficará responsável por revisar a migração da planilha atual?

---

## 32. Definição de pronto

Uma funcionalidade somente será considerada pronta quando:

- cumprir o requisito funcional;
- possuir validação de permissão;
- funcionar no servidor cPanel;
- ter tratamento de erro;
- funcionar em mobile e desktop quando aplicável;
- não expor dados sensíveis;
- possuir teste do fluxo principal;
- registrar auditoria quando necessário;
- estar documentada;
- ter sido validada em homologação.

---

## 33. Resultado esperado do produto

Ao final, a organização terá uma plataforma única capaz de:

- criar vários campeonatos;
- gerar um site personalizado para cada campeonato;
- controlar equipes, atletas e comissão técnica;
- montar a tabela e o mata-mata;
- operar partidas;
- gerar súmulas oficiais;
- atualizar estatísticas automaticamente;
- controlar cartões e suspensões;
- publicar conteúdo e fotografias;
- mostrar todas as informações ao público;
- reunir evidências e relatórios para prestação de contas;
- preservar histórico, segurança e integridade dos dados.

O produto deixará de ser apenas um site de resultados e passará a funcionar como o sistema operacional completo dos projetos esportivos.
