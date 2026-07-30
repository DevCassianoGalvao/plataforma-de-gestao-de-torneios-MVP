<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$slug = (string) ($championship['slug'] ?? '');
$base = App\Core\Config::url('/campeonatos/' . $slug);
$hasKnockout = $hasKnockout ?? false;
$assetUrl = static function (string $asset): string {
    $file = dirname(__DIR__, 3) . '/public/assets/' . $asset;
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return App\Core\Config::url('/assets/' . $asset) . '?v=' . rawurlencode($version);
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e(($seo['title'] ?? $title) . ' | Torneio Online Web App') ?></title>
    <meta name="description" content="<?= $e($seo['description'] ?? '') ?>">
    <link rel="canonical" href="<?= $e($seo['canonical'] ?? '') ?>">
    <link rel="icon" type="image/png" href="<?= $e($assetUrl('branding/favicon.png')) ?>">
    <?php if (!empty($seo['favicon'])): ?><link rel="icon" href="<?= $e($seo['favicon']) ?>"><?php endif; ?>
    <?php if (!empty($seo['image'])): ?><meta property="og:image" content="<?= $e($seo['image']) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
    <meta property="og:title" content="<?= $e($seo['title'] ?? $title) ?>">
    <meta property="og:description" content="<?= $e($seo['description'] ?? '') ?>">
    <link rel="stylesheet" href="<?= $e($assetUrl('app.css')) ?>">
</head>
<body class="public-portal" data-portal-primary="<?= $e($championship['primary_color'] ?? '') ?>" data-portal-secondary="<?= $e($championship['secondary_color'] ?? '') ?>" data-portal-accent="<?= $e($championship['accent_color'] ?? '') ?>">
    <header class="portal-header">
        <div class="portal-header-inner">
            <a class="portal-brand portal-brand--logo-only" href="<?= $e($base) ?>" aria-label="<?= $e($championship['name']) ?>">
                <?php if (!empty($championship['logo_path'])): ?><img src="<?= $e($base . '/assets/logo') ?>" alt="Logo de <?= $e($championship['name']) ?>"><?php else: ?><span class="portal-mark-fallback" data-icon="trophy" aria-hidden="true"></span><?php endif; ?>
            </a>
            <div class="portal-nav-tools"><button class="icon-button portal-nav-toggle" type="button" data-portal-nav-toggle aria-controls="portal-navigation" aria-label="Abrir navegação" aria-expanded="false" title="Abrir navegação">Menu</button></div>
            <nav id="portal-navigation" class="portal-nav" data-portal-nav aria-label="Navegação do campeonato">
                <a href="<?= $e($base) ?>">Início</a><a href="<?= $e($base . '/proximos-jogos') ?>">Jogos</a><a href="<?= $e($base . '/resultados') ?>">Resultados</a><a href="<?= $e($base . '/classificacao') ?>">Classificação</a><a href="<?= $e($base . '/equipes') ?>">Equipes</a><a href="<?= $e($base . '/noticias') ?>">Notícias</a><a href="<?= $e($base . '/vai-e-vem') ?>">Vai e Vem</a><?php if (!empty($hasKnockout)): ?><a href="<?= $e($base . '/mata-mata') ?>">Mata-mata</a><?php endif; ?><a href="<?= $e($base . '/arbitragem') ?>">Arbitragem</a><a href="<?= $e($base . '/contato') ?>">Contato</a>
            </nav>
        </div>
        <button class="portal-navigation-scrim" type="button" data-portal-nav-dismiss aria-label="Fechar navegação"></button>
    </header>
    <main class="portal-shell"><?= $content ?? '' ?></main>
    <?php if (!empty($sponsors ?? [])): ?>
        <section class="portal-partners" aria-label="Parceiros do campeonato">
            <?php $partnerLabels = ['sponsor' => 'Patrocinadores', 'supporter' => 'Apoiadores', 'organizer' => 'Organização']; foreach ($partnerLabels as $type => $label): $items = array_values(array_filter($sponsors, static fn (array $partner): bool => ($partner['partner_type'] ?? 'sponsor') === $type)); if (!$items) continue; ?>
                <div><p class="eyebrow"><?= $e($label) ?></p><div class="partner-logos"><?php foreach ($items as $partner): ?><?php if ($partner['website_url']): ?><a href="<?= $e($partner['website_url']) ?>" target="_blank" rel="noopener noreferrer"><?php else: ?><span><?php endif; ?><?php if ($partner['logo_path']): ?><img src="<?= $e($base . '/parceiros/' . $partner['id'] . '/logo') ?>" alt="<?= $e($partner['name']) ?>"><?php else: ?><b><?= $e($partner['name']) ?></b><?php endif; ?><?php if ($partner['website_url']): ?></a><?php else: ?></span><?php endif; ?><?php endforeach; ?></div></div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <footer class="portal-footer"><div><strong><?= $e($championship['name']) ?></strong><span><?= $e($championship['category_name']) ?> | <?= $e($championship['season_name']) ?></span></div><nav aria-label="Informações do campeonato"><a href="<?= $e($base . '/atletas') ?>">Atletas</a><a href="<?= $e($base . '/artilharia') ?>">Artilharia</a><a href="<?= $e($base . '/regulamento') ?>">Regulamento</a></nav><span class="portal-product-brand"><img src="<?= $e($assetUrl('branding/torneio-online-web-app.png')) ?>" alt="Torneio Online Web App"><small>Desenvolvido por Torneio Online Web App</small></span></footer>
    <script src="<?= $e($assetUrl('app.js')) ?>" defer></script>
</body>
</html>
