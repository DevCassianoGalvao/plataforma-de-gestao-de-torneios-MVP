<section>
    <div class="section-heading">
        <div><p class="eyebrow">Elenco e analise</p><h1>Inscrições</h1><p>Fluxo por campeonato, equipe e atleta.</p></div>
        <?php if ($canCreate): ?><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes/nova')) ?>">Nova inscricao</a><?php endif; ?>
        <a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes/elenco')) ?>">Elenco oficial</a>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <nav class="action-nav" aria-label="Status das inscricoes">
        <a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes')) ?>">Todas</a>
        <?php foreach ($statuses as $status): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes?status=' . $status)) ?>"><?= App\Core\View::e($status) ?></a><?php endforeach; ?>
    </nav>
    <form method="get" class="filters">
        <label>Campeonato <select name="championship_id"><option value="">Todos</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($query['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= App\Core\View::e($championship['name']) ?></option><?php endforeach; ?></select></label>
        <label>Equipe <select name="team_id"><option value="">Todas</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= (string) ($query['team_id'] ?? '') === (string) $team['id'] ? 'selected' : '' ?>><?= App\Core\View::e($team['name']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Filtrar</button>
    </form>
    <?php if ($items === []): ?><p>Nenhuma inscricao encontrada.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Atleta</th><th>Equipe</th><th>Campeonato</th><th>Número</th><th>Status</th><th>Atualizada</th><th>Acao</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><strong><?= App\Core\View::e($item['sporting_name'] ?: $item['athlete_name']) ?></strong><br><small><?= App\Core\View::e($item['athlete_name']) ?></small></td><td><?= App\Core\View::e($item['team_name']) ?></td><td><?= App\Core\View::e($item['championship_name']) ?></td><td><?= App\Core\View::e($item['requested_number'] ?: '-') ?></td><td><span class="status status-<?= App\Core\View::e($item['status']) ?>"><?= App\Core\View::e($item['status']) ?></span></td><td><?= App\Core\View::e($item['updated_at']) ?></td><td><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes/' . $item['id'])) ?>">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
