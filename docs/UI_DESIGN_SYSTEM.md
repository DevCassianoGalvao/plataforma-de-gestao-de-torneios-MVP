# Design system da plataforma

## Direcao

O sistema visual combina precisao de quadro tatico com a sobriedade de um centro de operacao. A assinatura e a camada noturna azul, marcada por verde de campo em estados esportivos e por azul eletrico nas acoes. A tela de login concentra o gesto visual mais expressivo; o restante da aplicacao privilegia leitura, comparacao e acao.

## Tokens

```css
--ui-bg: #0a1422;
--ui-bg-deep: #050e1c;
--ui-surface: #101c2b;
--ui-surface-2: #16202f;
--ui-surface-3: #212a39;
--ui-surface-4: #2c3545;
--ui-text: #d9e3f7;
--ui-text-muted: #aeb7ca;
--ui-text-soft: #8d97ad;
--ui-primary: #2563eb;
--ui-primary-soft: #b4c5ff;
--ui-secondary: #22c55e;
--ui-secondary-soft: #4ae176;
--ui-info: #60a5fa;
--ui-danger: #ff6b6b;
--ui-warning: #f6c453;
--ui-border: rgba(217, 227, 247, .12);
--ui-border-strong: rgba(180, 197, 255, .34);
--ui-radius-sm: 2px;
--ui-radius: 4px;
--ui-radius-lg: 8px;
--ui-radius-xl: 12px;
--ui-shadow: 0 16px 40px rgba(0, 0, 0, .18);
--ui-speed: 160ms ease;
```

## Tipografia

- `Hanken Grotesk`: h1-h6, placares, numeros, labels de contexto e marcas.
- `Inter`: paragrafo, formulario, tabela, ajuda e navegacao.
- Nenhuma regra usa letter-spacing negativo. Labels podem usar espaco positivo discreto.
- O tamanho responsivo usa limites fixos e `clamp` somente em titulos de destaque.

## Layout

- App administrativo: sidebar fixa de 264px no desktop, header de 72px e conteudo limitado a 1280px.
- Portal: header proprio por campeonato, conteudo limitado a 1280px e leitura publica separada.
- Mobile: sidebar vira drawer, tabelas usam rolagem horizontal, campo tatico preserva proporcao e controles alternativos continuam visiveis.
- Secoes de pagina sao superficies abertas; cards ficam reservados a itens repetidos, modais e ferramentas enquadradas.

## Componentes

- `button` primario azul, secundario ghost e acao critica vermelha.
- Inputs com superficie inset, foco em azul e erro acompanhado de texto.
- `status` com texto + cor + forma, mantendo leitura em monocromia.
- Cards de dados com borda sutil e hover tonal.
- Tabelas com cabecalho fixo visualmente, linhas escaneaveis e wrapper responsivo.
- Tabs, filtros, breadcrumbs, empty states e alertas usam a mesma escala de espacamento.
- Icones de acao sao curtos e sempre recebem `aria-label` ou texto visivel.

## Temas

- O administrador inicia no tema escuro do Stitch.
- O portal inicia claro para leitura publica, mas ambos aceitam alternancia e salvam a preferencia em `localStorage`.
- `prefers-color-scheme` e usado como fallback quando nao existe preferencia salva.
- Contrastes de campeonato usam fallback azul/verde quando a cor cadastrada nao passa pelo limite seguro.

## Acessibilidade e movimento

- Foco visivel com outline azul claro.
- Respeito a `prefers-reduced-motion`.
- Labels associados, mensagens de erro proximas e navegacao por teclado.
- Estados nunca dependem apenas de cor.
- Animacoes sao curtas e funcionais: drawer, hover, foco e troca de tema.
