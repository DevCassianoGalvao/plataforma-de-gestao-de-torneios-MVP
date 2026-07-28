<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= App\Core\View::e($title ?? 'Plataforma de Torneios') ?></title>
    <link rel="stylesheet" href="<?= App\Core\View::e(App\Core\Config::url('/assets/app.css')) ?>">
</head>
<body>
<?php $currentUser = App\Core\Auth::user(); ?>
<?php if ($currentUser): ?>
<header class="site-header">
    <a class="brand" href="<?= App\Core\View::e(App\Core\Config::url('/admin')) ?>">Torneios MVP</a>
    <nav aria-label="Navegacao principal">
        <a href="<?= App\Core\View::e(App\Core\Config::url('/admin/perfil')) ?>">Meu perfil</a>
        <?php $menuAuth = new App\Services\AuthorizationService(new App\Repositories\UserRepository(App\Core\Database::connection())); ?>
        <?php if ($menuAuth->can($currentUser, 'users.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios')) ?>">Usuarios</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'audit.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/auditoria')) ?>">Auditoria</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'championships.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos')) ?>">Campeonatos</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'seasons.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/temporadas')) ?>">Temporadas</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'categories.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/categorias')) ?>">Categorias</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'teams.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/equipes')) ?>">Equipes</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'athletes.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas')) ?>">Atletas</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'positions.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/posicoes')) ?>">Posicoes</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'registrations.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes')) ?>">Inscricoes</a><?php endif; ?>
        <?php if ($menuAuth->can($currentUser, 'rosters.view')): ?><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes/elenco')) ?>">Elenco oficial</a><?php endif; ?>
        <form class="logout-form" method="post" action="<?= App\Core\View::e(App\Core\Config::url('/logout')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><button type="submit">Sair</button></form>
    </nav>
</header>
<?php endif; ?>
<main class="page-shell">
    <?= $content ?? '' ?>
</main>
</body>
</html>
