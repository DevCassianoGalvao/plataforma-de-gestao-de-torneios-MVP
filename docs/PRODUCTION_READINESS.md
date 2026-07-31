# Preparação para produção

## Instalação limpa

Requisitos: PHP 8.2 com PDO MySQL, fileinfo, openssl, cURL, GD com suporte a WebP e EXIF, ZipArchive; MySQL 8 ou MariaDB compatível; acesso de escrita somente em `storage` e `public/uploads`.

1. Copie `.env.example` para `.env`.
2. Gere uma chave exclusiva: `php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"`.
3. Configure `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, `APP_BASE_PATH`, banco, SMTP e `APP_KEY`.
4. Em produção, use `SESSION_SECURE_COOKIE=true` e `APP_HSTS=true` somente depois de HTTPS estar ativo.
5. Execute `php bin/install.php`. O comando cria o banco, aplica migrations e prepara os diretórios.
6. O seed é opcional e bloqueado quando `APP_ENV=production`: `SEED_DEMO_PASSWORD=... php bin/install.php --seed`.
7. Inicie localmente com `php -S 127.0.0.1:8000 -t public` ou publique o diretório `public` no servidor web.
8. Teste login e portal com `php bin/install.php --smoke-url=http://127.0.0.1:8000/torneio-online`, usando `INSTALL_TEST_EMAIL` e `INSTALL_TEST_PASSWORD` apenas no ambiente de teste.

O instalador não inventa credenciais, não executa seed em produção e falha se `APP_KEY` for ausente, fraca ou ainda possuir o placeholder do exemplo.

## Segurança aplicada

- PDO usa prepared statements e o router escapa segmentos literais antes de criar a expressao de rota.
- Views usam escape HTML; conteúdo editorial e uploads são processados no servidor.
- CSRF e validado em mutações; o token e rotacionado depois do login.
- Guardas de permissão e escopo permanecem no servidor; IDs não substituem autorização.
- Storage rejeita caminhos absolutos, nulos e segmentos `..`; arquivos privados ficam fora de `public`.
- Uploads verificam MIME real, extensao, tamanho e processamento; arquivos executáveis não são aceitos.
- Fotos, escudos, logos, banners, capas, avatares, patrocinadores e evidências passam por correção EXIF, limite de 12 MP, redimensionamento proporcional e conversao para WebP. O envio aceita ate 12 MiB por padrão (`IMAGE_UPLOAD_MAX_BYTES`); documentos e favicon permanecem no formato original.
- Login possui limite por janela, registro de tentativas, bloqueio temporário e mensagem genérica.
- Redirects de login aceitam somente caminhos locais normalizados.
- Em produção, stack trace e mensagens de exceção não são exibidos.
- Respostas incluem CSP, nosniff, proteção de frame, Referrer Policy, Permissions Policy e HSTS condicional.
- Cookie de sessão e HttpOnly, SameSite configurável e Secure quando habilitado.

## Backup e restauração

O backup exclui `.env` e logs, inclui banco, `public/uploads` e `storage/private`, grava `manifest.json` e permite rotação:

```text
php bin/backup.php --verify --retain=7
php bin/restore.php --archive=storage/backups/tournament-YYYYMMDD-HHMMSS.zip --confirm
```

`mysqldump` e `mysql` podem ser apontados por `MYSQLDUMP_BIN` e `MYSQL_BIN`. A senha do banco e passada via `MYSQL_PWD`, não pela linha de comando. Restaure primeiro em homologação, valide migrations, login, downloads privados e portal, e somente depois substitua o ambiente ativo.

O diretório de backups deve ficar fora do document root, com permissão restrita e cópia externa. Rotação não substitui cópia off-site; mantenha ao menos uma cópia diária e uma semanal conforme a politica operacional.

## Testes reais

`bin/test.php` e a suíte de contrato em banco descartável. `bin/http-test.php` usa cURL contra um servidor PHP real; ele não e chamado de E2E. A cobertura atual combina instalação/migrations, autenticação, autorização, uploads, downloads, fluxo esportivo, súmula, notícias, transferências e portal.

Comandos:

```text
php bin/lint.php
APP_ENV=test DB_NAME=torneios_mvp_test SEED_DEMO_PASSWORD=TestDemo123 php bin/test.php
php -S 127.0.0.1:18081 -t public
HTTP_TEST_BASE_URL=http://127.0.0.1:18081/torneio-online TEST_PASSWORD=TestDemo123 php bin/http-test.php
```

## Limites

Esta etapa prepara o sistema para homologação. Ainda é obrigatório validar no cPanel real: versão e extensoes PHP, HTTPS/certificado, SMTP, permissão de storage, cron, backup off-site e restauração completa. Por isso o audit desta etapa não declara aprovação para produção sem essa evidência externa.
