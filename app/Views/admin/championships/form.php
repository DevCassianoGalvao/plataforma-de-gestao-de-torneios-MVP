<section>
    <p class="eyebrow">Campeonato</p><h1><?= !empty($editing) ? 'Editar informacoes gerais' : 'Novo campeonato' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url(!empty($editing) ? '/admin/campeonatos/' . ($record['slug'] ?? $record['id']) : '/admin/campeonatos')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
        <label>Nome curto <input name="short_name" value="<?= App\Core\View::e($record['short_name'] ?? '') ?>" required></label>
        <label>Slug <input name="slug" value="<?= App\Core\View::e($record['slug'] ?? '') ?>" placeholder="copa-brasil-de-talentos-2026"></label>
        <label>Descricao <textarea name="description" rows="4"><?= App\Core\View::e($record['description'] ?? '') ?></textarea></label>
        <label>Temporada <select name="season_id" required><option value="">Selecione</option><?php foreach ($seasons as $season): ?><option value="<?= (int) $season['id'] ?>" <?= (string) ($record['season_id'] ?? '') === (string) $season['id'] ? 'selected' : '' ?>><?= App\Core\View::e($season['name']) ?></option><?php endforeach; ?></select></label>
        <label>Categoria <select name="category_id" required><option value="">Selecione</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($record['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= App\Core\View::e($category['name']) ?></option><?php endforeach; ?></select></label>
        <label>Início <input type="date" name="starts_at" value="<?= App\Core\View::e($record['starts_at'] ?? '') ?>"></label>
        <label>Fim <input type="date" name="ends_at" value="<?= App\Core\View::e($record['ends_at'] ?? '') ?>"></label>
        <label>Início das inscricoes <input type="date" name="registration_starts_at" value="<?= App\Core\View::e($record['registration_starts_at'] ?? '') ?>"></label>
        <label>Fim das inscricoes <input type="date" name="registration_ends_at" value="<?= App\Core\View::e($record['registration_ends_at'] ?? '') ?>"></label>
        <label>Visibilidade <select name="visibility"><option value="private" <?= ($record['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Privado</option><option value="public" <?= ($record['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Publico</option></select></label>
        <button type="submit">Salvar informações</button>
    </form>
</section>
