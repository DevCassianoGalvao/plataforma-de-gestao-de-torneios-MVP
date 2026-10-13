<?php
$e = static fn (mixed $value): string => App\Core\View::e((string) $value);
$csrf = $e(App\Core\Security::csrfToken());
$open = $operation['status'] === 'open';
$typeLabels = ['goal' => 'Gol', 'own_goal' => 'Gol contra', 'yellow' => 'Amarelo', 'second_yellow' => '2º amarelo', 'red' => 'Vermelho'];
$teams = [
    ['id' => (int) $match['home_team_id'], 'name' => $match['home_team_name'], 'roster' => $rosters['home'] ?? []],
    ['id' => (int) $match['away_team_id'], 'name' => $match['away_team_name'], 'roster' => $rosters['away'] ?? []],
];
foreach ($teams as &$team) {
    $lineupPlayers = array_values(array_filter($players, static fn (array $player): bool => (int) $player['team_id'] === $team['id']));
    $team['options'] = $lineupPlayers !== []
        ? array_map(static fn (array $player): array => ['id' => (int) $player['athlete_id'], 'name' => (string) $player['name']], $lineupPlayers)
        : array_map(static fn (array $athlete): array => ['id' => (int) $athlete['athlete_id'], 'name' => (string) $athlete['full_name']], $team['roster']);
}
unset($team);
$visibleEvents = array_values(array_filter($events, static fn (array $event): bool => (int) $event['valid'] === 1 && isset($typeLabels[$event['event_type']])));
?>
<section class="operator-simple">
    <p class="eyebrow"><?= $e($match['championship_name'] ?? '') ?> · Rodada <?= (int) ($match['round_number'] ?? 0) ?></p>
    <h1>Súmula da partida</h1>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="error" role="alert"><?php foreach ($errors as $error): ?><p><?= $e($error) ?></p><?php endforeach; ?></div><?php endif; ?>

    <article class="operator-score">
        <strong><?= $e($match['home_team_name']) ?></strong>
        <b><?= (int) $score['home_score'] ?> x <?= (int) $score['away_score'] ?></b>
        <strong><?= $e($match['away_team_name']) ?></strong>
    </article>

    <?php if (!$open): ?>
        <p class="operator-status">Partida enviada para aprovação. Para corrigir algo, fale com a administração.</p>
    <?php elseif ($canOperate): ?>
        <article class="panel">
            <h2>Lançar gol ou cartão</h2>
            <form method="post" action="<?= $e($operationBase . '/evento') ?>" class="operator-form">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="period" value="regular">
                <fieldset class="operator-types">
                    <legend>O que aconteceu?</legend>
                    <label><input type="radio" name="event_type" value="goal" required> <span class="operator-card is-goal">Gol</span></label>
                    <label><input type="radio" name="event_type" value="yellow"> <span class="operator-card is-yellow">Amarelo</span></label>
                    <label><input type="radio" name="event_type" value="red"> <span class="operator-card is-red">Vermelho</span></label>
                </fieldset>
                <label>Jogador
                    <select name="athlete_id" required>
                        <option value="">Selecione o jogador</option>
                        <?php foreach ($teams as $team): ?>
                            <optgroup label="<?= $e($team['name']) ?>">
                                <?php foreach ($team['options'] as $option): ?><option value="<?= $option['id'] ?>"><?= $e($option['name']) ?></option><?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Minuto (opcional) <input type="number" name="minute" min="0" max="130" inputmode="numeric"></label>
                <button type="submit">Lançar</button>
            </form>
        </article>
    <?php endif; ?>

    <article class="panel">
        <h2>Lançamentos (<?= count($visibleEvents) ?>)</h2>
        <?php if ($visibleEvents === []): ?>
            <p class="muted">Nenhum gol ou cartão lançado.</p>
        <?php else: ?>
            <ul class="operator-events">
                <?php foreach ($visibleEvents as $event): ?>
                    <li>
                        <span class="operator-event-type is-<?= $e($event['event_type']) ?>"><?= $e($typeLabels[$event['event_type']]) ?></span>
                        <span class="operator-event-who"><strong><?= $e($event['athlete_name'] ?: 'Autor não informado') ?></strong><small><?= $e($event['team_name'] ?: '') ?><?= $event['minute'] !== null ? ' · ' . (int) $event['minute'] . "'" : '' ?></small></span>
                        <?php if ($open && $canOperate): ?>
                            <form method="post" action="<?= $e($operationBase . '/evento/remover') ?>"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>"><button type="submit" class="button-danger">Remover</button></form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Observações da partida</h2>
        <p class="muted">Ocorrências, brigas, paralisações, relatório do árbitro. Uso interno: não aparece no site.</p>
        <?php if ($open && $canOperate): ?>
            <form method="post" action="<?= $e($operationBase . '/observacoes') ?>" class="operator-form">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <textarea name="internal_notes" rows="7" maxlength="10000"><?= $e($notes['internal_notes'] ?? '') ?></textarea>
                <button type="submit">Salvar observações</button>
            </form>
        <?php else: ?>
            <p class="operator-notes"><?= nl2br($e($notes['internal_notes'] ?: 'Sem observações.')) ?></p>
        <?php endif; ?>
    </article>

    <?php if ($open && $canOperate): ?>
        <article class="panel critical-panel">
            <h2>Terminou a partida?</h2>
            <p class="muted">Confira o placar e os lançamentos. Depois de enviar, só a administração consegue alterar.</p>
            <form method="post" action="<?= $e($operationBase . '/finalizar') ?>" class="operator-form">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <label class="check"><input type="checkbox" name="confirm_checklist" value="yes" required> Conferi placar, gols e cartões</label>
                <button type="submit">Enviar para aprovação</button>
            </form>
        </article>
    <?php endif; ?>
</section>
