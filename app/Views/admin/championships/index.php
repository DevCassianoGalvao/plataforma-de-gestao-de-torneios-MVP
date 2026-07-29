<section>
    <div class="section-heading"><div><p class="eyebrow">Competicoes</p><h1>Campeonatos</h1></div><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/novo')) ?>">Novo campeonato</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form class="search-form" method="get" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos')) ?>">
        <label>Buscar <input type="search" name="q" value="<?= App\Core\View::e($query['q'] ?? '') ?>"></label>
        <label>Status <select name="status"><option value="">Todos</option><?php foreach (['draft' => 'Rascunho', 'registration' => 'Inscricoes', 'configured' => 'Configurado', 'in_progress' => 'Em andamento', 'finished' => 'Finalizado', 'archived' => 'Arquivado'] as $key => $label): ?><option value="<?= $key ?>" <?= ($query['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <label>Temporada <select name="season_id"><option value="">Todas</option><?php foreach ($seasons as $season): ?><option value="<?= (int) $season['id'] ?>" <?= (string) ($query['season_id'] ?? '') === (string) $season['id'] ? 'selected' : '' ?>><?= App\Core\View::e($season['name']) ?></option><?php endforeach; ?></select></label>
        <label>Categoria <select name="category_id"><option value="">Todas</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($query['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= App\Core\View::e($category['name']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Filtrar</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Campeonato</th><th>Temporada</th><th>Categoria</th><th>Status</th><th>Visibilidade</th><th>Acoes</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><strong><?= App\Core\View::e($item['name']) ?></strong><br><small><?= App\Core\View::e($item['short_name']) ?></small></td><td><?= App\Core\View::e($item['season_name']) ?></td><td><?= App\Core\View::e($item['category_name']) ?></td><td><span class="status status-<?= App\Core\View::e($item['status']) ?>"><?= App\Core\View::e($item['status']) ?></span></td><td><?= App\Core\View::e($item['visibility']) ?></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $item['slug'])) ?>">Abrir</a></td></tr><?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="6">Nenhum campeonato encontrado.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
