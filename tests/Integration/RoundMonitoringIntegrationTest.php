<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RoundMonitoringRepository;
use App\Repositories\UserRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class RoundMonitoringIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $repo = new RoundMonitoringRepository($pdo);
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        assert_true($admin !== null, 'Administrador de teste ausente');
        assert_true(in_array('round.monitor.view', $users->permissions((int) $admin['id']), true), 'Permissao de acompanhamento ausente');
        $rows = $repo->rounds($repo->filters(), (int) $admin['id'], true);
        assert_true($rows !== [], 'Rodadas de teste ausentes');
        $round = $rows[0];
        assert_true(array_key_exists('reports_missing_count', $round), 'Cobertura de sumulas ausente');
        assert_true(array_key_exists('evidence_missing_count', $round), 'Cobertura de evidencias ausente');
        assert_true(in_array($round['indicator'], ['sem_partidas', 'completa', 'parcial', 'atrasada', 'pendencia_critica'], true), 'Indicador de rodada invalido');
        $repo->saveDeadline((int) $round['championship_id'], 'hours', 24, (int) $admin['id']);
        $deadline = $repo->deadline((int) $round['championship_id']);
        assert_same('hours', $deadline['deadline_mode'], 'Prazo documental nao salvo');
        assert_same(24, (int) $deadline['custom_value'], 'Valor do prazo documental incorreto');
        assert_true($repo->round((int) $round['id']) !== null, 'Detalhe de rodada ausente');
        assert_true(is_array($repo->matches((int) $round['id'])), 'Partidas da rodada nao consultadas');
        assert_true(is_array($repo->exportRows((int) $round['id'])), 'Exportacao de pendencias nao preparada');
    }
}
