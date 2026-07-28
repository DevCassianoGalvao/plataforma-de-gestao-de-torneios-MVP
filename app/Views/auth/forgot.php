<section class="auth-card">
    <p class="eyebrow">Acesso</p>
    <h1>Recuperar senha</h1>
    <?php if (!empty($message)): ?><p class="alert" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/senha/esqueci')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>E-mail <input type="email" name="email" autocomplete="email" required></label>
        <button type="submit">Enviar instrucoes</button>
    </form>
    <p><a href="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">Voltar para o login</a></p>
</section>
