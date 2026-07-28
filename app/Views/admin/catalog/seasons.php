<section>
    <div class="section-heading"><div><p class="eyebrow">Catalogo</p><h1>Temporadas</h1></div><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/temporadas/nova')) ?>">Nova temporada</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Ano</th><th>Periodo</th><th>Status</th><th>Acoes</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= App\Core\View::e($item['name']) ?></td><td><?= (int) $item['year'] ?></td><td><?= App\Core\View::e(($item['starts_at'] ?: '-') . ' a ' . ($item['ends_at'] ?: '-')) ?></td><td><span class="status status-<?= App\Core\View::e($item['status']) ?>"><?= App\Core\View::e($item['status']) ?></span></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/temporadas/' . $item['id'] . '/editar')) ?>">Editar</a></td></tr><?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="5">Nenhuma temporada cadastrada.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
