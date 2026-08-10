<section>
    <p class="eyebrow">Atleta</p>
    <h1><?= !empty($editing) ? 'Editar atleta' : 'Novo atleta' ?></h1>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" action="<?= App\Core\View::e(App\Core\Config::url(!empty($editing) ? '/admin/atletas/' . $record['id'] : '/admin/atletas')) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <fieldset>
            <legend>Dados do atleta</legend>
            <?php if (empty($editing)): ?>
                <label>Equipe <select name="team_id" id="athlete-team" required><option value="">Selecione</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" data-requires-guardian="<?= !empty($team['requires_guardian']) ? '1' : '0' ?>" <?= (string) ($record['team_id'] ?? '') === (string) $team['id'] ? 'selected' : '' ?>><?= App\Core\View::e($team['name']) ?> &middot; <?= App\Core\View::e($team['championship_name']) ?></option><?php endforeach; ?></select></label>
            <?php else: ?>
                <p class="muted">Equipe atual: <?= App\Core\View::e($record['team_name']) ?>.</p>
            <?php endif; ?>
            <label>Nome completo <input name="full_name" value="<?= App\Core\View::e($record['full_name'] ?? '') ?>" required></label>
            <label>Nome esportivo <input name="sporting_name" value="<?= App\Core\View::e($record['sporting_name'] ?? '') ?>"></label>
            <label>Data de nascimento <input type="date" name="birth_date" value="<?= App\Core\View::e($record['birth_date'] ?? '') ?>" required></label>
            <label>Gênero <select name="gender"><option value="">Não informado</option><?php foreach (['male' => 'Masculino', 'female' => 'Feminino', 'other' => 'Outro'] as $key => $label): ?><option value="<?= $key ?>" <?= ($record['gender'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
            <label>Foto do atleta <input type="file" name="photo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" <?= empty($editing) ? 'required' : '' ?>></label>
            <p class="muted"><?= empty($editing) ? 'Envie uma foto nítida para identificação.' : 'Envie outra foto somente se quiser substituir a atual.' ?></p>
            <?php if (!empty($editing) && !empty($record['photo_path'])): ?><p class="current-athlete-photo"><img class="athlete-profile-photo" src="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $record['id'] . '/assets/photo?v=' . rawurlencode((string) $record['photo_path']))) ?>" alt="Foto atual do atleta"><span>Foto atual.</span></p><?php endif; ?>
        </fieldset>
        <fieldset>
            <legend>Posição</legend>
            <label>Posição principal <select name="primary_position_id" required><option value="">Selecione</option><?php foreach ($positions as $position): ?><option value="<?= (int) $position['id'] ?>" <?= (string) ($record['primary_position_id'] ?? '') === (string) $position['id'] ? 'selected' : '' ?>><?= App\Core\View::e($position['name']) ?></option><?php endforeach; ?></select></label>
        </fieldset>
        <fieldset>
            <legend>Documento de identificação</legend>
            <p class="muted">O documento fica protegido e só pode ser consultado pela equipe autorizada. Ele será analisado antes de o atleta ser escalado.</p>
            <label>Foto ou arquivo do documento <input type="file" name="identity_document" accept=".pdf,.png,.jpg,.jpeg,.webp,application/pdf,image/png,image/jpeg,image/webp" <?= empty($editing) ? 'required' : '' ?>></label>
            <p class="muted"><?= empty($editing) ? 'Obrigatório no cadastro.' : 'Envie um novo arquivo para substituir o documento em análise.' ?></p>
        </fieldset>
        <?php $showGuardian = !empty($record['requires_guardian']); ?>
        <fieldset id="athlete-guardian-fields" <?= $showGuardian ? '' : 'hidden' ?>>
            <legend>Responsável legal</legend>
            <p class="muted">Este campeonato exige responsável legal para atletas menores.</p>
            <label>Nome <input name="guardian_full_name" value="<?= App\Core\View::e($guardian['full_name'] ?? '') ?>"></label>
            <label>Parentesco <input name="guardian_relationship" value="<?= App\Core\View::e($guardian['relationship'] ?? '') ?>"></label>
            <label>Telefone <input name="guardian_phone" value="<?= App\Core\View::e($guardian['phone'] ?? '') ?>"></label>
            <label>E-mail <input type="email" name="guardian_email" value="<?= App\Core\View::e($guardian['email'] ?? '') ?>"></label>
            <label>Documento <input name="guardian_document" value="<?= App\Core\View::e($guardian['document_number'] ?? '') ?>" autocomplete="off"></label>
            <label>Observação da autorização <textarea name="guardian_authorization_note" rows="2"><?= App\Core\View::e($guardian['authorization_note'] ?? '') ?></textarea></label>
        </fieldset>
        <label>Observações privadas <textarea name="private_notes" rows="4"><?= App\Core\View::e($record['private_notes'] ?? '') ?></textarea></label>
        <div class="inline-actions">
            <?php if (empty($editing) && !empty($canCreateRegistration)): ?>
                <button type="submit" name="registration_action" value="create">Salvar e enviar para inscrição</button>
            <?php else: ?>
                <button type="submit" name="registration_action" value="save">Salvar atleta</button>
            <?php endif; ?>
        </div>
        <?php if (empty($editing) && !empty($canCreateRegistration)): ?><p class="muted">Essa opção cria o atleta e abre uma inscrição automaticamente, sem repetir o cadastro.</p><?php endif; ?>
    </form>
</section>
<?php if (empty($editing)): ?>
<script>
(() => {
    const team = document.getElementById('athlete-team');
    const guardian = document.getElementById('athlete-guardian-fields');
    if (!team || !guardian) return;
    const sync = () => {
        const option = team.options[team.selectedIndex];
        guardian.hidden = option?.dataset.requiresGuardian !== '1';
    };
    team.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>
