# Dados Fictícios de Teste

Todos os dados gerados são fictícios. E-mails usam `example.com`, telefones usam `0000`, documentos são placeholders e os arquivos são locais.

## Comando

```powershell
php database/seed.php --demo
```

Pré-requisitos: migrations aplicadas, MySQL local configurado e `APP_ENV` em `local`, `development` ou `testing`. Em staging, defina `DEMO_SEED_STAGING=1`. Em produção o comando falha antes de alterar o banco.

## Credenciais

Senha exclusiva de desenvolvimento: `Teste@2026`.

- `admin@example.com`
- `projeto@example.com`
- `organizador@example.com`
- `operador@example.com`
- `comunicacao@example.com`
- `auditoria@example.com`
- `treinador01@example.com` até `treinador14@example.com`

## Cenários

- Copa Brasil de Talentos 2026: dois grupos, classificação, quartas e semifinais geradas.
- Copa Serra Sub-15: grupo único com calendário parcial.
- Torneio Feminino Futuro em Campo: ainda não iniciado.
- Atletas, comissão, inscrições, cartões, suspensões, documentos, notícias, galerias, transferências e arquivos placeholder.

Não há `--fresh` para evitar exclusão acidental. Limpe dados demo somente em banco descartável ou por procedimento administrativo específico.
