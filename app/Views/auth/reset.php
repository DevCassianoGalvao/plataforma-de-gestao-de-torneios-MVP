<section class="auth-card auth-simple-card">
    <p class="eyebrow">Acesso administrativo</p>
    <h1>Criar nova senha</h1>
    <p class="auth-card-intro">Escolha uma senha nova para proteger sua conta.</p>
    <?php if (!empty($message)): ?><p class="alert" role="alert"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/senha/redefinir')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <input type="hidden" name="token" value="<?= App\Core\View::e($token ?? '') ?>">
        <label><span>Nova senha</span><input id="reset-password" type="password" name="password" autocomplete="new-password" required></label>
        <label><span>Confirmar senha</span><input type="password" name="password_confirmation" autocomplete="new-password" required></label>
        <button type="submit">Salvar nova senha</button>
    </form>
</section>
