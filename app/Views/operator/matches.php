<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
$isAdministrator = !empty($isAdministrator);
$statusLabels = ['draft' => 'Rascunho', 'scheduled' => 'Agendada', 'confirmed' => 'Confirmada', 'postponed' => 'Adiada'];
?>
<section class="page-stack operator-matches-page">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Opera&ccedil;&atilde;o de partida</p>
            <h1><?= $isAdministrator ? 'Partidas para operar' : 'Minhas partidas' ?></h1>
            <p class="muted"><?= $isAdministrator ? 'Vis&atilde;o administrativa de todas as partidas abertas. Acesse a central e as evid&ecirc;ncias conforme sua permiss&atilde;o.' : 'Aqui aparecem somente as partidas atribu&iacute;das ao seu usu&aacute;rio.' ?></p>
        </div>
        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>">Ver tabela e partidas</a>
    </div>

    <?php if ($items === []): ?>
        <article class="panel empty-state">
            <h2><?= $isAdministrator ? 'Nenhuma partida aberta' : 'Nenhuma partida atribu&iacute;da' ?></h2>
            <p><?= $isAdministrator ? 'As partidas abertas para opera&ccedil;&atilde;o aparecer&atilde;o aqui.' : 'O organizador ainda n&atilde;o atribuiu uma partida ao seu usu&aacute;rio.' ?></p>
        </article>
    <?php else: ?>
        <div class="match-grid" aria-label="Partidas abertas para opera&ccedil;&atilde;o">
            <?php foreach ($items as $item): ?>
                <article class="panel match-card match-operation-card">
                    <div class="match-card-heading">
                        <p class="eyebrow"><?= $e($item['championship_name']) ?> <span aria-hidden="true">&middot;</span> Rodada <?= (int) $item['round_number'] ?></p>
                        <?php $matchStatus = (string) ($item['status'] ?: 'scheduled'); ?>
                        <span class="status status-<?= $e($matchStatus) ?>"><?= $e($statusLabels[$matchStatus] ?? 'Aberta') ?></span>
                    </div>
                    <h2><?= $e($item['home_team_name']) ?> <span aria-hidden="true">x</span> <?= $e($item['away_team_name']) ?></h2>
                    <div class="match-card-meta">
                        <span><b>Quando</b> <?= $e($item['match_date'] ?: 'Data a definir') ?> <?= $e(substr((string) $item['match_time'], 0, 5)) ?></span>
                        <span><b>Local</b> <?= $e($item['venue_name'] ?: 'Local a definir') ?></span>
                    </div>
                    <div class="action-nav match-card-actions">
                        <a class="button" href="<?= $e(App\Core\Config::url('/admin/partidas/' . $item['id'] . '/operacao')) ?>">Abrir central</a>
                        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/partidas/' . $item['id'] . '/evidencias')) ?>">Ver evid&ecirc;ncias</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
