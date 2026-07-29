# Implantacao em cPanel

## Estrutura

URL final: `https://www.cassianogalvao.com.br/torneio-online`.

- PHP 8.2 selecionado no MultiPHP.
- Banco MySQL criado no cPanel; use o nome, usuario e host fornecidos pelo painel.
- Document root apontando para `public`.
- Para `/torneio-online`, publique o conteudo de `public` em `public_html/torneio-online` ou configure o alias/subdiretorio para esse document root.
- `.env` fica um nivel acima de `public` e nunca deve estar sob document root.
- `storage/private`, `storage/logs`, `storage/sessions`, `storage/cache`, `storage/exports` e `storage/backups` ficam fora de `public`.

## Publicacao

1. Envie o codigo sem `.env`, logs, dumps, ZIPs ou backups.
2. Copie `.env.example` para `.env` no servidor e preencha credenciais reais fora do Git.
3. Configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_BASE_PATH=/torneio-online`, `SESSION_SECURE_COOKIE=true` e `APP_HSTS=true` somente com HTTPS funcionando.
4. Garanta permissoes 750 para diretorios de storage e 640 para arquivos privados; o usuario PHP precisa escrever apenas nos diretorios previstos.
5. Execute `php bin/install.php` pelo Terminal do cPanel ou SSH.
6. Nao execute `--seed` em producao.
7. Teste `/login`, uma rota administrativa autorizada, um download privado autorizado e `/torneio-online/campeonatos/{slug}`.

O `public/.htaccess` desativa listagem de diretorios, encaminha rotas ao front controller e bloqueia extensoes sensiveis. O `public/uploads/.htaccess` impede execucao de scripts em uploads publicos.

## Cron e operacao

O MVP nao depende de cron para servir paginas. Use cron para backup, por exemplo uma vez por dia:

```text
0 3 * * * cd /home/USUARIO/app && /usr/local/bin/php bin/backup.php --verify --retain=14 >> storage/logs/backup-cron.log 2>&1
```

Mantenha o log do cron fora de `public`, limite sua rotacao e envie uma copia do ZIP para armazenamento externo. O SMTP deve usar credencial de aplicacao, TLS e remetente do dominio; nao coloque senha SMTP em codigo ou no repositorio.

## HTTPS e verificacao

Ative AutoSSL antes de `SESSION_SECURE_COOKIE=true` e HSTS. Depois valide redirecionamento HTTPS, cookie `Secure`, headers CSP/HSTS e ausencia de acesso a `.env`, `storage/private` e arquivos de backup pelo navegador.
