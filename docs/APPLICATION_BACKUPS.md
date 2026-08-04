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

Para Google Drive, selecione Google Drive na tela de Backups e cole o link da pasta. O sistema extrai o identificador da pasta, mas o link não é uma credencial: para gravar, a pasta deve estar compartilhada com a conta autorizada e o servidor deve receber `GOOGLE_DRIVE_ACCESS_TOKEN` no `.env`. Nunca versione token, credencial de conta de serviço ou `.env`.

Na mesma tela, ative o backup automático e escolha o horário. Em hospedagem compartilhada, o PHP não executa sozinho: cadastre no cron do cPanel uma tarefa a cada cinco minutos, ajustando os caminhos:

```cron
*/5 * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/USUARIO/caminho-do-projeto/bin/console.php backup:schedule >> /home/USUARIO/logs/backup-cron.log 2>&1
```

O comando verifica o horário salvo e impede mais de um backup automático por dia. O destino local continua funcionando sem Google Drive.

## Cron cPanel

Agende em horario de menor uso:

```text
0 3 * * * /usr/local/bin/php /home/USUARIO/caminho-do-projeto/bin/console.php backup:run >> /home/USUARIO/logs/backup.log 2>&1
```

Teste primeiro no terminal dentro da pasta do projeto: `php bin/console.php backup:run`. O processo cria lock exclusivo; duas execucoes simultaneas sao recusadas.

## Retencao e recuperacao

Retencao remove somente arquivos associados a registros concluidos do proprio modulo, apos o prazo configurado. Antes de excluir ou restaurar, valide hash, baixe uma copia e teste restauracao em banco descartavel. Backups nao aparecem no portal, prestacao de contas, sumulas ou consultas esportivas.
