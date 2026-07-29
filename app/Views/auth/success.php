<section class="auth-card auth-simple-card">
    <p class="eyebrow">Concluido</p>
    <h1>Senha atualizada</h1>
    <p><?= App\Core\View::e($message ?? '') ?></p>
    <p class="auth-meta"><a href="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">Entrar</a></p>
</section>
