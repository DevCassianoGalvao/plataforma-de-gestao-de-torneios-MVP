# Grupos, Rodadas e Tabela

## Escopo

Etapa 7 entrega a base estrutural da competição: locais, fases, grupos, distribuição de equipes, rodadas, partidas, agenda, histórico de alterações e decisões administrativas. Não entrega escalações, operação da partida, placar, cartões ou classificação final.

## Modelo

- `competition_phases` guarda fase, ordem, quantidade esperada de grupos/equipes, classificados e status;
- `competition_groups` guarda Grupo A/B, limites, classificados e publicação;
- `group_teams` vincula uma equipe a um único grupo dentro da fase, com posição e retirada;
- `venues` pertence ao campeonato e pode ser selecionado na agenda;
- `competition_rounds` identifica rodada por grupo e período;
- `matches` guarda mandante, visitante, rodada, local, data, hora, status e observação;
- `match_schedule_changes` preserva cada alteração de agenda;
- `administrative_decisions` registra decisões ligadas a uma partida.

Nenhum formulário aceita JSON ou pede ID para digitar. IDs existem apenas como valores internos de selects e rotas autorizadas.

## Round-robin

`RoundRobinGenerator` usa algoritmo de círculo:

- quantidade par gera `n - 1` rodadas;
- quantidade impar adiciona uma folga virtual e gera `n` rodadas;
- cada par de equipes se enfrenta uma vez no turno único;
- ida e volta inverte mandante/visitante e cria segundo bloco de rodadas;
- equipes nunca enfrentam a si mesmas;
- `fixture_key` SHA-256 torna a confirmação idempotente.

## Assistente

O wizard separa fase/grupos, confirmação das equipes, turno, período, dias permitidos, horários, locais, prévia, conflitos e confirmação. Conflitos básicos verificam equipe e local no mesmo horário, tanto contra partidas existentes quanto entre partidas da própria prévia.

## Status

Partidas usam `draft`, `scheduled`, `confirmed`, `postponed`, `cancelled`, `wo`, `finished` e `homologated`. Nesta etapa não existem placar ou impacto esportivo de W.O.; o status e a estrutura administrativa ficam preparados para etapas posteriores.

Alterações de data, hora, local, adiamento e cancelamento exigem motivo, permissão de agenda e CSRF. Cada alteração grava valores anterior e novo em `match_schedule_changes`.

## Escopo

- administrador acessa todos os campeonatos;
- organizador opera apenas campeonatos vinculados;
- treinador/gestor consulta partidas em que sua equipe participa;
- operador e comunicação recebem `403`;
- nenhuma partida possui rota pública nesta etapa.

## Seed e testes

`ScheduleSeed` cria de forma idempotente dois locais, fase de grupos, Grupo A e Grupo B, cinco equipes em cada grupo, quatro classificados por grupo, cinco rodadas por grupo e tabela fictícia de turno único.

Cobertura: round-robin par/impar, folgas, ida e volta, limites, equipe duplicada, conflitos, idempotência, agenda, adiamento, cancelamento, próximos jogos, escopo, IDOR, CSRF, base path e seed duplo.

Próxima etapa: escalações e campo visual. Classificação, operação da partida e disciplina permanecem fora deste commit.
