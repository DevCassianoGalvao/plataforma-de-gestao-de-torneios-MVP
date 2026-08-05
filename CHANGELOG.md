# Changelog

## Em desenvolvimento

### Adicionado

- Prestação de contas completa com filtros, detalhe oficial, CSV, Excel, PDF, pacote privado, hashes e anexo de súmula assinada.
- Retificação avançada com edição pontual de eventos, diff de campos, reaprovação e segunda aprovação configurável.
- Central de retenção com políticas por classe de dado, arquivamento, restauração, exclusão lógica e histórico auditável.
- Validacao final registrada: lint, suite descartavel, HTTP real, migration/seed e backup local.
- Manuais operacionais separados para administrador e operador de partida.

### Corrigido

- Comando legado `bin/backup.php` unificado ao servico auditavel de backups.

- Backups com historico auditavel, bloqueio de concorrencia, validacao ZIP e hash, download autorizado, retencao e destino Google Drive opcional.

- Configuracoes avancadas de regulamento, regras de elegibilidade entre fases, excecoes administrativas auditadas e validacao no servidor durante confirmacao de escalacao.

- Simulador interno isolado com cenários, partidas de referência, confrontos hipotéticos, placares, eventos e comparação com a classificação oficial.
- Cálculo de classificação reutiliza o motor compartilhado e não grava em partidas, rankings, súmulas, publicação ou prestação de contas oficiais.
- Acompanhamento administrativo por rodada com cobertura esportiva, documental e de publicação.
- Prazo documental configurável por campeonato, exportação CSV de pendências e pacote de súmulas reutilizado.
- Permissões específicas para consulta, configuração, relatórios, pacote e futuras ações em lote.
