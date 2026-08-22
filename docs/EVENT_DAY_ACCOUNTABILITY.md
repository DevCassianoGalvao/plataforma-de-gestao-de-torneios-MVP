# Prestação por Dia de Evento

## Objetivo

O módulo permite registrar evidências administrativas por dia de evento, sem prender o campeonato a um único local. Um dia pode ter um local principal ou representar atividades realizadas em vários locais; cada partida continua vinculada ao seu próprio local e operador.

## Configuração

O administrador cadastra dias de evento em `/admin/dias-evento` e informa:

- campeonato;
- nome do evento;
- data;
- local, quando houver;
- observações.

Os locais e suas cidades são cadastrados no catálogo de locais. O sistema não inventa cidades: o filtro de cidade usa os dados cadastrados no local da partida ou do dia de evento.

## Checklist configurável

Cada campeonato possui seu próprio checklist. Os itens podem ser duplicados de outro campeonato e depois ajustados sem alterar o checklist de origem. O seed da Copa Brasil cria, de forma idempotente, uma base inicial com:

- súmula da partida;
- fotos da partida;
- fotos do dia do evento;
- comprovante do local;
- lista de presença.

Os limites, tipos MIME, obrigatoriedade e bloqueios são propriedades do item configurável. A Copa Brasil não está hardcodada no fluxo da aplicação.

## Prestação de contas

Na prestação detalhada do campeonato, o usuário autorizado pode filtrar por:

- fase, grupo e rodada;
- equipe;
- local;
- cidade;
- dia de evento;
- período;
- situação documental.

Os rótulos das fases eliminatórias são exibidos de forma clara, como `Semifinal` e `Final`, usando o rótulo configurado na rodada.

Os downloads disponíveis são:

- CSV, Excel, PDF e ZIP dos dados consolidados;
- CSV e Excel específicos das evidências.

O ZIP inclui as evidências aprovadas das partidas e dos dias de evento, organizadas em pastas separadas. Cada exportação registra filtros, quantidade, nome e hash nos logs de prestação e na auditoria.

## Privacidade e aprovação

Somente evidências aprovadas e autorizadas são exportadas. Arquivos privados não são expostos no portal público. O perfil de prestação de contas consulta e baixa os dados permitidos, mas não recebe acesso às telas administrativas de configuração.

## Pendência operacional

O sistema fornece a estrutura genérica e o checklist inicial. As cidades, locais e dias reais da Copa devem ser cadastrados pelo administrador conforme o calendário oficial, pois esses dados não devem ser inventados pelo código.
