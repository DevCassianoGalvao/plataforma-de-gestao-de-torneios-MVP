<?php $e = static fn (mixed $value): string => App\Core\View::e($value); $filters = $filters ?? []; ?>
<section class="roster-page">
    <div class="section-heading">
        <div><p class="eyebrow">Elegibilidade da competição</p><h1>Elenco oficial</h1><p>Aqui ficam somente atletas com inscrição aprovada. Esta é a base usada nas escalações e na operação das partidas.</p></div>
        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/inscricoes')) ?>">Ver inscrições</a>
    </div>
    <div class="roster-notice"><span class="roster-notice-mark" aria-hidden="true">✓</span><div><strong>Relação liberada para a competição</strong><p>Documentos e regras foram validados no momento da aprovação. Pendências e rascunhos continuam na central de inscrições.</p></div><b><?= count($items) ?></b></div>
    <form method="get" class="filters filters-compact">
        <label>Campeonato <select name="championship_id"><option value="">Todos</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($filters['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= $e($championship['name']) ?></option><?php endforeach; ?></select></label>
        <label>Equipe <select name="team_id"><option value="">Todas</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= (string) ($filters['team_id'] ?? '') === (string) $team['id'] ? 'selected' : '' ?>><?= $e($team['name']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Aplicar filtros</button>
    </form>
    <?php if ($items === []): ?><div class="empty-state"><strong>Nenhum atleta aprovado neste recorte.</strong><p>Acompanhe rascunhos, análises e pendências na central de inscrições.</p></div><?php else: ?><div class="table-wrap roster-table"><table><thead><tr><th>Atleta</th><th>Equipe</th><th>Categoria</th><th>Número</th><th>Posição</th><th>Documentação</th><th>Aprovação</th><th>Situação</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><strong><?= $e($item['sporting_name'] ?: $item['athlete_name']) ?></strong></td><td><?= $e($item['team_name']) ?></td><td><?= $e($item['category_name']) ?></td><td><?= $e($item['requested_number'] ?: '-') ?></td><td><?= $e($item['primary_position_name']) ?></td><td>Conferida na aprovação</td><td><?= $e($item['decided_at'] ?: '-') ?></td><td><span class="status status-approved">Aprovada</span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
