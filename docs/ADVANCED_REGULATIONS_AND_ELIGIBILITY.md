# Regulamento Avancado e Elegibilidade

## Finalidade

Regulamento continua modulo unico por campeonato. Configuracoes novas vivem em rascunho versionado e somente passam a valer quando versao e publicada. Regras anteriores, partidas concluidas, sumulas e classificacoes historicas nao sao recalculadas silenciosamente.

## Configuracoes adicionais

- limite de comissao, equipes e alteracoes de elenco;
- inscricao apos inicio, aprovacao e documentos;
- transferencias permitidas ou bloqueadas;
- tratamento administrativo de partida abandonada, cancelada e adiada;
- excecao administrativa com motivo;
- elegibilidade entre fase de origem e destino.

Formato, pontos, W.O., penaltis, prorrogacao, cartoes, suspensoes, chaves e desempates usam servicos existentes. Ordem dos desempates permanece gravada em `regulation_tiebreakers` e consumida pelo calculador oficial.

## Elegibilidade

Cada regra relaciona fase de origem e destino. Pode exigir participacao como relacionado, atleta que entrou em campo ou titular; data de aprovacao da inscricao; documentos validos; e ausencia de suspensao. Confirmacao de escalação executa validacao no servidor para titulares e reservas. Requisicao direta nao ignora bloqueio.

Excecao exige permissao `regulations.grant_exception`, perfil administrador, CSRF, regra que permita excecao, atleta inscrito na equipe e motivo. Registro fica em `regulation_eligibility_exceptions` e auditoria.

## Compatibilidade

Migration nao cria configuracoes para regulamentos existentes. Portanto comportamento de campeonatos ja cadastrados permanece igual. Novo rascunho recebe valores padrao conservadores e deve ser revisado/publicado explicitamente.

## Rotas

- `GET /admin/campeonatos/{slug}/regulamento/editar`
- `POST /admin/campeonatos/{slug}/regulamento`
- `POST /admin/partidas/{id}/elegibilidade/excecoes`

## Tabelas

- `regulation_advanced_settings`
- `regulation_eligibility_rules`
- `regulation_eligibility_exceptions`
- `regulation_change_logs`
