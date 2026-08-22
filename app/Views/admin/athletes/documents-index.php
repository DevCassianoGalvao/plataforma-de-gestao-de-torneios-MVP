<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$documentStatusLabels = ['pending' => 'Pendente', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado'];
$canBulkReview = $status === 'pending';
?>
<section>
    <div class="section-heading"><div><p class="eyebrow">Cadastro esportivo</p><h1>Documentos para análise</h1><p>Revise documentos enviados pelos treinadores e aprove vários de uma vez.</p></div></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <nav class="action-nav status-tabs" aria-label="Status dos documentos">
        <?php foreach (['pending' => 'Pendentes', 'approved' => 'Aprovados', 'rejected' => 'Rejeitados'] as $key => $label): ?><a class="<?= $status === $key ? 'is-active' : '' ?>" href="<?= $e(App\Core\Config::url('/admin/documentos?status=' . $key)) ?>"><?= $e($label) ?></a><?php endforeach; ?>
    </nav>
    <?php if ($items === []): ?><div class="empty-state"><strong>Nenhum documento encontrado.</strong><p>Documentos pendentes aparecerão aqui para análise.</p></div><?php else: ?>
    <?php if ($canBulkReview): ?><form method="post" action="<?= $e(App\Core\Config::url('/admin/documentos/aprovar-em-lote')) ?>" data-bulk-form>
        <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
        <div class="bulk-toolbar"><label><input type="checkbox" data-select-all> Selecionar todos desta lista</label><button class="button" type="submit">Aprovar selecionados</button></div>
    <?php endif; ?>
        <div class="table-wrap"><table><thead><tr><th></th><th>Atleta</th><th>Equipe</th><th>Documento</th><th>Status</th><th>Enviado em</th><th>Ação</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?><tr><td><?php if ($canBulkReview): ?><input type="checkbox" name="document_ids[]" value="<?= (int) $item['id'] ?>" data-select-item><?php endif; ?></td><td><strong><?= $e($item['sporting_name'] ?: $item['athlete_name']) ?></strong><br><small><?= $e($item['athlete_name']) ?></small></td><td><?= $e($item['team_name']) ?></td><td><?= $e($item['document_type_name']) ?></td><td><span class="status status-<?= $e($item['status']) ?>"><?= $e($documentStatusLabels[$item['status']] ?? $item['status']) ?></span></td><td><?= $e($item['created_at']) ?></td><td><a href="<?= $e(App\Core\Config::url('/admin/atletas/' . $item['athlete_id'] . '/documentos')) ?>">Abrir atleta</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php if ($canBulkReview): ?></form>
    <?php endif; ?>
    <?php endif; ?>
</section>
