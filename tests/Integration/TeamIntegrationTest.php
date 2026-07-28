<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;
use App\Repositories\ChampionshipRepository;
use App\Repositories\StaffRoleRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TeamStaffRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\StorageService;
use App\Services\TacticalFormationService;
use App\Services\TeamAccessService;
use App\Services\TeamStatusService;
use function Tests\assert_same;
use function Tests\assert_true;

final class TeamIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        TacticalFormationSeed::run($pdo);
        TeamSeed::run($pdo);
        TeamSeed::run($pdo);
        assert_same(11, (int) $pdo->query('SELECT COUNT(*) FROM staff_roles')->fetchColumn(), 'Seed duplicou funcoes');
        assert_same(9, (int) $pdo->query('SELECT COUNT(*) FROM tactical_formations')->fetchColumn(), 'Seed duplicou formacoes');
        assert_same(99, (int) $pdo->query('SELECT COUNT(*) FROM tactical_formation_slots')->fetchColumn(), 'Cada formacao deve possuir 11 slots');
        assert_same(10, (int) $pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn(), 'Seed duplicou equipes');
        assert_same(10, (int) $pdo->query('SELECT COUNT(*) FROM team_user_assignments WHERE status = \'active\'')->fetchColumn(), 'Seed duplicou responsaveis');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM team_staff')->fetchColumn(), 'Seed duplicou comissao');

        $formations = new TacticalFormationRepository($pdo);
        $teamRepository = new TeamRepository($pdo);
        $formationService = new TacticalFormationService($formations, $teamRepository);
        foreach ($formations->listActive() as $formation) {
            $loaded = $formationService->find((int) $formation['id']);
            assert_true($loaded !== null && $formationService->validate($loaded)['ok'], 'Formacao invalida: ' . $formation['name']);
        }

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $organizer = $users->findByEmail('organizador@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $manager = $users->findByEmail('gestor@torneios.local');
        $outsider = $users->findByEmail('treinador-sem-equipe@torneios.local');
        $authorization = new AuthorizationService($users);
        $championships = new ChampionshipRepository($pdo);
        $access = new TeamAccessService($teamRepository, $championships, $authorization);
        assert_same(10, count($teamRepository->listForUser((int) $admin['id'], 'administrator')), 'Administrador nao ve todas as equipes');
        assert_same(10, count($teamRepository->listForUser((int) $organizer['id'], 'organizer')), 'Organizador nao ve equipes do campeonato autorizado');
        assert_same(5, count($teamRepository->listForUser((int) $trainer['id'], 'team')), 'Treinador recebeu escopo inesperado');
        assert_same(5, count($teamRepository->listForUser((int) $manager['id'], 'team')), 'Gestor recebeu escopo inesperado');
        assert_same(0, count($teamRepository->listForUser((int) $outsider['id'], 'team')), 'Treinador sem vinculo recebeu equipes');
        $first = $teamRepository->findForUserBySlug('estrela-norte-fc', (int) $trainer['id'], 'team');
        assert_true($first !== null && $access->find($trainer, (int) $first['id'], 'teams.view') !== null, 'Treinador nao acessa equipe vinculada');
        $foreign = $teamRepository->findForUserBySlug('serra-azul-futebol', (int) $trainer['id'], 'team');
        assert_true($foreign === null, 'Treinador acessou equipe de outro responsavel');

        $formation433 = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-3-3' LIMIT 1")->fetchColumn();
        $changed = $formationService->setDefault((int) $first['id'], $formation433, (int) $trainer['id']);
        assert_true($changed['ok'], 'Formacao padrao nao foi definida');
        $updated = $teamRepository->findForUser((int) $first['id'], (int) $trainer['id'], 'team', true);
        assert_same($formation433, (int) $updated['default_tactical_formation_id'], 'Formacao padrao nao persistiu');
        assert_same((int) $trainer['id'], (int) $updated['default_formation_changed_by'], 'Autor da formacao nao persistiu');

        $staff = new TeamStaffRepository($pdo);
        $roles = new StaffRoleRepository($pdo);
        $members = $staff->list((int) $first['id']);
        assert_same(2, count($members), 'Comissao inicial nao foi criada');
        $role = $roles->listActive()[1];
        $staffId = $staff->create((int) $first['id'], ['staff_role_id' => (int) $role['id'], 'user_id' => 0, 'full_name' => 'Membro de Integracao', 'display_name' => 'Integracao', 'email' => null, 'phone' => '', 'document_number' => '', 'photo_path' => null, 'registration_number' => '', 'status' => 'active', 'starts_at' => '2026-01-01', 'ends_at' => null, 'notes' => '']);
        $staff->updateStatus($staffId, 'inactive');
        assert_same('inactive', (string) $staff->find($staffId)['status'], 'Comissao nao pode ser inativada');

        $teamStatus = new TeamStatusService();
        $transition = $teamStatus->transition($updated, 'inactive');
        assert_true($transition['ok'], 'Transicao de equipe ativa para inativa falhou');
        $teamRepository->updateStatus((int) $first['id'], 'inactive');
        assert_true($teamStatus->transition(array_merge($updated, ['status' => 'inactive']), 'withdrawn')['ok'], 'Transicao de equipe inativa para retirada falhou');
        $teamRepository->updateStatus((int) $first['id'], 'active');

        $duplicateRejected = false;
        try {
            $teamRepository->create(['championship_id' => $first['championship_id'], 'name' => $first['name'], 'short_name' => 'Duplicada', 'slug' => $first['slug'], 'abbreviation' => 'DUP', 'description' => '', 'city' => '', 'state' => '', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'status' => 'draft', 'default_tactical_formation_id' => 0], (int) $admin['id']);
        } catch (\PDOException) {
            $duplicateRejected = true;
        }
        assert_true($duplicateRejected, 'Slug ou nome duplicado foi aceito');

        $temporary = tempnam(sys_get_temp_dir(), 'mvp-team-upload-');
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $storage = new StorageService();
        $stored = $storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'shield.png'], 'team-test/' . uniqid(), ['image/png']);
        assert_true($storage->read($stored['path']) !== null, 'Upload de escudo valido nao foi armazenado');
        $storage->delete($stored['path']);
        unlink($temporary);
    }
}
