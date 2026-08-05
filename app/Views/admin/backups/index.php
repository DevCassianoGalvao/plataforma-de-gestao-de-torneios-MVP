<?php
use App\Core\Config;
use App\Core\Security;

$intervals = [
    1 => 'Todos os dias',
    3 => 'A cada 3 dias',
    7 => 'Uma vez por semana',
    15 => 'A cada 15 dias',
    30 => 'Uma vez por mês',
];
$statusLabels = [
    'running' => 'Em andamento',
    'completed' => 'Concluído',
    'partially_completed' => 'Concluído parcialmente',
    'failed' => 'Falhou',
    'pending' => 'Pendente',
];
$remoteLabels = [
    'completed' => 'Google Drive confirmado',
    'failed' => 'Falha no Google Drive',
    'pending' => 'Aguardando envio',
    'not_configured' => 'Somente local',
];
$typeLabels = ['manual' => 'Manual', 'scheduled' => 'Agendado'];
$selectedInterval = (int) ($settings['schedule_interval_days'] ?? 1);
?>
<section class="page-heading backup-page-heading">
    <div>
        <p class="eyebrow">SEGURANÇA OPERACIONAL</p>
        <h1>Backups da aplicação</h1>
        <p>Cópias privadas do banco e dos arquivos enviados, com histórico e validação antes do armazenamento.</p>
    </div>
    <?php if ($canRun): ?>
        <form method="post" action="<?= Config::url('/admin/backups/executar') ?>" class="backup-run-form">
            <input type="hidden" name="_csrf" value="<?= Security::csrfToken() ?>">
            <button class="button button-primary" type="submit">Criar backup agora</button>
        </form>
    <?php endif; ?>
</section>
<?php if (!empty($_SESSION['flash_success'])): ?><div class="flash flash-success"><?= htmlspecialchars((string) $_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?><div class="flash flash-error"><?= htmlspecialchars((string) $_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

<section class="card backup-config-card">
    <div class="section-heading">
        <div>
            <p class="eyebrow">CONFIGURAÇÃO</p>
            <h2>Destino e agendamento</h2>
            <p class="muted">Escolha onde cada cópia será guardada e de quanto em quanto tempo o servidor deve criar uma nova.</p>
        </div>
        <span class="backup-config-status <?= $remoteConfigured && $tokenConfigured ? 'is-ready' : '' ?>">
            <?= $remoteConfigured && $tokenConfigured ? 'Google Drive pronto para teste' : ($remoteConfigured ? 'Google Drive pendente' : 'Armazenamento local ativo') ?>
        </span>
    </div>

    <?php if ($remoteConfigured && !$tokenConfigured): ?>
        <aside class="backup-secret-notice backup-secret-notice-warning">
            <strong>Token do Google Drive ainda não configurado</strong>
            <p>O token não é inserido nesta tela. No cPanel, abra o arquivo .env na pasta do sistema e preencha:</p>
            <code class="backup-env-line">GOOGLE_DRIVE_ACCESS_TOKEN=cole_aqui_o_token</code>
            <p class="muted">Não publique esse valor, não o coloque no Git e compartilhe a pasta do Drive com a conta autorizada pelo token. Depois, volte e use “Testar conexão”.</p>
        </aside>
    <?php elseif ($remoteConfigured && $tokenConfigured): ?>
        <aside class="backup-secret-notice">
            <strong>Token encontrado no ambiente</strong>
            <p>O segredo está protegido no .env. Ele nunca é exibido nem salvo no banco.</p>
        </aside>
    <?php endif; ?>

    <?php if ($canConfigure): ?>
        <form method="post" action="<?= Config::url('/admin/backups/configuracao') ?>" class="form-grid backup-config-form">
            <input type="hidden" name="_csrf" value="<?= Security::csrfToken() ?>">
            <label>Destino
                <select name="provider">
                    <option value="local" <?= ($settings['provider'] ?? 'local') === 'local' ? 'selected' : '' ?>>Armazenamento local seguro</option>
                    <option value="google_drive" <?= ($settings['provider'] ?? '') === 'google_drive' ? 'selected' : '' ?>>Google Drive</option>
                </select>
            </label>
            <label class="full-width">Link da pasta no Google Drive
                <input type="url" name="google_drive_folder_link" value="<?= Security::escape((string) ($settings['google_drive_folder_link'] ?? '')) ?>" placeholder="https://drive.google.com/drive/folders/..." inputmode="url">
                <small class="muted">Cole o link da pasta. O sistema extrai apenas o identificador; o link não concede acesso sozinho.</small>
            </label>
            <label class="checkbox-row full-width">
                <input type="checkbox" name="schedule_enabled" value="1" <?= !empty($settings['schedule_enabled']) ? 'checked' : '' ?>>
                <span>Ativar backup automático</span>
            </label>
            <label>Periodicidade
                <select name="schedule_interval_days">
                    <?php foreach ($intervals as $days => $label): ?>
                        <option value="<?= $days ?>" <?= $selectedInterval === $days ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Horário de execução
                <input type="time" name="schedule_time" value="<?= Security::escape((string) ($settings['schedule_time'] ?? '03:00')) ?>">
            </label>
            <div class="actions-row full-width">
                <button class="button button-primary" type="submit">Salvar configuração</button>
                <?php if ($remoteConfigured): ?>
                    <button class="button button-secondary" type="submit" formaction="<?= Config::url('/admin/backups/testar-conexao') ?>">Testar conexão</button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
    <p class="muted backup-cron-note">O agendamento depende de uma tarefa cron do cPanel executando php bin/console.php backup:schedule a cada cinco minutos. O sistema respeita o horário e a periodicidade escolhidos.</p>
</section>

<section class="card backup-history-card">
    <div class="section-heading">
        <div>
            <p class="eyebrow">HISTÓRICO</p>
            <h2>Cópias recentes</h2>
            <p class="muted">Baixe uma cópia validada ou exclua-a do armazenamento local e remoto.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Tipo</th><th>Situação</th><th>Arquivo</th><th>Destino remoto</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><?= Security::escape((string) ($backup['completed_at'] ?: $backup['started_at'])) ?></td>
                    <td><?= Security::escape($typeLabels[(string) $backup['type']] ?? 'Backup') ?></td>
                    <td><span class="status status-<?= Security::escape((string) $backup['status']) ?>"><?= Security::escape($statusLabels[(string) $backup['status']] ?? 'Não informado') ?></span></td>
                    <td><?= number_format((int) $backup['size_bytes'], 0, ',', '.') ?> bytes</td>
                    <td><?= Security::escape($remoteLabels[(string) $backup['remote_status']] ?? 'Não informado') ?></td>
                    <td class="table-actions">
                        <?php if (!empty($backup['local_path'])): ?><a class="button button-secondary button-small" href="<?= Config::url('/admin/backups/' . $backup['id'] . '/download') ?>">Baixar</a><?php endif; ?>
                        <?php if (($backup['remote_status'] ?? '') === 'failed'): ?><form method="post" action="<?= Config::url('/admin/backups/' . $backup['id'] . '/reenviar') ?>"><input type="hidden" name="_csrf" value="<?= Security::csrfToken() ?>"><button class="button button-secondary button-small" type="submit">Tentar envio novamente</button></form><?php endif; ?>
                        <form method="post" action="<?= Config::url('/admin/backups/' . $backup['id'] . '/excluir') ?>" onsubmit="return confirm('Excluir este backup local e remoto?')"><input type="hidden" name="_csrf" value="<?= Security::csrfToken() ?>"><button class="button button-danger button-small" type="submit">Excluir</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$backups): ?><tr><td colspan="6">Nenhum backup registrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
