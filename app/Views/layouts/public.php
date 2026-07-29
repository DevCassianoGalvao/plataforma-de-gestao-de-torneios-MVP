<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$slug = (string) ($championship['slug'] ?? '');
$base = App\Core\Config::url('/campeonatos/' . $slug);
$portalInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials ?: 'TM';
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($seo['title'] ?? $title) ?></title>
    <meta name="description" content="<?= $e($seo['description'] ?? '') ?>">
    <link rel="canonical" href="<?= $e($seo['canonical'] ?? '') ?>">
    <?php if (!empty($seo['favicon'])): ?><link rel="icon" href="<?= $e($seo['favicon']) ?>"><?php endif; ?>
    <?php if (!empty($seo['image'])): ?><meta property="og:image" content="<?= $e($seo['image']) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
    <meta property="og:title" content="<?= $e($seo['title'] ?? $title) ?>">
    <meta property="og:description" content="<?= $e($seo['description'] ?? '') ?>">
    <link rel="stylesheet" href="<?= $e(App\Core\Config::url('/assets/app.css')) ?>">
</head>
<body class="public-portal" data-portal-primary="<?= $e($championship['primary_color'] ?? '') ?>" data-portal-secondary="<?= $e($championship['secondary_color'] ?? '') ?>" data-portal-accent="<?= $e($championship['accent_color'] ?? '') ?>">
    <header class="portal-header">
        <div class="portal-header-inner">
            <a class="portal-brand" href="<?= $e($base) ?>">
                <?php if (!empty($championship['logo_path'])): ?><img src="<?= $e($base . '/assets/logo') ?>" alt="Logo de <?= $e($championship['name']) ?>"><?php else: ?><span class="portal-mark-fallback" aria-hidden="true"><?= $e($portalInitials((string) ($championship['name'] ?? 'Torneios'))) ?></span><?php endif; ?>
                <span><?= $e($championship['name']) ?></span>
            </a>
            <div class="portal-nav-tools">
                <button class="icon-button" type="button" data-theme-toggle aria-label="Ativar tema escuro" title="Ativar tema escuro">◐</button>
                <button class="icon-button portal-nav-toggle" type="button" data-portal-nav-toggle aria-label="Abrir navegação" aria-expanded="false" title="Abrir navegação">☰</button>
            </div>
            <nav class="portal-nav" data-portal-nav aria-label="Navegação do campeonato">
                <a href="<?= $e($base) ?>">Início</a>
                <a href="<?= $e($base . '/proximos-jogos') ?>">Jogos</a>
                <a href="<?= $e($base . '/resultados') ?>">Resultados</a>
                <a href="<?= $e($base . '/classificacao') ?>">Classificação</a>
                <a href="<?= $e($base . '/equipes') ?>">Equipes</a>
                <a href="<?= $e($base . '/noticias') ?>">Notícias</a>
                <a href="<?= $e($base . '/vai-e-vem') ?>">Vai e Vem</a>
                <a href="<?= $e($base . '/grupos') ?>">Grupos</a>
                <a href="<?= $e($base . '/mata-mata') ?>">Mata-mata</a>
            </nav>
        </div>
    </header>
    <main class="portal-shell"><?= $content ?? '' ?></main>
    <footer class="portal-footer">
        <div><strong><?= $e($championship['name']) ?></strong><span><?= $e($championship['category_name']) ?> | <?= $e($championship['season_name']) ?></span></div>
        <nav aria-label="Informações do campeonato"><a href="<?= $e($base . '/atletas') ?>">Atletas</a><a href="<?= $e($base . '/artilharia') ?>">Artilharia</a><a href="<?= $e($base . '/regulamento') ?>">Regulamento</a><a href="<?= $e($base . '/campeao') ?>">Campeão e vice</a></nav>
    </footer>
    <script src="<?= $e(App\Core\Config::url('/assets/app.js')) ?>" defer></script>
</body>
</html>
