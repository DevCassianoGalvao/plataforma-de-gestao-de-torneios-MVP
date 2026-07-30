<section>
    <p class="eyebrow">Identidade basica</p><h1><?= App\Core\View::e($championship['name']) ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <input type="hidden" name="default_theme" value="dark">
        <p class="muted">O portal e o painel usam o tema escuro como identidade unica do produto.</p>
        <label>Cor principal <input type="text" name="primary_color" value="<?= App\Core\View::e($championship['primary_color'] ?? '#123C32') ?>" pattern="#[0-9A-Fa-f]{6}" required></label>
        <label>Cor secundaria <input type="text" name="secondary_color" value="<?= App\Core\View::e($championship['secondary_color'] ?? '#245C4A') ?>" pattern="#[0-9A-Fa-f]{6}" required></label>
        <label>Cor de destaque <input type="text" name="accent_color" value="<?= App\Core\View::e($championship['accent_color'] ?? '#D9A441') ?>" pattern="#[0-9A-Fa-f]{6}" required></label>
        <?php foreach (['logo_path' => 'Logo', 'logo_light_path' => 'Logo para fundo claro', 'logo_dark_path' => 'Logo para fundo escuro', 'banner_path' => 'Banner', 'favicon_path' => 'Favicon', 'social_image_path' => 'Imagem social'] as $field => $label): ?><label><?= $label ?> <input type="file" name="<?= $field ?>" accept="<?= $field === 'favicon_path' ? '.png,.ico' : '.png,.jpg,.jpeg,.webp' ?>"><?php if (!empty($championship[$field])): ?><small>Arquivo atual: <a href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/assets/' . $field)) ?>">visualizar</a></small><?php endif; ?></label><?php endforeach; ?>
        <button type="submit">Salvar identidade</button>
    </form>
</section>
