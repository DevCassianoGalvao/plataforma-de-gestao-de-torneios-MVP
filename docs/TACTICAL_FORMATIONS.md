# Formacoes Taticas

## Catalogo inicial

O seed cria de forma idempotente estas nove formacoes:

- `4-4-2`
- `4-3-3`
- `4-2-3-1`
- `4-1-4-1`
- `4-5-1`
- `3-5-2`
- `3-4-3`
- `5-3-2`
- `5-4-1`

Cada formacao possui exatamente 11 slots estruturados, incluindo exatamente um goleiro. Os slots armazenam codigo de posicao, rotulo, grupo, ordem e coordenadas; a regra nao depende apenas do texto do nome da formacao.

## Coordenadas

As coordenadas sao normalizadas entre 0 e 100 e ficam em `DECIMAL(5,2)`:

- `horizontal_position`: 0 e a esquerda, 100 e a direita;
- `vertical_position`: 0 e a linha defensiva, 100 e a linha ofensiva.

O campo funcional de escalacoes usa essas coordenadas para renderizar os slots da partida. A distribuicao automatica prioriza a posicao principal, depois secundarias e grupo posicional; a comissao pode ajustar qualquer slot manualmente, inclusive fora de posicao.

## Service e uso

`TacticalFormationService` lista formacoes ativas, carrega slots, valida quantidade, goleiro e coordenadas, seleciona a formacao padrao da equipe e devolve uma representacao estruturada para uso posterior. A equipe registra qual formacao e padrao, quando foi alterada e por qual usuario.

A tela `/admin/equipes/{slug}/formacao` mostra o catalogo e permite selecionar a formacao padrao da equipe. A partida pode sugerir essa formacao e substitui-la sem alterar a configuracao da equipe. A autorizacao segue o escopo da equipe: administrador, organizador autorizado e treinador/gestor vinculado podem consultar conforme suas permissoes; a selecao exige a permissao correspondente.

## Limites

Formacao padrao e uma sugestao da equipe, nao uma escalacao. Gols, cartoes, substituicoes e classificacao continuam fora da Etapa 8.
