# Perfis e permissoes

## Regra de autorizacao

Permissao tem duas camadas: menu filtra o que aparece e controller/service valida novamente. `ScopeService` resolve organizacao, projeto, campeonato, equipe e partida a partir do banco. Valores enviados pelo cliente nao definem escopo.

## Perfis

| Perfil | Escopo | Pode fazer | Nao pode fazer |
|---|---|---|---|
| Superadministrador | plataforma | tudo, incluindo perfis, permissoes e escopos | sem restricao operacional; toda acao fica auditada |
| Organizador | campeonatos atribuídos | regulamento, equipes, inscricoes, grupos, agenda, homologacao, mata-mata, noticias, transferencias, sumulas | acessar campeonato fora da atribuicao |
| Gestor/treinador | equipe atribuida no campeonato | atletas, comissao, inscricoes, formacao, escalação e consulta disciplinar | homologar resultado ou acessar outra equipe |
| Operador de partida | partidas atribuídas | escalações de consulta, eventos, placar, ocorrencias e envio para homologacao | editar regulamento ou homologar |
| Comunicacao | campeonatos atribuídos | noticias, publicacoes e Vai e Vem | alterar escalação, resultado ou disciplina |
| Consulta/prestacao | campeonatos atribuídos | indicadores, consulta e downloads autorizados | mutacoes esportivas |

## Permissoes persistidas

`view`, `create`, `update`, `delete`, `restore`, `publish`, `export`, `approve_registration`, `reject_registration`, `manage_roster`, `manage_lineup`, `operate_match`, `finish_match`, `homologate_match`, `request_rectification`, `approve_rectification`, `manage_regulation`, `create_regulation_version`, `manage_bracket`, `apply_penalty`, `download_private_file`, `manage_permissions` e `view_audit_logs`.

## Matriz resumida

| Acao | Super | Organizador | Gestor | Operador | Comunicacao | Consulta |
|---|---:|---:|---:|---:|---:|---:|
| Configurar regulamento | sim | sim | nao | nao | nao | nao |
| Cadastrar equipe/atleta | sim | sim | equipe propria | nao | nao | nao |
| Aprovar inscricao | sim | sim | nao | nao | nao | nao |
| Montar escalação | sim | sim | equipe propria | consulta/apoio | nao | nao |
| Operar partida | sim | sim | nao | partida atribuida | nao | nao |
| Homologar resultado | sim | sim | nao | nao | nao | nao |
| Publicar noticia/transferencia | sim | sim | nao | nao | sim | nao |
| Baixar arquivo privado | sim | sim | equipe/campeonato permitido | partida permitido | documento permitido | permitido |
| Exportar relatorio | sim | sim | nao | nao | nao | sim |

## Controles obrigatorios

- CSRF em toda mutacao web.
- Escopo resolvido no servidor para toda leitura/escrita/download.
- Upload valida MIME, tamanho, extensao segura e destino publico/privado.
- Arquivos privados fora de `public/`.
- Dados de responsavel, CPF, telefone, e-mail, endereco e documentos nunca entram no presenter publico.
- Negacoes criticas registradas em `audit_logs`.
