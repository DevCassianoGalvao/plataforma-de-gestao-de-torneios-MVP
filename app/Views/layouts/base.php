<?php
use App\Support\Env;
use App\Support\Session;
use App\Support\View;

$flashSuccess = Session::takeFlash('success');
$flashError = Session::takeFlash('error');
$base = rtrim((string) Env::get('APP_URL', ''), '/');
$defaultTheme = ($theme['default_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$pageTitle = $title ?? 'Plataforma de Gestao de Torneios';
$metaDescription = $description ?? 'Gestao de campeonatos, equipes, partidas e resultados.';
?>
<!doctype html>
<html lang="pt-BR" data-theme="<?= View::e($defaultTheme) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="<?= View::e($metaDescription) ?>">
  <title><?= View::e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/tokens.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/themes.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/layout.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/management.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/public-portal.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/foundation.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/operation.css">
</head>
<body>
  <a class="skip-link" href="#conteudo">Pular para o conteudo</a>
  <div class="shell">
    <?php if ($flashSuccess): ?><div class="toast success" role="status"><?= View::e($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="toast error" role="alert"><?= View::e($flashError) ?></div><?php endif; ?>
    <?= $content ?>
  </div>
  <script type="module" src="<?= $base ?>/assets/js/app.js"></script>
</body>
</html>
