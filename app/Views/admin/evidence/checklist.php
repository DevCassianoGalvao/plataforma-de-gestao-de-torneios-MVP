<?php
$e = static fn (mixed $v): string => App\Core\View::e($v);
$csrf = App\Core\Security::csrfToken();
$moments = [
    'before_match' => 'Antes da partida',
    'during_match' => 'Durante a partida',
    'after_match' => 'Depois da partida',
    'final_documentation' => 'Documentação final',
    'event_day' => 'Dia do evento',
];
?>
<section class="page-stack evidence-checklist-page">
    <div class="section-heading">
        <div>
            <p class="eyebrow"><?= $e($championship['name']) ?></p>
            <h1>Checklist de evidências</h1>
            <p>Configure o que o operador precisa enviar e o que ficará disponível na prestação de contas.</p>
        </div>
        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'])) ?>">Voltar ao campeonato</a>
    </div>
    <?php if ($message): ?><p class="success"><?= $e($message) ?></p><?php endif; ?>
    <article class="panel evidence-flow-note">
        <div class="evidence-flow-icon" data-icon="file-check-2" aria-hidden="true"></div>
        <div><h2>Como a configuração chega aos perfis</h2><p><strong>Administrador:</strong> configura os itens. <strong>Operador:</strong> vê somente itens ativos no dia de evento ou na partida correspondente. <strong>Prestação de contas:</strong> recebe apenas arquivos aprovados e marcados para exibição.</p><p class="muted">Marcar um item como obrigatório não cria o dia de evento. Cadastre a data em <strong>Dias de evento</strong> para liberar os envios de equipe de trabalho, arbitragem e público.</p></div>
    </article>
    <article class="panel">
        <p class="eyebrow">Modelo recomendado</p>
        <h2>Prestação de contas esportiva</h2>
        <p>Aplica sete itens configuráveis: equipes perfiladas, três fotos do jogo, súmula, equipe de trabalho, arbitragem e duas fotos do público.</p>
        <p class="muted">O modelo só pode ser aplicado quando o campeonato ainda não possui itens ativos. Depois, você pode editar nomes, formatos, limites e obrigatoriedade.</p>
        <form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/modelo-futebol')) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <button>Aplicar modelo completo</button>
        </form>
    </article>
    <article class="panel">
        <h2>Novo item</h2>
        <form method="post" class="form-grid" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias')) ?>">
            <?php include __DIR__ . '/form.php'; ?>
            <button>Adicionar item</button>
        </form>
    </article>
    <article class="panel">
        <h2>Organizar e duplicar</h2>
        <form method="post" class="inline-form" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/reordenar')) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <?php foreach ($items as $item): if (!$item['deleted_at']): ?>
                <label><?= $e($item['name']) ?><input type="number" min="1" name="order[<?= $e($item['id']) ?>]" value="<?= $e($item['display_order']) ?>"></label>
            <?php endif; endforeach; ?>
            <button class="secondary">Salvar ordem</button>
        </form>
        <form method="post" class="inline-form" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/duplicar')) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <label>Copiar itens de<select name="source_championship_id" required><option value="">Selecione</option><?php foreach ($championships as $source): if ((int) $source['id'] !== (int) $championship['id']): ?><option value="<?= $e($source['id']) ?>"><?= $e($source['name']) ?></option><?php endif; endforeach; ?></select></label>
            <button class="secondary">Duplicar checklist</button>
        </form>
    </article>
    <?php foreach ($items as $item): ?>
        <article class="panel evidence-item-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><span class="evidence-item-icon" data-icon="file-check-2" aria-hidden="true"></span><?= $e($moments[$item['expected_moment']] ?? $item['expected_moment']) ?> · <?= $item['scope'] === 'event_day' ? 'dia de evento' : 'partida' ?> · <?= (int) $item['usage_count'] ?> uso(s)</p>
                    <h2><?= $e($item['name']) ?> <?= $item['deleted_at'] ? '(removido)' : '' ?></h2>
                    <p><?= $e($item['description']) ?></p>
                    <div class="evidence-status-list"><span class="status-chip <?= $item['is_active'] ? 'status-chip-success' : '' ?>"><?= $item['is_active'] ? 'Ativo para o operador' : 'Inativo' ?></span><span class="status-chip <?= $item['is_required'] ? 'status-chip-warning' : '' ?>"><?= $item['is_required'] ? 'Obrigatório' : 'Opcional' ?></span><span class="status-chip <?= $item['show_in_accountability'] ? 'status-chip-success' : '' ?>"><?= $item['show_in_accountability'] ? 'Visível na prestação' : 'Não exibido na prestação' ?></span></div>
                </div>
            </div>
            <?php if (!$item['deleted_at']): ?>
                <form method="post" class="form-grid" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="item_id" value="<?= $e($item['id']) ?>">
                    <?php $editing = $item; include __DIR__ . '/form.php'; ?>
                    <button>Salvar alterações</button>
                </form>
                <div class="button-row">
                    <form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/' . $item['id'] . '/situacao')) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="active" value="<?= $item['is_active'] ? '0' : '1' ?>"><button class="secondary"><?= $item['is_active'] ? 'Desativar' : 'Ativar' ?></button></form>
                    <form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/' . $item['id'] . '/excluir')) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="secondary">Remover</button></form>
                </div>
            <?php else: ?>
                <form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/evidencias/' . $item['id'] . '/restaurar')) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button>Restaurar item</button></form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
