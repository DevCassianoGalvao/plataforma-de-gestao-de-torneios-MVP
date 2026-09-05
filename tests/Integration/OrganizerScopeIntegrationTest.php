<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\AthleteRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuthorizationService;
use App\Services\TeamAccessService;
use function Tests\assert_same;
use function Tests\assert_true;

/** Garante que o papel "organizer" enxerga apenas o campeonato ao qual esta vinculado. */
final class OrganizerScopeIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        assert_true($admin !== null, 'Administrador base ausente para o teste de organizador.');

        $championshipA = (int) $pdo->query("SELECT id FROM championships WHERE deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        assert_true($championshipA > 0, 'Nenhum campeonato base para vincular o organizador.');

        // Campeonato B isolado, com uma equipe propria.
        $seasonId = (int) $pdo->query('SELECT id FROM seasons ORDER BY id LIMIT 1')->fetchColumn();
        $categoryId = (int) $pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
        $slugB = 'organizer-scope-b-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $pdo->prepare('INSERT INTO championships (name, short_name, slug, description, season_id, category_id, status, visibility, default_theme, primary_color, secondary_color, accent_color, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(['Campeonato B (isolamento)', 'Camp B', $slugB, 'Campeonato de controle para o teste de escopo.', $seasonId, $categoryId, 'configured', 'private', 'dark', '#111111', '#222222', '#333333', (int) $admin['id'], $now, $now]);
        $championshipB = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, description, city, state, primary_color, secondary_color, shield_path, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, NULL, ?, ?, ?, ?)')
            ->execute([$championshipB, 'Equipe Isolada B', 'Isolada B', $slugB . '-time', 'IB', 'Equipe do campeonato de controle.', '#111111', '#333333', 'active', (int) $admin['id'], $now, $now]);
        $teamB = (int) $pdo->lastInsertId();

        // Usuario organizador vinculado apenas ao campeonato A.
        $roleId = (int) $pdo->query("SELECT id FROM roles WHERE `key` = 'organizer' LIMIT 1")->fetchColumn();
        assert_true($roleId > 0, 'Papel organizer nao foi criado pela migracao/seed.');
        $email = 'organizador-teste-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@torneios.local';
        $pdo->prepare("INSERT INTO users (name, email, password_hash, status, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, ?)")
            ->execute(['Organizador Teste', $email, password_hash('Organizador123', PASSWORD_DEFAULT), $now, $now]);
        $organizerId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO user_roles (user_id, role_id, created_at, created_by) VALUES (?, ?, ?, ?)')->execute([$organizerId, $roleId, $now, (int) $admin['id']]);
        $pdo->prepare("INSERT INTO championship_user_assignments (championship_id, user_id, assignment_type, created_at, created_by) VALUES (?, ?, 'organizer', ?, ?)")->execute([$championshipA, $organizerId, $now, (int) $admin['id']]);
        $organizer = $users->findById($organizerId);

        $championships = new ChampionshipRepository($pdo);
        $teams = new TeamRepository($pdo);
        $athletes = new AthleteRepository($pdo);
        $registrations = new RegistrationRepository($pdo);
        $authorization = new AuthorizationService($users);
        $teamAccess = new TeamAccessService($teams, $championships, $authorization);

        assert_same('championship', $teamAccess->scope($organizer), 'Organizador nao recebeu o escopo de campeonato.');

        // Campeonatos: ve A, nao ve B.
        assert_true($championships->findForUser($championshipA, $organizerId, false) !== null, 'Organizador nao acessa o campeonato vinculado.');
        assert_true($championships->findForUser($championshipB, $organizerId, false) === null, 'Organizador acessou um campeonato nao vinculado.');
        $visible = $championships->listForUser($organizerId, false);
        assert_same(1, count($visible), 'Organizador enxergou mais de um campeonato.');
        assert_same($championshipA, (int) $visible[0]['id'], 'Lista de campeonatos do organizador trouxe o campeonato errado.');

        // Equipes: todas do A, nenhuma do B.
        $organizerTeams = $teams->listForUser($organizerId, 'championship');
        assert_true($organizerTeams !== [], 'Organizador nao enxergou nenhuma equipe do campeonato vinculado.');
        foreach ($organizerTeams as $team) {
            assert_same($championshipA, (int) $team['championship_id'], 'Organizador enxergou equipe de outro campeonato.');
        }
        assert_true($teams->findForUser($teamB, $organizerId, 'championship') === null, 'Organizador acessou equipe do campeonato de controle.');
        assert_true($teams->findForUser($teamB, $organizerId, 'championship', true) === null, 'Organizador pode mutar equipe do campeonato de controle.');

        // Atletas e inscricoes: nada do campeonato B.
        foreach ($athletes->listForUser($organizerId, 'championship') as $athlete) {
            assert_true((int) $athlete['team_id'] !== $teamB, 'Organizador enxergou atleta do campeonato de controle.');
        }
        foreach ($registrations->listForUser($organizerId, 'championship') as $registration) {
            assert_same($championshipA, (int) $registration['championship_id'], 'Organizador enxergou inscricao de outro campeonato.');
        }

        // Limpeza.
        $pdo->prepare('DELETE FROM championship_user_assignments WHERE user_id = ?')->execute([$organizerId]);
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$organizerId]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$organizerId]);
        $pdo->prepare('DELETE FROM teams WHERE id = ?')->execute([$teamB]);
        $pdo->prepare('DELETE FROM championships WHERE id = ?')->execute([$championshipB]);
    }
}
