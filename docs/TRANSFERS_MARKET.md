# Vai e Vem de Transferências

## Escopo

Etapa 14 registra movimentações editoriais por campeonato e atleta: contratação, transferência, empréstimo, retorno, renovação e saida. Cada registro preserva equipe anterior, nova equipe, data, observação pública, notas internas, autor, status e histórico.

O movimento é separado do vínculo oficial em `athletes.team_id`. Aprovar ou publicar uma movimentação não troca a equipe do atleta. Uma etapa posterior pode criar uma ação administrativa explícita para aplicar o vínculo, sempre com auditoria e histórico.

## Fluxo

1. Administrador cria `draft`/`pending` para qualquer equipe, ou Treinador/Gestor cria uma solicitação `pending` do tipo `transferencia` a partir da própria equipe, já escolhendo o clube de destino.
2. Só o Administrador aprova (`approved`) e publica (`published`).
3. O Administrador aplica o vínculo oficial em ação separada, atualizando `athletes.team_id` de fato.
4. O autor da solicitação (Treinador/Gestor) pode cancelar (`cancelled`) o próprio registro enquanto `draft`/`pending`; o Administrador pode cancelar qualquer registro a qualquer momento.

Toda transição grava `transfer_movement_history` e um evento de auditoria. Registros aprovados ou publicados não podem ser editados silenciosamente.

## Regras

- atleta, equipes e campeonato precisam existir e pertencer ao mesmo campeonato;
- saida exige equipe anterior e não possui nova equipe;
- os demais tipos exigem nova equipe; renovação pode manter a mesma equipe;
- a janela e o limite são opcionais em `regulation_transfer_settings`;
- limite considera movimentações não canceladas dentro da janela;
- o formulário usa selects de entidades, sem exigir IDs manuais;
- notas internas nunca aparecem no portal público;
- publicação exige campeonato público e status `published` com data alcancada;
- foto do atleta continua em armazenamento privado e só e servida para movimento publicado.

## Rotas

- `/admin/vai-e-vem`: painel, busca e filtros;
- `/admin/vai-e-vem/novo`: fluxo assistido de criação;
- `/admin/vai-e-vem/{id}`: detalhe, histórico e transições;
- `/campeonatos/{slug}/vai-e-vem`: listagem pública paginada;
- `/campeonatos/{slug}/vai-e-vem/{id}/foto`: foto pública somente quando o movimento está publicado.

## Permissões

`transfers.manage` (Administrador): acessa e opera qualquer movimentação de qualquer campeonato — criar, editar, aprovar, publicar, cancelar e aplicar o vínculo oficial.

`transfers.request` (Treinador/Gestor): cria, visualiza e cancela somente solicitações do tipo `transferencia` de sua própria autoria, com equipe de origem sob sua gestão (via `team_user_assignments`). Não aprova, não publica e não aplica vínculo oficial.

Operador e Prestação de Contas recebem `403`. CSRF e escopo são revalidados no servidor em toda mutação.

## Dados

`transfer_movements` guarda o registro atual. `transfer_movement_history` guarda as transições. `regulation_transfer_settings` permite janela e limite sem fixar regra no código. O seed cria um movimento publicado e um pendente de forma idempotente.
