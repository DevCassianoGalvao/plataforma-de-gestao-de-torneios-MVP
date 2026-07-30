# Auditoria de Responsividade Mobile

Data: 2026-07-30

## Escopo auditado

- Painel administrativo autenticado: navegação, cabeçalho, filtros, formulários, tabelas, cartões, centro operacional e escalações.
- Portal público: cabeçalho, menu, páginas de campeonato, resultados, classificação, equipes, atletas, notícias, Vai e Vem, contato e detalhes de partida.
- Larguras de referência: 320 px, 375 px, 390 px, 600 px e 800 px.

## Correções aplicadas

- O menu administrativo passou a ser uma gaveta com camada de fundo, fechamento por botão, clique fora, navegação por link, `Escape` e bloqueio de rolagem do conteúdo.
- O menu do portal passou a ser uma gaveta flutuante. A lista de links deixa de ocupar ou quebrar o cabeçalho em telas estreitas.
- Os dois gatilhos usam `aria-controls` e atualizam `aria-expanded`.
- Filtros e ações se reorganizam em uma coluna quando necessário; campos mantêm fonte de 16 px para impedir zoom automático no iOS.
- Tabelas permanecem dentro de áreas com rolagem horizontal própria. Não provocam rolagem horizontal na página.
- Placar, centro de operação, campos táticos e escalações passam para uma coluna no celular; o editor tático reduz os elementos com segurança em 320 px.
- Cabeçalhos, títulos, status e botões recebem limites de largura, quebra segura e espaçamento compatível com toque.

## Evidência de validação

- Navegador automatizado: 48 combinações de rota e largura no portal, em 320 px, 390 px, 600 px e 800 px; nenhuma apresentou rolagem horizontal da página.
- Navegador automatizado: 64 combinações de rota e largura no painel administrativo nas mesmas faixas; nenhuma apresentou rolagem horizontal da página.
- Navegação móvel: menu público e administrativo validados para abrir, atualizar `aria-expanded`, bloquear a rolagem e fechar por toque externo ou `Escape`.
- Código: PHP lint, sintaxe de JavaScript e suíte unitária, integração e HTTP executados com sucesso.

## Risco residual e validação manual

O projeto tem CSS legado em múltiplas camadas. Esta rodada adiciona uma camada final que prevalece nos breakpoints móveis sem alterar fluxos de negócio. Antes de publicar, validar no navegador real:

1. Abrir e fechar ambos os menus em 320 px e 390 px, inclusive com `Escape` e toque fora.
2. Percorrer filtros extensos, tabelas de inscrições e classificação, confirmando que só a tabela rola lateralmente.
3. Abrir o centro de partida e a tela de escalação em 320 px, 390 px e 768 px.
4. Conferir portal, detalhe de partida e as duas formações em 375 px e 600 px.
5. Confirmar que nenhum formulário ativa zoom automático no iPhone.

Durante a auditoria, a home pública expôs uma inconsistência de schema em uma base legada: a migration constava como aplicada, porém `championship_sponsors.partner_type` não existia. A migration `0021_partner_type_schema_repair.sql` corrige esse estado antes da publicação visual.

## Próxima manutenção recomendada

Consolidar as regras legadas do `public/assets/app.css` em blocos por produto (admin e portal). Não é uma correção segura para ser misturada com esta rodada, porque alteraria a cascata visual em todas as telas de desktop.
