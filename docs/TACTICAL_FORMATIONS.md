# Formações Táticas

## Catálogo inicial

O seed cria de forma idempotente estas nove formações:

- `4-4-2`
- `4-3-3`
- `4-2-3-1`
- `4-1-4-1`
- `4-5-1`
- `3-5-2`
- `3-4-3`
- `5-3-2`
- `5-4-1`

Cada formação possui exatamente 11 slots estruturados, incluindo exatamente um goleiro. Os slots armazenam código de posição, rotulo, grupo, ordem e coordenadas; a regra não depende apenas do texto do nome da formação.

## Coordenadas

As coordenadas são normalizadas entre 0 e 100 e ficam em `DECIMAL(5,2)`:

- `horizontal_position`: 0 e a esquerda, 100 e a direita;
- `vertical_position`: 0 e a linha defensiva, 100 e a linha ofensiva.

O campo funcional de escalações usa essas coordenadas para renderizar os slots da partida. A distribuição automática prioriza a posição principal, depois secundárias e grupo posicional; a comissão pode ajustar qualquer slot manualmente, inclusive fora de posição.

## Service e uso

`TacticalFormationService` lista formações ativas, carrega slots, valida quantidade, goleiro e coordenadas, seleciona a formação padrão da equipe e devolve uma representação estruturada para uso posterior. A equipe registra qual formação e padrão, quando foi alterada e por qual usuário.

A tela `/admin/equipes/{slug}/formacao` mostra o catálogo e permite selecionar a formação padrão da equipe. A partida pode sugerir essa formação e substitui-la sem alterar a configuração da equipe. A autorização segue o escopo da equipe: administrador, organizador autorizado e treinador/gestor vinculado podem consultar conforme suas permissões; a seleção exige a permissão correspondente.

## Limites

Formação padrão e uma sugestao da equipe, não uma escalação. Gols, cartões, substituições e classificação continuam fora da Etapa 8.
