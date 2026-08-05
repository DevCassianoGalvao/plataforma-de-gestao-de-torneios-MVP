# Backups da aplicação

## O que o módulo faz

O módulo cria um arquivo ZIP privado com o banco MySQL, os uploads públicos, os arquivos privados permitidos e um manifesto de verificação. Arquivos .env, logs, sessões, cache e outros backups nunca entram no pacote.

Cada cópia fica registrada com tamanho, hash SHA-256, duração, situação local, situação remota e auditoria. O backup só é considerado concluído depois da validação local do ZIP.

## Operação no painel

Em Backups, um administrador pode:

- criar uma cópia manual;
- testar o Google Drive;
- baixar uma cópia local validada;
- reenviar uma cópia cujo envio remoto falhou;
- excluir a cópia local e a cópia remota, quando existir;
- ativar e configurar o agendamento.

A exclusão exige sessão autenticada, permissão e CSRF. A confirmação visual do navegador é apenas uma proteção adicional. A restauração não é feita pela web: use o comando bin/restore.php --archive=... --confirm em ambiente controlado, sempre em banco descartável.

## Google Drive: onde colocar o token

O link da pasta não é uma credencial. Ele apenas informa em qual pasta o arquivo será gravado.

No cPanel:

1. Abra o Gerenciador de arquivos.
2. Entre na pasta da aplicação.
3. Edite o arquivo .env.
4. Preencha somente no servidor:

~~~dotenv
BACKUP_STORAGE_PROVIDER=google_drive
GOOGLE_DRIVE_ACCESS_TOKEN=cole_o_token_de_acesso_aqui
~~~

Na tela Backups, selecione Google Drive e cole o link da pasta. A pasta precisa estar compartilhada com a conta autorizada pelo token. O token nunca deve ser colocado no formulário, no banco, no Git ou em um chamado.

Depois de salvar o .env, volte ao painel e clique em Testar conexão. Se o teste confirmar a pasta, um backup manual fará o primeiro envio real. Sem token, o destino local continua funcionando.

## Periodicidade

Na tela Backups, ative o backup automático e escolha:

- todos os dias;
- a cada 3 dias;
- uma vez por semana;
- a cada 15 dias;
- uma vez por mês.

Escolha também o horário. A periodicidade fica salva no banco; não é necessário editar código. O cron deve apenas acordar o sistema e pode executar a cada cinco minutos:

~~~cron
*/5 * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/USUARIO/caminho-do-projeto/bin/console.php backup:schedule >> /home/USUARIO/logs/backup-cron.log 2>&1
~~~

O comando respeita o horário e o intervalo escolhido e impede duplicidade mesmo se o cron executar várias vezes no mesmo período.

## Configuração de ambiente

~~~dotenv
BACKUP_ENABLED=true
BACKUP_DIR=storage/backups
BACKUP_RETENTION_DAYS=14
BACKUP_STORAGE_PROVIDER=local
GOOGLE_DRIVE_FOLDER_ID=
GOOGLE_DRIVE_ACCESS_TOKEN=
~~~

O diretório de backup deve ficar dentro de storage, fora do document root público, com permissão restrita.

## Teste manual

Dentro da pasta do projeto:

~~~bash
/opt/cpanel/ea-php82/root/usr/bin/php bin/console.php backup:run
/opt/cpanel/ea-php82/root/usr/bin/php bin/console.php backup:schedule
~~~

Confirme no painel se a cópia aparece como concluída, baixe o ZIP e verifique se o Google Drive recebeu o arquivo quando o destino remoto estiver ativo.

## Retenção e recuperação

A retenção remove arquivos locais antigos associados a cópias concluídas. Ela não substitui uma cópia externa. Antes de excluir ou restaurar, valide o hash, baixe uma cópia e teste a restauração em banco descartável. Backups não aparecem no portal público, nas súmulas nem na prestação de contas.
