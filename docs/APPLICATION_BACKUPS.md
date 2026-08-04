# Backups da aplicacao

## Escopo

O modulo cria um ZIP privado com dump MySQL, `public/uploads`, `storage/private` e `manifest.json`. Arquivos `.env`, logs, sessoes, cache e outros backups nao entram no pacote.

Cada execucao fica registrada em `application_backups`, com tamanho, hash SHA-256, duracao, tentativas, estado local/remoto e auditoria. Estados: pendente, em execucao, concluido, concluido parcialmente, falhou e excluido logicamente.

## Operacao administrativa

Administrador acessa **Backups** no painel. Pode criar backup manual, testar conexao remota, baixar pacote validado, reenviar envio remoto falho e excluir mediante confirmacao. Restauracao nao esta disponivel pela web: use somente `bin/restore.php --archive=... --confirm` em ambiente controlado e com backup testado.

## Configuracao

```dotenv
BACKUP_ENABLED=true
BACKUP_DIR=storage/backups
BACKUP_RETENTION_DAYS=14
BACKUP_STORAGE_PROVIDER=local
GOOGLE_DRIVE_FOLDER_ID=
GOOGLE_DRIVE_ACCESS_TOKEN=
GOOGLE_DRIVE_CREDENTIALS_PATH=storage/private/google-drive-service-account.json
```

Para Google Drive, configure `BACKUP_STORAGE_PROVIDER=google_drive`, pasta privada e token de acesso provisionado fora do repositorio. Nunca versione token, credencial de conta de servico ou `.env`. O caminho de credencial existe para operacao controlada do servidor; o provedor atual usa token injetado pelo ambiente e nunca o registra em logs.

## Cron cPanel

Agende em horario de menor uso:

```text
0 3 * * * /usr/local/bin/php /home/USUARIO/caminho-do-projeto/bin/console.php backup:run >> /home/USUARIO/logs/backup.log 2>&1
```

Teste primeiro no terminal dentro da pasta do projeto: `php bin/console.php backup:run`. O processo cria lock exclusivo; duas execucoes simultaneas sao recusadas.

## Retencao e recuperacao

Retencao remove somente arquivos associados a registros concluidos do proprio modulo, apos o prazo configurado. Antes de excluir ou restaurar, valide hash, baixe uma copia e teste restauracao em banco descartavel. Backups nao aparecem no portal, prestacao de contas, sumulas ou consultas esportivas.
