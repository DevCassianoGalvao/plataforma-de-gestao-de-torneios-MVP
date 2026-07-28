# Schema de Banco

## Migrations implementadas

| Migration | Conteudo | Estado |
|---|---|---|
| `0001_foundation.sql` | `schema_migrations`, `foundation_health` | implementada |
| `0002_authentication.sql` | usuarios, papeis, permissoes, tokens, tentativas e auditoria | implementada |

## Tabelas da Etapa 2

| Tabela | Finalidade |
|---|---|
| `users` | identidade, status, hash de senha, login e timestamps |
| `roles` | catalogo global de perfis |
| `permissions` | catalogo nomeado por modulo |
| `role_permissions` | relacionamento sem duplicidade entre perfis e permissoes |
| `user_roles` | relacionamento global entre usuario e perfil |
| `password_reset_tokens` | hash, expiracao e uso unico de recuperacao |
| `login_attempts` | tentativa, e-mail anonimizado, IP, user agent e resultado |
| `audit_logs` | eventos de seguranca e gestao |

O e-mail de `users` e unico. Nenhuma senha, token original ou credencial real e armazenada no repositorio.

## Preparacao para escopos

`user_roles` e global nesta etapa. Quando campeonamentos, equipes e partidas existirem, o relacionamento devera receber um contexto de escopo em tabela propria ou colunas explicitamente validadas. Nao declarar isolamento por campeonato ou equipe antes dessa implementacao.

## Entidades futuras

Campeonatos, regulamentos, equipes, atletas, inscricoes, grupos, rodadas, partidas, escalacoes, eventos, classificacao, suspensoes, sumulas, noticias, transferencias e portal publico continuam pendentes conforme o plano.
