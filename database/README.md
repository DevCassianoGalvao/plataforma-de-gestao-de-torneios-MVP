# Seed de demonstração

Use somente em `local`, `development`, `testing` ou `staging` com `DEMO_SEED_STAGING=1`.

```powershell
php database/seed.php --demo
```

O comando é idempotente e cria/atualiza somente o namespace de dados fictícios (`demo-*` e `@example.com`). O modo `--fresh` é deliberadamente bloqueado: este repositório pode compartilhar uma base com dados que não pertencem ao seed. Para recriação completa, use um banco local descartável, execute migrations e rode o seed comum.
