<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\MigrationRunner;
use App\Repositories\MatchPublicationRepository;
use App\Services\AuditService;
use App\Services\MatchPublicationService;
use App\Repositories\BackupRepository;
use App\Repositories\BackupSettingsRepository;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupProvider;
use App\Core\Config;
use App\Database\AuthSeed;
use App\Database\AthleteDocumentTypeSeed;
use App\Database\AthleteSeed;
use App\Database\ChampionshipSeed;
use App\Database\CopaBrasilTalentos2026Seed;
use App\Database\PositionSeed;
use App\Database\PortalEngagementSeed;
use App\Database\RegistrationSeed;
use App\Database\ScheduleSeed;
use App\Database\LineupSeed;
use App\Database\MatchLineupDemoSeed;
use App\Database\MatchOperationSeed;
use App\Database\DisciplineSeed;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;
use App\Database\NewsSeed;
use App\Database\TransferSeed;
use App\Database\TournamentProgressSeed;

$command = $argv[1] ?? 'help';
$runner = new MigrationRunner(Database::connection(), dirname(__DIR__) . '/database/migrations');

if ($command === 'migrate') {
    foreach ($runner->migrate() as $migration) {
        echo "Applied {$migration}\n";
    }
    echo "Migrations concluídas.\n";
    exit(0);
}

if ($command === 'migrate:status') {
    foreach ($runner->status() as $row) {
        echo $row['status'] . "\t" . $row['migration'] . "\n";
    }
    exit(0);
}

if ($command === 'db:seed') {
    $password = getenv('SEED_DEMO_PASSWORD') ?: '';
    if ($password === '') {
        fwrite(STDERR, "SEED_DEMO_PASSWORD e obrigatoria para o seed.\n");
        exit(1);
    }
    AuthSeed::run(Database::connection(), $password);
    ChampionshipSeed::run(Database::connection());
    TacticalFormationSeed::run(Database::connection());
    TeamSeed::run(Database::connection());
    PositionSeed::run(Database::connection());
    AthleteDocumentTypeSeed::run(Database::connection());
    AthleteSeed::run(Database::connection());
    RegistrationSeed::run(Database::connection());
    ScheduleSeed::run(Database::connection());
    LineupSeed::run(Database::connection());
    MatchOperationSeed::run(Database::connection());
    DisciplineSeed::run(Database::connection());
    NewsSeed::run(Database::connection());
    TransferSeed::run(Database::connection());
    PortalEngagementSeed::run(Database::connection());
    echo "Seed de autenticacao concluido.\n";
    exit(0);
}

if ($command === 'db:seed:copa-brasil-2026') {
    $pdo = Database::connection();
    $adminExists = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@torneios.local'")->fetchColumn() > 0;
    if (!$adminExists) {
        $password = getenv('SEED_DEMO_PASSWORD') ?: '';
        if ($password === '') {
            fwrite(STDERR, "SEED_DEMO_PASSWORD e obrigatoria para criar o administrador inicial.\n");
            exit(1);
        }
        AuthSeed::run($pdo, $password);
    }
    TacticalFormationSeed::run($pdo);
    AthleteDocumentTypeSeed::run($pdo);
    $result = CopaBrasilTalentos2026Seed::run($pdo);
    echo 'COPA_BRASIL_2026_OK championship_id=' . $result['championship_id'] . ' regulation_id=' . $result['regulation_id'] . ' teams=' . count($result['team_ids']) . "\n";
    exit(0);
}

if ($command === 'matches:publish-due') {
    $pdo = Database::connection();
    $result = (new MatchPublicationService(new MatchPublicationRepository($pdo), new AuditService($pdo)))->publishDue();
    echo "MATCH_PUBLICATION_DUE_OK published={$result['published']} at={$result['at']}\n";
    exit(0);
}

if ($command === 'backup:run') {
    $pdo = Database::connection();
    $settings = (new BackupSettingsRepository($pdo))->get();
    $remote = ($settings['provider'] ?? 'local') === 'google_drive' ? new GoogleDriveBackupProvider((string) ($settings['google_drive_folder_id'] ?? '')) : null;
    $backup = (new BackupService(new BackupRepository($pdo), new AuditService($pdo), $remote))->run(null, 'scheduled');
    echo 'BACKUP_OK id=' . $backup['id'] . ' status=' . $backup['status'] . "\n";
    exit(0);
}

if ($command === 'backup:schedule') {
    $pdo = Database::connection();
    $settings = (new BackupSettingsRepository($pdo))->get();
    if (empty($settings['schedule_enabled'])) {
        echo "BACKUP_SCHEDULE_DISABLED\n";
        exit(0);
    }
    $now = date('H:i');
    if ($now < (string) $settings['schedule_time']) {
        echo 'BACKUP_SCHEDULE_WAITING now=' . $now . ' at=' . $settings['schedule_time'] . "\n";
        exit(0);
    }
    $latest = (new BackupRepository($pdo))->latestCompleted();
    $interval = max(1, (int) ($settings['schedule_interval_days'] ?? 1));
    $lastRun = strtotime((string) ($latest['completed_at'] ?? ''));
    $scheduleBase = $lastRun === false ? false : strtotime(date('Y-m-d', $lastRun) . ' ' . (string) $settings['schedule_time'] . ':00');
    $nextRun = $scheduleBase === false ? 0 : strtotime('+' . $interval . ' days', $scheduleBase);
    if ($lastRun !== false && $nextRun !== false && time() < $nextRun) {
        echo 'BACKUP_SCHEDULE_WAITING_INTERVAL next=' . date('Y-m-d H:i', $nextRun) . "\n";
        exit(0);
    }
    $remote = ($settings['provider'] ?? 'local') === 'google_drive' ? new GoogleDriveBackupProvider((string) ($settings['google_drive_folder_id'] ?? '')) : null;
    $backup = (new BackupService(new BackupRepository($pdo), new AuditService($pdo), $remote))->run(null, 'scheduled');
    echo 'BACKUP_SCHEDULE_OK id=' . $backup['id'] . ' status=' . $backup['status'] . "\n";
    exit(0);
}

if ($command === 'db:seed:simulation') {
    TournamentProgressSeed::run(Database::connection());
    MatchLineupDemoSeed::run(Database::connection());
    echo "SIMULATION_OK fase_de_grupos=concluida quartas=concluidas semifinais=agendadas escalacoes=confirmadas\n";
    exit(0);
}

if ($command === 'db:seed:simulation-lineups') {
    MatchLineupDemoSeed::run(Database::connection());
    echo "SIMULATION_LINEUPS_OK semifinais=escalacoes_confirmadas\n";
    exit(0);
}

echo "Comandos: migrate | migrate:status | matches:publish-due | backup:run | backup:schedule | db:seed | db:seed:copa-brasil-2026 | db:seed:simulation | db:seed:simulation-lineups\n";
exit($command === 'help' ? 0 : 1);
