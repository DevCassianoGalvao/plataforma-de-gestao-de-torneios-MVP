<section>
    <p class="eyebrow">Seguranca</p>
    <h1>Auditoria</h1>
    <div class="table-wrap"><table><thead><tr><th>Data</th><th>Usuario</th><th>Acao</th><th>Recurso</th><th>IP</th></tr></thead><tbody>
    <?php foreach ($entries as $entry): ?><tr><td><?= App\Core\View::e($entry['created_at']) ?></td><td><?= App\Core\View::e($entry['user_name'] ?: 'Sistema / anonimo') ?></td><td><?= App\Core\View::e(str_replace(['auth.', 'users.', 'profile.', 'authorization.'], '', (string) $entry['action'])) ?></td><td><?= App\Core\View::e(trim(($entry['resource_type'] ?? '') . ' ' . ($entry['resource_id'] ?? '')) ?: '-') ?></td><td><?= App\Core\View::e($entry['ip'] ?: '-') ?></td></tr><?php endforeach; ?>
    <?php if ($entries === []): ?><tr><td colspan="5">Nenhum evento registrado.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
