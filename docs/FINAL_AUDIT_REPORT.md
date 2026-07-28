# Auditoria Técnica Final

## Release audit update - 27/07/2026

The release verdict is **APROVADO PARA HOMOLOGACAO, NAO PARA PRODUCAO**. `docs/FINAL_RELEASE_AUDIT.md` supersedes any previous implication that passing service-level tests is production evidence. Missing authenticated HTTP, complete guided administration, safe rectification reconstruction, public SEO/responsive proof and target infrastructure validation are release blockers.

Data-base: 27/07/2026. Este documento preserva os achados da auditoria original e separa o estado posterior comprovado por código e testes.

## Achados originais (histórico)

Na primeira auditoria foram identificadas lacunas críticas no fluxo esportivo: inscrições operacionais, grupos, tabela de jogos, escalações, operação da partida, homologação, mata-mata, definição de campeão e PDF. Também foram registrados riscos no CRUD genérico, que dependia de superadministrador e não aplicava escopo por recurso.

Esses achados descreviam o estado anterior e não devem ser usados como descrição do produto atual.

## Correções implementadas e comprovadas

`tests/tournament_e2e.php` executa fluxo persistido de campeonato: inscrições, dois grupos, geração de confrontos, escalações, eventos, homologação, classificação, geração de mata-mata, avanço, campeão, vice-campeão e PDF privado. A migration `012_operational_tournament.sql`, `TournamentOperationService`, `TournamentOperationController`, `StandingsService`, `ScheduleGenerationService` e `PdfReportService` sustentam esse fluxo.

O download privado usa ID persistido, autenticação, permissão/escopo, `realpath`, resposta 404 para arquivo indisponível, cabeçalhos de anexo e auditoria. O portal e as telas administrativas existentes consultam o banco; não são protótipos estáticos.

## Pendências atuais reais

1. **Crítica - autorização transversal incompleta:** o CRUD genérico ainda precisa migrar de `requireSuperAdmin()` e `Repository` direto para políticas e consultas filtradas por escopo. Esta é a etapa em andamento.
2. **Alta - inventário e prova de IDOR:** faltam matriz de autorização, teste E2E dedicado para todos os perfis e teste HTTP autenticado para 403/404/CSRF/sessão.
3. **Alta - cobertura de escopo:** relacionamentos indiretos (pessoa, inscrição, documento, evento, súmula e recursos de conteúdo) precisam ser resolvidos centralmente antes de liberar os perfis não globais no CRUD.
4. **Média - regras esportivas avançadas e portal público:** continuam sujeitos às etapas próprias do plano e não são declarados concluídos por esta auditoria.

## Conclusão corrente

O fluxo esportivo mínimo está implementado e comprovado. O sistema ainda não deve ser considerado pronto para operação multiusuário até a autorização granular e o isolamento de dados serem aplicados e testados em todas as rotas sensíveis.
