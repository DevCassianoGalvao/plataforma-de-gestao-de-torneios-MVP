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

## Exclusão definitiva em lote

A exclusão definitiva é uma ferramenta de limpeza de dados de teste e só pode ser usada por um administrador com a permissão `retention.purge`. Ela fica em `/admin/retencao`, no bloco **Excluir dados definitivamente**.

O administrador pode selecionar vários campeonatos, equipes ou atletas na mesma operação. Antes da confirmação, a tela mostra os registros selecionados e o sistema calcula os vínculos que serão removidos. Ao excluir um campeonato, seus dados esportivos dependentes também são removidos; ao excluir uma equipe, seus atletas e vínculos dependentes seguem a mesma regra.

Para confirmar:

1. Selecione os registros desejados.
2. Informe um motivo operacional.
3. Digite exatamente `EXCLUIR DEFINITIVAMENTE`.
4. Confirme a ação destrutiva.

A operação é transacional: se algum vínculo não puder ser tratado com segurança, nada é removido. Arquivos privados associados são apagados depois da transação. Usuários, permissões, logs, configurações e backups nunca entram nessa ferramenta. Ações de exclusão ficam registradas em `retention_actions` e na auditoria.

Use esta função somente depois de confirmar o ambiente e manter um backup externo válido. Ela não substitui o procedimento de restauração nem deve ser usada para apagar histórico oficial sem autorização formal.
