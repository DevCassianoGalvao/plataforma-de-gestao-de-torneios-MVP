<?php
$e = static fn (mixed $value): string => App\Core\View::e($value);
?>
<section class="page-stack retention-page">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Governança de dados</p>
            <h1>Retenção e arquivamento</h1>
            <p class="muted">Arquive e restaure registros com histórico. A exclusão definitiva fica separada e exige uma confirmação explícita.</p>
        </div>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>

    <div class="panel-grid">
        <?php foreach ($policies as $policy): ?>
            <article class="panel">
                <h2><?= $e($policy['name']) ?></h2>
                <form method="post" action="<?= $e(App\Core\Config::url('/admin/retencao/' . $policy['scope_key'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
                    <label>Prazo em dias (vazio = sem expiração)<input type="number" min="1" max="36500" name="retention_days" value="<?= $e($policy['retention_days'] ?? '') ?>"></label>
                    <label class="check"><input type="checkbox" name="allow_archive" value="1" <?= !empty($policy['allow_archive']) ? 'checked' : '' ?>> Permitir arquivamento</label>
                    <label class="check"><input type="checkbox" name="allow_restore" value="1" <?= !empty($policy['allow_restore']) ? 'checked' : '' ?>> Permitir restauração</label>
                    <label class="check"><input type="checkbox" name="allow_soft_delete" value="1" <?= !empty($policy['allow_soft_delete']) ? 'checked' : '' ?>> Permitir exclusão lógica</label>
                    <p class="muted"><?= !empty($policy['protected']) ? 'Classe protegida: exclusão permanente bloqueada.' : (!empty($policy['allow_hard_delete']) ? 'Exclusão definitiva habilitada para a limpeza esportiva.' : 'Exclusão definitiva indisponível por padrão.') ?></p>
                    <button type="submit">Salvar política</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <article class="panel purge-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Ação irreversível</p>
                <h2>Excluir dados definitivamente</h2>
                <p class="muted">Use para remover dados de teste antes de começar um campeonato real. Dependências esportivas, documentos e arquivos vinculados serão removidos na mesma transação. Logs de auditoria e usuários são preservados.</p>
            </div>
        </div>
        <div class="alert purge-warning" role="note"><strong>Atenção:</strong> esta ação não pode ser desfeita. Selecione apenas dados que realmente devem sair do sistema.</div>
        <div class="purge-grid">
            <?php foreach (($purgeOptions ?? []) as $entity => $group): ?>
                <form class="purge-card" method="post" action="<?= $e(App\Core\Config::url('/admin/retencao/excluir-definitivamente')) ?>" data-purge-form onsubmit="return confirm('A exclusão será definitiva e removerá os vínculos selecionados. Continuar?')">
                    <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
                    <input type="hidden" name="entity" value="<?= $e($entity) ?>">
                    <div class="purge-card-heading"><h3><?= $e($group['label']) ?></h3><span><?= count($group['records']) ?> disponível(is)</span></div>
                    <?php if (empty($group['records'])): ?>
                        <p class="muted">Nenhum registro cadastrado.</p>
                    <?php else: ?>
                        <div class="purge-record-list">
                            <?php foreach ($group['records'] as $record): ?>
                                <label class="checkbox-row purge-record">
                                    <input type="checkbox" name="ids[]" value="<?= (int) $record['id'] ?>">
                                    <span><strong><?= $e($record['display_name']) ?></strong><small><?= $e($record['status_label']) ?><?= !empty($record['archived']) ? ' · arquivado' : '' ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <label>Motivo da exclusão<textarea name="reason" rows="2" maxlength="1000" required placeholder="Ex.: remoção dos dados fictícios antes do início da operação real"></textarea></label>
                        <label>Digite <code><?= $e($confirmationPhrase) ?></code> para confirmar<input name="confirmation" required autocomplete="off" spellcheck="false"></label>
                        <p class="purge-selection-summary" data-purge-summary>0 selecionados</p>
                        <button class="button-danger" type="submit" disabled data-purge-submit>Excluir selecionados definitivamente</button>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <h2>Histórico de ações</h2>
        <div class="table-wrap"><table><thead><tr><th>Data</th><th>Registro</th><th>Ação</th><th>Motivo</th><th>Usuário</th></tr></thead><tbody>
            <?php foreach ($actions as $action): ?>
                <tr><td><?= $e($action['created_at']) ?></td><td><?= $e($action['entity_type']) ?> #<?= (int) $action['entity_id'] ?></td><td><?= $e($action['action']) ?></td><td><?= $e($action['reason']) ?></td><td><?= $e($action['user_name']) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </article>
</section>
