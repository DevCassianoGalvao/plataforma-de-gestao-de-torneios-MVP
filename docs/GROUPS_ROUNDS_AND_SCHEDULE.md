# Grupos, Rodadas e Tabela

## Escopo

Etapa 7 entrega a base estrutural da competicao: locais, fases, grupos, distribuicao de equipes, rodadas, partidas, agenda, historico de alteracoes e decisoes administrativas. Nao entrega escalacoes, operacao da partida, placar, cartoes ou classificacao final.

## Modelo

- `competition_phases` guarda fase, ordem, quantidade esperada de grupos/equipes, classificados e status;
- `competition_groups` guarda Grupo A/B, limites, classificados e publicacao;
- `group_teams` vincula uma equipe a um unico grupo dentro da fase, com posicao e retirada;
- `venues` pertence ao campeonato e pode ser selecionado na agenda;
- `competition_rounds` identifica rodada por grupo e periodo;
- `matches` guarda mandante, visitante, rodada, local, data, hora, status e observacao;
- `match_schedule_changes` preserva cada alteracao de agenda;
- `administrative_decisions` registra decisoes ligadas a uma partida.

Nenhum formulario aceita JSON ou pede ID para digitar. IDs existem apenas como valores internos de selects e rotas autorizadas.

## Round-robin

`RoundRobinGenerator` usa algoritmo de circulo:

- quantidade par gera `n - 1` rodadas;
- quantidade impar adiciona uma folga virtual e gera `n` rodadas;
- cada par de equipes se enfrenta uma vez no turno unico;
- ida e volta inverte mandante/visitante e cria segundo bloco de rodadas;
- equipes nunca enfrentam a si mesmas;
- `fixture_key` SHA-256 torna a confirmacao idempotente.

## Assistente

O wizard separa fase/grupos, confirmacao das equipes, turno, periodo, dias permitidos, horarios, locais, previa, conflitos e confirmacao. Conflitos basicos verificam equipe e local no mesmo horario, tanto contra partidas existentes quanto entre partidas da propria previa.

## Status

Partidas usam `draft`, `scheduled`, `confirmed`, `postponed`, `cancelled`, `wo`, `finished` e `homologated`. Nesta etapa nao existem placar ou impacto esportivo de W.O.; o status e a estrutura administrativa ficam preparados para etapas posteriores.

Alteracoes de data, hora, local, adiamento e cancelamento exigem motivo, permissao de agenda e CSRF. Cada alteracao grava valores anterior e novo em `match_schedule_changes`.

## Escopo

- administrador acessa todos os campeonatos;
- organizador opera apenas campeonatos vinculados;
- treinador/gestor consulta partidas em que sua equipe participa;
- operador e comunicacao recebem `403`;
- nenhuma partida possui rota publica nesta etapa.

## Seed e testes

`ScheduleSeed` cria de forma idempotente dois locais, fase de grupos, Grupo A e Grupo B, cinco equipes em cada grupo, quatro classificados por grupo, cinco rodadas por grupo e tabela ficticia de turno unico.

Cobertura: round-robin par/impar, folgas, ida e volta, limites, equipe duplicada, conflitos, idempotencia, agenda, adiamento, cancelamento, proximos jogos, escopo, IDOR, CSRF, base path e seed duplo.

Proxima etapa: escalacoes e campo visual. Classificacao, operacao da partida e disciplina permanecem fora deste commit.
