# Vai e Vem de Transferencias

## Escopo

Etapa 14 registra movimentacoes editoriais por campeonato e atleta: contratacao, transferencia, emprestimo, retorno, renovacao e saida. Cada registro preserva equipe anterior, nova equipe, data, observacao publica, notas internas, autor, status e historico.

O movimento e separado do vinculo oficial em `athletes.team_id`. Aprovar ou publicar uma movimentacao nao troca a equipe do atleta. Uma etapa posterior pode criar uma acao administrativa explicita para aplicar o vinculo, sempre com auditoria e historico.

## Fluxo

1. Administrador, organizador ou comunicacao autorizado cria `draft`.
2. O registro pode ser enviado como `pending`.
3. O responsavel autorizado aprova como `approved`.
4. A comunicacao pode publicar como `published`.
5. Um registro nao aplicavel pode ser `cancelled`.

Toda transicao grava `transfer_movement_history` e um evento de auditoria. Registros aprovados ou publicados nao podem ser editados silenciosamente.

## Regras

- atleta, equipes e campeonato precisam existir e pertencer ao mesmo campeonato;
- saida exige equipe anterior e nao possui nova equipe;
- os demais tipos exigem nova equipe; renovacao pode manter a mesma equipe;
- a janela e o limite sao opcionais em `regulation_transfer_settings`;
- limite considera movimentacoes nao canceladas dentro da janela;
- o formulario usa selects de entidades, sem exigir IDs manuais;
- notas internas nunca aparecem no portal publico;
- publicacao exige campeonato publico e status `published` com data alcancada;
- foto do atleta continua em armazenamento privado e so e servida para movimento publicado.

## Rotas

- `/admin/vai-e-vem`: painel, busca e filtros;
- `/admin/vai-e-vem/novo`: fluxo assistido de criacao;
- `/admin/vai-e-vem/{id}`: detalhe, historico e transicoes;
- `/campeonatos/{slug}/vai-e-vem`: listagem publica paginada;
- `/campeonatos/{slug}/vai-e-vem/{id}/foto`: foto publica somente quando o movimento esta publicado.

## Permissoes

Administrador acessa tudo. Organizador e comunicacao acessam somente campeonatos atribuidos por `championship_user_assignments`. Treinador e operador recebem `403`. CSRF e escopo sao revalidados no servidor em toda mutacao.

## Dados

`transfer_movements` guarda o registro atual. `transfer_movement_history` guarda as transicoes. `regulation_transfer_settings` permite janela e limite sem fixar regra no codigo. O seed cria um movimento publicado e um pendente de forma idempotente.
