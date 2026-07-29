<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\TransferSeed;
use App\Repositories\TransferRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\TransferAccessService;
use App\Services\TransferService;
use function Tests\assert_same;
use function Tests\assert_true;

final class TransferIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection(); TransferSeed::run($pdo); $before = (int) $pdo->query('SELECT COUNT(*) FROM transfer_movements')->fetchColumn(); TransferSeed::run($pdo); assert_same($before, (int) $pdo->query('SELECT COUNT(*) FROM transfer_movements')->fetchColumn(), 'Seed de transferencias nao foi idempotente');
        $users = new UserRepository($pdo); $admin = $users->findByEmail('admin@torneios.local'); $communication = $users->findByEmail('comunicacao@torneios.local'); $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn(); $repository = new TransferRepository($pdo); $access = new TransferAccessService($repository, new AuthorizationService($users)); assert_true($access->canManageChampionship($communication, $championshipId), 'Comunicacao sem escopo de transferencias');
        $published = $repository->listPublic($championshipId); assert_true(count($published) >= 1, 'Movimento publicado nao ficou publico'); $movement = $repository->find((int) $published[0]['id']); $teamBefore = (int) $pdo->query('SELECT team_id FROM athletes WHERE id = ' . (int) $movement['athlete_id'])->fetchColumn(); $service = new TransferService($repository, new AuditService($pdo)); assert_true($service->transition((int) $movement['id'], (int) $admin['id'], 'cancelled', 'cancelled', 'Correcao'), 'Cancelamento falhou'); $teamAfter = (int) $pdo->query('SELECT team_id FROM athletes WHERE id = ' . (int) $movement['athlete_id'])->fetchColumn(); assert_same($teamBefore, $teamAfter, 'Aprovacao/publicacao alterou vinculo oficial'); assert_true(count($repository->history((int) $movement['id'])) >= 2, 'Historico de transferencia ausente');
        $private = $repository->find((int) $repository->listAdmin(null)[0]['id']); assert_true(str_contains((string) $private['internal_notes'], 'Nota interna'), 'Nota interna nao foi armazenada'); assert_true(!array_key_exists('internal_notes', $published[0]), 'Consulta publica vazou campo privado');
    }
}
