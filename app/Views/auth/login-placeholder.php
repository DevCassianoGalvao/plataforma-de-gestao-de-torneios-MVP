<section>
    <p class="eyebrow">Etapa futura</p>
    <h1>Login</h1>
    <?php if (!empty($message)): ?><p role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>E-mail <input type="email" name="email" autocomplete="username"></label>
        <label>Senha <input type="password" name="password" autocomplete="current-password"></label>
        <button type="submit">Continuar</button>
    </form>
    <p>Autenticacao completa ainda nao esta disponivel.</p>
</section>
