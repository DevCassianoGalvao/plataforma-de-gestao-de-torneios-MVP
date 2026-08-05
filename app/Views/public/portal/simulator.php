<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$base = App\Core\Config::url('/campeonatos/' . $championship['slug']);
$initials = static function (string $name): string { $parts = preg_split('/\s+/', trim($name)) ?: []; $value = ''; foreach (array_slice($parts, 0, 2) as $part) $value .= strtoupper(substr($part, 0, 1)); return $value ?: 'TM'; };
$teamMark = static function (array $team) use ($e, $base, $initials): string {
    if (!empty($team['shield_path']) && !empty($team['slug'])) return '<img class="team-list-mark" src="' . $e($base . '/equipes/' . rawurlencode((string) $team['slug']) . '/escudo') . '" alt="">';
    return '<span class="team-list-mark" data-icon="shield" aria-hidden="true">' . $e($initials((string) ($team['team_name'] ?? $team['name'] ?? 'Equipe'))) . '</span>';
};
$fixtures = $simulator['matches'] ?? [];
$groups = $projection['groups'] ?? [];
$rounds = [];
$statusLabels = ['homologated' => 'Resultado aprovado', 'scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'postponed' => 'Adiado'];
foreach ($fixtures as $match) {
    $round = (int) ($match['round_number'] ?? 0);
    $rounds[$round] = $round > 0 ? 'Rodada ' . $round : 'Rodada a definir';
}
ksort($rounds);
?>
<section class="portal-page-heading portal-page-heading--action simulator-heading">
    <div>
        <p class="eyebrow">Simulador de resultados</p>
        <h1>Projete a classificação</h1>
        <p>Brinque com os resultados de qualquer rodada e veja a tabela mudar na hora, sem alterar os dados oficiais.</p>
    </div>
    <a class="button secondary" href="<?= $e($base . '/classificacao') ?>">Classificação oficial</a>
</section>

<div class="free-simulator" data-free-simulator data-simulator-endpoint="<?= $e($base . '/simulador/simular') ?>">
    <div class="free-simulator-grid">
        <section class="free-simulator-table" aria-live="polite">
            <div class="section-heading">
                <div><p class="eyebrow">Projeção em tempo real</p><h2>Classificação simulada</h2></div>
                <span class="simulator-result-label" data-simulator-label>Dados oficiais</span>
            </div>
            <p class="simulator-status-text" data-simulator-status>Informe os placares à direita para ver a projeção.</p>
            <div data-simulator-tables>
                <?php foreach ($groups as $group): ?>
                    <section class="simulator-group-table" data-simulator-group="<?= (int) $group['id'] ?>">
                        <h3><?= $e($group['name']) ?></h3>
                        <div class="table-wrap"><table><thead><tr><th>#</th><th>Equipe</th><th>J</th><th>V</th><th>E</th><th>D</th><th>GP</th><th>GC</th><th>SG</th><th>Pts</th></tr></thead><tbody>
                        <?php foreach ($group['simulated'] as $row): ?>
                            <tr data-free-simulator-row="<?= (int) $row['team_id'] ?>">
                                <td><?= (int) $row['position'] ?></td>
                                <td><a class="standings-team-link" href="<?= $e($base . '/equipes/' . rawurlencode((string) $row['slug'])) ?>"><?= $teamMark($row) ?><span><?= $e($row['team_name']) ?></span></a></td>
                                <td><?= (int) $row['matches_played'] ?></td><td><?= (int) $row['wins'] ?></td><td><?= (int) $row['draws'] ?></td><td><?= (int) $row['losses'] ?></td><td><?= (int) $row['goals_for'] ?></td><td><?= (int) $row['goals_against'] ?></td><td><?= (int) $row['goal_difference'] ?></td><td><strong><?= (int) $row['points'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                    </section>
                <?php endforeach; ?>
                <?php if (!$groups): ?><p>Nenhum grupo publicado para simular.</p><?php endif; ?>
            </div>
        </section>

        <section class="free-simulator-matches">
            <div class="section-heading">
                <div><p class="eyebrow">Partidas</p><h2>Escolha os resultados</h2></div>
                <button type="button" class="button secondary" data-simulator-reset>Limpar placares</button>
            </div>
            <div class="simulator-round-controls">
                <button type="button" class="button secondary" data-simulator-round-prev aria-label="Rodada anterior">‹</button>
                <select data-simulator-round-select aria-label="Selecionar rodada"><option value="all">Todas as rodadas</option><?php foreach ($rounds as $number => $label): ?><option value="<?= (int) $number ?>"><?= $e($label) ?></option><?php endforeach; ?></select>
                <button type="button" class="button secondary" data-simulator-round-next aria-label="Próxima rodada">›</button>
            </div>
            <p class="simulator-round-status" data-simulator-round-label>Mostrando todas as rodadas.</p>
            <div class="simulator-match-list">
                <?php foreach ($fixtures as $match): ?>
                    <?php $round = (int) ($match['round_number'] ?? 0); $status = $statusLabels[(string) $match['status']] ?? 'Partida'; $date = $match['match_date'] ?: 'Data a definir'; $time = $match['match_time'] ? substr((string) $match['match_time'], 0, 5) : ''; ?>
                    <article class="simulator-match" data-simulator-match data-round="<?= $round ?>" data-match-id="<?= (int) $match['id'] ?>">
                        <small><?= $e($match['group_name']) ?> · <?= $e($round > 0 ? 'Rodada ' . $round : 'Rodada a definir') ?> · <?= $e($date . ($time ? ' ' . $time : '')) ?></small>
                        <div class="simulator-match-teams">
                            <strong><?= $e($match['home_team_name']) ?></strong>
                            <div class="simulator-score-control"><input type="number" min="0" max="99" inputmode="numeric" data-simulator-score="home" data-match="<?= (int) $match['id'] ?>" aria-label="Gols de <?= $e($match['home_team_name']) ?>"><small>Atual: <?= (int) $match['home_score'] ?></small></div>
                            <b>×</b>
                            <div class="simulator-score-control"><input type="number" min="0" max="99" inputmode="numeric" data-simulator-score="away" data-match="<?= (int) $match['id'] ?>" aria-label="Gols de <?= $e($match['away_team_name']) ?>"><small>Atual: <?= (int) $match['away_score'] ?></small></div>
                            <strong><?= $e($match['away_team_name']) ?></strong>
                        </div>
                        <span class="simulator-match-status"><?= $e($status) ?></span>
                    </article>
                <?php endforeach; ?>
                <?php if (!$fixtures): ?><p>Nenhuma partida publicada está disponível para simulação.</p><?php endif; ?>
            </div>
        </section>
    </div>
    <p class="simulator-note"><strong>SIMULAÇÃO INTERNA.</strong> Os placares ficam somente neste navegador e nunca alteram resultados, classificação ou documentos oficiais.</p>
</div>
