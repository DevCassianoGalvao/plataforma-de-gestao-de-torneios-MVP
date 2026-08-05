# Prestação de contas completa

## Escopo

O módulo consolida somente registros oficiais de partidas aprovadas. Acesso é limitado ao administrador e ao usuário de prestação de contas vinculado ao campeonato.

## Configuração

Em `Prestação de contas`, o administrador pode definir se a conferência exige:

- súmula digital atual;
- súmula assinada anexada à versão atual;
- evidências de partida aprovadas.

As regras ficam por campeonato em `championship_accountability_settings`.

## Conferência e detalhe

O painel permite filtrar por fase, grupo, rodada, equipe, intervalo de datas e situação documental. Cada partida aprovada abre um detalhe com operação, eventos, arbitragem, versões da súmula e evidências aprovadas.

## Exportações

As rotas `/prestacao/campeonatos/{id}/exportar/{formato}` oferecem CSV, Excel (`.xlsx`), PDF e pacote privado (`.zip`). O pacote contém dados consolidados, manifesto com hashes, versões oficiais da súmula, documento assinado e evidências autorizadas quando existirem.

Cada exportação grava formato, filtros, partidas incluídas, nome e SHA-256 em `accountability_export_logs` e na auditoria.

## Súmula assinada

O PDF assinado é aceito somente como PDF, armazenado fora de `public` e vinculado à versão atual. A substituição cria novo vínculo e nunca sobrescreve silenciosamente a versão oficial anterior.

## Limites

Simulações, partidas não aprovadas, documentos privados não autorizados e registros de outros campeonatos ficam fora das consultas e dos pacotes.
