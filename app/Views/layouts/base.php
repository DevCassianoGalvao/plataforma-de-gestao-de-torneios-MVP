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
<main class="page-shell">
    <?= $content ?? '' ?>
</main>
</body>
</html>
