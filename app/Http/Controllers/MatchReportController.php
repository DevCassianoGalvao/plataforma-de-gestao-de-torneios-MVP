<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MatchReportRepository;
use App\Services\MatchReportAccessService;
use App\Services\MatchReportService;
use App\Services\StorageService;

final class MatchReportController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly MatchReportRepository $reports, private readonly MatchReportService $service, private readonly MatchReportAccessService $access, private readonly StorageService $storage)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.view'); if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match) return Response::forbidden();
        $report = $this->reports->reportForMatch((int) $match['id']);
        if (!$report || empty($report['version_id'])) return Response::html('Sumula ainda nao gerada.', 404);
        return $this->page('Sumula digital', 'admin/reports/show', ['user' => $guard, 'match' => $match, 'report' => $report, 'html' => $report['html_snapshot'], 'versions' => $this->reports->versions((int) $match['id']), 'canGenerate' => $this->access->canGenerate($guard, $match), 'message' => Session::consumeFlash('report_message')]);
    }

    public function generate(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.generate'); if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match || !$this->access->canGenerate($guard, $match)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->generateForHomologatedMatch($match, (int) $guard['id']);
        if (!$result['ok']) return $this->errorPage('Sumula digital', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('report_message', 'Versao da sumula gerada.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/sumula'));
    }

    public function pdf(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.download'); if ($guard instanceof Response) return $guard;
        $versionId = (int) ($params[0] ?? 0); $version = $this->reports->version($versionId);
        if (!$version) return Response::html('Sumula nao encontrada.', 404);
        $match = $this->access->matchForUser($guard, (int) $version['match_id']);
        if (!$match || !$this->access->canDownload($guard, $match)) return Response::forbidden();
        $file = $this->storage->read($version['storage_path']); if (!$file) return Response::html('Arquivo da sumula nao encontrado.', 404);
        return Response::binary($file['body'], 'application/pdf', $version['original_name']);
    }

    public function currentPdf(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.download'); if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0)); if (!$match || !$this->access->canDownload($guard, $match)) return Response::forbidden();
        $report = $this->reports->reportForMatch((int) $match['id']); if (!$report || empty($report['version_id'])) return Response::html('Sumula ainda nao gerada.', 404);
        $file = $this->storage->read($report['storage_path']); if (!$file) return Response::html('Arquivo da sumula nao encontrado.', 404);
        return Response::binary($file['body'], 'application/pdf', $report['original_name']);
    }

    public function roundPackage(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.package'); if ($guard instanceof Response) return $guard;
        $roundId = (int) ($params[0] ?? 0); $round = $this->reports->round($roundId); if (!$round || !$this->access->canPackage($guard, (int) $round['championship_id'])) return Response::forbidden();
        $result = $this->service->package($this->reports->currentByRound($roundId), 'sumulas-rodada-' . $round['round_number'], (int) $guard['id']); if (!$result['ok']) return Response::html(implode(' ', $result['errors']), 422);
        $file = $this->storage->read($result['file']['path']); if (!$file) return Response::html('Pacote nao encontrado.', 404); return Response::binary($file['body'], 'application/zip', $result['name']);
    }

    public function championshipPackage(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_reports.package'); if ($guard instanceof Response) return $guard;
        $championshipId = (int) ($params[0] ?? 0); if (!$this->access->canPackage($guard, $championshipId)) return Response::forbidden();
        $result = $this->service->package($this->reports->currentByChampionship($championshipId), 'sumulas-campeonato-' . $championshipId, (int) $guard['id']); if (!$result['ok']) return Response::html(implode(' ', $result['errors']), 422);
        $file = $this->storage->read($result['file']['path']); if (!$file) return Response::html('Pacote nao encontrado.', 404); return Response::binary($file['body'], 'application/zip', $result['name']);
    }
}
