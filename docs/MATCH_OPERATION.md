# Central Operacional da Partida

## Escopo

A Etapa 9 separa a operação de uma partida da homologação administrativa. A central e uma página própria, protegida por permissão e escopo, para registrar o que ocorreu em campo e preparar o resultado.

Não fazem parte desta etapa a classificação definitiva, a súmula PDF, notícias, Vai e Vem ou portal público. A acumulação disciplinar desta etapa e processada depois da homologação.

## Disciplina após homologação

Eventos válidos de amarelo, segundo amarelo e vermelho são enviados ao ledger disciplinar. A suspensão automática e o cumprimento de suspensões anteriores são processados de forma idempotente.

## Operação

Cada partida possui uma operação com os estados:

- open: recebe registros e ajustes operacionais;
- awaiting_homologation: o operador finalizou e aguarda organizador;
- homologated: resultado confirmado; não ha edição comum.

O operador finaliza com checklist confirmado. O organizador ou administrador homologa em uma ação separada, com confirmação explícita. O operador não homologa a própria partida. Retificação avançada não faz parte do MVP.

## Registros

A central aceita:

- gol;
- gol contra;
- assistência;
- amarelo;
- segundo amarelo;
- vermelho;
- substituição;
- ocorrência;
- penalti convertido ou perdido;
- início e fim dos tempos, incluindo prorrogação quando configurada.

O minuto é opcional. A ordem temporal não é obrigatória. Registros inválidos não entram no calculo.

O gol contra e salvo com o atleta da equipe adversária e com o time que marcou, portanto o placar continua calculável por equipe. Pênaltis da disputa ficam separados e não entram no placar do tempo normal nem em artilharia.

## Placar administrativo

O operador pode registrar resultado administrativo com placar não negativo e motivo. Quando presente, ele substitui o placar calculado pelos gols válidos. A alteração fica vinculada ao usuário e ao horário.

## Substituições

A substituição registra equipe, atleta que sai, atleta que entra, período, janela e minuto opcional. O servidor verifica que:

- a equipe pertence a partida;
- a escalação está confirmada;
- o atleta que sai e titular;
- o atleta que entra e reserva;
- a janela está configurada no regulamento;
- o limite total está configurado no regulamento.

## Arbitragem e checklist

A arbitragem possui funções configuráveis para árbitro, assistentes, mesário, quarto oficial e outras funções previstas na súmula. Para finalizar, o servidor exige:

- duas escalações confirmadas com onze titulares;
- árbitro principal;
- início e fim dos dois tempos;
- confirmação explícita do operador.

Gols, cartões, substituições, ocorrências e pênaltis podem ser inexistentes quando não ocorreram; a central não exige cronologia minuto a minuto.

## Permissões e privacidade

- administrador acessa e opera tudo;
- operador acessa somente partidas atribuidas e pode operar;
- organizador visualiza e homologa partidas de campeonatos autorizados;
- treinador visualiza conforme o escopo da equipe;
- comunicação recebe 403;
- adversários não recebem permissão de edição;
- nenhum dado privado de atleta ou documento e servido por rota pública.

Todas as mutações usam CSRF, auditoria e histórico de transição de operação.

## Rotas

Todas respeitam APP_BASE_PATH.

- GET /admin/partidas/{id}/operação
- POST /admin/partidas/{id}/operação/evento
- POST /admin/partidas/{id}/operação/substituição
- POST /admin/partidas/{id}/operação/arbitragem
- POST /admin/partidas/{id}/operação/horários
- POST /admin/partidas/{id}/operação/resultado-administrativo
- POST /admin/partidas/{id}/operação/finalizar
- POST /admin/partidas/{id}/operação/homologar

## Persistência

A migration 0009_match_operation.sql cria operações, atribuições de operador, arbitragem, eventos, substituições e histórico. O seed de demonstração atribui um operador e quatro funções de arbitragem de forma idempotente.
