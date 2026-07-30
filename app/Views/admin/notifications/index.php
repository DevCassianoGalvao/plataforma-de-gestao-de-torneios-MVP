<?php $e = static fn (mixed $value): string => App\Core\View::e($value); $csrf = App\Core\Security::csrfToken(); ?>
<section>
    <div class="section-heading">
        <div><p class="eyebrow">Acompanhamento do sistema</p><h1>Central de notificacoes</h1><p>Eventos recentes de campeonatos, partidas, usuarios, conteudo e seguranca.</p></div>
        <?php if (($unreadCount ?? 0) > 0): ?><form method="post" action="<?= $e(App\Core\Config::url('/admin/notificacoes/ler-todas')) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="button secondary" type="submit">Marcar tudo como lido</button></form><?php endif; ?>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <div class="notification-list">
        <?php foreach ($items as $item): ?><article class="panel notification-item <?= empty($item['read_at']) ? 'is-unread' : '' ?>"><div><p class="eyebrow"><?= $e($item['created_at']) ?> · <?= $e($item['actor_name'] ?: 'Sistema') ?></p><h2><?= $e($item['title']) ?></h2><p><?= $e($item['message']) ?></p><small><?= $e($item['action']) ?><?= $item['resource_type'] ? ' · ' . $e($item['resource_type']) . ' #' . (int) $item['resource_id'] : '' ?></small></div><?php if (empty($item['read_at'])): ?><form method="post" action="<?= $e(App\Core\Config::url('/admin/notificacoes/' . $item['id'] . '/ler')) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="button secondary" type="submit">Marcar como lida</button></form><?php else: ?><span class="status status-approved">Lida</span><?php endif; ?></article><?php endforeach; ?>
        <?php if ($items === []): ?><article class="panel empty-state"><h2>Nenhuma notificacao</h2><p>Novas atividades do sistema aparecerao aqui.</p></article><?php endif; ?>
    </div>
</section>
