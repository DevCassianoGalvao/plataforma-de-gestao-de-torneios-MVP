<section>
    <p class="eyebrow">Escopo de acesso</p><h1>Organizadores de <?= App\Core\View::e($championship['name']) ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/organizadores')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Adicionar organizador <select name="user_id" required><option value="">Selecione</option><?php foreach ($candidates as $candidate): ?><option value="<?= (int) $candidate['id'] ?>"><?= App\Core\View::e($candidate['name'] . ' — ' . $candidate['email']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Vincular</button>
    </form>
    <h2>Vínculos atuais</h2><div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($assignments as $assignment): ?><tr><td><?= App\Core\View::e($assignment['name']) ?></td><td><?= App\Core\View::e($assignment['email']) ?></td><td><?= App\Core\View::e($assignment['assignment_type']) ?></td><td><form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/organizadores/' . $assignment['user_id'] . '/remover')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><button type="submit">Remover</button></form></td></tr><?php endforeach; ?>
    <?php if ($assignments === []): ?><tr><td colspan="4">Nenhum organizador vinculado.</td></tr><?php endif; ?></tbody></table></div>
</section>
