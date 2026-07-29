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
?>
<section>
    <div class="section-heading">
        <div><p class="eyebrow"><?= App\Core\View::e($lineup['team_name']) ?></p><h1>Escalação</h1><p><?= App\Core\View::e($match['home_team_name']) ?> x <?= App\Core\View::e($match['away_team_name']) ?> - versão <?= (int) $lineup['version'] ?></p></div>
        <span class="status status-<?= App\Core\View::e($lineup['status']) ?>"><?= App\Core\View::e($lineup['status']) ?></span>
    </div>
    <?php if ($errors !== []): ?><div class="error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= App\Core\View::e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId)) ?>">
        <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
        <fieldset>
            <legend>Formação</legend>
            <label>Escolhida <select name="formation_id" required><?php foreach ($formations as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) ($formData['formation_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>><?= App\Core\View::e($item['name']) ?></option><?php endforeach; ?></select></label>
            <p>Sugestão padrao da equipe pode ser alterada nesta partida.</p>
        </fieldset>
        <?php if ($formation): ?>
        <fieldset>
            <legend>Campo funcional</legend>
            <div class="lineup-field" aria-label="Campo de futebol">
                <div class="field-half-line"></div>
                <?php foreach ($formation['slots'] as $slot): ?>
                    <?php $athleteId = (int) ($selectedStarters[$slot['slot_key']] ?? 0); $selectedAthlete = $athleteById[$athleteId] ?? null; $outOfPosition = $selectedAthlete !== null && !$isSlotCompatible($selectedAthlete, $slot); ?>
                    <div class="lineup-slot" style="left: <?= (float) $slot['horizontal_position'] ?>%; top: <?= (float) $slot['vertical_position'] ?>%;">
                        <div class="lineup-player-card <?= $outOfPosition ? 'out-of-position' : '' ?>">
                            <?php if ($selectedAthlete && !empty($selectedAthlete['photo_path'])): ?><img class="lineup-player-photo" src="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $selectedAthlete['id'] . '/assets/photo')) ?>" alt="Foto de <?= App\Core\View::e($displayName($selectedAthlete)) ?>"><?php else: ?><span class="lineup-player-photo lineup-player-placeholder" aria-hidden="true"></span><?php endif; ?>
                            <strong><?= $selectedAthlete ? App\Core\View::e($displayName($selectedAthlete)) : 'Slot vazio' ?></strong>
                            <?php if ($selectedAthlete): ?><small>#<?= App\Core\View::e($selectedAthlete['preferred_number'] ?: '-') ?> - <?= App\Core\View::e($selectedAthlete['primary_position_name']) ?></small><?php endif; ?>
                            <?php if ($outOfPosition): ?><em>Fora de posicao</em><?php endif; ?>
                        </div>
                        <label><?= App\Core\View::e($slot['label']) ?><select name="starters[<?= App\Core\View::e($slot['slot_key']) ?>]"><option value="">Vazio</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= $athleteId === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?> #<?= App\Core\View::e($athlete['preferred_number'] ?: '-') ?></option><?php endforeach; ?></select></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="muted">Troque atleta de slot pelos selects. Mover, substituir ou ajustar não depende de arrastar.</p>
        </fieldset>
        <?php endif; ?>
        <fieldset>
            <legend>Capitao e goleiro</legend>
            <label>Capitao <select name="captain_athlete_id"><option value="">Selecione</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= (int) ($formData['captain_athlete_id'] ?? 0) === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?></option><?php endforeach; ?></select></label>
            <label>Goleiro <select name="goalkeeper_athlete_id"><option value="">Selecione</option><?php foreach ($athletes as $athlete): ?><option value="<?= (int) $athlete['id'] ?>" <?= (int) ($formData['goalkeeper_athlete_id'] ?? 0) === (int) $athlete['id'] ? 'selected' : '' ?>><?= App\Core\View::e($displayName($athlete)) ?></option><?php endforeach; ?></select></label>
        </fieldset>
        <fieldset>
            <legend>Reservas</legend>
            <div class="choice-grid"><?php foreach ($athletes as $athlete): ?><label><input type="checkbox" name="reserves[]" value="<?= (int) $athlete['id'] ?>" <?= in_array((int) $athlete['id'], $selectedReserves, true) ? 'checked' : '' ?>> <?= App\Core\View::e($displayName($athlete)) ?> #<?= App\Core\View::e($athlete['preferred_number'] ?: '-') ?> <small><?= App\Core\View::e($athlete['primary_position_name']) ?></small></label><?php endforeach; ?></div>
        </fieldset>
        <fieldset>
            <legend>Comissão presente</legend>
            <div class="choice-grid"><?php foreach ($staff as $member): ?><label><input type="checkbox" name="staff_ids[]" value="<?= (int) $member['id'] ?>" <?= in_array((int) $member['id'], $selectedStaff, true) ? 'checked' : '' ?>> <?= App\Core\View::e($member['display_name'] ?: $member['full_name']) ?> - <?= App\Core\View::e($member['role_name']) ?></label><?php endforeach; ?></div>
        </fieldset>
        <?php if ($lineup['status'] === 'draft'): ?>
            <div class="action-nav"><button type="submit" name="action" value="save">Salvar rascunho</button><button type="submit" name="action" value="confirm">Validar e confirmar</button></div>
    </form>
            <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId . '/automatico')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="formation_id" value="<?= (int) ($formData['formation_id'] ?? $lineup['tactical_formation_id']) ?>"><button type="submit">Distribuir automaticamente</button></form>
        <?php else: ?>
    </form>
            <?php if ($canReopen): ?><form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId . '/reabrir')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><label>Motivo da reabertura <textarea name="reason" required></textarea></label><button type="submit">Reabrir escalacao</button></form><?php endif; ?>
        <?php endif; ?>
    <article class="panel"><h2>Histórico minimo</h2><ul><?php foreach ($history as $item): ?><li>v<?= (int) $item['version'] ?> - <?= App\Core\View::e($item['action']) ?> - <?= App\Core\View::e($item['status']) ?> - <?= App\Core\View::e($item['created_at']) ?></li><?php endforeach; ?></ul></article>
</section>
