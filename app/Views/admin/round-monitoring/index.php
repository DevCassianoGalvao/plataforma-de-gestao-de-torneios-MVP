<?php
$e = static fn(mixed $v): string => App\Core\View::e($v);
$label = static fn(string $key): string => match($key) {
    'completa' => 'Rodada completa', 'parcial' => 'Parcialmente completa', 'atrasada' => 'Rodada atrasada',
    'pendencia_critica' => 'Pendência crítica', 'sem_partidas' => 'Sem partidas', default => 'Em acompanhamento',
};
?>
<section class="page-stack">
    <div class="section-heading"><div><p class="eyebrow">Operação e documentos</p><h1>Acompanhamento por rodada</h1><p class="muted">Cobertura esportiva, súmulas, evidências, publicação e prazos.</p></div></div>
    <form class="filters" method="get">
        <label>Campeonato<select name="championship_id"><option value="">Todos</option><?php foreach($championships as $championship): ?><option value="<?= (int)$championship['id'] ?>" <?= $filters['championship_id']===(string)$championship['id']?'selected':'' ?>><?= $e($championship['name']) ?></option><?php endforeach ?></select></label>
        <label>Fase<select name="phase_id"><option value="">Todas</option><?php foreach($options['phases'] as $phase): ?><option value="<?= (int)$phase['id'] ?>" <?= $filters['phase_id']===(string)$phase['id']?'selected':'' ?>><?= $e($phase['championship_name'].' · '.$phase['name']) ?></option><?php endforeach ?></select></label>
        <label>Grupo<select name="group_id"><option value="">Todos</option><?php foreach($options['groups'] as $group): ?><option value="<?= (int)$group['id'] ?>" <?= $filters['group_id']===(string)$group['id']?'selected':'' ?>><?= $e($group['phase_name'].' · '.$group['name']) ?></option><?php endforeach ?></select></label>
        <label>Rodada<select name="round_id"><option value="">Todas</option><?php foreach($options['rounds'] as $roundOption): ?><option value="<?= (int)$roundOption['id'] ?>" <?= $filters['round_id']===(string)$roundOption['id']?'selected':'' ?>><?= $e($roundOption['championship_name'].' · '.$roundOption['phase_name'].' · '.$roundOption['group_name'].' · '.$roundOption['round_number']) ?></option><?php endforeach ?></select></label>
        <label>Equipe<select name="team_id"><option value="">Todas</option><?php foreach($options['teams'] as $team): ?><option value="<?= (int)$team['id'] ?>" <?= $filters['team_id']===(string)$team['id']?'selected':'' ?>><?= $e($team['name']) ?></option><?php endforeach ?></select></label>
        <label>Operador<select name="operator_id"><option value="">Todos</option><?php foreach($options['operators'] as $operator): ?><option value="<?= (int)$operator['id'] ?>" <?= $filters['operator_id']===(string)$operator['id']?'selected':'' ?>><?= $e($operator['name']) ?></option><?php endforeach ?></select></label>
        <label>De<input type="date" name="from" value="<?= $e($filters['from']) ?>"></label><label>Até<input type="date" name="to" value="<?= $e($filters['to']) ?>"></label>
        <label>Status documental<select name="document_status"><option value="">Todos</option><option value="complete" <?= $filters['document_status']==='complete'?'selected':'' ?>>Completo</option></select></label>
        <label>Status esportivo<select name="sport_status"><option value="">Todos</option><option value="approved" <?= $filters['sport_status']==='approved'?'selected':'' ?>>Aprovado</option></select></label><label>Status de aprovação<select name="approval_status"><option value="">Todos</option><option value="pending" <?= $filters['approval_status']==='pending'?'selected':'' ?>>Pendente</option></select></label><label>Status de publicação<select name="publication_status"><option value="">Todos</option><option value="published" <?= $filters['publication_status']==='published'?'selected':'' ?>>Publicado</option></select></label>
        <label><input type="checkbox" name="only_pending" value="1" <?= $filters['only_pending']?'checked':'' ?>> Somente pendências</label><label><input type="checkbox" name="overdue" value="1" <?= $filters['overdue']?'checked':'' ?>> Prazo vencido</label><button type="submit">Filtrar</button>
    </form>
    <?php if($rounds===[]): ?><article class="panel"><p>Nenhuma rodada encontrada para os filtros.</p></article><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Rodada</th><th>Partidas</th><th>Operação</th><th>Documentos</th><th>Publicação</th><th>Indicador</th><th></th></tr></thead><tbody><?php foreach($rounds as $round): ?><tr>
        <td><strong><?= $e($round['championship_name']) ?></strong><br><small><?= $e($round['phase_name']) ?> · <?= $e($round['group_name']) ?> · Rodada <?= (int)$round['round_number'] ?></small><br><small><?= $e($round['period_start'] ?: 'Data a definir') ?><?= $round['period_end'] ? ' a '.$e($round['period_end']) : '' ?></small></td>
        <td><?= (int)$round['matches_count'] ?> previstas<br><small><?= (int)$round['scheduled_count'] ?> agendadas · <?= (int)$round['in_progress_count'] ?> em andamento · <?= (int)$round['postponed_count'] ?> adiadas</small></td>
        <td><?= (int)$round['approved_count'] ?> aprovadas<br><small><?= (int)$round['review_count'] ?> em revisão · <?= (int)$round['approval_count'] ?> aguardando aprovação · <?= (int)$round['wo_count'] ?> W.O. · <?= (int)$round['events_missing_count'] ?> sem registros</small></td>
        <td><?= (int)$round['reports_generated_count'] ?> súmulas geradas<br><small><?= (int)$round['reports_not_started_count'] ?> não iniciadas · <?= (int)$round['reports_missing_count'] ?> pendentes após encerramento · <?= (int)$round['evidence_missing_count'] ?> evidências pendentes</small></td>
        <td><?= (int)$round['published_count'] ?> publicadas<br><small><?= (int)$round['publication_scheduled_count'] ?> programadas</small></td>
        <td><span class="status status-<?= $e($round['indicator']) ?>"><?= $e($label($round['indicator'])) ?></span><?php if($round['is_overdue']): ?><br><small class="error">Prazo vencido</small><?php elseif($round['deadline_at']): ?><br><small>Limite: <?= $e($round['deadline_at']) ?></small><?php endif ?></td>
        <td><a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/rodadas/'.$round['id'].'/acompanhamento')) ?>">Abrir</a></td>
    </tr><?php endforeach ?></tbody></table></div><?php endif ?>
</section>
