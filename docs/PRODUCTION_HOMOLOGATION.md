# Homologacao de producao

Data da verificacao externa: 2026-08-05.

## Resultado atual

O ambiente publico responde em `https://www.cassianogalvao.com.br/torneio-online`, mas esta rodada ainda nao pode ser declarada como producao aprovada. O acesso ao cPanel, ao banco real, ao cron e as credenciais externas nao esta disponivel neste ambiente Codex.

## Evidencias publicas

- Portal da Copa: HTTP 200 em `/torneio-online/campeonatos/copa-brasil-de-talentos-2026`.
- Login: HTTP 200 em `/torneio-online/login`.
- `.env`: bloqueado com HTTP 403.
- `storage/logs/app.log`: bloqueado com HTTP 403.
- `storage/backups/`: bloqueado com HTTP 403.
- HTTPS entrega cookies com `Secure` quando a aplicacao responde com sessao.
- HTTP ainda respondeu HTTP 200 durante a verificacao anterior; os `.htaccess` agora forcam redirecionamento para HTTPS e precisam ser publicados e revalidados no cPanel.

## Bloqueios para o veredito de producao

- PHP, extensoes, MySQL, permissoes, espaco e timezone do servidor ainda precisam de evidencia no cPanel.
- Backup antes de migration precisa ser criado e validado no banco real.
- Migrations precisam ser executadas em janela controlada, sem seed demo.
- Cron de publicacao, backup e retencao precisa ser executado e comprovado.
- Google Drive exige token, pasta compartilhada e teste de upload real.
- Restauracao precisa ocorrer em banco separado, nunca sobre producao.
- SMTP precisa entregar e-mail de teste real.
- Responsividade e acessibilidade precisam de verificacao manual em dispositivos reais.

## Comandos cPanel

Execute dentro do clone, depois de criar e validar o backup:

```bash
cd /home/xdigcomb/cassianogalvao.com.br/torneio-online
/opt/cpanel/ea-php82/root/usr/bin/php bin/console.php migrate:status
/opt/cpanel/ea-php82/root/usr/bin/php bin/backup.php --verify --retain=14
/opt/cpanel/ea-php82/root/usr/bin/php bin/console.php migrate
```

Nao execute `db:seed` ou `install.php --seed` em producao.

## Veredito

**APROVADO PARA HOMOLOGACAO**. Nao aprovado para producao ate que os bloqueios acima tenham evidencias registradas.
