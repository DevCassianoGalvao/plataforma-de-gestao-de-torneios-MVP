# Sistema de Design da Plataforma

## Fundacao

Esta camada atende painel administrativo denso e portal por campeonato sem copiar marcas, textos ou composicoes de terceiros. Ela nao altera regras esportivas, dados ou permissoes.

## Arquitetura CSS

- `tokens.css`: tipografia, espacamento, raios, sombras e cores semanticas.
- `themes.css`: tema escuro e status esportivos.
- `layout.css`: shell, portal, grades e breakpoints.
- `components.css`: componentes reutilizaveis.
- `foundation.css`: tipografia, foco e reduced motion.
- `app.css` e `operation.css`: legado preservado durante a migracao visual.

## Ownership ativo

`app.css` foi removido da carga global em 2026-07-28 por duplicar tokens, body, sidebar, painel e botao. Ele continua no repositorio apenas como rollback documentado. A pilha ativa e deliberada: tokens, temas, fundacao, layout, componentes e folhas estritamente compostas por jornada.

## Tipografia e temas

`Bricolage Grotesk` atende headings, numeros e placares; `Inter` atende tabelas, formularios e navegacao. Ambas possuem fallback seguro. Light usa superficie clara e cards brancos; dark usa camadas azul-petroleo, sem preto puro.

## Personalizacao por campeonato

`ThemeService::allowed()` aceita apenas hexadecimal de seis digitos. O portal aplica `--champ-primary`, `--champ-secondary` e `--champ-accent` com fallback da plataforma. Elas devem decorar o contexto do campeonato, sem alterar texto global ou markup.

## Componentes e acessibilidade

Botoes, cards, metric cards, badges, status, avatar, escudo, placar, tabelas, tabs, modal, drawer, alerts, toast, empty state e skeleton compartilham tokens. Status usam texto e formato alem de cor. Tabelas usam `.table-wrap`; controles possuem ao menos 42px. Ha skip link, foco visivel e `prefers-reduced-motion`.

## Responsividade

O shell e fluido e tem ajustes em 800px e 520px. Sidebar fica fixa no desktop e navegacao horizontal controlada no mobile; o controlador de drawer esta disponivel para templates que adotarem o menu recolhivel.

## Escopo desta fase

Dashboard operacional, configuracao, central de partida, CRUD assistido e demais telas do portal ainda devem adotar esta base em fases futuras. A validacao estrutural esta em `tests/ui_foundation_e2e.php`; verificacao visual em navegador nos breakpoints de 320px a 1920px continua pendente.
