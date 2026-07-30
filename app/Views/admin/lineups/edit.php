<?php
$selectedStarters = (array) ($formData['starters'] ?? []);
$selectedReserves = array_map('intval', (array) ($formData['reserves'] ?? []));
$selectedStaff = array_map('intval', (array) ($formData['staff_ids'] ?? []));
$athleteById = [];
foreach ($athletes as $athlete) $athleteById[(int) $athlete['id']] = $athlete;
$displayName = static fn (array $athlete): string => (string) ($athlete['sporting_name'] ?: $athlete['full_name']);
$isSlotCompatible = static function (array $athlete, array $slot): bool {
    $secondary = array_filter(explode(',', (string) ($athlete['secondary_position_codes'] ?? '')));
    return (string) $athlete['primary_position_code'] === (string) $slot['position_code'] || in_array((string) $slot['position_code'], $secondary, true);
};
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$athletePayload = array_map(static fn (array $athlete): array => [
    'id' => (int) $athlete['id'],
    'name' => (string) ($athlete['sporting_name'] ?: $athlete['full_name']),
    'number' => $athlete['preferred_number'] ?: '-',
    'position' => (string) $athlete['primary_position_name'],
    'primaryCode' => (string) $athlete['primary_position_code'],
    'secondaryCodes' => array_values(array_filter(explode(',', (string) ($athlete['secondary_position_codes'] ?? '')))),
    'photoUrl' => !empty($athlete['photo_path']) ? App\Core\Config::url('/admin/atletas/' . $athlete['id'] . '/assets/photo?v=' . rawurlencode((string) $athlete['photo_path'])) : '',
], $athletes);
?>
<section>
    <div class="section-heading">
        <div><p class="eyebrow"><?= App\Core\View::e($lineup['team_name']) ?></p><h1>Escala&ccedil;&atilde;o</h1><p><?= App\Core\View::e($match['home_team_name']) ?> x <?= App\Core\View::e($match['away_team_name']) ?> - vers&atilde;o <?= (int) $lineup['version'] ?></p></div>
        <span class="status status-<?= App\Core\View::e($lineup['status']) ?>"><?= App\Core\View::e($lineup['status']) ?></span>
    </div>
    <?php if ($errors !== []): ?><div class="error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= App\Core\View::e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId)) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <fieldset>
            <legend>Forma&ccedil;&atilde;o</legend>
            <label>Escolhida <select name="formation_id" data-formation-select required <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>><?php foreach ($formations as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) ($formData['formation_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>><?= App\Core\View::e($item['name']) ?></option><?php endforeach; ?></select></label>
            <p>Troque a forma&ccedil;&atilde;o para ver o campo reorganizado na hora. O rascunho s&oacute; muda ao salvar.</p>
        </fieldset>
        <fieldset>
            <legend>Campo t&aacute;tico</legend>
            <div class="tactical-field tactical-field--editor" data-lineup-field aria-label="Campo de futebol de <?= App\Core\View::e($lineup['team_name']) ?>">
                <img src="<?= App\Core\View::e(App\Core\Config::url('/assets/football-field.svg')) ?>" alt="" aria-hidden="true">
                <div data-lineup-slots>
                    <?php foreach (($formation['slots'] ?? []) as $slot): ?>
                        <?php $athleteId = (int) ($selectedStarters[$slot['slot_key']] ?? 0); $selectedAthlete = $athleteById[$athleteId] ?? null; $outOfPosition = $selectedAthlete !== null && !$isSlotCompatible($selectedAthlete, $slot); ?>
                        <div class="tactical-editor-slot" style="--slot-x: <?= (float) $slot['horizontal_position'] ?>%; --slot-y: <?= (float) $slot['vertical_position'] ?>%;">
                            <div class="tactical-player <?= $outOfPosition ? 'is-out-of-position' : '' ?>">
                                <?php if ($selectedAthlete && !empty($selectedAthlete['photo_path'])): ?><img src="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $selectedAthlete['id'] . '/assets/photo?v=' . rawurlencode((string) $selectedAthlete['photo_path']))) ?>" alt="Foto de <?= App\Core\View::e($displayName($selectedAthlete)) ?>"><?php else: ?><span><?= $selectedAthlete ? App\Core\View::e(strtoupper(substr($displayName($selectedAthlete), 0, 2))) : '+' ?></span><?php endif; ?>
                                <small><?= $selectedAthlete ? App\Core\View::e($displayName($selectedAthlete)) : 'Vazio' ?></small>
                            </div>
                            <label><span><?= App\Core\View::e($slot['label']) ?></span><select name="starters[<?= App\Core\View::e($slot['slot_key']) ?>]" <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>><option value="">Vazio</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= $athleteId === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?> #<?= App\Core\View::e($athlete['preferred_number'] ?: '-') ?></option><?php endforeach; ?></select></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="muted">Altere atleta no seletor abaixo de cada bolinha. Aviso amarelo indica atleta fora da posi&ccedil;&atilde;o preferencial.</p>
        </fieldset>
        <fieldset>
            <legend>Capit&atilde;o e goleiro</legend>
            <label>Capit&atilde;o <select name="captain_athlete_id" <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>><option value="">Selecione</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= (int) ($formData['captain_athlete_id'] ?? 0) === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?></option><?php endforeach; ?></select></label>
            <label>Goleiro <select name="goalkeeper_athlete_id" <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>><option value="">Selecione</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= (int) ($formData['goalkeeper_athlete_id'] ?? 0) === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?></option><?php endforeach; ?></select></label>
        </fieldset>
        <fieldset>
            <legend>Reservas</legend>
            <div class="choice-grid"><?php foreach ($athletes as $athlete): ?><label><input type="checkbox" name="reserves[]" value="<?= (int) $athlete['id'] ?>" <?= in_array((int) $athlete['id'], $selectedReserves, true) ? 'checked' : '' ?> <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>> <?= App\Core\View::e($displayName($athlete)) ?> #<?= App\Core\View::e($athlete['preferred_number'] ?: '-') ?> <small><?= App\Core\View::e($athlete['primary_position_name']) ?></small></label><?php endforeach; ?></div>
        </fieldset>
        <fieldset>
            <legend>Comiss&atilde;o presente</legend>
            <div class="choice-grid"><?php foreach ($staff as $member): ?><label><input type="checkbox" name="staff_ids[]" value="<?= (int) $member['id'] ?>" <?= in_array((int) $member['id'], $selectedStaff, true) ? 'checked' : '' ?> <?= $lineup['status'] === 'draft' ? '' : 'disabled' ?>> <?= App\Core\View::e($member['display_name'] ?: $member['full_name']) ?> - <?= App\Core\View::e($member['role_name']) ?></label><?php endforeach; ?></div>
        </fieldset>
        <?php if ($lineup['status'] === 'draft'): ?><div class="action-nav"><button type="submit" name="action" value="save">Salvar rascunho</button><button type="submit" name="action" value="confirm">Validar e confirmar</button></div><?php endif; ?>
    </form>
    <?php if ($lineup['status'] === 'draft'): ?>
        <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId . '/automatico')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="formation_id" data-auto-formation value="<?= (int) ($formData['formation_id'] ?? $lineup['tactical_formation_id']) ?>"><button type="submit">Distribuir automaticamente</button></form>
    <?php elseif ($canReopen): ?>
        <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId . '/reabrir')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><label>Motivo da reabertura <textarea name="reason" required></textarea></label><button type="submit">Reabrir escala&ccedil;&atilde;o</button></form>
    <?php endif; ?>
    <article class="panel"><h2>Hist&oacute;rico</h2><ul><?php foreach ($history as $item): ?><li>v<?= (int) $item['version'] ?> - <?= App\Core\View::e($item['action']) ?> - <?= App\Core\View::e($item['status']) ?> - <?= App\Core\View::e($item['created_at']) ?></li><?php endforeach; ?></ul></article>
</section>
<?php if ($lineup['status'] === 'draft'): ?>
<script type="application/json" id="lineup-formation-catalog"><?= json_encode($formationCatalog, $jsonFlags) ?></script>
<script type="application/json" id="lineup-athletes"><?= json_encode($athletePayload, $jsonFlags) ?></script>
<script>
(() => {
    const field = document.querySelector('[data-lineup-field]');
    const formationSelect = document.querySelector('[data-formation-select]');
    const slotsRoot = field?.querySelector('[data-lineup-slots]');
    if (!field || !formationSelect || !slotsRoot) return;
    const formations = JSON.parse(document.getElementById('lineup-formation-catalog').textContent || '[]');
    const athletes = JSON.parse(document.getElementById('lineup-athletes').textContent || '[]');
    const athleteMap = new Map(athletes.map((athlete) => [String(athlete.id), athlete]));
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
    const currentAssignments = () => Array.from(slotsRoot.querySelectorAll('select[name^="starters["]')).map((select) => ({ key: select.name.slice(9, -1), value: select.value })).filter((item) => item.value);
    const render = (formationId) => {
        const formation = formations.find((item) => String(item.id) === String(formationId));
        if (!formation) return;
        const assignments = currentAssignments();
        const bySlot = new Map(assignments.map((item) => [item.key, item.value]));
        const remaining = assignments.map((item) => item.value);
        const used = new Set();
        const nextValue = (slotKey) => {
            const retained = bySlot.get(slotKey);
            if (retained && !used.has(retained)) { used.add(retained); return retained; }
            const next = remaining.find((value) => !used.has(value));
            if (next) used.add(next);
            return next || '';
        };
        slotsRoot.innerHTML = formation.slots.map((slot) => {
            const athleteId = nextValue(slot.slot_key);
            const athlete = athleteMap.get(athleteId);
            const compatible = !athlete || athlete.primaryCode === slot.position_code || athlete.secondaryCodes.includes(slot.position_code);
            const avatar = athlete?.photoUrl ? `<img src="${escapeHtml(athlete.photoUrl)}" alt="Foto de ${escapeHtml(athlete.name)}">` : `<span>${escapeHtml(athlete ? athlete.name.slice(0, 2).toUpperCase() : '+')}</span>`;
            const options = ['<option value="">Vazio</option>'].concat(athletes.map((item) => `<option value="${item.id}"${String(item.id) === athleteId ? ' selected' : ''}>${escapeHtml(item.name)} #${escapeHtml(item.number)}</option>`)).join('');
            return `<div class="tactical-editor-slot" style="--slot-x:${slot.horizontal_position}%;--slot-y:${slot.vertical_position}%;"><div class="tactical-player${compatible ? '' : ' is-out-of-position'}">${avatar}<small>${escapeHtml(athlete?.name || 'Vazio')}</small></div><label><span>${escapeHtml(slot.label)}</span><select name="starters[${escapeHtml(slot.slot_key)}]">${options}</select></label></div>`;
        }).join('');
        document.querySelector('[data-auto-formation]').value = formation.id;
    };
    formationSelect.addEventListener('change', () => render(formationSelect.value));
    slotsRoot.addEventListener('change', (event) => { if (event.target.matches('select[name^="starters["]')) render(formationSelect.value); });
})();
</script>
<?php endif; ?>
