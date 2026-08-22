<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$statusLabels = ['draft' => 'Rascunhos', 'submitted' => 'Enviadas', 'under_review' => 'Em análise', 'pending_correction' => 'Pendentes', 'approved' => 'Aprovadas', 'rejected' => 'Rejeitadas', 'suspended' => 'Suspensas', 'cancelled' => 'Canceladas'];
$selectedStatus = (string) ($query['status'] ?? '');
?>
<section class="registrations-page">
    <div class="section-heading">
        <div><p class="eyebrow">Inscrições e elenco</p><h1>Inscrições</h1><p>Gerencie as inscrições e consulte o elenco oficial aprovado.</p></div>
        <?php if ($canCreate): ?><a class="button" href="<?= $e(App\Core\Config::url('/admin/inscricoes/nova')) ?>">Nova inscrição</a><?php endif; ?>
        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/inscricoes/elenco')) ?>">Elenco oficial</a>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <nav class="action-nav status-tabs" aria-label="Status das inscrições">
        <a class="<?= $selectedStatus === '' ? 'is-active' : '' ?>" href="<?= $e(App\Core\Config::url('/admin/inscricoes')) ?>">Todas</a>
        <?php foreach ($statuses as $status): ?><a class="<?= $selectedStatus === $status ? 'is-active' : '' ?>" href="<?= $e(App\Core\Config::url('/admin/inscricoes?status=' . $status)) ?>"><?= $e($statusLabels[$status] ?? $status) ?></a><?php endforeach; ?>
    </nav>
    <form method="get" class="filters filters-compact">
        <input type="hidden" name="status" value="<?= $e($selectedStatus) ?>">
        <label>Campeonato <select name="championship_id"><option value="">Todos</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($query['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= $e($championship['name']) ?></option><?php endforeach; ?></select></label>
        <label>Equipe <select name="team_id"><option value="">Todas</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= (string) ($query['team_id'] ?? '') === (string) $team['id'] ? 'selected' : '' ?>><?= $e($team['name']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Aplicar filtros</button>
    </form>
    <?php if ($items === []): ?><div class="empty-state"><strong>Nenhuma inscrição encontrada.</strong><p>Altere os filtros ou crie uma nova inscrição para iniciar o fluxo.</p></div><?php else: ?>
    <form method="post" action="<?= $e(App\Core\Config::url('/admin/inscricoes/aprovar-em-lote')) ?>" data-registration-bulk-form>
        <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
        <?php if ($canBulkApprove): ?><div class="bulk-toolbar"><label><input type="checkbox" data-registration-select-all> Selecionar todas desta lista</label><button class="button" type="submit">Aprovar selecionadas</button></div><?php endif; ?>
        <div class="table-wrap"><table><thead><tr><th></th><th>Atleta</th><th>Equipe</th><th>Campeonato</th><th>Número</th><th>Status</th><th>Pendências</th><th>Atualizada</th><th>Ação</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?php if ($canBulkApprove && in_array((string) $item['status'], ['submitted', 'under_review'], true)): ?><input type="checkbox" name="registration_ids[]" value="<?= (int) $item['id'] ?>" data-registration-select-item><?php endif; ?></td><td><strong><?= $e($item['sporting_name'] ?: $item['athlete_name']) ?></strong><br><small><?= $e($item['athlete_name']) ?></small></td><td><?= $e($item['team_name']) ?></td><td><?= $e($item['championship_name']) ?></td><td><?= $e($item['requested_number'] ?: '-') ?></td><td><span class="status status-<?= $e($item['status']) ?>"><?= $e($statusLabels[$item['status']] ?? $item['status']) ?></span></td><td><?php if (!empty($item['pending_issues'])): ?><span class="status status-pending_correction" title="<?= $e($item['pending_issues']) ?>">Ver correção</span><?php elseif ($item['status'] === 'submitted'): ?>Aguardando análise<?php else: ?>- <?php endif; ?></td><td><?= $e($item['updated_at']) ?></td><td><a href="<?= $e(App\Core\Config::url('/admin/inscricoes/' . $item['id'])) ?>">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div>
    </form>
    <?php if ($canBulkApprove): ?><script>document.querySelector('[data-registration-select-all]')?.addEventListener('change', function(){document.querySelectorAll('[data-registration-select-item]').forEach(function(item){item.checked=this.checked;}, this);});document.querySelector('[data-registration-bulk-form]')?.addEventListener('submit', function(event){if(!document.querySelector('[data-registration-select-item]:checked')){event.preventDefault();alert('Selecione ao menos uma inscrição.');}else if(!confirm('Aprovar as inscrições selecionadas e incluí-las no elenco oficial?')){event.preventDefault();}});</script><?php endif; ?>
    <?php endif; ?>
</section>
