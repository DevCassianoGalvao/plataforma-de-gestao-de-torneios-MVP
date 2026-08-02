<?php
declare(strict_types=1);
$e = static fn (mixed $value): string => App\Core\View::e($value);
$currentUser = App\Core\Auth::user();
$menuAuth = $currentUser ? new App\Services\AuthorizationService(new App\Repositories\UserRepository(App\Core\Database::connection())) : null;
$isAdministrator = $currentUser && $menuAuth && in_array('administrator', $menuAuth->roleKeys($currentUser), true);
$isAccountabilityOnly = $currentUser && $menuAuth && !$isAdministrator && in_array('accountability', $menuAuth->roleKeys($currentUser), true);
$notificationCount = 0;
if ($isAdministrator) {
    try { $notificationCount = (new App\Repositories\NotificationRepository(App\Core\Database::connection()))->unreadCount((int) $currentUser['id']); } catch (\Throwable) { $notificationCount = 0; }
}
$userInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials ?: 'TM';
};
$assetUrl = static function (string $asset): string {
    $file = dirname(__DIR__, 3) . '/public/assets/' . $asset;
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return App\Core\Config::url('/assets/' . $asset) . '?v=' . rawurlencode($version);
};
$currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = static fn (string $path): bool => $path === '/' ? $currentPath === App\Core\Config::url('/') : str_starts_with($currentPath, App\Core\Config::url($path));
$isExactActive = static fn (string $path): bool => $currentPath === App\Core\Config::url($path);
$isRegistrationsActive = $isActive('/admin/inscricoes');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e(!empty($title) ? $title . ' | Torneio Online Web App' : 'Torneio Online Web App') ?></title>
    <link rel="icon" type="image/png" href="<?= $e($assetUrl('branding/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= $e($assetUrl('app.css')) ?>">
</head>
<body class="<?= $currentUser ? 'app-body' . ($isAccountabilityOnly ? ' accountability-only' : '') : 'auth-body' ?>" data-theme="dark">
<?php if ($currentUser): ?>
<div class="app-shell">
    <aside id="app-sidebar" class="app-sidebar" data-sidebar aria-label="Navegação administrativa">
        <button class="sidebar-close" type="button" data-sidebar-dismiss aria-label="Fechar menu" title="Fechar menu">Fechar</button>
        <a class="app-brand" href="<?= $e(App\Core\Config::url('/admin')) ?>">
            <img class="app-brand-logo" src="<?= $e($assetUrl('branding/torneio-online-web-app.png')) ?>" alt="Torneio Online Web App">
        </a>
        <div class="sidebar-context">
            <small>Espaço ativo</small>
            <strong>Operação de campeonatos</strong>
        </div>
        <nav class="sidebar-nav" aria-label="Módulos">
            <span class="sidebar-section-label">Visão geral</span>
            <a href="<?= $e(App\Core\Config::url('/admin')) ?>"<?= $isExactActive('/admin') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="overview" aria-hidden="true">OV</span><span class="nav-label">Visão geral</span></a>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'championships.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/campeonatos')) ?>"<?= $isActive('/admin/campeonatos') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="championship" aria-hidden="true">CP</span><span class="nav-label">Campeonatos</span></a><?php endif; ?>
            <span class="sidebar-section-label">Operação</span>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'schedule.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"<?= $isActive('/admin/tabela') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="schedule" aria-hidden="true">TB</span><span class="nav-label">Tabela e partidas</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'matches.operate')): ?><a href="<?= $e(App\Core\Config::url('/minhas-partidas')) ?>"<?= $isActive('/minhas-partidas') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="clipboard-check" aria-hidden="true">OP</span><span class="nav-label">Partidas para operar</span></a><?php endif; ?>
            <span class="sidebar-section-label">Cadastro esportivo</span>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'teams.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/equipes')) ?>"<?= $isActive('/admin/equipes') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="team" aria-hidden="true">EQ</span><span class="nav-label">Equipes</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'athletes.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/atletas')) ?>"<?= $isActive('/admin/atletas') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="athlete" aria-hidden="true">AT</span><span class="nav-label">Atletas</span></a><?php endif; ?>
            <?php if ($menuAuth && ($menuAuth->can($currentUser, 'registrations.view') || $menuAuth->can($currentUser, 'rosters.view'))): ?><a href="<?= $e(App\Core\Config::url('/admin/inscricoes')) ?>"<?= $isRegistrationsActive ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="registration" aria-hidden="true">IN</span><span class="nav-label">Inscrições e elenco</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'transfers.manage')): ?><a href="<?= $e(App\Core\Config::url('/admin/vai-e-vem')) ?>"<?= $isActive('/admin/vai-e-vem') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="transfer" aria-hidden="true">VV</span><span class="nav-label">Vai e Vem</span></a><?php endif; ?>
            <span class="sidebar-section-label">Conteúdo e acesso</span>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'content.manage')): ?><a href="<?= $e(App\Core\Config::url('/admin/noticias')) ?>"<?= $isActive('/admin/noticias') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="news" aria-hidden="true">NT</span><span class="nav-label">Notícias</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'championships.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/arbitros')) ?>"<?= $isActive('/admin/arbitros') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="whistle" aria-hidden="true">AR</span><span class="nav-label">Arbitragem</span></a><?php endif; ?>
            <?php if ($isAdministrator): ?><a href="<?= $e(App\Core\Config::url('/admin/contatos')) ?>"<?= $isActive('/admin/contatos') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="mail" aria-hidden="true">CT</span><span class="nav-label">Contatos</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'users.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/usuarios')) ?>"<?= $isActive('/admin/usuarios') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="user" aria-hidden="true">US</span><span class="nav-label">Usuários</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'audit.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/auditoria')) ?>"<?= $isActive('/admin/auditoria') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="audit" aria-hidden="true">AU</span><span class="nav-label">Logs</span></a><?php endif; ?>
            <?php if ($isAdministrator): ?><a href="<?= $e(App\Core\Config::url('/admin/notificacoes')) ?>"<?= $isActive('/admin/notificacoes') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="bell" aria-hidden="true">NO</span><span class="nav-label">Notificacoes<?php if ($notificationCount > 0): ?> <b class="nav-count"><?= (int) $notificationCount ?></b><?php endif; ?></span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'accountability.view')): ?><a href="<?= $e(App\Core\Config::url('/prestacao')) ?>"<?= $isActive('/prestacao') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="file-check-2" aria-hidden="true">PC</span><span class="nav-label">Prestacao de contas</span></a><?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <?php if (!empty($currentUser['avatar_path'])): ?><img class="user-avatar user-avatar-photo" src="<?= $e(App\Core\Config::url('/admin/perfil/foto?v=' . rawurlencode((string) ($currentUser['updated_at'] ?? '0')))) ?>" alt="">
                <?php else: ?><span class="user-avatar" aria-hidden="true"><?= $e($userInitials((string) ($currentUser['name'] ?? 'TM'))) ?></span><?php endif; ?>
                <div><strong><?= $e($currentUser['name'] ?? '') ?></strong><span><?= $e($currentUser['role_name'] ?? 'Acesso autorizado') ?></span></div>
            </div>
            <a class="sidebar-nav" href="<?= $e(App\Core\Config::url('/admin/perfil')) ?>"<?= $isExactActive('/admin/perfil') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="profile" aria-hidden="true">PF</span><span class="nav-label">Meu perfil</span></a>
            <form class="logout-form sidebar-logout" method="post" action="<?= $e(App\Core\Config::url('/logout')) ?>"><input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><button type="submit">Sair</button></form>
        </div>
    </aside>
    <button class="app-navigation-scrim" type="button" data-sidebar-dismiss aria-label="Fechar menu"></button>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <button class="topbar-back" type="button" data-history-back aria-label="Voltar para a página anterior">← Voltar</button>
                <small>Plataforma de gestão</small>
                <strong><?= $e($title ?? 'Centro de operação') ?></strong>
            </div>
            <div class="topbar-actions">
                <?php if ($isAdministrator): ?><a class="icon-button notification-button" href="<?= $e(App\Core\Config::url('/admin/notificacoes')) ?>" aria-label="Abrir notificacoes" title="Notificacoes"><span data-icon="bell" aria-hidden="true">NO</span><?php if ($notificationCount > 0): ?><b class="notification-count"><?= (int) $notificationCount ?></b><?php endif; ?></a><?php endif; ?>
                <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-label="Abrir menu" aria-expanded="false" title="Abrir menu">☰</button>
                <div class="topbar-user"><?php if (!empty($currentUser['avatar_path'])): ?><img class="user-avatar user-avatar-photo" src="<?= $e(App\Core\Config::url('/admin/perfil/foto?v=' . rawurlencode((string) ($currentUser['updated_at'] ?? '0')))) ?>" alt=""><?php else: ?><span class="user-avatar" aria-hidden="true"><?= $e($userInitials((string) ($currentUser['name'] ?? 'TM'))) ?></span><?php endif; ?><strong><?= $e($currentUser['name'] ?? '') ?></strong><span><?= $e($currentUser['role_name'] ?? 'Acesso autorizado') ?></span></div>
                <form class="logout-form" method="post" action="<?= $e(App\Core\Config::url('/logout')) ?>"><input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><button type="submit" aria-label="Sair" title="Sair">Sair</button></form>
            </div>
        </header>
        <main class="page-shell">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<?php else: ?>
<main class="page-shell home-canvas">
    <?= $content ?? '' ?>
</main>
<?php endif; ?>
<script src="<?= $e($assetUrl('app.js')) ?>" defer></script>
</body>
</html>
