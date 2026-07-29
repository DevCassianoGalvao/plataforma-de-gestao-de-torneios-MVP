<section>
    <div class="section-heading"><div><p class="eyebrow">Catalogo</p><h1>Categorias</h1></div><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/categorias/nova')) ?>">Nova categoria</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Slug</th><th>Idade</th><th>Genero</th><th>Status</th><th>Acoes</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= App\Core\View::e($item['name']) ?></td><td><?= App\Core\View::e($item['slug']) ?></td><td><?= App\Core\View::e(($item['minimum_age'] ?? '-') . ' a ' . ($item['maximum_age'] ?? '-')) ?></td><td><?= App\Core\View::e($item['gender_rule'] ?: '-') ?></td><td><?= App\Core\View::e($item['status']) ?></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/categorias/' . $item['id'] . '/editar')) ?>">Editar</a></td></tr><?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="6">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
