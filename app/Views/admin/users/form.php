<section>
    <p class="eyebrow">Acesso administrativo</p>
    <h1><?= $mode === 'create' ? 'Novo usuario' : 'Editar usuario' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url($mode === 'create' ? '/admin/usuarios' : '/admin/usuarios/' . ($record['id'] ?? $actionId ?? 0))) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
        <label>E-mail <input type="email" name="email" value="<?= App\Core\View::e($record['email'] ?? '') ?>" required></label>
        <?php if ($mode === 'create'): ?>
            <label>Senha <input type="password" name="password" autocomplete="new-password" required></label>
            <label>Confirmar senha <input type="password" name="password_confirmation" autocomplete="new-password" required></label>
            <label>Status <select name="status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></label>
        <?php endif; ?>
        <fieldset><legend>Perfis</legend><?php foreach ($roles as $role): ?><label class="check"><input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>" <?= in_array((int) $role['id'], $selectedRoles ?? [], true) ? 'checked' : '' ?>> <?= App\Core\View::e($role['name']) ?></label><?php endforeach; ?></fieldset>
        <button type="submit">Salvar</button>
    </form>
    <p><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios')) ?>">Voltar</a></p>
</section>
