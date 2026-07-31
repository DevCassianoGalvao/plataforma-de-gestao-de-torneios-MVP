<section>
    <p class="eyebrow">Painel do campeonato</p>
    <div class="section-heading"><div><h1><?= App\Core\View::e($championship['name']) ?></h1><p><?= App\Core\View::e($championship['short_name']) ?> · <?= App\Core\View::e($championship['season_name']) ?> · <?= App\Core\View::e($championship['category_name']) ?></p></div><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/editar')) ?>">Editar</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <p><span class="status status-<?= App\Core\View::e($championship['status']) ?>">Status: <?= App\Core\View::e($championship['status']) ?></span> <span class="status">Visibilidade: <?= App\Core\View::e($championship['visibility']) ?></span></p>
    <?php if ($championship['visibility'] === 'public' && $championship['status'] !== 'draft'): ?>
        <p><a href="<?= App\Core\View::e(App\Core\Config::absoluteUrl('/campeonatos/' . $championship['slug'])) ?>" target="_blank" rel="noopener">Ver portal público ↗</a></p>
    <?php else: ?>
        <p class="muted">O portal público fica disponível quando a visibilidade for "Pública" e o status sair de rascunho.</p>
    <?php endif; ?>
    <p><?= App\Core\View::e($championship['description'] ?: 'Sem descricao.') ?></p>
    <div class="grid-links">
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade')) ?>"><strong>Identidade</strong><span>Cores, tema e arquivos</span></a>
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/regulamento')) ?>"><strong>Regulamento</strong><span><?= $regulation ? App\Core\View::e($regulation['status'] . ' · versão ' . $regulation['version_number']) : 'Ainda não criado' ?></span></a>
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/parceiros')) ?>"><strong>Parceiros</strong><span>Patrocinadores, apoiadores e organizadores</span></a>
    </div>
    <h2>Checklist</h2>
    <ul class="checklist"><li class="done">Informações gerais</li><li class="<?= $championship['logo_path'] || $championship['primary_color'] !== '#123C32' ? 'done' : '' ?>">Identidade basica</li><li class="<?= $regulation && $regulation['status'] === 'published' ? 'done' : '' ?>">Regulamento</li><li class="done">Equipes e elenco oficial</li><li class="done">Tabela, partidas e operação</li></ul>
    <h2>Status</h2>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/status')) ?>" class="inline-form"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><label>Próxima etapa <select name="status"><option value="registration">Abrir inscricoes</option><option value="configured">Marcar configurado</option><option value="in_progress">Iniciar campeonato</option><option value="finished">Finalizar campeonato</option></select></label><button type="submit">Atualizar status</button></form>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/arquivar')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><button type="submit">Arquivar</button></form>
</section>
