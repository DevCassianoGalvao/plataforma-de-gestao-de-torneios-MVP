<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$base = App\Core\Config::url('/campeonatos/' . $article['championship_slug']);
$content = $e($article['content']);
$content = preg_replace_callback('/\[\[imagem:news\/content\/([a-f0-9]{32}\.(?:jpg|png|webp))\]\]/', static fn (array $match): string => '<figure class="news-inline-image"><img src="' . $e($base . '/noticias/' . rawurlencode((string) $article['slug']) . '/imagens/' . rawurlencode($match[1])) . '" alt="Imagem da notícia"></figure>', $content) ?? $content;
?>
<article class="public-news-detail"><p class="eyebrow"><?= $e($article['championship_name']) ?></p><h1><?= $e($article['title']) ?></h1><p class="news-byline">Publicado em <?= $e(substr((string) $article['published_at'], 0, 10)) ?> por <?= $e($article['author_name']) ?></p><?php if (!empty($article['cover_image_path'])): ?><img class="news-cover" src="<?= $e($base . '/noticias/' . $article['slug'] . '/capa') ?>" alt="Capa: <?= $e($article['title']) ?>"><?php endif; ?><p class="news-summary"><?= $e($article['summary']) ?></p><div class="news-content"><?= nl2br($content) ?></div><p><a href="<?= $e($base . '/noticias') ?>">Voltar para notícias</a></p></article>
