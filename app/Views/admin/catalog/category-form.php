<section>
    <p class="eyebrow">Catalogo</p><h1><?= $editing ? 'Editar categoria' : 'Nova categoria' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url($editing ? '/admin/categorias/' . $record['id'] : '/admin/categorias')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
        <label>Slug <input name="slug" value="<?= App\Core\View::e($record['slug'] ?? '') ?>" placeholder="sub-15-masculino"></label>
        <label>Descricao <textarea name="description" rows="4"><?= App\Core\View::e($record['description'] ?? '') ?></textarea></label>
        <label>Idade minima <input type="number" name="minimum_age" min="0" value="<?= App\Core\View::e($record['minimum_age'] ?? '') ?>"></label>
        <label>Idade maxima <input type="number" name="maximum_age" min="0" value="<?= App\Core\View::e($record['maximum_age'] ?? '') ?>"></label>
        <label>Regra de genero <select name="gender_rule"><option value="">Nao definida</option><option value="male" <?= ($record['gender_rule'] ?? '') === 'male' ? 'selected' : '' ?>>Masculino</option><option value="female" <?= ($record['gender_rule'] ?? '') === 'female' ? 'selected' : '' ?>>Feminino</option><option value="mixed" <?= ($record['gender_rule'] ?? '') === 'mixed' ? 'selected' : '' ?>>Misto</option></select></label>
        <label>Status <select name="status"><option value="active" <?= ($record['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativa</option><option value="inactive" <?= ($record['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativa</option></select></label>
        <button type="submit">Salvar</button>
    </form>
</section>
