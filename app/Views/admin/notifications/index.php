<?php
declare(strict_types=1);

$e = static fn (mixed $value): string => App\Core\View::e($value);
$csrf = App\Core\Security::csrfToken();
$formatDate = static function (?string $value): string {
    if (!$value) return 'Agora';
    try { return (new DateTimeImmutable($value))->format('d/m/Y · H:i'); } catch (Throwable) { return $value; }
};
$activity = static function (array $item): array {
    $action = (string) ($item['action'] ?? '');
    $actor = (string) ($item['actor_name'] ?: 'Sistema');
    $resource = (string) ($item['resource_type'] ?? '');
    $module = [
        'athlete' => ['Atletas', 'user-round'], 'athlete_guardian' => ['Responsáveis', 'users-round'], 'athlete_document' => ['Documentos', 'file-check-2'],
        'athlete_registration' => ['Inscrições', 'file-check-2'], 'team' => ['Equipes', 'shield'], 'team_staff' => ['Comissões técnicas', 'users-round'],
        'championship' => ['Campeonatos', 'trophy'], 'competition_phase' => ['Campeonatos', 'trophy'], 'competition_group' => ['Campeonatos', 'trophy'],
        'match' => ['Partidas', 'calendar-days'], 'match_lineup' => ['Escalações', 'clipboard-check'], 'match_operation_event' => ['Partidas', 'calendar-days'],
        'match_substitution' => ['Partidas', 'calendar-days'], 'match_media' => ['Partidas', 'calendar-days'], 'match_report_version' => ['Súmulas', 'file-check-2'],
        'match_report_package' => ['Súmulas', 'file-check-2'], 'news_article' => ['Notícias', 'newspaper'], 'transfer_movement' => ['Vai e Vem', 'arrow-left-right'],
        'discipline_suspension' => ['Disciplina', 'shield-alert'], 'championship_official' => ['Arbitragem', 'calendar-days'], 'championship_sponsor' => ['Parceiros', 'trophy'],
        'user' => ['Usuários', 'users-round'], 'public_contact_message' => ['Contatos', 'bell'], 'permission' => ['Segurança', 'shield-alert'],
    ];
    [$category, $icon] = $module[$resource] ?? ['Sistema', 'scan-line'];
    $verbs = ['created' => 'Novo registro', 'updated' => 'Atualização realizada', 'changed' => 'Status atualizado', 'deleted' => 'Registro removido', 'approved' => 'Registro aprovado', 'rejected' => 'Registro rejeitado', 'reviewed' => 'Documento analisado', 'uploaded' => 'Arquivo enviado', 'published' => 'Conteúdo publicado', 'confirmed' => 'Confirmação registrada', 'saved' => 'Informações salvas', 'exported' => 'Arquivo exportado', 'homologated' => 'Resultado homologado'];
    $parts = explode('.', $action);
    $verb = end($parts) ?: '';
    if (str_starts_with($action, 'auth.login')) return ['Acesso', 'shield', 'Acesso realizado', $actor . ' entrou na plataforma.'];
    if (str_starts_with($action, 'auth.')) return ['Segurança', 'shield-alert', 'Atividade de segurança', $actor . ' concluiu uma ação de acesso.'];
    $title = $verbs[$verb] ?? 'Atividade registrada';
    $message = trim((string) ($item['message'] ?? ''));
    if ($message === '' || str_starts_with($message, 'Evento registrado:')) {
        $message = $resource !== '' ? $actor . ' registrou uma atualização em ' . strtolower($category) . '.' : $actor . ' registrou uma atualização no sistema.';
    }
    return [$category, $icon, $title, $message];
};
?>
<section class="notification-center">
    <header class="notification-center-header">
        <div class="notification-center-copy">
            <p class="eyebrow">Acompanhamento do sistema</p>
            <h1>Central de notificações</h1>
            <p>Atualizações relevantes da operação, conteúdo e segurança.</p>
        </div>
        <div class="notification-center-summary" aria-label="Resumo de notificações">
            <span>Não lidas</span>
            <strong><?= (int) ($unreadCount ?? 0) ?></strong>
            <?php if (($unreadCount ?? 0) > 0): ?>
                <form method="post" action="<?= $e(App\Core\Config::url('/admin/notificacoes/ler-todas')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="button secondary" type="submit" data-icon="clipboard-check">Marcar todas como lidas</button>
                </form>
            <?php else: ?><small>Tudo em dia</small><?php endif; ?>
        </div>
    </header>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>

    <div class="notification-feed-heading">
        <div><h2>Atividades recentes</h2><p><?= count($items) ?> eventos mais recentes</p></div>
        <span class="notification-feed-live"><i></i> Atualizado ao registrar ações</span>
    </div>

    <div class="notification-list" aria-live="polite">
        <?php foreach ($items as $item): ?>
            <?php [$category, $icon, $title, $description] = $activity($item); $unread = empty($item['read_at']); ?>
            <article class="notification-item <?= $unread ? 'is-unread' : 'is-read' ?>">
                <div class="notification-item-icon" data-icon="<?= $e($icon) ?>" aria-hidden="true"></div>
                <div class="notification-item-content">
                    <div class="notification-item-meta"><span><?= $e($category) ?></span><time datetime="<?= $e($item['created_at']) ?>"><?= $e($formatDate($item['created_at'])) ?></time></div>
                    <h3><?= $e($title) ?></h3>
                    <p><?= $e($description) ?></p>
                    <small>Por <?= $e($item['actor_name'] ?: 'Sistema') ?></small>
                </div>
                <div class="notification-item-action">
                    <?php if ($unread): ?>
                        <form method="post" action="<?= $e(App\Core\Config::url('/admin/notificacoes/' . $item['id'] . '/ler')) ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="icon-button notification-mark-read" type="submit" data-icon="clipboard-check" aria-label="Marcar como lida" title="Marcar como lida">Marcar como lida</button>
                        </form>
                    <?php else: ?><span class="notification-read-state" title="Notificação lida"><span data-icon="clipboard-check" aria-hidden="true"></span><span>Lida</span></span><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <article class="notification-empty-state"><span data-icon="bell" aria-hidden="true"></span><h2>Nenhuma notificação por enquanto</h2><p>Novas atividades aparecerão aqui.</p></article>
        <?php endif; ?>
    </div>
</section>
