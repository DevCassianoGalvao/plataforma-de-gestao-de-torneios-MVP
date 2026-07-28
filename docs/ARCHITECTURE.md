# Arquitetura do MVP

## Stack e fluxo

- PHP 8.2 sem framework obrigatorio.
- MySQL via PDO com prepared statements.
- MVC simples, autoload PSR-4 minimo e front controller em `public/index.php`.
- Rotas declaradas em `routes/web.php`.
- `APP_BASE_PATH` controla URLs em `/copa-online`.
- Views PHP simples; a UI/UX definitiva fica para uma rodada posterior.

O bootstrap carrega ambiente, timezone e sessao. O roteador remove o base path, resolve a rota e entrega o request ao controller. Controllers usam repositories e services; views nao executam regras de autorizacao para proteger recursos.

## Autenticacao e autorizacao

`AuthService` concentra login, logout, tentativas e regeneracao de sessao. `AuthorizationService` resolve permissoes a partir dos papeis persistidos. Controllers usam `Controller::guard`, portanto a protecao ocorre no servidor e nao depende do menu.

O escopo global dos papeis e intencional nesta etapa. A arquitetura reserva o espaco para escopos por organizacao, campeonato, equipe e partida quando essas entidades existirem; nenhum isolamento esportivo esta concluido agora.

## Auditoria e JSON

Eventos importantes sao gravados em `audit_logs`. O campo `metadata` usa JSON porque cada evento pode ter atributos pequenos e diferentes, sem transformar a auditoria em tabelas rigidas. Esse JSON fica somente no banco e nunca e exibido bruto em formularios ou na listagem; a interface mostra apenas acao, recurso, data, usuario e IP.

## Seguranca

- senhas usam `password_hash` e `password_verify`;
- tokens de recuperacao sao armazenados apenas como SHA-256;
- CSRF e exigido em todas as mutacoes;
- cookies sao HttpOnly, SameSite=Lax e Secure quando HTTPS;
- o ID de sessao regenera apos login;
- timeout de inatividade e configuravel;
- rate limit de login e lock temporario sao configuraveis;
- mensagens de login nao revelam se o e-mail existe;
- logs e arquivos privados ficam fora do versionamento.

## Limites atuais

Nao existem ainda campeonatos, equipes, atletas, partidas, noticias, Vai e Vem, sumula ou portal publico. O dashboard atual serve apenas como base de acesso e gestao de usuarios.
