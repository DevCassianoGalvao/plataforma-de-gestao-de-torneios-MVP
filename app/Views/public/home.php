<?php use App\Support\View; ?>
<main id="conteudo" class="public-page">
  <header><h1><?= View::e($tournament['name']) ?></h1><p><?= View::e($tournament['description']) ?></p></header>
  <section><h2>Proximos jogos</h2><?php foreach (array_slice($data['matches'], 0, 6) as $match): ?><p><?= View::e($match['home_name']) ?> x <?= View::e($match['away_name']) ?> · <?= View::e((string) $match['scheduled_at']) ?></p><?php endforeach; ?></section>
  <section><h2>Classificacao</h2><?php foreach (array_slice($data['standings'], 0, 4) as $row): ?><p><?= (int) $row['position'] ?>. <?= View::e($row['team_name']) ?> · <?= (int) $row['points'] ?> pts</p><?php endforeach; ?></section>
  <section><h2>Noticias</h2><?php foreach ($data['news'] as $news): ?><article><h3><?= View::e($news['title']) ?></h3><p><?= View::e((string) $news['excerpt']) ?></p></article><?php endforeach; ?></section>
</main>
