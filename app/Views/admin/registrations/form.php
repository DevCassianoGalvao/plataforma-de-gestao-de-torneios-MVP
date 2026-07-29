<section>
    <div class="section-heading"><div><p class="eyebrow">Cadastro</p><h1>Nova inscricao</h1><p>Selecione registros existentes. Nenhum identificador precisa ser digitado.</p></div><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes')) ?>">Voltar</a></div>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/inscricoes')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <label>Campeonato <select name="championship_id" required><option value="">Selecione</option><?php foreach ($championships as $championship): ?><option value="<?= (int) $championship['id'] ?>" <?= (string) ($record['championship_id'] ?? '') === (string) $championship['id'] ? 'selected' : '' ?>><?= App\Core\View::e($championship['name']) ?></option><?php endforeach; ?></select></label>
        <label>Equipe <select name="team_id" required><option value="">Selecione</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= (string) ($record['team_id'] ?? '') === (string) $team['id'] ? 'selected' : '' ?>><?= App\Core\View::e($team['name']) ?> · <?= App\Core\View::e($team['championship_name']) ?></option><?php endforeach; ?></select></label>
        <label>Atleta <select name="athlete_id" required><option value="">Selecione</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= (string) ($record['athlete_id'] ?? '') === (string) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($athlete['sporting_name'] ?: $athlete['full_name']) ?> · <?= App\Core\View::e($athlete['team_name']) ?></option><?php endforeach; ?></select></label>
        <label>Número pretendido <input type="number" name="requested_number" min="1" max="99" value="<?= App\Core\View::e($record['requested_number'] ?? '') ?>"></label>
        <label>Observacoes <textarea name="observations" rows="4"><?= App\Core\View::e($record['observations'] ?? '') ?></textarea></label>
        <button type="submit">Salvar rascunho</button>
    </form>
</section>
