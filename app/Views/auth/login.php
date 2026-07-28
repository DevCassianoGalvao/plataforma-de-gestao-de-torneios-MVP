<section class="auth-card">
    <p class="eyebrow">Plataforma de torneios</p>
    <h1>Entrar</h1>
    <?php if (!empty($message)): ?><p class="alert" role="alert"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <input type="hidden" name="next" value="<?= App\Core\View::e($next ?? '') ?>">
        <label>E-mail <input type="email" name="email" value="<?= App\Core\View::e($oldEmail ?? '') ?>" autocomplete="username" required></label>
        <label>Senha <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Entrar</button>
    </form>
    <p><a href="<?= App\Core\View::e(App\Core\Config::url('/senha/esqueci')) ?>">Esqueci minha senha</a></p>
</section>
