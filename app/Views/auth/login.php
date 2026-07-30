<div class="auth-screen">
    <section class="auth-visual" aria-label="Plataforma de gestão de torneios">
        <a class="auth-brand" href="<?= App\Core\View::e(App\Core\Config::url('/')) ?>">
            <img class="auth-brand-logo" src="<?= App\Core\View::e(App\Core\Config::url('/assets/branding/torneio-online-web-app.png')) ?>" alt="Torneio Online Web App">
        </a>
        <div class="auth-visual-copy">
            <p class="eyebrow">Centro de comando esportivo</p>
            <h1>O jogo inteiro, em uma única visao.</h1>
            <p>Organize equipes, partidas, resultados e o portal do campeonato com clareza para decidir no momento certo.</p>
        </div>
        <p class="auth-visual-note">Operação precisa. Competição viva.</p>
    </section>
    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow">Acesso administrativo</p>
            <h1>Entrar</h1>
            <p class="auth-card-intro">Acesse o painel para continuar a operacao do campeonato.</p>
            <?php if (!empty($message)): ?><p class="alert" role="alert"><?= App\Core\View::e($message) ?></p><?php endif; ?>
            <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/login')) ?>">
                <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
                <input type="hidden" name="next" value="<?= App\Core\View::e($next ?? '') ?>">
                <label><span>E-mail</span><input type="email" name="email" value="<?= App\Core\View::e($oldEmail ?? '') ?>" autocomplete="username" placeholder="voce@exemplo.com" required></label>
                <div class="field password-field">
                    <label for="login-password"><span>Senha</span></label>
                    <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Digite sua senha" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="login-password" aria-label="Mostrar senha" title="Mostrar senha">Ver</button>
                </div>
                <button type="submit">Entrar no painel</button>
            </form>
            <div class="auth-meta"><span>Ambiente protegido</span><a href="<?= App\Core\View::e(App\Core\Config::url('/senha/esqueci')) ?>">Esqueci minha senha</a></div>
            <p class="auth-footer">Torneio Online Web App</p>
        </div>
    </section>
</div>
