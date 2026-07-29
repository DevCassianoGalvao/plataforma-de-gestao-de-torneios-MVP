# Etapa 10 — Disciplina e suspensões

## Escopo

Esta etapa registra cartões de atletas e comissão técnica, acumulação configurável, pendurados, suspensões automáticas e suspensões manuais. Não implementa classificação definitiva, mata-mata, campeão, súmula PDF, notícias, Vai e Vem ou portal público.

## Modelo

- discipline_ledger: lançamento disciplinar por evento homologado, com origem, fase, pessoa, status e chave idempotente.
- discipline_processing_runs: marca o processamento de cada partida homologada.
- discipline_suspensions: suspensão automática ou manual, quantidade total, cumprimento, status e motivo.
- discipline_suspension_fulfillments: histórico de partidas elegíveis que cumpriram uma suspensão.
- discipline_card_resets: limpeza configurável de cartões na transição de fase.
- discipline_history: trilha de decisões, anulações, acúmulos e cumprimentos.

A migration 0010_discipline_and_suspensions.sql adiciona também pessoa da comissão e campos de anulação aos eventos da central da partida. Migrations anteriores não são alteradas.

## Regras

- Amarelo conta para o limite definido em regulation_discipline_settings.
- Segundo amarelo e vermelho geram suspensão automática quando a regra de vermelho automático estiver habilitada.
- Pênaltis não geram cartão ou acúmulo.
- Cartão inválido/anulado não entra no ledger considerado.
- A homologação processa eventos válidos e pode ser repetida sem duplicação.
- A suspensão começa na partida seguinte elegível, nunca na partida que a gerou.
- Partida cancelada não cumpre suspensão; partidas homologadas e adiadas permanecem no histórico da agenda.
- Cumprimentos são registrados uma única vez por suspensão e partida.
- Cartões podem ser limpos por fase quando reset_cards_enabled e reset_cards_stage forem configurados.
- Revogação e anulação preservam o histórico e exigem motivo.

## Acesso

- administrador: acesso total;
- organizador: disciplina do campeonato autorizado, incluindo decisões manuais;
- treinador: leitura restrita às próprias equipes;
- operador: registra cartões na partida atribuída pela central;
- comunicação: 403;
- documentos e dados privados permanecem fora das rotas públicas.

## Rotas

- GET /admin/disciplina
- POST /admin/disciplina/suspensao
- POST /admin/disciplina/suspensao/{id}/revogar
- cartões são processados automaticamente em POST /admin/partidas/{id}/operacao/homologar.

## Escopo futuro

O bloqueio de atleta suspenso já ocorre na confirmação da escalação. A lógica acumulada de classificação e efeitos esportivos completos de W.O. ficam para etapas posteriores.
