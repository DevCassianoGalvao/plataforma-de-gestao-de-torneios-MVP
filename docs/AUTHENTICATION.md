# Autenticacao e Acesso

## Login

O login normaliza o e-mail, verifica a senha com `password_verify`, registra a tentativa sem senha, aplica limite configuravel e retorna mensagem generica para qualquer falha. Depois do sucesso, o ID da sessao e regenerado e somente `user_id` e timestamp de atividade ficam na sessao.

Perfis redirecionam para: administrador em `/admin`, organizador em `/meus-campeonatos`, treinador/gestor em `/minha-equipe`, operador em `/minhas-partidas` e comunicacao em `/conteudo`.

## Sessao e cookies

O cookie e HttpOnly, SameSite=Lax e Secure quando a requisicao usa HTTPS. O timeout padrao e 1800 segundos e pode ser alterado por `SESSION_TIMEOUT`. Logout remove os dados da sessao e invalida o cookie.

## Recuperacao de senha

O pedido sempre retorna uma resposta generica. O sistema gera token aleatorio, grava somente o hash SHA-256, define expiracao de uma hora e marca o token como usado depois da redefinicao. O transporte padrao e `log`; o transporte `test` existe apenas para capturar mensagens em testes isolados. O token nao e escrito na interface como texto visivel.

## Senhas

Regra minima: 8 caracteres, uma letra, um numero e confirmacao igual. A senha nunca e armazenada em texto puro. O seed usa `SEED_DEMO_PASSWORD` somente em desenvolvimento/teste.

## Perfis e permissoes

Os perfis globais sao administrador, organizador, treinador/gestor de equipe, operador de partida e comunicacao. Toda rota protegida verifica permissao no servidor com `AuthorizationService`. Esconder um link do menu nao e a protecao.

## Auditoria

Login, logout, falhas de login, recuperacao, alteracoes de usuario, status, perfis, senha e acessos negados sao registrados. A listagem traduz a acao e nao apresenta o JSON de metadata bruto.

## Seed

Execute `db:seed` depois de `migrate` com `SEED_DEMO_PASSWORD` definido. Os e-mails `@torneios.local` sao ficticios e destinados somente a desenvolvimento. O seed e idempotente e e proibido em producao.

## Limitacoes atuais

Papeis ainda sao globais. Nao existe escopo por campeonato, equipe ou partida. O dashboard e a gestao de acesso sao funcionais, mas o layout final, tema dark, design system e modulos esportivos pertencem a etapas posteriores.
