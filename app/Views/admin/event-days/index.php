<?php
declare(strict_types=1);
$e = static fn (mixed $value): string => App\Core\View::e($value);
$championshipId = (int) ($championship['id'] ?? 0);
$base = App\Core\Config::url('/admin/dias-evento');
?>
<section class="page-stack event-days-page">
    <div class="section-heading">
        <div><p class="eyebrow"><span class="section-heading-icon" data-icon="calendar-days" aria-hidden="true"></span>Prestação de contas</p><h1>Dias de evento</h1><p>Cadastre cada data do campeonato para liberar o envio das fotos de trabalho, arbitragem e público.</p></div>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <article class="panel event-days-guide"><div class="event-days-guide-icon" data-icon="camera" aria-hidden="true"></div><div><h2>Fluxo das evidências do dia</h2><p>1. Cadastre a data e o local. 2. O operador escolhe o tipo de evidência no envio. 3. O administrador aprova os arquivos. 4. A prestação de contas baixa somente o que foi aprovado.</p><p class="muted">Os itens precisam estar ativos no checklist do campeonato. Se não houver um dia cadastrado, eles não aparecem para o operador.</p></div></article>
    <form method="get" class="filters">
        <label>Campeonato
            <select name="championship_id" onchange="this.form.submit()">
                <?php foreach ($championships as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) $item['id'] === $championshipId ? 'selected' : '' ?>><?= $e($item['name']) ?></option><?php endforeach; ?>
            </select>
        </label>
    </form>
    <?php if ($championshipId && !empty($canManage)): ?>
    <div class="panel">
        <p class="eyebrow">Novo registro</p><h2>Cadastrar dia de evento</h2>
        <form method="post" action="<?= $e($base) ?>" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="championship_id" value="<?= $championshipId ?>">
            <label>Data <input type="date" name="event_date" required></label>
            <label>Nome do dia <input name="name" placeholder="Ex.: Abertura e rodada 1"></label>
            <label>Local
                <select name="venue_id"><option value="0">Vários locais / não definido</option><?php foreach ($venues as $venue): ?><option value="<?= (int) $venue['id'] ?>"><?= $e($venue['name'] . (!empty($venue['city']) ? ' - ' . $venue['city'] : '')) ?></option><?php endforeach; ?></select>
            </label>
            <label class="form-grid-full">Observações <textarea name="notes" rows="2"></textarea></label>
            <button type="submit">Cadastrar dia</button>
        </form>
    </div>
    <?php endif; ?>
    <?php foreach ($eventDays as $day): ?>
    <article class="panel event-day-panel">
        <div class="section-heading"><div><p class="eyebrow"><?= $e($day['event_date']) ?></p><h2><?= $e($day['name'] ?: 'Dia de evento') ?></h2><p><?= $e($day['venue_name'] ? $day['venue_name'] . (!empty($day['venue_city']) ? ' - ' . $day['venue_city'] : '') : 'Vários locais ou local não definido') ?> · <?= (int) $day['media_count'] ?> evidência(s)</p></div>
            <?php if (!empty($canManage)): ?><form method="post" action="<?= $e($base . '/' . (int) $day['id'] . '/excluir') ?>"><input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="championship_id" value="<?= $championshipId ?>"><button class="button-danger" type="submit">Arquivar</button></form><?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data" action="<?= $e($base . '/' . (int) $day['id'] . '/evidencias') ?>" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
            <label>Título <input name="title" placeholder="Ex.: Foto da equipe de trabalho"></label>
            <label>Data e hora da captura <input type="datetime-local" name="captured_at"></label>
            <?php if ($checklist): ?><label>Tipo de evidência <select name="checklist_item_id"><option value="0">Sem tipo específico</option><?php foreach ($checklist as $item): ?><option value="<?= (int) $item['id'] ?>"><?= $e($item['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <label class="form-grid-full">Arquivos <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple required><span class="muted">No celular, escolha arquivos da galeria ou use a câmera para registrar as evidências.</span></label>
            <label class="form-grid-full">Legenda <textarea name="caption" rows="2"></textarea></label>
            <button type="submit">Enviar evidências</button>
        </form>
        <?php if (!empty($day['media'])): ?><div class="table-wrap"><table><thead><tr><th>Arquivo</th><th>Tipo</th><th>Análise</th><th>Ações</th></tr></thead><tbody><?php foreach ($day['media'] as $media): ?><tr><td><?= $e($media['title'] ?: $media['original_name']) ?><br><small><?= $e($media['original_name']) ?></small></td><td><?= $e($media['checklist_name'] ?: 'Dia de evento') ?></td><td><?= $e($media['review_status']) ?></td><td><a href="<?= $e($base . '/' . (int) $day['id'] . '/evidencias/' . (int) $media['id']) ?>">Baixar</a> <?php if ($media['review_status'] !== 'approved'): ?><form method="post" action="<?= $e($base . '/' . (int) $day['id'] . '/evidencias/' . (int) $media['id'] . '/analise') ?>" style="display:inline"><input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="decision" value="approved"><button type="submit">Aprovar</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php if (!$eventDays): ?><div class="panel"><p>Nenhum dia de evento cadastrado neste campeonato.</p><?php if (empty($canManage)): ?><p class="muted">Peça ao administrador para cadastrar a data do evento. Depois disso, você poderá enviar as fotos da equipe de trabalho, arbitragem e público diretamente aqui.</p><?php endif; ?></div><?php endif; ?>
</section>
