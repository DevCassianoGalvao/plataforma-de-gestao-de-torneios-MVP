# Rotas e Paginas

Todas as URLs passam por `Config::url` e respeitam `APP_BASE_PATH=/copa-online`.

## Publicas

| Metodo | Rota | Funcao |
|---|---|---|
| GET | `/` | base tecnica |
| GET | `/health` | health JSON |
| GET | `/login` | formulario de login |
| POST | `/login` | autenticar com CSRF |
| POST | `/logout` | encerrar sessao com CSRF |
| GET | `/senha/esqueci` | solicitar recuperacao |
| POST | `/senha/esqueci` | gerar token sem revelar cadastro |
| GET | `/senha/redefinir` | formulario de nova senha |
| POST | `/senha/redefinir` | validar e consumir token |

## Protegidas

| Metodo | Rota | Permissao |
|---|---|---|
| GET | `/admin` | `system.access` |
| GET/POST | `/admin/perfil` | usuario autenticado |
| POST | `/admin/perfil/senha` | usuario autenticado |
| GET | `/admin/usuarios` | `users.view` |
| GET | `/admin/usuarios/novo` | `users.create` |
| POST | `/admin/usuarios` | `users.create` |
| GET | `/admin/usuarios/{id}/editar` | `users.update` |
| POST | `/admin/usuarios/{id}` | `users.update` e, quando aplicavel, `users.manage_roles` |
| POST | `/admin/usuarios/{id}/status` | `users.deactivate` |
| POST | `/admin/usuarios/{id}/perfis` | `users.manage_roles` |
| POST | `/admin/usuarios/{id}/reset-password` | `users.update` |
| GET | `/admin/auditoria` | `audit.view` |

## Placeholders protegidos

`/meus-campeonatos`, `/minha-equipe`, `/minhas-partidas` e `/conteudo` exigem sessao e informam: "Modulo previsto para a proxima etapa." Nenhum dado esportivo falso e criado.
