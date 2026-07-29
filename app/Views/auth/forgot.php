<section class="auth-card auth-simple-card">
    <p class="eyebrow">Acesso administrativo</p>
    <h1>Recuperar senha</h1>
    <p class="auth-card-intro">Informe seu e-mail para receber as instrucoes de acesso.</p>
    <?php if (!empty($message)): ?><p class="alert" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/senha/esqueci')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label><span>E-mail</span><input type="email" name="email" autocomplete="email" placeholder="voce@exemplo.com" required></label>
        <button type="submit">Enviar instrucoes</button>
    </form>
    <p class="auth-meta"><a href="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">Voltar para o login</a></p>
</section>
