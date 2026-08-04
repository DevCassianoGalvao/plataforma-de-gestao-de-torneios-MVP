# Cobertura, pendencias e acompanhamento por rodada

## Objetivo

O painel administrativo `Acompanhamento por rodada` consolida situacao esportiva, documental e publicacao de cada rodada sem alterar resultados, campeonatos ou registros existentes.

## Reaproveitamento

- `competition_rounds` e `matches`: calendario, fase, grupo e agenda.
- `match_operations`: andamento, revisao e aprovacao.
- `match_reports` e `match_report_versions`: sumulas oficiais.
- `championship_evidence_checklist_items` e `match_media`: evidencias obrigatorias.
- `match_publications`: publicacao interna, agendada e publicada.
- `match_operator_assignments`: operador atribuido.

## Novo dado

Migration `0031_round_coverage_monitoring.sql` adiciona somente `championship_document_deadlines`. A configuracao e unica por campeonato: mesmo dia, dia seguinte, horas ou dias personalizados. Vencimento cria alerta visual; nunca bloqueia, apaga ou altera registros.

## Cobertura e limites honestos

Cada rodada informa partidas previstas, agendadas, em andamento, encerradas, em revisao, aguardando aprovacao, aprovadas, programadas para publicacao, publicadas, adiadas, canceladas, W.O. e abandonadas quando existir decisao administrativa correspondente.

O painel informa sumulas nao iniciadas, em preenchimento, geradas e evidencias obrigatorias pendentes. Pendencias documentais so ficam criticas depois que a partida foi encerrada; rodada futura nao e marcada como atrasada sem motivo. Assinatura digital e ocorrencia com fluxo proprio ainda nao existem como entidades independentes; o painel nao inventa status para esses dados.

## Indicadores

- rodada completa;
- parcialmente completa;
- atrasada;
- pendencia critica;
- pronta para prestacao de contas;
- pronta para publicacao;
- publicada;
- prazo documental vencido.

## Acoes seguras

Detalhe permite exportar pendencias em CSV e baixar pacote de sumulas existente. Aprovacao cega em lote nao foi criada: aprovar arquivos sem revisao individual e risco operacional e de auditoria.

## Permissoes e rotas

Permissoes: `round.monitor.view`, `round.monitor.manage`, `round.report.generate`, `round.package.download`, `round.bulk.review`, `round.bulk.approve`.

- `GET /admin/rodadas/acompanhamento`
- `GET /admin/rodadas/{id}/acompanhamento`
- `POST /admin/rodadas/{id}/acompanhamento/prazo`
- `GET /admin/rodadas/{id}/acompanhamento/exportar`
- `GET /admin/rodadas/{id}/acompanhamento/pacote`

Administrador recebe todas. Prestacao de Contas recebe visualizacao, exportacao e pacote dentro dos campeonatos vinculados.

## Teste

`RoundMonitoringIntegrationTest` valida permissao, consulta, indicadores, prazo, detalhe, partidas e exportacao em banco descartavel.
