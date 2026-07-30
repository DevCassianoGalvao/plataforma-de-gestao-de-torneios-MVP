<section>
    <p class="eyebrow">Identidade</p><h1><?= App\Core\View::e($team['name']) ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <p><?php if (!empty($team['shield_path'])): ?><img class="team-shield" src="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes/' . $team['slug'] . '/assets/shield_path')) ?>" alt="Escudo de <?= App\Core\View::e($team['name']) ?>"><?php else: ?><span class="team-shield team-mark-fallback" data-icon="shield" aria-hidden="true"></span><?php endif; ?></p>
    <form method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes/' . $team['slug'] . '/identidade')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Cor principal <input type="color" name="primary_color" value="<?= App\Core\View::e($team['primary_color']) ?>"></label>
        <label>Cor secundaria <input type="color" name="secondary_color" value="<?= App\Core\View::e($team['secondary_color']) ?>"></label>
        <label>Escudo <input type="file" name="shield" accept="image/png,image/jpeg,image/webp"></label>
        <button type="submit">Salvar identidade</button>
    </form>
    <p><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes/' . $team['slug'])) ?>">Voltar para equipe</a></p>
</section>
