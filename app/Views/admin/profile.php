<?php $e = static fn (mixed $value): string => App\Core\View::e($value); ?>
<section class="profile-page">
    <div class="section-heading">
        <div><p class="eyebrow">Conta e acesso</p><h1>Meu perfil</h1><p>Atualize a identificação visível no painel e mantenha a sua conta protegida.</p></div>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= $e($error) ?></p><?php endforeach; ?>
    <div class="profile-layout">
        <aside class="profile-card">
            <?php if (!empty($user['avatar_path'])): ?><img class="profile-photo" src="<?= $e(App\Core\Config::url('/admin/perfil/foto?v=' . rawurlencode((string) ($user['updated_at'] ?? '0')))) ?>" alt="Foto de <?= $e($user['name']) ?>"><?php else: ?><span class="profile-photo profile-photo-fallback" aria-hidden="true"><?= $e(strtoupper(substr((string) $user['name'], 0, 1))) ?></span><?php endif; ?>
            <div><strong><?= $e($user['name']) ?></strong><small><?= $e($user['email']) ?></small></div>
            <form method="post" enctype="multipart/form-data" action="<?= $e(App\Core\Config::url('/admin/perfil/foto')) ?>">
                <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
                <label class="file-picker">Foto do perfil <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required><span>PNG, JPG ou WebP, até 2 MB</span></label>
                <button type="submit" class="button secondary">Atualizar foto</button>
            </form>
        </aside>
        <div class="profile-forms">
            <article class="profile-panel">
                <h2>Dados pessoais</h2>
                <form method="post" action="<?= $e(App\Core\Config::url('/admin/perfil')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
                    <label>Nome <input name="name" value="<?= $e($user['name']) ?>" required></label>
                    <label>E-mail <input value="<?= $e($user['email']) ?>" disabled></label>
                    <div class="form-actions"><button type="submit">Salvar dados</button></div>
                </form>
            </article>
            <article class="profile-panel">
                <h2>Alterar senha</h2>
                <form method="post" action="<?= $e(App\Core\Config::url('/admin/perfil/senha')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
                    <label>Senha atual <input type="password" name="current_password" autocomplete="current-password" required></label>
                    <label>Nova senha <input type="password" name="password" autocomplete="new-password" required></label>
                    <label>Confirmar senha <input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                    <div class="form-actions"><button type="submit">Alterar senha</button></div>
                </form>
            </article>
        </div>
    </div>
</section>
