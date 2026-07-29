<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\MatchReportRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\MatchReportHtmlRenderer;
use App\Services\MatchReportPdf;
use App\Services\MatchReportService;
use App\Services\StorageService;
use PDO;
use function Tests\assert_same;
use function Tests\assert_true;

final class MatchReportIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection(); $users = new UserRepository($pdo); $admin = $users->findByEmail('admin@torneios.local'); $match = $pdo->query("SELECT * FROM matches WHERE status = 'homologated' ORDER BY id LIMIT 1")->fetch();
        assert_true((bool) $admin && (bool) $match, 'Partida homologada ausente para sumula');
        $repository = new MatchReportRepository($pdo); $storage = new StorageService(); $service = new MatchReportService($repository, $storage, new AuditService($pdo), new MatchReportHtmlRenderer(), new MatchReportPdf()); $userId = (int) $admin['id'];
        $first = $service->generateForHomologatedMatch($match, $userId); assert_true($first['ok'] && $first['created'], 'Primeira versao nao foi criada');
        $versionOne = $first['version']; $pathOne = $versionOne['storage_path'];
        $second = $service->generateForHomologatedMatch($match, $userId); assert_true($second['ok'] && !$second['created'], 'Geracao idempotente criou versao duplicada'); assert_same(1, count($repository->versions((int) $match['id'])), 'Geracao idempotente duplicou historico');
        $fileOne = $storage->read($pathOne); assert_true($fileOne && str_starts_with($fileOne['body'], '%PDF-1.4'), 'PDF privado nao foi armazenado'); assert_true(str_contains($fileOne['body'], '/Count 2'), 'PDF nao possui segunda pagina');
        $pdo->prepare("UPDATE match_operations SET administrative_result_reason = 'Retificacao autorizada de teste' WHERE match_id = ?")->execute([(int) $match['id']]);
        $third = $service->generateForHomologatedMatch($match, $userId); assert_true($third['ok'] && $third['created'], 'Retificacao autorizada nao criou nova versao'); assert_same(2, count($repository->versions((int) $match['id'])), 'Historico de versoes foi sobrescrito'); assert_true($storage->read($pathOne) !== null, 'Versao anterior foi apagada');
        $roundId = (int) $match['round_id']; $package = $service->package($repository->currentByRound($roundId), 'teste-rodada', $userId); assert_true($package['ok'], 'Pacote por rodada nao foi criado'); $zip = $storage->read($package['file']['path']); assert_true($zip && str_starts_with($zip['body'], 'PK'), 'Pacote nao e ZIP valido');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM match_report_versions WHERE match_report_id = (SELECT id FROM match_reports WHERE match_id = ' . (int) $match['id'] . ')')->fetchColumn(), 'Historico de sumula nao foi preservado');
    }
}
