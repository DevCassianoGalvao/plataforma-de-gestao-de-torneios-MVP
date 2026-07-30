# Operacao Administrativa

## Minhas partidas

`/minhas-partidas` exige a permissao de operacao. O operador ve apenas partidas atribuidas; o administrador ve todas as partidas abertas para acompanhar ou operar. Partidas canceladas e homologadas ficam fora dessa fila.

## Elenco dentro da equipe

`/admin/equipes/{slug}` mostra o elenco oficial aprovado da equipe. O quadro usa as inscricoes aprovadas e oferece acesso a `/admin/inscricoes/elenco`; rascunhos, pendencias e documentos privados continuam na central protegida de inscricoes.

## Central de notificacoes

Administradores acessam `/admin/notificacoes`. Cada evento de auditoria gera uma notificacao com data, usuario, acao e recurso. A migration inicial tambem importa o historico existente. A central permite marcar uma notificacao ou todas como lidas; o contador aparece no menu e na barra superior.

## Categorias

A migration `0018_admin_notifications_and_categories.sql` mantem a Sub-15 existente e adiciona Sub-09, Sub-11, Sub-13, Sub-17, Sub-20 e Adulto, todas idempotentes. A migration `0019_demo_championship_adult.sql` atualiza somente o campeonato demo e seus atletas ficticios para Adulto, sem alterar atletas reais. Novos campeonatos continuam escolhendo a categoria por nome no catalogo, sem IDs digitados.

## Estado dos modulos

Inscricoes, elenco, partidas, disciplina, classificacao, sumula, noticias, Vai e Vem e portal publico possuem rotas e telas proprias. A pagina do atleta usa atalhos para esses modulos; nao apresenta mais avisos de implementacao futura.
