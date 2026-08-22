<section>
    <p class="eyebrow">Campeonato</p>
    <h1><?= !empty($editing) ? 'Editar informações gerais' : 'Novo campeonato' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url(!empty($editing) ? '/admin/campeonatos/' . ($record['id'] ?? $record['slug']) : '/admin/campeonatos')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
        <label>Nome curto <input name="short_name" value="<?= App\Core\View::e($record['short_name'] ?? '') ?>" required></label>
        <label>Slug <input name="slug" value="<?= App\Core\View::e($record['slug'] ?? '') ?>" placeholder="copa-brasil-de-talentos-2026"></label>
        <label>Descrição <textarea name="description" rows="4"><?= App\Core\View::e($record['description'] ?? '') ?></textarea></label>
        <label>Temporada <select name="season_id" required><option value="">Selecione</option><?php foreach ($seasons as $season): ?><option value="<?= (int) $season['id'] ?>" <?= (string) ($record['season_id'] ?? '') === (string) $season['id'] ? 'selected' : '' ?>><?= App\Core\View::e($season['name']) ?></option><?php endforeach; ?></select></label>
        <label>Categoria <select name="category_id" id="championship-category" required><option value="">Selecione</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" data-min-age="<?= App\Core\View::e((string) ($category['minimum_age'] ?? '')) ?>" data-max-age="<?= App\Core\View::e((string) ($category['maximum_age'] ?? '')) ?>" <?= (string) ($record['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= App\Core\View::e($category['name']) ?></option><?php endforeach; ?></select></label>
        <fieldset>
            <legend>Regras de cadastro</legend>
            <input type="hidden" id="championship-requires-guardian" name="requires_guardian" value="<?= !empty($record['allow_underage_athletes']) ? '1' : '0' ?>">
            <label class="check"><input type="checkbox" id="championship-allow-underage" name="allow_underage_athletes" value="1" <?= !empty($record['allow_underage_athletes']) ? 'checked' : '' ?>> Este campeonato aceita atletas menores de 18 anos</label>
            <p class="muted">Ative para categorias de base ou campeonatos adultos cujo regulamento permita menores. Quando ativo, o sistema solicita responsável legal e autorização somente para atletas menores.</p>
            <p class="alert success" id="minor-category-warning" hidden>Menores serão aceitos neste campeonato. Atletas menores precisarão de responsável legal e autorização.</p>
        </fieldset>
        <label>Início <input type="date" name="starts_at" value="<?= App\Core\View::e($record['starts_at'] ?? '') ?>"></label>
        <label>Fim <input type="date" name="ends_at" value="<?= App\Core\View::e($record['ends_at'] ?? '') ?>"></label>
        <label>Início das inscrições <input type="date" name="registration_starts_at" value="<?= App\Core\View::e($record['registration_starts_at'] ?? '') ?>"></label>
        <label>Fim das inscrições <input type="date" name="registration_ends_at" value="<?= App\Core\View::e($record['registration_ends_at'] ?? '') ?>"></label>
        <label>Visibilidade <select name="visibility"><option value="private" <?= ($record['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Privado</option><option value="public" <?= ($record['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Público</option></select></label>
        <button type="submit">Salvar informações</button>
    </form>
</section>
<script>
(() => {
    const category = document.getElementById('championship-category');
    const allowUnderage = document.getElementById('championship-allow-underage');
    const guardian = document.getElementById('championship-requires-guardian');
    const warning = document.getElementById('minor-category-warning');

    if (!category || !allowUnderage || !guardian || !warning) {
        return;
    }

    const updateWarning = () => {
        guardian.value = allowUnderage.checked ? '1' : '0';
        warning.hidden = !allowUnderage.checked;
    };

    category.addEventListener('change', updateWarning);
    allowUnderage.addEventListener('change', updateWarning);
    updateWarning();
})();
</script>
