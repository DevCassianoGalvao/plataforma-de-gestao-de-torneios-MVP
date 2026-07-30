<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Database\ChampionshipSeed;
use App\Repositories\CategoryRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegulationRepository;
use App\Repositories\SeasonRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\ChampionshipAccessService;
use App\Services\ChampionshipStatusService;
use App\Services\RegulationService;
use App\Services\RegulationRules;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class ChampionshipIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $password = getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123';
        ChampionshipSeed::run($pdo);
        $categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        ChampionshipSeed::run($pdo);
        assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM seasons')->fetchColumn(), 'Seed duplicou temporada');
        assert_same($categoryCount, (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(), 'Seed duplicou categoria');
        assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM championships')->fetchColumn(), 'Seed duplicou campeonato');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM championship_user_assignments')->fetchColumn(), 'Seed duplicou vinculo');
        assert_same(1, (int) $pdo->query("SELECT COUNT(*) FROM regulations WHERE status = 'published'")->fetchColumn(), 'Seed nao publicou regulamento');
        assert_same(7, (int) $pdo->query('SELECT COUNT(*) FROM regulation_tiebreakers')->fetchColumn(), 'Preset nao criou desempates');
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $organizer = $users->findByEmail('organizador@torneios.local');
        $outsider = $users->findByEmail('organizador-sem-acesso@torneios.local');
        $championship = $pdo->query('SELECT * FROM championships LIMIT 1')->fetch();
        $repository = new ChampionshipRepository($pdo);
        $seasonRepository = new SeasonRepository($pdo);
        $categoryRepository = new CategoryRepository($pdo);
        $catalogSeasonId = $seasonRepository->create(['name' => 'Temporada de Integracao', 'year' => 2027, 'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31', 'status' => 'draft']);
        $catalogCategoryId = $categoryRepository->create(['name' => 'Categoria de Integracao', 'slug' => 'categoria-de-integracao', 'description' => '', 'minimum_age' => null, 'maximum_age' => null, 'gender_rule' => '', 'status' => 'active']);
        assert_true($seasonRepository->find($catalogSeasonId) !== null, 'Temporada nao foi persistida');
        assert_true($categoryRepository->find($catalogCategoryId) !== null, 'Categoria nao foi persistida');
        $access = new ChampionshipAccessService($repository, new AuthorizationService($users));
        assert_same(1, count($repository->listForUser((int) $admin['id'], true)), 'Administrador nao ve todos os campeonatos');
        assert_same(1, count($repository->listForUser((int) $organizer['id'], false)), 'Organizador atribuido nao ve campeonato');
        assert_same(0, count($repository->listForUser((int) $outsider['id'], false)), 'Organizador sem vinculo recebeu acesso');
        assert_true($access->canView($organizer, (int) $championship['id']), 'Escopo do organizador atribuido falhou');
        assert_true(!$access->canView($outsider, (int) $championship['id']), 'Escopo do organizador externo falhou');
        $regulations = new RegulationRepository($pdo);
        $service = new RegulationService($pdo, $regulations, new AuditService($pdo));
        $draftId = $service->ensureDraft((int) $championship['id'], (int) $organizer['id'], Request::fake('GET', '/regulamento'));
        $service->applyPreset((int) $championship['id'], (int) $organizer['id'], Request::fake('POST', '/regulamento/preset'));
        $service->applyPreset((int) $championship['id'], (int) $organizer['id'], Request::fake('POST', '/regulamento/preset'));
        assert_same($draftId, (int) $regulations->draft((int) $championship['id'])['id'], 'Preset criou versoes duplicadas');
        $published = $service->publish((int) $championship['id'], (int) $organizer['id'], Request::fake('POST', '/regulamento/publicar'));
        assert_true($published['ok'] === true, 'Publicacao do rascunho falhou');
        assert_same(1, (int) $pdo->query("SELECT COUNT(*) FROM regulations WHERE championship_id = {$championship['id']} AND status = 'published'")->fetchColumn(), 'Mais de uma versao publicada');
        assert_same('superseded', (string) $pdo->query("SELECT status FROM regulations WHERE championship_id = {$championship['id']} AND id <> {$published['id']} ORDER BY version_number ASC LIMIT 1")->fetchColumn(), 'Versao anterior nao foi substituida');
        $nextDraftId = $service->ensureDraft((int) $championship['id'], (int) $organizer['id'], Request::fake('GET', '/regulamento/editar'));
        assert_same(3, (int) $regulations->find($nextDraftId)['version_number'], 'Nova versao nao recebeu numero seguinte');
        $custom = RegulationRules::preset();
        $custom['discipline']['yellow_cards_for_suspension'] = 2;
        assert_true($service->save((int) $championship['id'], (int) $organizer['id'], array_merge(['name' => 'Regulamento customizado', 'effective_from' => '2026-02-01'], $custom), Request::fake('POST', '/regulamento'))['ok'], 'Regulamento customizado nao foi salvo');
        $saved = $regulations->findWithSettings($nextDraftId);
        assert_same(2, (int) $saved['discipline_settings']['yellow_cards_for_suspension'], 'Regra de amarelos nao foi persistida');
        $status = new ChampionshipStatusService($repository, $regulations);
        $result = $status->transition($championship, 'in_progress');
        assert_true($result['ok'] === true, 'Transicao com regulamento publicado falhou');
        $invalid = $status->transition(array_merge($championship, ['status' => 'draft']), 'in_progress');
        assert_true($invalid['ok'] === false, 'Transicao incoerente aceita');
        $storage = new StorageService();
        $temporary = tempnam(sys_get_temp_dir(), 'mvp-upload-');
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $stored = $storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'logo.png'], 'test/' . uniqid(), ['image/png']);
        assert_true($storage->read($stored['path']) !== null, 'Upload valido nao pode ser lido');
        unlink(dirname(__DIR__, 2) . '/storage/private/' . $stored['path']);
        unlink($temporary);
        $invalidFile = tempnam(sys_get_temp_dir(), 'mvp-upload-invalid-');
        file_put_contents($invalidFile, 'nao sou imagem');
        $rejected = false;
        try {
            $storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'fake.png'], 'test/' . uniqid(), ['image/png']);
        } catch (\RuntimeException) {
            // MIME real rejeitado.
            $rejected = true;
        }
        unlink($invalidFile);
        assert_true($rejected, 'Upload invalido foi aceito');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'regulations.%'")->fetchColumn() >= 3, 'Auditoria de regulamento ausente');
    }
}
