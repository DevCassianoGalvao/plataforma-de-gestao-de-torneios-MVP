<section>
    <p class="eyebrow">Identidade básica</p><h1><?= App\Core\View::e($championship['name']) ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <input type="hidden" name="default_theme" value="dark">
        <p class="muted">Escolha a paleta visual do campeonato. O portal e o painel usam o tema escuro como identidade única do produto.</p>
        <fieldset class="color-settings"><legend>Paleta do campeonato</legend>
            <p class="muted color-settings-help">Clique em uma amostra para escolher a cor. O código serve apenas como referência.</p>
            <div class="color-picker-grid">
                <?php foreach (['primary_color' => ['Cor principal', 'Usada como base da identidade'], 'secondary_color' => ['Cor secundária', 'Usada em áreas de apoio'], 'accent_color' => ['Cor de destaque', 'Usada para chamadas importantes']] as $field => [$label, $description]): ?>
                    <?php $value = $championship[$field] ?? ($field === 'primary_color' ? '#123C32' : ($field === 'secondary_color' ? '#245C4A' : '#D9A441')); ?>
                    <label class="color-control" data-color-field>
                        <span class="color-control-copy"><strong><?= App\Core\View::e($label) ?></strong><small><?= App\Core\View::e($description) ?></small></span>
                        <span class="color-control-picker"><input class="color-picker" type="color" name="<?= App\Core\View::e($field) ?>" value="<?= App\Core\View::e($value) ?>" aria-label="<?= App\Core\View::e($label) ?>"><output data-color-code><?= App\Core\View::e(strtoupper($value)) ?></output></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php foreach (['logo_path' => 'Logo', 'logo_light_path' => 'Logo para fundo claro', 'logo_dark_path' => 'Logo para fundo escuro', 'banner_path' => 'Banner', 'favicon_path' => 'Favicon', 'social_image_path' => 'Imagem social'] as $field => $label): ?><label><?= $label ?> <input type="file" name="<?= $field ?>" accept="<?= $field === 'favicon_path' ? '.png,.ico' : '.png,.jpg,.jpeg,.webp' ?>"><?php if (!empty($championship[$field])): ?><small>Arquivo atual: <a href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/assets/' . $field)) ?>">visualizar</a></small><?php endif; ?></label><?php endforeach; ?>
        <button type="submit">Salvar identidade</button>
    </form>
</section>
