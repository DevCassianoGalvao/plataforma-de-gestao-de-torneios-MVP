<?php
declare(strict_types=1);
$e = static fn (mixed $value): string => App\Core\View::e($value);
$currentUser = App\Core\Auth::user();
$menuAuth = $currentUser ? new App\Services\AuthorizationService(new App\Repositories\UserRepository(App\Core\Database::connection())) : null;
$userInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials ?: 'TM';
};
$currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = static fn (string $path): bool => $path === '/' ? $currentPath === App\Core\Config::url('/') : str_starts_with($currentPath, App\Core\Config::url($path));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($title ?? 'Plataforma de Torneios') ?></title>
    <link rel="stylesheet" href="<?= $e(App\Core\Config::url('/assets/app.css')) ?>">
</head>
<body class="<?= $currentUser ? 'app-body' : 'auth-body' ?>" data-theme="dark">
<?php if ($currentUser): ?>
<div class="app-shell">
    <aside class="app-sidebar" data-sidebar aria-label="Navegação administrativa">
        <a class="app-brand" href="<?= $e(App\Core\Config::url('/admin')) ?>">
            <span class="brand-mark" aria-hidden="true">TM</span>
            <span class="brand-copy">TORNEIOS<small>MVP / OPERAÇÃO</small></span>
        </a>
        <div class="sidebar-context">
            <small>Espaço ativo</small>
            <strong>Operação de campeonatos</strong>
        </div>
        <nav class="sidebar-nav" aria-label="Módulos">
            <span class="sidebar-section-label">Visão geral</span>
            <a href="<?= $e(App\Core\Config::url('/admin')) ?>"<?= $isActive('/admin') && !$isActive('/admin/usuarios') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="overview" aria-hidden="true">OV</span><span class="nav-label">Visão geral</span></a>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'championships.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/campeonatos')) ?>"<?= $isActive('/admin/campeonatos') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="championship" aria-hidden="true">CP</span><span class="nav-label">Campeonatos</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'schedule.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"<?= $isActive('/admin/tabela') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="schedule" aria-hidden="true">TB</span><span class="nav-label">Tabela e partidas</span></a><?php endif; ?>
            <span class="sidebar-section-label">Competição</span>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'teams.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/equipes')) ?>"<?= $isActive('/admin/equipes') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="team" aria-hidden="true">EQ</span><span class="nav-label">Equipes</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'athletes.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/atletas')) ?>"<?= $isActive('/admin/atletas') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="athlete" aria-hidden="true">AT</span><span class="nav-label">Atletas</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'registrations.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/inscricoes')) ?>"<?= $isActive('/admin/inscricoes') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="registration" aria-hidden="true">IN</span><span class="nav-label">Inscrições</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'rosters.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/inscricoes/elenco')) ?>"><span class="nav-icon" data-icon="roster" aria-hidden="true">EL</span><span class="nav-label">Elenco oficial</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'transfers.manage')): ?><a href="<?= $e(App\Core\Config::url('/admin/vai-e-vem')) ?>"<?= $isActive('/admin/vai-e-vem') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="transfer" aria-hidden="true">VV</span><span class="nav-label">Vai e Vem</span></a><?php endif; ?>
            <span class="sidebar-section-label">Conteúdo e acesso</span>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'content.manage')): ?><a href="<?= $e(App\Core\Config::url('/admin/noticias')) ?>"<?= $isActive('/admin/noticias') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="news" aria-hidden="true">NT</span><span class="nav-label">Notícias</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'users.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/usuarios')) ?>"<?= $isActive('/admin/usuarios') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="user" aria-hidden="true">US</span><span class="nav-label">Usuários</span></a><?php endif; ?>
            <?php if ($menuAuth && $menuAuth->can($currentUser, 'audit.view')): ?><a href="<?= $e(App\Core\Config::url('/admin/auditoria')) ?>"<?= $isActive('/admin/auditoria') ? ' aria-current="page"' : '' ?>><span class="nav-icon" data-icon="audit" aria-hidden="true">AU</span><span class="nav-label">Auditoria</span></a><?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <a class="sidebar-nav" href="<?= $e(App\Core\Config::url('/admin/perfil')) ?>"><span class="nav-icon" data-icon="profile" aria-hidden="true">PF</span><span class="nav-label">Meu perfil</span></a>
        </div>
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <small>Plataforma de gestão</small>
                <strong><?= $e($title ?? 'Centro de operação') ?></strong>
            </div>
            <div class="topbar-actions">
                <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu" aria-expanded="false" title="Abrir menu">☰</button>
                <button class="icon-button" type="button" data-theme-toggle aria-label="Ativar tema claro" title="Ativar tema claro">◐</button>
                <div class="topbar-user"><span class="user-avatar" aria-hidden="true"><?= $e($userInitials((string) ($currentUser['name'] ?? 'TM'))) ?></span><strong><?= $e($currentUser['name'] ?? '') ?></strong><span><?= $e($currentUser['role_name'] ?? 'Acesso autorizado') ?></span></div>
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
<script src="<?= $e(App\Core\Config::url('/assets/app.js')) ?>" defer></script>
</body>
</html>
