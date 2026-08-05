# Autenticação e Acesso

## Login

O login normaliza o e-mail, verifica a senha com `password_verify`, registra a tentativa sem senha, aplica limite configurável e retorna mensagem genérica para qualquer falha. Depois do sucesso, o ID da sessão é regenerado e somente `user_id` e timestamp de atividade ficam na sessão.

Perfis redirecionam para: administrador em `/admin`, organizador em `/meus-campeonatos`, treinador/gestor em `/minha-equipe`, operador em `/minhas-partidas` e comunicação em `/conteudo`.

## Sessão e cookies

O cookie é HttpOnly, SameSite=Lax e Secure quando a requisição usa HTTPS. O timeout padrão é 1800 segundos e pode ser alterado por `SESSION_TIMEOUT`. Logout remove os dados da sessão e invalida o cookie.

## Recuperação de senha

O sistema não oferece recuperação por e-mail. Quando um usuário perde a senha, um administrador deve abrir Usuários, usar Gerar nova senha e entregar a senha temporária ao usuário por um canal seguro. A senha é armazenada somente como hash, exibida uma única vez na tela administrativa e deve ser trocada pelo usuário em Meu perfil depois do acesso.

## Senhas

Regra mínima: 8 caracteres, uma letra, um número e confirmação igual. A senha nunca e armazenada em texto puro. O seed usa `SEED_DEMO_PASSWORD` somente em desenvolvimento/teste.

## Perfis e permissões

Os perfis globais são administrador, organizador, treinador/gestor de equipe, operador de partida e comunicação. Toda rota protegida verifica permissão no servidor com `AuthorizationService`. Esconder um link do menu não e a proteção.

## Auditoria

Login, logout, falhas de login, recuperação, alterações de usuário, status, perfis, senha e acessos negados são registrados. A listagem traduz a ação e não apresenta o JSON de metadata bruto.

## Seed

Execute `db:seed` depois de `migrate` com `SEED_DEMO_PASSWORD` definido. Os e-mails `@torneios.local` são fictícios e destinados somente a desenvolvimento. O seed é idempotente e é proibido em produção.

## Limitações atuais

Papeis ainda são globais. Não existe escopo por campeonato, equipe ou partida. O dashboard e a gestão de acesso são funcionais, mas o layout final, tema dark, design system e módulos esportivos pertencem a etapas posteriores.
