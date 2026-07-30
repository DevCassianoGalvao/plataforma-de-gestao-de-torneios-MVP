# Formacoes e Escalacoes Taticas

## Escopo

Etapa 8 implementa uma escalacao independente para cada equipe em cada partida. A partida continua sem placar, gols, cartoes ou classificacao. A formacao padrao da equipe e uma sugestao; a formacao escolhida fica registrada na escalacao da partida.

## Modelo

As tabelas de migration 0008 sao:

- match_lineups: partida, equipe, formacao, status, versao, capitao, goleiro e reabertura;
- match_lineup_players: titulares, reservas, slot, numero, posicao e alerta de fora de posicao;
- match_lineup_staff: membros da comissao presentes;
- match_lineup_history: criacao, salvamento, confirmacao e reabertura.

Existe no maximo uma escalacao por equipe em uma partida. O status inicia como draft e passa a confirmed somente com validacao do servidor.

## Distribuicao automatica

O comando de distribuicao usa os atletas aprovados no elenco oficial, ativos e pertencentes a equipe da partida. Para cada slot, a prioridade e:

1. posicao principal exata;
2. posicao secundaria exata;
3. mesmo grupo posicional principal;
4. mesmo grupo posicional secundario;
5. qualquer atleta elegivel restante.

O algoritmo preenche ate onze titulares e ate sete reservas. Posicao incompatível nao impede o salvamento: o registro recebe is_out_of_position e a tela mostra o aviso. O treinador pode trocar o atleta do slot por select, alterar a formacao, enviar para reserva ou substituir titular sem depender de drag and drop.

## Confirmacao

Para confirmar:

- a partida nao pode estar cancelada, finalizada ou homologada;
- devem existir exatamente onze titulares;
- o capitao deve ser titular;
- o goleiro deve ser titular e ter goleiro como posicao principal ou secundaria;
- cada atleta deve estar no elenco aprovado, ativo, nao bloqueado e na equipe da partida;
- nenhum atleta pode ocupar dois slots ou ser titular e reserva;
- membros da comissao devem ser ativos e da equipe.

Depois da confirmacao, edicao comum e bloqueada. Administrador com permissao pode reabrir com motivo; a versao e o historico sao incrementados.

## Interface e rotas

- GET /admin/partidas/{id}/escalacoes: central da partida;
- GET /admin/partidas/{id}/escalacao/{teamId}: campo funcional e dados da equipe;
- POST na mesma rota: salvar rascunho ou confirmar;
- POST /automatico: gerar uma sugestao sem persistir;
- POST /reutilizar-anterior: copia a ultima escalação confirmada para um novo rascunho;
- POST /reabrir: reabrir uma confirmada com motivo.

O campo usa coordenadas normalizadas da formacao. Cada slot mostra foto quando existente, nome esportivo, numero, posicao e aviso posicional. A troca de formacao no rascunho reorganiza a visualizacao imediatamente no navegador e preserva os atletas selecionados sempre que houver slot equivalente; somente Salvar rascunho persiste a mudanca. Os dados de rascunho ficam privados da equipe; operadores visualizam somente confirmadas.

## Visualizacao da partida

O mesmo campo SVG e usado no editor, na central operacional e no portal publico. A central mostra um campo por equipe com os titulares da escalacao confirmada. O registro publico da partida sempre mostra a secao de escalacoes: com confirmacao, exibe os dois campos, foto circular ou iniciais, nome curto e reservas; sem confirmacao, exibe campo com aviso explicito, sem inventar atletas ou formacao oficial. Cada atleta aponta para seu perfil publico. O read model publico entrega apenas coordenadas, identificacao esportiva e indicador de foto, nunca caminho do arquivo ou outros dados privados.

## Escopo

- administrador: todas as equipes e partidas;
- organizador: leitura nos campeonatos autorizados;
- treinador/gestor: gestao da propria equipe;
- operador: leitura de escalacoes confirmadas;
- comunicacao: 403.

Nao ha rota publica para rascunhos, documentos ou dados privados. Escalacoes confirmadas e fotos autorizadas sao exibidas somente no registro publico da partida por rota controlada.

## Seed e testes

LineupSeed e idempotente e cria atletas ficticios aprovados para as dez equipes, com posicoes variadas, reservas e comissao disponivel. Para a simulacao autorizada, `db:seed:simulation-lineups` cria somente as quatro escalacoes faltantes das semifinais, sem sobrescrever qualquer escalacao existente. Em producao, exige `ALLOW_DEMO_SIMULATION=1` no comando. Os testes cobrem seed duplo, titulares, reservas, goleiro, capitao, posicoes principal/secundaria, fora de posicao, ajuste manual, reutilizacao entre partidas, equipe alheia, duplicidade, confirmacao, reabertura, historico, escopo, IDOR, CSRF e APP_BASE_PATH=/torneio-online.
