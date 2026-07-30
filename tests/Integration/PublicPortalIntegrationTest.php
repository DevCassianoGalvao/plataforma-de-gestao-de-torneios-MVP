<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\NewsSeed;
use App\Database\PortalEngagementSeed;
use App\Database\ScheduleSeed;
use App\Database\TransferSeed;
use App\Repositories\PublicPortalRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class PublicPortalIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        ScheduleSeed::run($pdo);
        NewsSeed::run($pdo);
        TransferSeed::run($pdo);
        PortalEngagementSeed::run($pdo);
        PortalEngagementSeed::run($pdo);
        $repository = new PublicPortalRepository($pdo);
        $championship = $repository->championship('copa-brasil-de-talentos-2026');
        assert_true((bool) $championship, 'Campeonato publico ausente no read model');
        foreach (['documents', 'document_number', 'phone', 'email', 'address', 'guardian', 'private_notes', 'internal_notes'] as $privateField) {
            assert_true(!array_key_exists($privateField, $championship), 'Campo privado carregado no campeonato publico: ' . $privateField);
        }

        $championshipId = (int) $championship['id'];
        $publicTeams = $repository->teams($championshipId);
        assert_true(count($publicTeams) >= 1, 'Equipes publicas ausentes');
        $publicAthletes = $repository->athletes($championshipId);
        assert_true(count($publicAthletes) >= 1, 'Atletas publicos ausentes');
        $matchRows = $repository->nextMatches($championshipId);
        assert_true(count($matchRows) >= 1, 'Proximos jogos publicos ausentes');
        $publicMatch = $repository->match($championshipId, (int) $matchRows[0]['id']);
        assert_true((bool) $publicMatch, 'Detalhe publico de partida ausente');
        foreach (['observation', 'private_notes', 'documents', 'guardian', 'phone', 'email', 'address', 'photo_path'] as $privateField) {
            assert_true(!array_key_exists($privateField, $publicMatch), 'Campo privado carregado na partida publica: ' . $privateField);
        }
        foreach ($publicMatch['lineups'] as $player) {
            assert_true(array_key_exists('slot_key', $player) && array_key_exists('horizontal_position', $player), 'Escalacao publica sem coordenadas taticas.');
            assert_true(!array_key_exists('photo_path', $player), 'Caminho privado de foto vazou na escalacao publica.');
        }

        $publicAthlete = $repository->athlete($championshipId, (int) $publicAthletes[0]['id']);
        assert_true((bool) $publicAthlete, 'Detalhe publico de atleta ausente');
        foreach (['document_number', 'phone', 'email', 'address', 'guardian', 'private_notes', 'internal_notes'] as $privateField) {
            assert_true(!array_key_exists($privateField, $publicAthlete), 'Campo privado carregado no atleta publico: ' . $privateField);
        }
        assert_same(null, $repository->championship('campeonato-inexistente'), 'Slug inexistente atravessou isolamento publico');
        assert_same(null, $repository->team($championshipId, 'equipe-inexistente'), 'Equipe inexistente atravessou isolamento publico');
        assert_same(null, $repository->athlete($championshipId, PHP_INT_MAX), 'Atleta inexistente atravessou isolamento publico');
        assert_same(3, count($repository->officials($championshipId)), 'Arbitragem publica de demonstracao ausente ou duplicada');
        assert_same(3, count($repository->sponsors($championshipId)), 'Parceiros publicos de demonstracao ausentes ou duplicados');
    }
}
