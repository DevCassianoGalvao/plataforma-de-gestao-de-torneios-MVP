<?php $e = static fn (mixed $value): string => App\Core\View::e($value); $metric = static fn (string $key): int => (int) (($metrics ?? [])[$key] ?? 0); ?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="eyebrow">Centro de comando</p>
        <h1>O campeonato, sob controle.</h1>
        <p class="muted">Acompanhe pendências, decisões e o próximo movimento da operação em um só lugar.</p>
    </div>
</section>
<section class="metric-grid" aria-label="Resumo operacional">
    <article class="metric-card metric-card-primary"><span class="metric-icon" data-icon="trophy" aria-hidden="true"></span><span>Campeonatos</span><strong><?= $metric('championships') ?></strong><small>cadastros na plataforma</small></article>
    <article class="metric-card"><span class="metric-icon" data-icon="shield" aria-hidden="true"></span><span>Equipes ativas</span><strong><?= $metric('teams') ?></strong><small>com status ativo</small></article>
    <article class="metric-card"><span class="metric-icon" data-icon="user-round" aria-hidden="true"></span><span>Atletas ativos</span><strong><?= $metric('athletes') ?></strong><small>elenco cadastrado</small></article>
    <article class="metric-card"><span class="metric-icon" data-icon="file-check-2" aria-hidden="true"></span><span>Inscrições aprovadas</span><strong><?= $metric('registrations') ?></strong><small>prontos para competir</small></article>
</section>
<section class="dashboard-grid">
    <div class="dashboard-panel">
        <div class="section-heading"><div><p class="eyebrow">Radar da operação</p><h2>O que merece atenção</h2></div><span class="status status-active">Dados reais</span></div>
        <div class="attention-list">
            <a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"><span class="attention-icon" data-icon="calendar-days" aria-hidden="true"></span><span><strong>Próximos confrontos</strong><small><?= $metric('upcoming_matches') ?> partidas agendadas</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"><span class="attention-icon attention-warning" data-icon="clipboard-check" aria-hidden="true"></span><span><strong>Partidas a homologar</strong><small><?= $metric('awaiting_homologation') ?> aguardando decisão</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/disciplina')) ?>"><span class="attention-icon attention-danger" data-icon="shield-alert" aria-hidden="true"></span><span><strong>Suspensões ativas</strong><small><?= $metric('suspended') ?> atletas ou membros</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/noticias')) ?>"><span class="attention-icon" data-icon="newspaper" aria-hidden="true"></span><span><strong>Notícias publicadas</strong><small><?= $metric('published_news') ?> no portal</small></span><b aria-hidden="true">›</b></a>
        </div>
    </div>
    <div class="dashboard-panel dashboard-panel-quiet">
        <p class="eyebrow">Acesso rápido</p>
        <h2>Comece pela próxima ação</h2>
        <div class="quick-actions">
            <a class="button" data-icon="trophy" href="<?= $e(App\Core\Config::url('/admin/campeonatos')) ?>">Abrir campeonatos</a>
            <a class="button secondary" data-icon="shield" href="<?= $e(App\Core\Config::url('/admin/equipes')) ?>">Ver equipes</a>
            <a class="button secondary" data-icon="users-round" href="<?= $e(App\Core\Config::url('/admin/atletas')) ?>">Ver atletas</a>
        </div>
        <p class="muted">Use a navegação lateral para entrar em competição, conteúdo e acesso.</p>
    </div>
</section>
<?php if (!empty($championships)): ?>
<section class="dashboard-panel">
    <div class="section-heading"><div><p class="eyebrow">Divulgação</p><h2>Portal público de cada campeonato</h2></div></div>
    <div class="attention-list">
        <?php foreach ($championships as $item): ?>
            <?php if ($item['visibility'] === 'public' && $item['status'] !== 'draft'): ?>
                <a href="<?= $e(App\Core\Config::absoluteUrl('/campeonatos/' . $item['slug'])) ?>" target="_blank" rel="noopener"><span class="attention-icon" data-icon="globe" aria-hidden="true"></span><span><strong><?= $e($item['name']) ?></strong><small><?= $e(App\Core\Config::absoluteUrl('/campeonatos/' . $item['slug'])) ?></small></span><b aria-hidden="true">↗</b></a>
            <?php else: ?>
                <span class="attention-list-item-disabled"><span class="attention-icon" data-icon="globe" aria-hidden="true"></span><span><strong><?= $e($item['name']) ?></strong><small>ainda não público (visibilidade: <?= $e($item['visibility']) ?>, status: <?= $e($item['status']) ?>)</small></span></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
