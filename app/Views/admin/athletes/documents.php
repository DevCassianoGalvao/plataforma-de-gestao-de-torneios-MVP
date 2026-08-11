<section>
    <div class="section-heading"><div><p class="eyebrow">Atleta</p><h1>Documentos</h1><p><?= App\Core\View::e($athlete['full_name']) ?></p></div><div class="button-row"><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'])) ?>">Voltar ao atleta</a><?php if ($canCreateRegistration): ?><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes/nova?athlete_id=' . (int) $athlete['id'] . '&team_id=' . (int) $athlete['team_id'])) ?>">Criar inscrição</a><?php endif; ?></div></div>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <?php if ($items === []): ?><p>Nenhum documento enviado.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Arquivo</th><th>Validade</th><th>Status</th><th>Motivo</th><th>Análise</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?>
        <tr><td><?= App\Core\View::e($item['document_type_name']) ?></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'] . '/documentos/' . $item['id'])) ?>">Abrir privado</a></td><td><?= App\Core\View::e($item['expires_at'] ?: 'Sem validade') ?></td><td><?= App\Core\View::e($item['status']) ?></td><td><?= App\Core\View::e($item['rejection_reason'] ?: '-') ?></td><td><?= App\Core\View::e($item['reviewer_name'] ?? 'Pendente') ?></td></tr>
        <?php if ($canReview): ?><tr><td colspan="6"><form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'] . '/documentos/' . $item['id'] . '/status')) ?>" class="inline-form"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><select name="status"><?php foreach (['pending', 'approved', 'rejected', 'expired', 'replaced', 'archived'] as $status): ?><option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select><input name="rejection_reason" placeholder="Motivo se rejeitado"><button type="submit">Registrar analise</button></form></td></tr><?php endif; ?>
    <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php if ($canManage): ?>
<section><h2>Enviar documento</h2><form method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'] . '/documentos')) ?>">
    <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
    <label>Tipo <select name="document_type_id" required><option value="">Selecione</option><?php foreach ($types as $type): ?><option value="<?= (int) $type['id'] ?>"><?= App\Core\View::e($type['name']) ?></option><?php endforeach; ?></select></label>
    <label>Responsável, quando aplicavel <select name="guardian_id"><option value="">Não se aplica</option><?php foreach ($guardians as $guardian): ?><option value="<?= (int) $guardian['id'] ?>"><?= App\Core\View::e($guardian['full_name']) ?> · <?= App\Core\View::e($guardian['relationship']) ?></option><?php endforeach; ?></select></label>
    <label>Arquivo <input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg,.webp,application/pdf,image/png,image/jpeg,image/webp" required></label><label>Validade <input type="date" name="expires_at"></label><label>Observação <textarea name="observation" rows="3"></textarea></label><button type="submit">Enviar para analise</button>
</form></section>
<?php endif; ?>
