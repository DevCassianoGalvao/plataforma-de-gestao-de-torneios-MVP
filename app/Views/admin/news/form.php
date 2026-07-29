<?php $e = static fn (mixed $value): string => App\Core\View::e($value); $record = $record ?? []; $date = !empty($record['published_at']) ? str_replace(' ', 'T', substr((string) $record['published_at'], 0, 16)) : ''; ?>
<section>
    <p class="eyebrow">Conteúdo editorial</p><h1><?= !empty($editing) ? 'Editar noticia' : 'Nova noticia' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= $e($error) ?></p><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" action="<?= $e(App\Core\Config::url(!empty($editing) ? '/admin/noticias/' . $record['id'] : '/admin/noticias')) ?>">
        <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
        <label>Campeonato <select name="championship_id" required><option value="">Selecione</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($record['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= $e($championship['name']) ?></option><?php endforeach; ?></select></label>
        <label>Titulo <input name="title" value="<?= $e($record['title'] ?? '') ?>" required maxlength="255"></label>
        <label>Slug <input name="slug" value="<?= $e($record['slug'] ?? '') ?>" placeholder="noticia-do-campeonato"></label>
        <label>Resumo <textarea name="summary" rows="3" maxlength="600"><?= $e($record['summary'] ?? '') ?></textarea></label>
        <label>Conteúdo <textarea name="content" rows="12" required><?= $e($record['content'] ?? '') ?></textarea></label>
        <label>Imagem de capa <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"></label>
        <?php if (!empty($record['cover_image_path'])): ?><p><img class="news-cover-thumb" src="<?= $e(App\Core\Config::url('/admin/noticias/' . $record['id'] . '/capa')) ?>" alt="Capa atual"></p><?php endif; ?>
        <label>Status <select name="status"><?php foreach (['draft', 'scheduled', 'published', 'unpublished', 'archived'] as $status): ?><option value="<?= $e($status) ?>" <?= ($record['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= $e($status) ?></option><?php endforeach; ?></select></label>
        <label>Data de publicacao <input type="datetime-local" name="published_at" value="<?= $e($date) ?>"><small>Obrigatoria no agendamento; publicacao sem data usa o horario atual.</small></label>
        <label class="checkbox-line"><input type="checkbox" name="featured" value="1" <?= !empty($record['featured']) ? 'checked' : '' ?>> Destacar nas noticias do campeonato</label>
        <button type="submit">Salvar noticia</button>
    </form>
</section>
