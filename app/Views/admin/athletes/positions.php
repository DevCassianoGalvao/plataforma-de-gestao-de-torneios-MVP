<section>
    <div class="section-heading"><div><p class="eyebrow">Catalogo</p><h1>Posicoes</h1><p>Catalogo estruturado usado na posicao principal e nas alternativas.</p></div><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas')) ?>">Atletas</a></div>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Codigo</th><th>Grupo</th><th>Ordem</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= App\Core\View::e($item['name']) ?></td><td><?= App\Core\View::e($item['code']) ?></td><td><?= App\Core\View::e($item['position_group']) ?></td><td><?= (int) $item['display_order'] ?></td><td><?= App\Core\View::e($item['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
