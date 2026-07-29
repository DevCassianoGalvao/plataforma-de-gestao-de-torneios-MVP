# Classificacao e Mata-mata

## Escopo

A Etapa 11 calcula a classificacao por grupo a partir somente de partidas com `matches.status = homologated`. Operacao, disciplina e portal publico continuam com seus proprios limites; esta etapa nao cria escalações nem substitui a homologacao.

## Classificacao

Cada equipe recebe jogos, vitorias, empates, derrotas, gols pro, gols contra, saldo, pontos, aproveitamento, posicao e situacao. A pontuacao vem de `regulation_points_settings`, e a quantidade de classificados vem de `regulation_format_settings`; nenhum limite fica fixo no service.

Os desempates habilitados sao carregados em ordem de `regulation_tiebreakers`: vitorias, saldo, gols marcados, confronto direto, menor disciplina, decisao administrativa e sorteio. O confronto direto calcula uma mini-tabela com os jogos homologados entre o conjunto empatado. A coluna `separated_by` registra o criterio que separou a linha.

O recalculo ocorre em transacao. O snapshot por grupo e substituido de forma atomica e uma chave SHA-256 das entradas impede duplicacao de execucoes com a mesma fonte. O criterio `draw_lots` usa o identificador da equipe como desempate deterministico, permitindo reproducao e auditoria do resultado.

## Mata-mata

O preset cria uma fase `knockout` com Grupo A x Grupo B, quatro quartas, duas semifinais e uma final. Os cruzamentos sao:

- A1 x B4;
- B1 x A4;
- A2 x B3;
- B2 x A3.

As semifinais sao QF1 x QF3 e QF2 x QF4. A final usa os vencedores das semifinais. Uma partida eliminatoria so avanca depois de homologada. O resultado normal vem de gols validos; resultado administrativo tem precedencia; empate pode ser decidido por penaltis registrados na operacao. W.O. e decisao administrativa usam o resultado administrativo homologado, preservando o criterio da decisao.

O processamento e idempotente: uma tie finalizada nao e reprocessada, fases, rodadas, ties e partidas usam chaves de negocio e a final grava um unico campeao e vice.

## Permissoes e rotas

- `standings.view`: administrador, organizador autorizado e treinador/gestor da equipe;
- `standings.recalculate`: administrador e organizador autorizado;
- `knockout.generate`: administrador e organizador autorizado;
- `knockout.advance`: administrador e organizador autorizado.

Operador e comunicacao recebem `403`. Acoes POST exigem CSRF e o service valida campeonato/fase no servidor.

Rotas administrativas:

- `GET /admin/classificacao?phase_id=...`;
- `POST /admin/classificacao/recalcular`;
- `POST /admin/mata-mata/gerar`;
- `GET /admin/mata-mata?phase_id=...`;
- `POST /admin/mata-mata/partidas/{id}/avancar`.

## Banco

`competition_standings` guarda o snapshot atual; `standings_calculation_runs` guarda hashes de fonte; `knockout_rounds` e `knockout_ties` representam a chave; `competition_results` guarda campeao e vice. A migration e `0011_standings_and_knockout.sql`.

## Limites

A UI desta etapa e administrativa e funcional, sem design visual definitivo. Na Etapa 11 ficaram fora do escopo noticias, Vai e Vem, portal publico, retificacao avancada e nova logica acumulada de suspensoes. A sumula digital foi entregue na Etapa 12.
