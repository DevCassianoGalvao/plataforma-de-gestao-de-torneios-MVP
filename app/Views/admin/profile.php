<section>
    <p class="eyebrow">Conta</p>
    <h1>Meu perfil</h1>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <h2>Dados pessoais</h2>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/perfil')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Nome <input name="name" value="<?= App\Core\View::e($user['name']) ?>" required></label>
        <label>E-mail <input value="<?= App\Core\View::e($user['email']) ?>" disabled></label>
        <button type="submit">Salvar nome</button>
    </form>
    <h2>Alterar senha</h2>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/perfil/senha')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Senha atual <input type="password" name="current_password" autocomplete="current-password" required></label>
        <label>Nova senha <input type="password" name="password" autocomplete="new-password" required></label>
        <label>Confirmar senha <input type="password" name="password_confirmation" autocomplete="new-password" required></label>
        <button type="submit">Alterar senha</button>
    </form>
</section>
