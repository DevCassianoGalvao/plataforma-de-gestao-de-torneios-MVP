# Retenção, arquivamento e exclusão lógica

## Objetivo

Centralizar prazos e ações de retenção sem permitir apagamento permanente de histórico esportivo, documentos oficiais ou logs.

## Políticas

As políticas ficam em `retention_policies`, com prazo em dias, permissão de arquivar, restaurar e excluir logicamente, além da marca de proteção. A tela `/admin/retencao` é exclusiva do administrador.

Classes iniciais:

- rascunhos operacionais;
- histórico esportivo;
- documentos oficiais;
- logs e auditoria.

## Ações

Arquivar altera o registro para `archived`, preenche `deleted_at` e exige motivo. Restaurar recupera o estado anterior registrado na ação. Cada operação grava usuário, data, estado anterior, estado novo, motivo e metadados em `retention_actions`.

## Segurança

As entidades administráveis são uma lista fixa no serviço: campeonatos, equipes, atletas, notícias e simulações. Partidas, súmulas, evidências oficiais, classificações e logs não podem ser manipulados por esta central. Não existe ação de exclusão física na interface.

Todas as mutações exigem CSRF, permissão específica e auditoria. IDs não são aceitos para montar nomes de tabela; o mapeamento é fixo no backend.

## Operação

1. Abra `Retenção e arquivamento`.
2. Ajuste a política aplicável, respeitando a proteção dos dados oficiais.
3. Use a ação de arquivamento ou restauração no módulo de origem, informando o motivo.
4. Consulte o histórico central para auditoria.

Backups e retenção são controles diferentes: a retenção organiza registros do sistema; backups continuam sendo cópias privadas para recuperação operacional.
