<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$base = App\Core\Config::url('/campeonatos/' . $championship['slug']);
$initials = static function (string $name): string { $parts = preg_split('/\s+/', trim($name)) ?: []; return strtoupper(substr((string) ($parts[0] ?? 'AT'), 0, 1) . substr((string) ($parts[1] ?? ''), 0, 1)); };
$typesMap = ['contratacao' => 'Contratação', 'transferencia' => 'Transferência', 'emprestimo' => 'Empréstimo', 'retorno' => 'Retorno', 'renovacao' => 'Renovação', 'saida' => 'Saída'];
$teamMark = static function (?string $path, ?string $slug, ?string $name) use ($base, $e): string {
    if ($path && $slug) return '<img src="' . $e($base . '/equipes/' . rawurlencode($slug) . '/escudo') . '" alt="">';
    return '<span data-icon="shield" aria-hidden="true"></span>';
};
?>
<section class="public-news public-transfers">
    <header><p class="eyebrow"><?= $e($championship['name']) ?></p><h1>Vai e Vem</h1><p>Últimas transferências e movimentações de atletas publicadas no campeonato.</p></header>
    <form class="filter-bar" method="get" action="<?= $e($base . '/vai-e-vem') ?>"><label>Buscar atleta ou equipe <input name="q" value="<?= $e($filters['q']) ?>" placeholder="Ex.: João Silva"></label><label>Tipo de movimentação <select name="type"><option value="">Todos</option><?php foreach ($types as $type): ?><option value="<?= $e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= $e($typesMap[$type] ?? $type) ?></option><?php endforeach; ?></select></label><button type="submit">Buscar</button></form>
    <div class="news-grid transfers-grid">
        <?php foreach ($items as $item): ?>
            <article class="news-card transfer-card">
                <div class="transfer-card-head">
                    <?php if ($item['photo_path']): ?><img src="<?= $e($base . '/vai-e-vem/' . $item['id'] . '/foto') ?>" alt="Foto de <?= $e($item['athlete_name']) ?>"><?php else: ?><span><?= $e($initials((string) ($item['sporting_name'] ?: $item['athlete_name']))) ?></span><?php endif; ?>
                    <div><p class="eyebrow"><?= $e($typesMap[$item['type']] ?? $item['type']) ?> · <?= $e($item['movement_date']) ?></p><h2><?= $e($item['sporting_name'] ?: $item['athlete_name']) ?></h2></div>
                </div>
                <div class="transfer-route">
                    <span><small>Origem</small><i><?= $teamMark($item['previous_team_shield_path'], $item['previous_team_slug'], $item['previous_team_name']) ?></i><b><?= $e($item['previous_team_name'] ?: 'Sem equipe') ?></b></span>
                    <em aria-hidden="true">→</em>
                    <span><small>Destino</small><i><?= $teamMark($item['new_team_shield_path'], $item['new_team_slug'], $item['new_team_name']) ?></i><b><?= $e($item['new_team_name'] ?: 'Sem equipe') ?></b></span>
                </div>
                <?php if ($item['public_observation']): ?><p class="transfer-observation"><?= nl2br($e($item['public_observation'])) ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><p>Nenhuma transferência publicada.</p><?php endif; ?>
    <?php if ($pages > 1): ?><nav class="pagination" aria-label="Paginação"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="<?= $e($base . '/vai-e-vem?page=' . $i . '&q=' . urlencode($filters['q']) . '&type=' . urlencode($filters['type'])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</section>
