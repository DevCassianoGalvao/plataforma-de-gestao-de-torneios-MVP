# Manual do administrador

## Rotina principal

1. Crie campeonato, temporada, categoria e regulamento.
2. Cadastre equipes, atribua treinadores e revise atletas/documentos.
3. Gere tabela, atribua operador por partida e acompanhe pendencias.
4. Revise e aprove partidas; dados aprovados alimentam portal, classificacao, rankings e sumulas.
5. Publique noticias, Vai e Vem e identidade do campeonato quando necessario.

## Backups

Abra **Backups**. Crie copia manual antes de alteracoes importantes, baixe somente para armazenamento seguro e teste conexao remota quando Google Drive estiver configurado. Exclusao exige confirmacao. Restauracao nao e feita pelo painel: use procedimento controlado descrito em [APPLICATION_BACKUPS.md](APPLICATION_BACKUPS.md).

## Seguranca

Nao compartilhe contas, nao envie `.env`, tokens ou ZIPs por canais publicos. Vincule perfis ao escopo correto: treinador a equipe, operador a partida e prestacao de contas ao campeonato.
