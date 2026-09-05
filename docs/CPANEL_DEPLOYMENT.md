# Implantação em cPanel

## Estrutura

URL final: `https://www.cassianogalvao.com.br/torneio-online`.

O nome do banco pode conter letras, números, underscore e hifen, por exemplo `xdigcomb_torneio-online`.

- PHP 8.2 selecionado no MultiPHP.
- Banco MySQL criado no cPanel; use o nome, usuário e host fornecidos pelo painel.
- Clone do repositório em `/home/xdigcomb/cassianogalvao.com.br/torneio-online`.
- Preferencialmente, configure o Document Root de `/torneio-online` para `/home/xdigcomb/cassianogalvao.com.br/torneio-online/public`.
- Se o cPanel mantiver o Document Root na raiz do clone, o `.htaccess` da raiz encaminha a aplicação para `public` e bloqueia o código privado.
- `.env` fica na raiz do clone, fora de `public`, e nunca deve ser versionado.
- `storage/private`, `storage/logs`, `storage/sessions`, `storage/cache`, `storage/exports` e `storage/backups` ficam fora de `public`.

## Publicação

1. Envie o código sem `.env`, logs, dumps, ZIPs ou backups.
2. Copie `config/cpanel.env.example` para `.env` no servidor e preencha `DB_PASS` e `APP_KEY` fora do Git.
3. Configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_BASE_PATH=/torneio-online`, `SESSION_SECURE_COOKIE=true` e `APP_HSTS=true` somente com HTTPS funcionando.
4. Garanta permissões 750 para diretórios de storage e 640 para arquivos privados; o usuário PHP precisa escrever apenas nos diretórios previstos.
5. No Terminal do cPanel, entre em `/home/xdigcomb/cassianogalvao.com.br/torneio-online` e execute o instalador com o PHP 8.2.
6. Não execute `--seed` em produção.
7. Teste `/login`, uma rota administrativa autorizada, um download privado autorizado e `/torneio-online/campeonatos/{slug}`.

## Imagens otimizadas

No `MultiPHP INI Editor` do cPanel, mantenha a extensao `gd` com suporte a WebP e `exif` habilitadas. Para aceitar o limite padrão do sistema, configure pelo menos:

```text
upload_max_filesize = 12M
post_max_size = 14M
memory_limit = 256M
max_execution_time = 60
```

O sistema aceita JPEG, PNG e WebP, corrige orientação de celulares, redimensiona proporcionalmente e salva WebP. `IMAGE_UPLOAD_MAX_BYTES=12582912` controla o limite de tamanho em bytes; não defina valor maior que o `upload_max_filesize` do PHP. `IMAGE_UPLOAD_MAX_PIXELS=50000000` controla o limite de resolução (padrão 50 MP, teto 120 MP); o serviço eleva o `memory_limit` do PHP só durante a conversão de imagens grandes. Favicon e documentos permanecem no formato enviado.

O `.htaccess` da raiz impede listagem, bloqueia o código privado e encaminha as requisições para `public`. O `public/.htaccess` trata as requisições quando `public` e o Document Root. O `public/uploads/.htaccess` impede execução de scripts em uploads publicos.

## Cron e operação

O MVP não depende de cron para servir páginas. Use cron para backup, por exemplo uma vez por dia:

```text
0 3 * * * cd /home/xdigcomb/cassianogalvao.com.br/torneio-online && /opt/cpanel/ea-php82/root/usr/bin/php bin/backup.php --verify --retain=14 >> /home/xdigcomb/torneios-backup.log 2>&1
```

Se o provedor usar outro caminho para o PHP 8.2, selecione a versão no MultiPHP e use o caminho exibido pelo próprio cPanel. Para atualizar pelo Git Version Control, use o diretório do clone:

```bash
cd /home/xdigcomb/cassianogalvao.com.br/torneio-online
git pull --ff-only origin main
/opt/cpanel/ea-php82/root/usr/bin/php bin/install.php
```

Mantenha o log do cron fora de `public`, limite sua rotação e envie uma cópia do ZIP para armazenamento externo. O SMTP deve usar credencial de aplicação, TLS e remetente do domínio; não coloque senha SMTP em código ou no repositório.

## HTTPS e verificação

Ative AutoSSL antes de `SESSION_SECURE_COOKIE=true` e HSTS. Depois valide redirecionamento HTTPS, cookie `Secure`, headers CSP/HSTS e ausência de acesso a `.env`, `storage/private` e arquivos de backup pelo navegador.
