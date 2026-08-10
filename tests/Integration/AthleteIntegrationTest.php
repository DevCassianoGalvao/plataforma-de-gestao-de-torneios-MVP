<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\AthleteDocumentTypeSeed;
use App\Database\AthleteSeed;
use App\Database\PositionSeed;
use App\Repositories\AthleteRepository;
use App\Repositories\AthleteDocumentRepository;
use App\Repositories\GuardianRepository;
use App\Repositories\PositionRepository;
use App\Repositories\UserRepository;
use App\Services\AthleteAccessService;
use App\Services\AuthorizationService;
use App\Services\StorageService;
use App\Repositories\ChampionshipRepository;
use App\Repositories\TeamRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class AthleteIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        PositionSeed::run($pdo);
        AthleteDocumentTypeSeed::run($pdo);
        AthleteSeed::run($pdo);
        AthleteSeed::run($pdo);
        assert_same(13, (int) $pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn(), 'Seed duplicou posicoes');
        assert_same(6, (int) $pdo->query('SELECT COUNT(*) FROM athlete_document_types')->fetchColumn(), 'Seed duplicou tipos de documento');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM athletes')->fetchColumn(), 'Seed duplicou atletas');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM legal_guardians')->fetchColumn(), 'Seed duplicou responsaveis');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM athlete_guardians')->fetchColumn(), 'Seed duplicou vinculos de responsaveis');
        assert_same(40, (int) $pdo->query('SELECT COUNT(*) FROM athlete_documents')->fetchColumn(), 'Seed duplicou documentos');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM athlete_secondary_positions')->fetchColumn(), 'Seed duplicou posicoes secundarias');

        $athletes = new AthleteRepository($pdo);
        $positions = new PositionRepository($pdo);
        $guardians = new GuardianRepository($pdo);
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local'")->fetchColumn();
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $positionId = (int) $pdo->query("SELECT id FROM positions WHERE code = 'center_forward'")->fetchColumn();
        assert_true($positions->find($positionId) !== null, 'Catalogo de posicoes nao carregou');
        assert_true($athletes->duplicateExists($teamId, 'Atleta Estrela Norte 1', '2012-03-01'), 'Duplicidade de atleta nao identificada');
        assert_true(!$athletes->duplicateExists($teamId, 'Atleta Novo', '2012-03-01'), 'Falso positivo de duplicidade');
        $first = $athletes->findForUser(1, $adminId, 'administrator');
        assert_true($first !== null && (int) $first['age'] >= 12 && (int) $first['age'] <= 15, 'Idade de atleta seed incorreta');
        assert_true(count($athletes->secondaryPositions((int) $first['id'])) === 1, 'Posicao secundaria ausente');
        assert_true(count($guardians->listForAthlete((int) $first['id'])) === 1, 'Responsavel legal ausente');
        $guardianRow = $guardians->listForAthlete((int) $first['id'])[0];
        assert_true($guardianRow['document_display'] === 'Documento protegido', 'Documento pessoal exposto');
        $documents = new AthleteDocumentRepository($pdo);
        assert_true($documents->hasValidAthleteDocument((int) $first['id']), 'Documento de identificacao aprovado nao foi reconhecido');
        $latestIdentity = $pdo->prepare("SELECT d.id FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id WHERE d.athlete_id = ? AND dt.`key` = 'athlete_document' AND d.deleted_at IS NULL ORDER BY d.created_at DESC, d.id DESC LIMIT 1");
        $latestIdentity->execute([(int) $first['id']]);
        $latestIdentityId = (int) $latestIdentity->fetchColumn();
        $pdo->prepare("UPDATE athlete_documents SET status = 'pending' WHERE id = ?")->execute([$latestIdentityId]);
        assert_true(!$documents->hasValidAthleteDocument((int) $first['id']), 'Documento pendente mais recente nao bloqueou atleta');
        $pdo->prepare("UPDATE athlete_documents SET status = 'approved' WHERE id = ?")->execute([$latestIdentityId]);
        $ciphertext = (string) $pdo->query('SELECT document_ciphertext FROM legal_guardians LIMIT 1')->fetchColumn();
        assert_true(!str_contains($ciphertext, 'DOC-'), 'Documento pessoal armazenado em texto puro');

        $users = new UserRepository($pdo);
        $authorization = new AuthorizationService($users);
        $teamRepository = new TeamRepository($pdo);
        $championships = new ChampionshipRepository($pdo);
        $access = new AthleteAccessService($athletes, $teamRepository, $championships, $authorization);
        $admin = $users->findByEmail('admin@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $outsider = $users->findByEmail('treinador-sem-equipe@torneios.local');
        assert_same(20, count($access->list($admin)), 'Administrador nao ve todos os atletas');
        assert_same(10, count($access->list($trainer)), 'Treinador recebeu escopo inesperado');
        assert_same(0, count($access->list($outsider)), 'Treinador sem equipe recebeu atletas');

        $storage = new StorageService();
        $seedPath = dirname(__DIR__, 2) . '/storage/private/athletes-seed/guardian-1.pdf';
        assert_true(is_file($seedPath), 'Arquivo ficticio de documento nao foi criado');
        $storage->read('athletes-seed/guardian-1.pdf');
        self::removeDirectory(dirname(__DIR__, 2) . '/storage/private/athletes-seed');
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? self::removeDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }
}
