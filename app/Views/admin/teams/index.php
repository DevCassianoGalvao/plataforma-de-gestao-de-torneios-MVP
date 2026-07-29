<section>
    <div class="section-heading"><div><p class="eyebrow">Cadastro esportivo</p><h1>Equipes</h1></div><?php if ($canCreate): ?><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes/nova')) ?>">Nova equipe</a><?php endif; ?></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form class="search-form" method="get" action="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes')) ?>">
        <label>Buscar <input type="search" name="search" value="<?= App\Core\View::e($query['search'] ?? '') ?>"></label>
        <?php if ($championships !== []): ?><label>Campeonato <select name="championship_id"><option value="">Todos</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($query['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= App\Core\View::e($championship['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <label>Status <select name="status"><option value="">Todos</option><?php foreach (['draft' => 'Rascunho', 'active' => 'Ativa', 'inactive' => 'Inativa', 'withdrawn' => 'Retirada', 'archived' => 'Arquivada'] as $key => $label): ?><option value="<?= $key ?>" <?= ($query['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <label>Cidade <input name="city" value="<?= App\Core\View::e($query['city'] ?? '') ?>"></label>
        <button type="submit">Filtrar</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Equipe</th><th>Campeonato</th><th>Cidade</th><th>Treinador</th><th>Formacao</th><th>Status</th><th>Acoes</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><strong><?= App\Core\View::e($item['name']) ?></strong><br><small><?= App\Core\View::e($item['abbreviation']) ?> · <?= App\Core\View::e($item['short_name']) ?></small></td><td><?= App\Core\View::e($item['championship_name']) ?></td><td><?= App\Core\View::e(trim(($item['city'] ?? '') . ' / ' . ($item['state'] ?? ''), ' /')) ?></td><td><?= App\Core\View::e($item['coach_name'] ?: 'Nao definido') ?></td><td><?= App\Core\View::e($item['formation_name'] ?: 'Nao definida') ?></td><td><span class="status status-<?= App\Core\View::e($item['status']) ?>"><?= App\Core\View::e($item['status']) ?></span></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes/' . $item['slug'])) ?>">Abrir</a></td></tr><?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="7">Nenhuma equipe encontrada.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
