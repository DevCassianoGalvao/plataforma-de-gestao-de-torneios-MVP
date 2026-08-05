# Retificação avançada

## Fluxo

1. Um administrador solicita retificação de uma partida aprovada e informa o motivo e o grupo de campo afetado.
2. Outro administrador, ou o mesmo quando a regra não exigir segunda aprovação, autoriza ou rejeita o pedido.
3. A correção autorizada reabre a operação, retira a publicação pública e mantém as versões anteriores da súmula.
4. O responsável edita apenas os campos permitidos e registra o motivo da alteração.
5. A correção é concluída e enviada para nova aprovação.
6. Quando configurado, a aprovação final exige usuário diferente de quem corrigiu.

## Campos controlados

Os grupos disponíveis são operação, evento, placar, arbitragem, horários e escalação. A edição pontual de eventos registra valor anterior, valor novo, motivo, usuário e horário em `match_rectification_changes`.

Eventos passam por validação de tipo, período, minuto e equipe da própria partida. Não é possível editar evento de outra partida pela rota.

## Configuração

`championship_rectification_settings.require_second_approval` define a exigência de segunda aprovação por campeonato. Campos críticos como placar, eventos e escalação são marcados para facilitar a revisão.

## Rotas e permissões

- `POST /admin/partidas/{id}/operacao/retificacao`: solicita o pedido;
- `POST /admin/partidas/{id}/operacao/retificacao/decisao`: autoriza ou rejeita;
- `POST /admin/partidas/{id}/operacao/retificacao/evento`: edita evento autorizado;
- `POST /admin/partidas/{id}/operacao/retificacao/concluir`: envia para nova aprovação.

As permissões são `match_operation.rectify.edit`, `match_operation.rectify.complete` e `match_operation.rectify.approve`. Todas as transições geram histórico e auditoria.

## Proteções

Não há conversão automática de retificação em partida nova. O portal, a classificação, a disciplina e a súmula só recebem novamente os dados depois da aprovação final.
