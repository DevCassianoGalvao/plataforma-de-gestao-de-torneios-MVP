# Operação Administrativa

## Minhas partidas

`/minhas-partidas` exige a permissão de operação. O operador vê apenas partidas atribuídas; o administrador vê todas as partidas abertas para acompanhar ou operar. Partidas canceladas e homologadas ficam fora dessa fila.

## Elenco dentro da equipe

`/admin/equipes/{slug}` mostra o elenco oficial aprovado da equipe. O quadro usa as inscrições aprovadas e oferece acesso a `/admin/inscricoes/elenco`; rascunhos, pendências e documentos privados continuam na central protegida de inscrições.

## Central de notificações

Administradores acessam `/admin/notificacoes`. Cada evento de auditoria gera uma notificação com data, usuário, ação e recurso. A migration inicial também importa o histórico existente. A central permite marcar uma notificação ou todas como lidas; o contador aparece no menu e na barra superior.

## Categorias

A migration `0018_admin_notifications_and_categories.sql` mantém a Sub-15 existente e adiciona Sub-09, Sub-11, Sub-13, Sub-17, Sub-20 e Adulto, todas idempotentes. A migration `0019_demo_championship_adult.sql` atualiza somente o campeonato demo e seus atletas fictícios para Adulto, sem alterar atletas reais. Novos campeonatos continuam escolhendo a categoria por nome no catálogo, sem IDs digitados.

## Estado dos módulos

Inscrições, elenco, partidas, disciplina, classificação, súmula, notícias, Vai e Vem e portal público possuem rotas e telas próprias. A página do atleta usa atalhos para esses módulos; não apresenta mais avisos de implementação futura.
