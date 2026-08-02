<section class="carousel-manager carousel-manager--page">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Página inicial</p>
            <h1>Carrossel de destaques</h1>
            <p class="muted">Cadastre imagens, títulos e links opcionais para os destaques do portal público.</p>
        </div>
        <a class="button secondary" href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade')) ?>">Voltar à identidade</a>
    </div>

    <?php if (!empty($carouselSlides)): ?>
        <div class="carousel-manager-list">
            <?php foreach ($carouselSlides as $slide): ?>
                <article>
                    <img src="<?= App\Core\View::e(App\Core\Config::url('/campeonatos/' . $championship['slug'] . '/carrossel/' . $slide['id'] . '/imagem')) ?>" alt="">
                    <div>
                        <strong><?= App\Core\View::e($slide['title']) ?></strong>
                        <small><?= App\Core\View::e($slide['link_url'] ?: 'Sem link de destino') ?></small>
                    </div>
                    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade/carrossel/' . $slide['id'] . '/excluir')) ?>">
                        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
                        <button type="submit" class="button secondary">Remover</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="carousel-manager-form" method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade/carrossel')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Título <input name="title" maxlength="180" required placeholder="Ex.: Artilheiro da rodada"></label>
        <label>Link de destino <input name="link_url" maxlength="500" placeholder="https://... ou /campeonatos/.../atletas/1"></label>
        <label>Posição <input name="display_order" type="number" min="0" value="0"></label>
        <label>Imagem <input name="image" type="file" accept=".png,.jpg,.jpeg,.webp" required></label>
        <button type="submit">Adicionar destaque</button>
    </form>
</section>
