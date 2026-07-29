<section>
    <p class="eyebrow">Catalogo</p><h1><?= $editing ? 'Editar temporada' : 'Nova temporada' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url($editing ? '/admin/temporadas/' . $record['id'] : '/admin/temporadas')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
        <label>Ano <input type="number" name="year" min="2000" max="2200" value="<?= (int) ($record['year'] ?? date('Y')) ?>" required></label>
        <label>Inicio <input type="date" name="starts_at" value="<?= App\Core\View::e($record['starts_at'] ?? '') ?>"></label>
        <label>Fim <input type="date" name="ends_at" value="<?= App\Core\View::e($record['ends_at'] ?? '') ?>"></label>
        <label>Status <select name="status"><?php foreach (['draft' => 'Rascunho', 'active' => 'Ativa', 'finished' => 'Finalizada', 'archived' => 'Arquivada'] as $key => $label): ?><option value="<?= $key ?>" <?= ($record['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <button type="submit">Salvar</button>
    </form>
</section>
