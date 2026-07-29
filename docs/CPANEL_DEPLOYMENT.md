# Implantacao em cPanel

## Estrutura

URL final: `https://www.cassianogalvao.com.br/torneio-online`.

O nome do banco pode conter letras, numeros, underscore e hifen, por exemplo `xdigcomb_torneio-online`.

- PHP 8.2 selecionado no MultiPHP.
- Banco MySQL criado no cPanel; use o nome, usuario e host fornecidos pelo painel.
- Clone do repositorio em `/home/xdigcomb/cassianogalvao.com.br/torneio-online`.
- Preferencialmente, configure o Document Root de `/torneio-online` para `/home/xdigcomb/cassianogalvao.com.br/torneio-online/public`.
- Se o cPanel mantiver o Document Root na raiz do clone, o `.htaccess` da raiz encaminha a aplicacao para `public` e bloqueia o codigo privado.
- `.env` fica na raiz do clone, fora de `public`, e nunca deve ser versionado.
- `storage/private`, `storage/logs`, `storage/sessions`, `storage/cache`, `storage/exports` e `storage/backups` ficam fora de `public`.

## Publicacao

1. Envie o codigo sem `.env`, logs, dumps, ZIPs ou backups.
2. Copie `config/cpanel.env.example` para `.env` no servidor e preencha `DB_PASS` e `APP_KEY` fora do Git.
3. Configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_BASE_PATH=/torneio-online`, `SESSION_SECURE_COOKIE=true` e `APP_HSTS=true` somente com HTTPS funcionando.
4. Garanta permissoes 750 para diretorios de storage e 640 para arquivos privados; o usuario PHP precisa escrever apenas nos diretorios previstos.
5. No Terminal do cPanel, entre em `/home/xdigcomb/cassianogalvao.com.br/torneio-online` e execute o instalador com o PHP 8.2.
6. Nao execute `--seed` em producao.
7. Teste `/login`, uma rota administrativa autorizada, um download privado autorizado e `/torneio-online/campeonatos/{slug}`.

O `.htaccess` da raiz impede listagem, bloqueia o codigo privado e encaminha as requisicoes para `public`. O `public/.htaccess` trata as requisicoes quando `public` e o Document Root. O `public/uploads/.htaccess` impede execucao de scripts em uploads publicos.

## Cron e operacao

O MVP nao depende de cron para servir paginas. Use cron para backup, por exemplo uma vez por dia:

```text
0 3 * * * cd /home/xdigcomb/cassianogalvao.com.br/torneio-online && /opt/cpanel/ea-php82/root/usr/bin/php bin/backup.php --verify --retain=14 >> /home/xdigcomb/torneios-backup.log 2>&1
```

Se o provedor usar outro caminho para o PHP 8.2, selecione a versao no MultiPHP e use o caminho exibido pelo proprio cPanel. Para atualizar pelo Git Version Control, use o diretorio do clone:

```bash
cd /home/xdigcomb/cassianogalvao.com.br/torneio-online
git pull --ff-only origin main
/opt/cpanel/ea-php82/root/usr/bin/php bin/install.php
```

Mantenha o log do cron fora de `public`, limite sua rotacao e envie uma copia do ZIP para armazenamento externo. O SMTP deve usar credencial de aplicacao, TLS e remetente do dominio; nao coloque senha SMTP em codigo ou no repositorio.

## HTTPS e verificacao

Ative AutoSSL antes de `SESSION_SECURE_COOKIE=true` e HSTS. Depois valide redirecionamento HTTPS, cookie `Secure`, headers CSP/HSTS e ausencia de acesso a `.env`, `storage/private` e arquivos de backup pelo navegador.
