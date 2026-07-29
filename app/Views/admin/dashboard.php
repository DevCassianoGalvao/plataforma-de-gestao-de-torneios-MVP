<?php $e = static fn (mixed $value): string => App\Core\View::e($value); $metric = static fn (string $key): int => (int) (($metrics ?? [])[$key] ?? 0); ?>
<section class="dashboard-hero">
    <div>
        <p class="eyebrow">Centro de operacao</p>
        <h1>Bom trabalho, <?= $e($user['name']) ?>.</h1>
        <p class="muted">Acompanhe o campeonato, encontre pendências e aja antes do próximo apito.</p>
    </div>
    <div class="dashboard-hero-mark" aria-hidden="true">90:00</div>
</section>
<section class="metric-grid" aria-label="Resumo operacional">
    <article class="metric-card metric-card-primary"><span>Campeonatos</span><strong><?= $metric('championships') ?></strong><small>cadastros na plataforma</small></article>
    <article class="metric-card"><span>Equipes ativas</span><strong><?= $metric('teams') ?></strong><small>com status ativo</small></article>
    <article class="metric-card"><span>Atletas ativos</span><strong><?= $metric('athletes') ?></strong><small>elenco cadastrado</small></article>
    <article class="metric-card"><span>Inscrições aprovadas</span><strong><?= $metric('registrations') ?></strong><small>prontos para competir</small></article>
</section>
<section class="dashboard-grid">
    <div class="dashboard-panel">
        <div class="section-heading"><div><p class="eyebrow">Radar do dia</p><h2>O que merece atenção</h2></div><span class="status status-active">Dados reais</span></div>
        <div class="attention-list">
            <a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"><span class="attention-icon">JG</span><span><strong>Próximos confrontos</strong><small><?= $metric('upcoming_matches') ?> partidas agendadas</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/tabela')) ?>"><span class="attention-icon attention-warning">HM</span><span><strong>Partidas a homologar</strong><small><?= $metric('awaiting_homologation') ?> aguardando decisão</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/disciplina')) ?>"><span class="attention-icon attention-danger">DS</span><span><strong>Suspensões ativas</strong><small><?= $metric('suspended') ?> atletas ou membros</small></span><b aria-hidden="true">›</b></a>
            <a href="<?= $e(App\Core\Config::url('/admin/noticias')) ?>"><span class="attention-icon">NT</span><span><strong>Notícias publicadas</strong><small><?= $metric('published_news') ?> no portal</small></span><b aria-hidden="true">›</b></a>
        </div>
    </div>
    <div class="dashboard-panel dashboard-panel-quiet">
        <p class="eyebrow">Acesso rápido</p>
        <h2>Comece pela próxima acao</h2>
        <div class="quick-actions">
            <a class="button" href="<?= $e(App\Core\Config::url('/admin/campeonatos')) ?>">Abrir campeonatos</a>
            <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/equipes')) ?>">Ver equipes</a>
            <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/atletas')) ?>">Ver atletas</a>
        </div>
        <p class="muted">O menu lateral organiza o trabalho por competição, conteúdo e acesso.</p>
    </div>
</section>
