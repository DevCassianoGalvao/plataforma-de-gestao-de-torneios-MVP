# Auditoria de Divergências — Plataforma de Gestão de Torneios

> **Revisao de rastreabilidade (2026-07-27):** esta auditoria foi cruzada com
> `public/index.php`, os controllers, views, services, CSS, JavaScript e testes
> presentes na branch `refactor/product-reconstruction`. Onde a implementacao
> atual diverge desta auditoria, prevalece a evidencia concreta registrada nos
> documentos de reconstrucao e no plano executavel.

## Addendum: branch evidence - 2026-07-27

| Claim under audit | Current evidence | Correct conclusion |
|---|---|---|
| Product navigation does not exist | `ProductNavigationService`, `ProductNavigationController`, routes 42-47 in `public/index.php`, and `tests/navigation_http_e2e.php` | A server-authorized navigation foundation exists. It is not a completed information architecture because destinations still share `admin/product-page.php`. |
| Mega-screen is the primary entry | `ProductNavigationController::legacyOperation()` and `::legacyAction()` require `AuthPolicy::requireSuperAdmin()` before using `TournamentOperationController` | It is now a guarded legacy fallback; its teams-to-reports workflow still must be split before real operations can leave it. |
| Generic CRUD is gone | entity loop at `public/index.php:48`; `AdminController::{index,edit,save,delete}`; `admin/crud.php` | False. It remains a live compatibility UI and must not be treated as product completion. |
| Public presenter is private-safe by design | `PublicPortalPresenter::content()` uses `SELECT *`; `PublicController` always renders `public/portal.php` | Incomplete. Existing tests give limited coverage, but page DTOs and page-specific templates remain required. |
| Current UI tests are browser E2E | `tests/dashboard_ui_e2e.php`, `management_ui_e2e.php`, `match_center_ui_e2e.php`, `public_ui_e2e.php`, `ui_foundation_e2e.php`, `ui_ux_audit_e2e.php` use source reads/string checks | False. Keep as structural checks; add Playwright before any visual acceptance claim. |

The reconstruction plan preserves executed migrations, existing service contracts,
authentication and authorization boundaries. No audit conclusion authorizes a
rewrite of sports rules, seed data or public/private field policy without separate
regression proof.

**Arquivo analisado:** `public.zip`
**Tipo de auditoria:** revisão estática do código, estrutura, templates, CSS, rotas, testes e documentação
**Limitação:** não foi realizada uma sessão completa no navegador conectada ao banco do servidor. As conclusões abaixo se baseiam no conteúdo efetivamente presente no ZIP e em evidências reproduzíveis no código.

---

## 1. Veredito executivo

A percepção de que o projeto está estranho, cru, mal explicado e difícil de usar é sustentada pelo código analisado.

O projeto atual é melhor descrito como uma **base técnica funcional com serviços esportivos e um painel genérico de administração de banco**, e não como o produto de gestão de torneios planejado no PRD.

A infraestrutura e parte do núcleo esportivo podem ser aproveitadas, mas a camada de produto — arquitetura de informação, fluxos assistidos, clareza da interface, portal esportivo e experiência operacional — precisa de reconstrução significativa.

### Nível de aderência estimado

| Área | Estado | Avaliação |
|---|---|---|
| Infraestrutura PHP/MySQL | Aproveitável | Base funcional |
| Banco, migrations e serviços | Parcialmente aproveitável | Exige revisão de domínio |
| Autenticação e segurança básica | Parcialmente aproveitável | Testes reais insuficientes |
| Fluxo esportivo por serviços | Parcialmente funcional | Não equivale a produto utilizável |
| Painel administrativo | Divergência grave | Parece um editor de tabelas do banco |
| Central de partida | Divergência grave | Misturada a uma tela extensa e técnica |
| Súmula e PDF | Muito abaixo do planejado | Documento textual mínimo |
| Portal público | Divergência grave | Listagens genéricas, sem experiência esportiva |
| Design system | Parcial e inconsistente | CSS sobreposto ao legado |
| Responsividade e acessibilidade | Não comprovadas | Testes apenas estáticos |
| Documentação de conclusão | Contraditória | Marcações e relatórios geram falsa segurança |

---

## 2. O que foi planejado

O PRD definiu uma plataforma com:

- operação multi-campeonato;
- identidade visual própria para cada competição;
- painel por perfil e escopo;
- cadastros assistidos;
- inscrições e documentos;
- grupos, tabela e mata-mata;
- central de partida;
- súmula digital completa;
- estatísticas e disciplina automáticas;
- portal público com experiência esportiva;
- light e dark mode;
- fontes Bricolage Grotesk e Inter;
- interface clean, legível e responsiva;
- componentes esportivos claros;
- nenhuma dependência de IDs, JSON ou edição manual do banco.

O resultado atual se distancia principalmente da forma como o usuário precisa operar o sistema.

---

## 3. Divergência crítica: o CRUD é um editor de banco

### Evidência

Em `app/Controllers/AdminController.php`, as entidades são descritas por campos técnicos como:

- `organization_id`;
- `project_id`;
- `tournament_id`;
- `team_id`;
- `person_id`;
- `settings_json`.

Em `app/Views/admin/crud.php`, esses campos são renderizados genericamente como inputs. A mesma view percorre colunas retornadas por `SELECT *` e exibe os valores diretamente.

### Consequência

A interface apresenta conceitos de banco para pessoas comuns:

- IDs numéricos;
- nomes internos de colunas;
- JSON;
- estados técnicos;
- relações sem contexto;
- datas sem tratamento;
- valores crus.

Isso explica a sensação de que “nada está bem explicado”. O usuário não recebe um fluxo de trabalho; recebe uma forma visual de editar tabelas.

### Divergência em relação ao planejado

Foi solicitado um sistema assistido com seleções, buscas, cards, filtros, etapas e validações contextualizadas. O resultado atual mantém a lógica do banco como lógica da interface.

---

## 4. Entradas técnicas continuam expostas

### Exemplos encontrados

- `app/Views/admin/tournament-configuration.php`: o regulamento é editado em um textarea chamado **Configuração JSON**.
- `app/Views/admin/upload-document.php`: o usuário precisa informar `tournament_id` numericamente.
- `app/Views/admin/access-control.php`: organização, projeto, campeonato e equipe são atribuídos por IDs numéricos.
- A tabela de acessos mostra escopos no formato `O1 P2 C3 E4`.
- Listagens administrativas ainda exibem campos como `category_id`.

### Consequência

Mesmo existindo serviços e tabelas, a aplicação ainda exige conhecimento técnico para tarefas essenciais.

### Correção necessária

Substituir os campos técnicos por:

- selects com nomes e contexto;
- busca e autocomplete;
- relações filtradas pelo escopo;
- editor estruturado de regulamento;
- labels em linguagem do negócio;
- validação e ajuda contextual.

---

## 5. Arquitetura de informação inadequada

### Evidência principal

`app/Views/admin/tournament-operations.php` concentra em uma única tela:

- resumo do campeonato;
- métricas repetidas;
- criação e listagem de equipes;
- criação e listagem de atletas;
- comissão técnica;
- inscrições;
- grupos;
- geração da tabela;
- partidas;
- escalações;
- eventos;
- homologação;
- relatórios.

A central da partida aparece dentro da tabela de jogos, em elementos expansíveis.

### Problemas de usabilidade

- página excessivamente longa;
- ausência de foco por tarefa;
- alto esforço cognitivo;
- difícil localização de ações;
- uso ruim em tablet e celular;
- risco de operar a partida errada;
- mistura de configuração, cadastro e operação ao vivo;
- nenhuma jornada guiada.

### Estrutura recomendada

Separar em rotas e páginas específicas:

1. Dashboard do campeonato;
2. Equipes;
3. Atletas e comissão;
4. Inscrições;
5. Grupos;
6. Tabela e rodadas;
7. Partidas;
8. Escalações;
9. Central da partida;
10. Homologação;
11. Classificação;
12. Mata-mata;
13. Súmulas;
14. Relatórios.

---

## 6. Navegação e layout não formam um produto consistente

### Problemas encontrados

- A sidebar está duplicada em views diferentes, em vez de ser um componente reutilizável.
- O menu é incompleto e não representa os módulos principais.
- A interface não demonstra uma adaptação consistente por perfil.
- Não existe seletor de campeonato bem integrado ao cabeçalho.
- O sistema não apresenta claramente contexto, campeonato ativo e próxima ação.
- Elementos de navegação usam caracteres e textos improvisados no lugar de um sistema de ícones.
- O painel não oferece uma hierarquia clara entre organização, projeto, campeonato, equipe e partida.

### Impacto

O usuário se perde porque não entende:

- onde está;
- qual campeonato está operando;
- o que pode fazer;
- qual é a próxima etapa;
- quais pendências precisam de atenção.

---

## 7. CSS e design system conflitantes

### Evidência

O layout base carrega diversos arquivos CSS simultaneamente, incluindo:

- `app.css`;
- `tokens.css`;
- `themes.css`;
- `layout.css`;
- `components.css`;
- `dashboard.css`;
- `management.css`;
- `public-portal.css`;
- `foundation.css`;
- `operation.css`.

Foi identificada repetição de dezenas de seletores entre os arquivos, incluindo:

- `:root`;
- tema dark;
- `body`;
- títulos;
- botões;
- sidebar;
- cards;
- conteúdo;
- navegação pública;
- hero pública.

A documentação também informa que CSS legado foi preservado durante a migração visual.

### Consequência

A aparência final depende da ordem da cascata e de sobrescritas sucessivas. Isso provoca:

- estilos inconsistentes;
- páginas com aparência diferente;
- correções que quebram outras telas;
- dificuldade de manutenção;
- comportamento imprevisível entre light e dark;
- sensação de projeto “remendado”.

### Recomendação

Não continuar adicionando CSS sobre a base atual. É necessário:

1. inventariar componentes;
2. remover ou isolar o CSS legado;
3. consolidar tokens;
4. criar um shell único;
5. migrar página por página;
6. eliminar seletores duplicados.

---

## 8. O portal público não corresponde a um portal esportivo

### Estrutura atual

`app/Views/public/portal.php` é usado como template genérico para quase todas as páginas públicas.

### Divergências encontradas

- Header com poucas opções e sem estrutura completa do campeonato.
- Marca fixa “TG”, em vez do logo real da competição.
- Logos light/dark, banner, favicon e imagem social cadastrados não são usados de forma efetiva.
- Fotos e escudos são buscados em alguns presenters, mas não aparecem nas páginas.
- Não há cards esportivos completos.
- Não há chave visual de mata-mata.
- A página “mata-mata” reaproveita a lista comum de partidas.
- A página “grupos” reaproveita uma tabela plana de classificação.
- Artilharia, assistências, cartões e rankings usam uma tabela genérica semelhante.
- Regulamento e suspensões não possuem apresentação própria consistente.
- Detalhes de notícias e galerias não estão completos no controller.
- “Próximos jogos” pode mostrar partidas antigas, pois a consulta não filtra corretamente por data e status.
- Datas e status aparecem em formatos técnicos.
- Não há paginação e filtros adequados.
- As páginas públicas usam IDs internos em vez de slugs amigáveis para jogos, equipes e atletas.
- Metadados SEO são genéricos.
- O sitemap é básico e incompleto.

### Resultado

O portal parece um conjunto de tabelas administrativas publicadas, e não uma central esportiva inspirada nas referências apresentadas.

---

## 9. Personalização por campeonato é superficial

### O que existe

- cores primária, secundária e de destaque;
- light e dark mode;
- armazenamento de alguns assets.

### O que não está realmente integrado

- logo do campeonato no portal;
- versões de logo para fundos claros e escuros;
- banner principal;
- favicon dinâmico;
- imagem social;
- patrocinadores em composição adequada;
- contraste automático das cores;
- tema do campeonato refletido no painel administrativo;
- preview confiável da personalização.

### Divergência

Foi planejado que cada campeonato parecesse uma experiência própria sobre a mesma plataforma. Atualmente, há principalmente troca de cores.

---

## 10. Central da partida abaixo do necessário

### Problemas de interface

- central misturada à tabela de partidas;
- seleção de atletas pouco contextual;
- possibilidade de escolher jogador da equipe errada antes da validação do servidor;
- evento de substituição sem uma interface clara de jogador que sai e jogador que entra;
- ausência de operação visual adequada para prorrogação;
- ausência de disputa de pênaltis realmente assistida;
- ausência de timeline operacional rica na tela principal;
- ações críticas sem uma hierarquia clara;
- risco elevado em uso ao vivo.

### Problema concreto de confirmação

O JavaScript conecta confirmações a formulários com `data-confirm`, enquanto uma ação de mudança de status coloca esse atributo no botão. Nesse caso, a confirmação não é executada como esperado.

### Consequência

Embora os services permitam demonstrar um fluxo automatizado, a operação humana real ainda é frágil.

---

## 11. Súmula e PDF muito abaixo do escopo

### Visualização atual

A súmula apresenta principalmente:

- metadados básicos;
- escalações;
- eventos;
- placar.

### Elementos planejados que não estão completos

- comissão técnica organizada;
- equipe de arbitragem completa;
- ocorrências estruturadas;
- cartões e substituições em seções oficiais;
- observações;
- confirmações ou assinaturas;
- histórico de versões;
- comparação de retificação;
- código de verificação bem integrado;
- identidade visual completa;
- geração em lote por rodada e campeonato.

### PDF

O gerador atual cria um PDF textual extremamente simples. Os arquivos gerados são muito pequenos e não correspondem a uma súmula oficial A4 com qualidade de prestação de contas.

---

## 12. Os “testes E2E” não comprovam a experiência real

### Evidência

Diversos testes chamados de E2E verificam principalmente a presença de strings no código-fonte, por exemplo:

- nome de classe CSS;
- texto de um controller;
- presença de uma constante;
- trecho de header;
- nome de componente.

Eles não abrem a aplicação em navegador real, não clicam, não preenchem os formulários e não validam o layout renderizado.

### O que esses testes não comprovam

- fluxo completo pelo navegador;
- usabilidade;
- contraste;
- overflow;
- menu mobile;
- foco por teclado;
- responsividade;
- formulário realmente funcional;
- login HTTP real;
- CSRF em navegação real;
- upload completo;
- IDOR através das rotas;
- comportamento com dados reais.

### Consequência

Mensagens como `UI_E2E_OK` e `HTTP_AUTHENTICATED_E2E_OK` transmitem um nível de segurança maior do que os testes realmente entregam.

### Correção necessária

Adotar testes reais com navegador, preferencialmente Playwright, cobrindo:

- login;
- troca de campeonato;
- cadastros;
- inscrição;
- escalação;
- eventos;
- homologação;
- portal público;
- mobile;
- permissões por perfil.

---

## 13. Documentação contraditória

### Problemas encontrados

- O plano possui itens marcados como concluídos no topo e pendentes em seções posteriores.
- Auditorias antigas e atualizações novas convivem sem separação clara.
- O sistema recebe o status “aprovado para homologação”, embora a própria documentação reconheça grandes lacunas de interface e operação.
- A auditoria de UI se declara final, mas admite que não houve validação real em navegador.

### Impacto

O Codex e o responsável pelo projeto passam a trabalhar com uma percepção incorreta de avanço.

### Recomendação

Criar uma única fonte de verdade:

- `PRODUCT_GAP_REPORT.md`;
- `REBUILD_PLAN.md`;
- critérios de aceite objetivos;
- evidência de teste real para cada item;
- nenhuma marcação baseada somente em existência de arquivo ou classe.

---

## 14. Problemas de arquitetura e manutenção

### Pontos encontrados

- Views e controllers extensos/minificados dificultam revisão.
- Existem templates públicos duplicados ou aparentemente abandonados.
- Há serviços antigos com regras fixas convivendo com serviços novos configuráveis.
- O controller público carrega vários conjuntos de dados mesmo quando a página não precisa deles.
- Algumas filtragens de escopo são feitas depois de buscar muitos registros, em vez de filtrar no SQL.
- Uso frequente de `SELECT *`.
- Arquivos de log, exports e grande quantidade de PDFs de teste foram incluídos no ZIP.
- O pacote mistura código-fonte com artefatos gerados.

### Consequência

O projeto fica mais pesado, confuso e propenso a regressão.

---

## 15. Registros de erros encontrados

O ZIP inclui logs com erros de desenvolvimento anteriores, incluindo situações como:

- colunas incompatíveis;
- ausência de `deleted_at`;
- escalação duplicada;
- mata-mata de demonstração ausente;
- escopo de equipe ou operador negado;
- entrada técnica exposta;
- portal indisponível;
- atleta associado à equipe incorreta.

Esses registros podem representar problemas já corrigidos, mas demonstram que o pacote entregue contém rastros de testes e falhas sem uma limpeza formal de release.

---

## 16. O que pode ser preservado

Nem tudo precisa ser descartado.

### Base aproveitável

- stack PHP/MySQL/HTML/CSS/JS;
- estrutura MVC básica;
- migrations;
- serviços de campeonato;
- esquema multi-campeonato;
- autenticação;
- controle inicial de escopo;
- armazenamento privado;
- parte do fluxo esportivo automatizado;
- seed de demonstração;
- tokens iniciais de tema;
- Bricolage Grotesk e Inter.

### Partes que exigem reconstrução

- navegação;
- arquitetura de telas;
- CRUDs genéricos;
- fluxo de regulamento;
- central da partida;
- súmula;
- portal público;
- integração dos assets do campeonato;
- componentes visuais;
- validação em navegador;
- documentação de status.

---

## 17. Diagnóstico por camada

### Backend e banco

**Estado:** parcialmente aproveitável.
**Ação:** revisar serviços críticos, remover duplicidades e preservar o que já tem testes de domínio confiáveis.

### Painel administrativo

**Estado:** reconstrução alta.
**Ação:** substituir CRUD genérico por telas orientadas a tarefas.

### Central da partida

**Estado:** reconstrução alta.
**Ação:** criar rota e interface dedicadas, adequadas a operação ao vivo.

### Portal público

**Estado:** praticamente reconstrução.
**Ação:** desenvolver um portal esportivo próprio, com dados reais e identidade do campeonato.

### UI/design system

**Estado:** fundação parcial e inconsistente.
**Ação:** consolidar CSS antes de redesenhar páginas.

### Testes

**Estado:** testes de serviços úteis, validação de interface insuficiente.
**Ação:** criar testes de navegador e aceite por perfil.

---

## 18. Plano de recuperação recomendado

### Etapa 1 — Congelar e preservar

- manter a branch atual como referência;
- criar uma branch de reconstrução;
- fazer backup do banco;
- não continuar aplicando CSS sobre as telas atuais;
- não adicionar mais módulos antes de reorganizar o produto.

### Etapa 2 — Definir o produto real

Criar:

- mapa de módulos;
- mapa de rotas;
- matriz de perfis;
- fluxos de tarefa;
- inventário de páginas;
- critérios de aceite por tela.

### Etapa 3 — Refatorar a arquitetura de informação

Separar as operações em páginas específicas e remover o mega-dashboard operacional.

### Etapa 4 — Desativar o CRUD genérico para usuários

O CRUD genérico pode permanecer apenas como ferramenta interna de desenvolvimento, protegido e fora da navegação principal.

### Etapa 5 — Consolidar o design system

- remover legado conflitante;
- manter uma única fonte de tokens;
- criar componentes reutilizáveis;
- implementar layout administrativo único;
- validar light e dark.

### Etapa 6 — Reconstruir os fluxos prioritários

1. Campeonato;
2. Equipes;
3. Atletas;
4. Inscrições;
5. Grupos e tabela;
6. Partidas;
7. Escalação;
8. Central da partida;
9. Homologação;
10. Súmula.

### Etapa 7 — Reconstruir o portal público

Criar páginas e componentes esportivos reais, usando os assets e a identidade de cada campeonato.

### Etapa 8 — Validar no navegador

Executar testes reais por perfil e viewport antes de declarar qualquer módulo concluído.

---

## 19. Critérios mínimos antes de voltar ao design visual final

Antes de investir em refinamento visual, o sistema precisa demonstrar:

- nenhuma entrada manual de ID;
- nenhum JSON visível para usuário comum;
- menu por perfil;
- contexto claro de campeonato;
- uma página por tarefa importante;
- escalação visual;
- central de partida dedicada;
- súmula completa;
- portal com rotas próprias;
- assets do campeonato funcionando;
- estados traduzidos;
- datas formatadas;
- testes reais em navegador.

---

## 20. Conclusão

O projeto não falhou por falta de código. Ele falhou principalmente por ter sido desenvolvido como uma coleção de tabelas, services e provas técnicas, sem uma tradução adequada para o produto que seria operado por organizadores, treinadores, operadores e público.

A base técnica pode ser reaproveitada, mas a interface não deve continuar recebendo pequenos remendos. O caminho mais seguro é preservar o backend útil e reconstruir a camada de produto com arquitetura de informação, fluxos assistidos e componentes próprios.

**Veredito:** o sistema atual não representa fielmente o produto planejado e não deve ser tratado como uma simples etapa de “melhoria visual”. É necessária uma reconstrução orientada a produto da interface administrativa e do portal público.
