# Preparacao para producao

## Instalacao limpa

Requisitos: PHP 8.2 com PDO MySQL, fileinfo, openssl, cURL, GD e ZipArchive; MySQL 8 ou MariaDB compativel; acesso de escrita somente em `storage` e `public/uploads`.

1. Copie `.env.example` para `.env`.
2. Gere uma chave exclusiva: `php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"`.
3. Configure `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, `APP_BASE_PATH`, banco, SMTP e `APP_KEY`.
4. Em producao, use `SESSION_SECURE_COOKIE=true` e `APP_HSTS=true` somente depois de HTTPS estar ativo.
5. Execute `php bin/install.php`. O comando cria o banco, aplica migrations e prepara os diretorios.
6. O seed e opcional e bloqueado quando `APP_ENV=production`: `SEED_DEMO_PASSWORD=... php bin/install.php --seed`.
7. Inicie localmente com `php -S 127.0.0.1:8000 -t public` ou publique o diretorio `public` no servidor web.
8. Teste login e portal com `php bin/install.php --smoke-url=http://127.0.0.1:8000/torneio-online`, usando `INSTALL_TEST_EMAIL` e `INSTALL_TEST_PASSWORD` apenas no ambiente de teste.

O instalador nao inventa credenciais, nao executa seed em producao e falha se `APP_KEY` for ausente, fraca ou ainda possuir o placeholder do exemplo.

## Seguranca aplicada

- PDO usa prepared statements e o router escapa segmentos literais antes de criar a expressao de rota.
- Views usam escape HTML; conteudo editorial e uploads sao processados no servidor.
- CSRF e validado em mutacoes; o token e rotacionado depois do login.
- Guardas de permissao e escopo permanecem no servidor; IDs nao substituem autorizacao.
- Storage rejeita caminhos absolutos, nulos e segmentos `..`; arquivos privados ficam fora de `public`.
- Uploads verificam MIME real, extensao, tamanho e processamento; arquivos executaveis nao sao aceitos.
- Login possui limite por janela, registro de tentativas, bloqueio temporario e mensagem generica.
- Redirects de login aceitam somente caminhos locais normalizados.
- Em producao, stack trace e mensagens de excecao nao sao exibidos.
- Respostas incluem CSP, nosniff, protecao de frame, Referrer Policy, Permissions Policy e HSTS condicional.
- Cookie de sessao e HttpOnly, SameSite configuravel e Secure quando habilitado.

## Backup e restauracao

O backup exclui `.env` e logs, inclui banco, `public/uploads` e `storage/private`, grava `manifest.json` e permite rotacao:

```text
php bin/backup.php --verify --retain=7
php bin/restore.php --archive=storage/backups/tournament-YYYYMMDD-HHMMSS.zip --confirm
```

`mysqldump` e `mysql` podem ser apontados por `MYSQLDUMP_BIN` e `MYSQL_BIN`. A senha do banco e passada via `MYSQL_PWD`, nao pela linha de comando. Restaure primeiro em homologacao, valide migrations, login, downloads privados e portal, e somente depois substitua o ambiente ativo.

O diretorio de backups deve ficar fora do document root, com permissao restrita e copia externa. Rotacao nao substitui copia off-site; mantenha ao menos uma copia diaria e uma semanal conforme a politica operacional.

## Testes reais

`bin/test.php` e a suite de contrato em banco descartavel. `bin/http-test.php` usa cURL contra um servidor PHP real; ele nao e chamado de E2E. A cobertura atual combina instalacao/migrations, autenticacao, autorizacao, uploads, downloads, fluxo esportivo, sumula, noticias, transferencias e portal.

Comandos:

```text
php bin/lint.php
APP_ENV=test DB_NAME=torneios_mvp_test SEED_DEMO_PASSWORD=TestDemo123 php bin/test.php
php -S 127.0.0.1:18081 -t public
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/torneio-online TEST_PASSWORD=TestDemo123 php bin/http-test.php
```

## Limites

Esta etapa prepara o sistema para homologacao. Ainda e obrigatorio validar no cPanel real: versao e extensoes PHP, HTTPS/certificado, SMTP, permissao de storage, cron, backup off-site e restauracao completa. Por isso o audit desta etapa nao declara aprovacao para producao sem essa evidencia externa.
