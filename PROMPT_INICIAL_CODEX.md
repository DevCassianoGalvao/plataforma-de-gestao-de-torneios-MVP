# Prompt inicial para o Codex — Plataforma de Gestão de Torneios

Você está iniciando o desenvolvimento de uma plataforma web completa para gestão de torneios de futebol.

Antes de escrever qualquer código, localize e leia integralmente estes dois arquivos na raiz do projeto:

1. `PRD_Plataforma_Gestao_Torneios.md`
2. `REGULAMENTO_COPA_BRASIL_DE_TALENTOS.md`

Esses documentos são a fonte principal de verdade do projeto.

## Ordem de precedência

Em caso de conflito entre os documentos:

1. O arquivo `REGULAMENTO_COPA_BRASIL_DE_TALENTOS.md` prevalece para as regras esportivas específicas da Copa Brasil de Talentos.
2. O arquivo `PRD_Plataforma_Gestao_Torneios.md` prevalece para arquitetura, produto, perfis, segurança, banco de dados, experiência do usuário e requisitos gerais da plataforma.
3. Este prompt define o método de trabalho, a arquitetura inicial e a prioridade do primeiro ciclo de desenvolvimento.

Não altere os dois documentos originais. Caso identifique inconsistências, registre-as em `docs/OPEN_QUESTIONS.md` e implemente a solução mais segura, configurável e não destrutiva.

---

# 1. Objetivo do produto

Desenvolver uma plataforma multi-campeonato com uma única base de código e banco de dados, composta por:

- portal público de cada campeonato;
- painel administrativo;
- gestão de projetos esportivos;
- gestão de campeonatos, equipes, atletas e comissões técnicas;
- operação de partidas;
- súmula digital;
- classificação automática;
- mata-mata;
- estatísticas;
- controle de cartões, pendurados e suspensões;
- notícias, galerias, craque da rodada e vai e vem;
- relatórios e prestação de contas.

Cada campeonato deve funcionar visualmente como um site próprio, com:

- nome;
- logo;
- slug;
- cores primária, secundária e de destaque;
- imagens;
- patrocinadores;
- tema light e dark;
- regulamento e parâmetros esportivos próprios.

As regras esportivas nunca devem ficar fixas no código. A Copa Brasil de Talentos será a configuração inicial, mas a plataforma deverá aceitar outros formatos de competição.

---

# 2. Stack obrigatória

Utilize:

- HTML5 semântico;
- CSS3 com custom properties;
- JavaScript moderno e modular, sem framework de front-end;
- PHP 8.2 ou versão estável compatível com cPanel;
- MySQL 8 ou versão disponível no servidor;
- PDO com prepared statements;
- Apache e `.htaccess`;
- Composer para autoload e dependências realmente necessárias;
- arquitetura MVC leve e modular;
- templates PHP renderizados no servidor;
- endpoints JSON internos para interações assíncronas;
- hospedagem final em cPanel.

Não utilize Laravel, Symfony, React, Vue, Angular, Node como dependência de execução ou qualquer arquitetura que dificulte a publicação em hospedagem cPanel convencional.

Dependências externas devem ser poucas, justificadas, versionadas e documentadas. O sistema precisa funcionar mesmo sem um processo de build no servidor.

---

# 3. Direção visual

A interface deve ser clean, moderna, esportiva e profissional.

Use:

- `Bricolage Grotesk` em títulos, números e destaques;
- `Inter` em textos, tabelas, formulários e elementos de interface;
- base visual branca, azul e verde;
- temas light e dark;
- identidade controlada por variáveis CSS;
- personalização de cores por campeonato sem comprometer contraste e legibilidade;
- layout mobile-first;
- responsividade completa para desktop, tablet e celular;
- componentes acessíveis e estados claros de foco, erro, sucesso, carregamento e vazio.

Crie os tokens visuais em um arquivo central, por exemplo:

```css
--color-primary;
--color-secondary;
--color-accent;
--color-background;
--color-surface;
--color-text;
--color-muted;
--color-border;
--color-success;
--color-warning;
--color-danger;
--radius-sm;
--radius-md;
--radius-lg;
--shadow-sm;
--container-width;
```

A personalização do campeonato deverá sobrescrever somente tokens permitidos. Não crie folhas de estilo totalmente separadas para cada copa.

---

# 4. Regras arquiteturais obrigatórias

Implemente uma aplicação monolítica modular, com separação clara entre apresentação, domínio e persistência.

Estrutura-base sugerida:

```text
/app
  /Controllers
  /Services
  /Repositories
  /Models
  /Policies
  /Validators
  /Middleware
  /Support
  /Views
/config
/database
  /migrations
  /seeds
/docs
/public
  index.php
  .htaccess
  /assets
    /css
    /js
    /images
  /uploads-public
/routes
/storage
  /private
  /exports
  /logs
  /cache
/tests
/vendor
.env.example
composer.json
README.md
```

Regras:

- usar front controller em `public/index.php`;
- centralizar rotas;
- não colocar regra de negócio em views;
- não colocar SQL em controllers;
- usar Services para regras esportivas;
- usar Repositories para persistência;
- usar Policies ou serviço equivalente para autorização;
- usar Validators para entrada de dados;
- usar transações em operações críticas;
- usar migrations e seeds versionados;
- usar exclusão lógica em registros importantes;
- usar auditoria em alterações críticas;
- manter dados de cada campeonato isolados pelo respectivo `tournament_id`;
- impedir acesso cruzado entre organizações, projetos, campeonatos e equipes;
- não expor CPF, documentos, WhatsApp, data de nascimento completa ou dados privados em páginas e endpoints públicos.

Crie serviços de domínio preparados para evolução, incluindo:

- `StandingsService`;
- `MatchEventService`;
- `DisciplineService`;
- `SuspensionService`;
- `BracketService`;
- `RegistrationService`;
- `MatchReportService`;
- `ThemeService`;
- `AuditService`;
- `ExportService`.

Neste primeiro ciclo, implemente somente os serviços necessários para a Fundação, mas preserve a arquitetura para os demais.

---

# 5. Regras de segurança

Implemente desde o início:

- `password_hash()` e `password_verify()`;
- regeneração do ID da sessão após login;
- cookies de sessão com `HttpOnly`, `SameSite` e `Secure` quando HTTPS estiver ativo;
- proteção CSRF em toda operação de escrita;
- validação e normalização no servidor;
- escape de saída para prevenir XSS;
- prepared statements em 100% das consultas;
- autorização por perfil e escopo;
- limite e validação de uploads;
- nomes aleatórios para arquivos enviados;
- arquivos privados fora de `/public`;
- rate limit básico para login;
- auditoria de login, logout e alterações críticas;
- mensagens de erro seguras em produção;
- logs detalhados apenas em ambiente de desenvolvimento;
- variáveis sensíveis exclusivamente no `.env`;
- `.env`, logs, documentos privados e backups fora do acesso público.

O sistema trabalhará com dados de menores de idade. Trate privacidade e controle de acesso como requisitos centrais, não como ajustes futuros.

---

# 6. Configuração esportiva

A Copa Brasil de Talentos deverá ser criada como configuração inicial, conforme o regulamento:

- 10 equipes;
- 2 grupos com 5 equipes;
- turno único dentro de cada grupo;
- 4 classificados por grupo;
- 8 equipes nas quartas de final;
- quartas em jogo único;
- semifinais em jogo único;
- final em jogo único;
- sem disputa de terceiro lugar por padrão;
- cruzamentos conforme o arquivo de regulamento;
- empate no mata-mata decidido conforme a configuração do campeonato;
- regras de pontuação, desempate, cartões, suspensões, substituições, W.O. e súmula lidas de configurações persistidas no banco.

Não transforme esses valores em constantes globais. Eles devem ser sementes de configuração para esse campeonato.

Modele as configurações para permitir, futuramente:

- quantidade variável de grupos;
- quantidade variável de equipes;
- turno único ou turno e returno;
- número de classificados por grupo;
- melhor campanha geral;
- cruzamentos personalizados;
- jogo único ou ida e volta;
- prorrogação e pênaltis;
- pontuação personalizada;
- critérios de desempate ordenáveis;
- limite de cartões para suspensão;
- limpeza de cartões por fase;
- duração das partidas;
- substituições;
- W.O.;
- categorias diferentes.

---

# 7. Método de execução

Não tente implementar o sistema inteiro de uma só vez.

Trabalhe em fases pequenas, funcionais e verificáveis. Antes de codificar:

1. inspecione todos os arquivos existentes no repositório;
2. leia integralmente os dois documentos de requisitos;
3. identifique o estado atual do projeto;
4. crie um plano técnico por fases;
5. registre decisões e dúvidas;
6. só então inicie o código.

Crie e mantenha estes documentos:

```text
docs/IMPLEMENTATION_PLAN.md
docs/ARCHITECTURE.md
docs/DATABASE_SCHEMA.md
docs/DECISIONS.md
docs/OPEN_QUESTIONS.md
docs/CPANEL_DEPLOYMENT.md
CHANGELOG.md
```

Quando uma decisão não estiver definida:

- não invente uma regra esportiva definitiva;
- escolha uma implementação configurável;
- registre a suposição em `docs/DECISIONS.md`;
- registre o ponto pendente em `docs/OPEN_QUESTIONS.md`;
- não bloqueie o desenvolvimento quando for possível avançar com segurança.

Não remova nem sobrescreva arquivos existentes sem necessidade. Preserve o histórico do projeto.

---

# 8. Primeiro ciclo de desenvolvimento — Fundação

Neste primeiro ciclo, implemente completamente a Fase 1 da plataforma.

## 8.1 Infraestrutura

- estrutura MVC leve;
- autoload PSR-4 via Composer;
- carregamento de `.env`;
- configuração por ambiente;
- conexão PDO;
- roteador;
- controllers base;
- sistema de views e layouts;
- tratamento central de erros;
- logs;
- helpers de URL, sessão, CSRF, validação e escape;
- migration runner em PHP;
- seed runner em PHP;
- `.htaccess` compatível com Apache/cPanel;
- página de erro 404 e 500;
- README com instalação local e em cPanel.

## 8.2 Autenticação e autorização

- login;
- logout;
- recuperação de sessão;
- alteração de senha pelo usuário autenticado;
- perfis e permissões;
- autorização por rota e ação;
- escopo por organização, projeto, campeonato e equipe;
- usuário superadministrador inicial criado por seed;
- credenciais de desenvolvimento documentadas sem usar senha real de produção.

Perfis mínimos:

- superadministrador;
- administrador do projeto;
- organizador do campeonato;
- treinador ou responsável pelo time;
- operador de partida;
- comunicação ou fotógrafo;
- prestação de contas ou auditor.

## 8.3 Estrutura organizacional

Implementar CRUD funcional de:

- organizações;
- projetos esportivos;
- campeonatos;
- configurações do campeonato;
- tema do campeonato;
- equipes;
- pessoas;
- vínculo de equipe com campeonato;
- vínculo de pessoa com equipe;
- inscrições de atletas e comissão técnica.

Cada ação deve respeitar as permissões e o escopo do usuário.

## 8.4 Banco de dados inicial

Crie migrations, chaves estrangeiras, índices e seeds para, no mínimo:

```text
organizations
projects
tournaments
tournament_settings
tournament_themes
users
roles
permissions
user_role_assignments
people
teams
team_tournament_entries
team_memberships
registrations
audit_logs
login_attempts
```

Adicione colunas de controle quando aplicável:

```text
id
status
created_at
updated_at
deleted_at
created_by
updated_by
```

Use tipos apropriados, índices por escopo e integridade referencial. Documente o schema em `docs/DATABASE_SCHEMA.md`.

## 8.5 Site público inicial

Criar uma estrutura pública funcional por slug:

```text
/campeonatos/{slug}
```

A página inicial do campeonato deverá buscar os dados reais no banco e exibir:

- logo;
- nome;
- temporada ou ano;
- descrição;
- imagem de capa;
- cores configuradas;
- patrocinadores, quando houver;
- navegação-base;
- estados vazios para jogos, classificação, equipes, notícias e estatísticas ainda não cadastrados;
- alternância light/dark;
- metadados básicos de SEO.

Não crie dados falsos como se fossem oficiais. Seeds de desenvolvimento devem ser claramente identificados como fictícios.

## 8.6 Painel administrativo inicial

Criar:

- tela de login;
- layout administrativo responsivo;
- sidebar e header;
- dashboard com indicadores reais disponíveis;
- seleção de projeto e campeonato ativo;
- CRUDs da Fundação;
- formulários com validação no cliente e no servidor;
- tabelas com busca, paginação e estados vazios;
- feedback de sucesso e erro;
- confirmação para operações destrutivas;
- alternância light/dark;
- aplicação da identidade do campeonato ativo sem perder a identidade-base do sistema.

## 8.7 Seed da Copa Brasil de Talentos

Crie um seed de desenvolvimento para:

- organização de demonstração;
- projeto esportivo de demonstração;
- campeonato `Copa Brasil de Talentos`;
- slug `copa-brasil-de-talentos`;
- configuração esportiva baseada no regulamento;
- tema inicial branco, azul e verde;
- light e dark habilitados;
- grupos e partidas ainda não precisam ser gerados neste ciclo, mas as configurações devem estar persistidas de forma estruturada.

Não crie nomes reais de atletas ou documentos pessoais.

---

# 9. Qualidade de código

- use `declare(strict_types=1);` nos arquivos PHP apropriados;
- siga PSR-12;
- use namespaces;
- evite classes gigantes;
- use nomes claros em inglês no código e português na interface;
- não duplique regras;
- não use números mágicos;
- não silencie exceções;
- trate falhas de banco e validação;
- use transações em operações com múltiplas gravações;
- mantenha funções pequenas;
- documente decisões complexas;
- não adicione comentários que apenas repetem o código;
- mantenha compatibilidade com cPanel e Apache;
- não dependa de comandos que só funcionem em Linux com acesso root.

Crie testes para os fluxos críticos da Fundação, principalmente:

- autenticação;
- autorização;
- isolamento de escopo;
- criação de campeonato;
- persistência do tema;
- CSRF;
- proteção de rotas;
- auditoria.

Caso utilize PHPUnit, configure-o como dependência de desenvolvimento e documente sua execução.

---

# 10. Critérios de aceite do primeiro ciclo

O primeiro ciclo só estará concluído quando:

- o projeto instalar e iniciar seguindo o README;
- as migrations criarem o banco do zero;
- os seeds criarem o ambiente de demonstração;
- o superadministrador conseguir entrar e sair do sistema;
- permissões impedirem acessos indevidos;
- organizações, projetos, campeonatos, equipes, pessoas e inscrições possuírem CRUD funcional;
- o campeonato possuir configurações e tema próprios;
- a página pública carregar pelo slug;
- light e dark funcionarem;
- alterações críticas gerarem auditoria;
- dados privados não aparecerem em páginas públicas;
- a aplicação funcionar em desktop e mobile;
- não houver erros PHP, SQL ou JavaScript no fluxo principal;
- a implantação em cPanel estiver documentada;
- os documentos técnicos estiverem atualizados.

---

# 11. Entrega ao final deste ciclo

Ao finalizar, apresente um relatório objetivo contendo:

1. resumo do que foi implementado;
2. estrutura de arquivos criada;
3. migrations e tabelas adicionadas;
4. rotas públicas e administrativas disponíveis;
5. perfis e permissões implementados;
6. como instalar localmente;
7. como publicar em cPanel;
8. como executar migrations, seeds e testes;
9. credenciais fictícias do ambiente de desenvolvimento;
10. decisões assumidas;
11. dúvidas pendentes;
12. limitações conhecidas;
13. próximo ciclo recomendado.

Comece agora lendo os dois arquivos de requisitos e inspecionando o repositório. Depois crie o plano técnico e implemente a Fundação de maneira funcional, segura e preparada para as próximas fases.
