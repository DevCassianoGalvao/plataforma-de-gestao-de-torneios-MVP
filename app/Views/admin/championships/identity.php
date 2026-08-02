<section>
    <div class="section-heading">
        <div>
            <p class="eyebrow">Identidade básica</p><h1><?= App\Core\View::e($championship['name']) ?></h1>
        </div>
        <a class="button secondary" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade/carrossel')) ?>">Gerenciar carrossel</a>
    </div>
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
        <fieldset class="asset-settings"><legend>Arquivos da identidade</legend>
            <p class="muted color-settings-help">Escolha um novo arquivo somente para substituir o que já está em uso. Arquivos existentes permanecem salvos.</p>
            <?php
            $assets = [
                'logo_path' => ['Logo', 'PNG, JPG ou WebP. Recomendado: arquivo quadrado, 512 × 512 px.'],
                'logo_light_path' => ['Logo para fundo claro', 'Versão do logo para fundos claros. Recomendado: 512 × 512 px.'],
                'logo_dark_path' => ['Logo para fundo escuro', 'Versão do logo para fundos escuros. Recomendado: 512 × 512 px.'],
                'banner_path' => ['Banner', 'Recomendado: 1920 × 720 px (8:3). Mantenha textos e rostos na área central; no celular as bordas podem ser cortadas.'],
                'favicon_path' => ['Favicon', 'PNG ou ICO quadrado. Recomendado: 512 × 512 px.'],
                'social_image_path' => ['Imagem social', 'Imagem usada ao compartilhar o portal. Recomendado: 1200 × 630 px.'],
            ];
            ?>
            <div class="asset-upload-grid">
                <?php foreach ($assets as $field => [$label, $hint]): ?>
                    <?php
                    $hasAsset = !empty($championship[$field]);
                    $inputId = 'asset-' . $field;
                    $assetUrl = App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/assets/' . $field);
                    ?>
                    <section class="asset-upload-card <?= $hasAsset ? 'has-current-asset' : '' ?>">
                        <div class="asset-upload-heading"><strong><?= App\Core\View::e($label) ?></strong><small><?= App\Core\View::e($hint) ?></small></div>
                        <div class="asset-file-control">
                            <input class="asset-file-input" id="<?= App\Core\View::e($inputId) ?>" type="file" name="<?= App\Core\View::e($field) ?>" accept="<?= $field === 'favicon_path' ? '.png,.ico' : '.png,.jpg,.jpeg,.webp' ?>" data-file-input aria-label="<?= App\Core\View::e($label) ?>">
                            <label class="asset-file-trigger" for="<?= App\Core\View::e($inputId) ?>">Escolher novo arquivo</label>
                            <output class="asset-file-state" data-file-state data-file-input-id="<?= App\Core\View::e($inputId) ?>" data-empty-label="<?= $hasAsset ? 'Arquivo atual será mantido' : 'Nenhum arquivo selecionado' ?>"><?= $hasAsset ? 'Arquivo atual será mantido' : 'Nenhum arquivo selecionado' ?></output>
                        </div>
                        <?php if ($hasAsset): ?>
                            <div class="asset-current">
                                <?php if ($field !== 'favicon_path'): ?><img class="asset-preview asset-preview--<?= App\Core\View::e(str_replace('_path', '', $field)) ?>" src="<?= App\Core\View::e($assetUrl) ?>" alt="Prévia de <?= App\Core\View::e(strtolower($label)) ?>"><?php endif; ?>
                                <div><strong>Arquivo atual em uso</strong><small>Será mantido até você salvar uma substituição.</small><a href="<?= App\Core\View::e($assetUrl) ?>" target="_blank" rel="noopener">Visualizar arquivo atual</a></div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <button type="submit">Salvar identidade</button>
    </form>
</section>
