<section>
    <p class="eyebrow">Equipe</p><h1><?= !empty($editing) ? 'Editar equipe' : 'Nova equipe' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url(!empty($editing) ? '/admin/equipes/' . ($record['slug'] ?? '') : '/admin/equipes')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <fieldset><legend>Informacoes gerais</legend>
            <?php if (empty($editing)): ?><label>Campeonato <select name="championship_id" required><option value="">Selecione</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($record['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= App\Core\View::e($championship['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <label>Nome <input name="name" value="<?= App\Core\View::e($record['name'] ?? '') ?>" required></label>
            <label>Nome curto <input name="short_name" value="<?= App\Core\View::e($record['short_name'] ?? '') ?>" required></label>
            <label>Slug <input name="slug" value="<?= App\Core\View::e($record['slug'] ?? '') ?>" placeholder="nome-da-equipe"></label>
            <label>Sigla <input name="abbreviation" maxlength="8" value="<?= App\Core\View::e($record['abbreviation'] ?? '') ?>" required></label>
            <label>Descricao <textarea name="description" rows="3"><?= App\Core\View::e($record['description'] ?? '') ?></textarea></label>
            <label>Cidade <input name="city" value="<?= App\Core\View::e($record['city'] ?? '') ?>"></label>
            <label>Estado <input name="state" value="<?= App\Core\View::e($record['state'] ?? '') ?>"></label>
        </fieldset>
        <fieldset><legend>Identidade inicial</legend><label>Cor principal <input type="color" name="primary_color" value="<?= App\Core\View::e($record['primary_color'] ?? '#123C32') ?>"></label><label>Cor secundaria <input type="color" name="secondary_color" value="<?= App\Core\View::e($record['secondary_color'] ?? '#D9A441') ?>"></label></fieldset>
        <fieldset><legend>Formacao padrao</legend><label>Esquema <select name="default_tactical_formation_id"><option value="">Definir depois</option><?php foreach ($formations as $formation): ?><option value="<?= (int) $formation['id'] ?>" <?= (string) ($record['default_tactical_formation_id'] ?? '') === (string) $formation['id'] ? 'selected' : '' ?>><?= App\Core\View::e($formation['name']) ?></option><?php endforeach; ?></select></label></fieldset>
        <?php if (!empty($editing)): ?><p class="muted">Status atual: <?= App\Core\View::e($record['status']) ?>. Use a pagina da equipe para transicoes validadas.</p><?php endif; ?>
        <button type="submit">Salvar equipe</button>
    </form>
</section>
