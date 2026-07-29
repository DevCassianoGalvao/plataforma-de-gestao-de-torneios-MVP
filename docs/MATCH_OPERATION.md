# Central Operacional da Partida

## Escopo

A Etapa 9 separa a operacao de uma partida da homologacao administrativa. A central e uma pagina propria, protegida por permissao e escopo, para registrar o que ocorreu em campo e preparar o resultado.

Nao fazem parte desta etapa a classificacao definitiva, a sumula PDF, noticias, Vai e Vem ou portal publico. A acumulacao disciplinar desta etapa e processada depois da homologacao.

## Disciplina apos homologacao

Eventos validos de amarelo, segundo amarelo e vermelho sao enviados ao ledger disciplinar. A suspensao automatica e o cumprimento de suspensoes anteriores sao processados de forma idempotente.

## Operacao

Cada partida possui uma operacao com os estados:

- open: recebe registros e ajustes operacionais;
- awaiting_homologation: o operador finalizou e aguarda organizador;
- homologated: resultado confirmado; nao ha edicao comum.

O operador finaliza com checklist confirmado. O organizador ou administrador homologa em uma acao separada, com confirmacao explicita. O operador nao homologa a propria partida. Retificacao avancada nao faz parte do MVP.

## Registros

A central aceita:

- gol;
- gol contra;
- assistencia;
- amarelo;
- segundo amarelo;
- vermelho;
- substituicao;
- ocorrencia;
- penalti convertido ou perdido;
- inicio e fim dos tempos, incluindo prorrogacao quando configurada.

O minuto e opcional. A ordem temporal nao e obrigatoria. Registros invalidos nao entram no calculo.

O gol contra e salvo com o atleta da equipe adversaria e com o time que marcou, portanto o placar continua calculavel por equipe. Penaltis da disputa ficam separados e nao entram no placar do tempo normal nem em artilharia.

## Placar administrativo

O operador pode registrar resultado administrativo com placar nao negativo e motivo. Quando presente, ele substitui o placar calculado pelos gols validos. A alteracao fica vinculada ao usuario e ao horario.

## Substituicoes

A substituicao registra equipe, atleta que sai, atleta que entra, periodo, janela e minuto opcional. O servidor verifica que:

- a equipe pertence a partida;
- a escalacao esta confirmada;
- o atleta que sai e titular;
- o atleta que entra e reserva;
- a janela esta configurada no regulamento;
- o limite total esta configurado no regulamento.

## Arbitragem e checklist

A arbitragem possui funcoes configuraveis para arbitro, assistentes, mesario, quarto oficial e outras funcoes previstas na sumula. Para finalizar, o servidor exige:

- duas escalacoes confirmadas com onze titulares;
- arbitro principal;
- inicio e fim dos dois tempos;
- confirmacao explicita do operador.

Gols, cartoes, substituicoes, ocorrencias e penaltis podem ser inexistentes quando nao ocorreram; a central nao exige cronologia minuto a minuto.

## Permissoes e privacidade

- administrador acessa e opera tudo;
- operador acessa somente partidas atribuidas e pode operar;
- organizador visualiza e homologa partidas de campeonatos autorizados;
- treinador visualiza conforme o escopo da equipe;
- comunicacao recebe 403;
- adversarios nao recebem permissao de edicao;
- nenhum dado privado de atleta ou documento e servido por rota publica.

Todas as mutacoes usam CSRF, auditoria e historico de transicao de operacao.

## Rotas

Todas respeitam APP_BASE_PATH.

- GET /admin/partidas/{id}/operacao
- POST /admin/partidas/{id}/operacao/evento
- POST /admin/partidas/{id}/operacao/substituicao
- POST /admin/partidas/{id}/operacao/arbitragem
- POST /admin/partidas/{id}/operacao/horarios
- POST /admin/partidas/{id}/operacao/resultado-administrativo
- POST /admin/partidas/{id}/operacao/finalizar
- POST /admin/partidas/{id}/operacao/homologar

## Persistencia

A migration 0009_match_operation.sql cria operacoes, atribuicoes de operador, arbitragem, eventos, substituicoes e historico. O seed de demonstracao atribui um operador e quatro funcoes de arbitragem de forma idempotente.
