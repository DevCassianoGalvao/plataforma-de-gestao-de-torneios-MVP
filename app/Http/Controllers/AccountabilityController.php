<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AccountabilityRepository;
use App\Services\AccountabilityExportService;
use App\Services\Slugger;
use App\Services\StorageService;

final class AccountabilityController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly AccountabilityRepository $reports, private readonly AccountabilityExportService $exports, private readonly StorageService $storage)
    { parent::__construct($users, $authorization, $audit); }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'accountability.view');
        if ($user instanceof Response) return $user;
        return $this->page('Prestação de contas', 'admin/accountability/index', ['user' => $user, 'championships' => $this->reports->championshipsFor((int) $user['id'], $this->isAdministrator($user))]);
    }

    public function show(Request $request, array $params = []): Response
    {
        $user = $this->guardAny($request, ['accountability.detail', 'accountability.view']);
        if ($user instanceof Response) return $user;
        $id = (int) ($params[0] ?? 0);
        if (!$this->reports->allowed($id, (int) $user['id'], $this->isAdministrator($user))) return Response::forbidden();
        $filters = $this->filters($request->query);
        return $this->page('Prestação de contas', 'admin/accountability/show', [
            'user' => $user, 'championshipId' => $id, 'summary' => $this->reports->summary($id), 'settings' => $this->reports->settings($id),
            'matches' => $this->reports->matches($id, $filters), 'filterOptions' => $this->reports->filterOptions($id), 'filters' => $filters,
            'message' => Session::consumeFlash('accountability_message'),
        ]);
    }

    public function detail(Request $request, array $params = []): Response
    {
        // A consulta da prestação também autoriza a abertura do detalhe.
        // A permissão específica continua válida quando a instalação já a possui.
        $user = $this->guardAny($request, ['accountability.detail', 'accountability.view']);
        if ($user instanceof Response) return $user;
        $championshipId = (int) ($params[0] ?? 0); $matchId = (int) ($params[1] ?? 0);
        if (!$this->reports->allowed($championshipId, (int) $user['id'], $this->isAdministrator($user))) return Response::forbidden();
        $detail = $this->reports->matchDetail($championshipId, $matchId);
        if (!$detail) return $this->errorPage('Prestação de contas', 'errors/404', ['path' => 'Partida não encontrada.'], 404);
        return $this->page('Partida na prestação', 'admin/accountability/match', ['user' => $user, 'championshipId' => $championshipId, 'match' => $detail]);
    }

    public function export(Request $request, array $params = []): Response
    {
        $kind = (string) ($params[1] ?? '');
        $permission = match ($kind) { 'pdf' => 'accountability.export_pdf', 'xlsx' => 'accountability.export_xlsx', 'zip', 'atletas-documentos-zip' => 'accountability.export_zip', default => 'accountability.export' };
        $user = $this->guard($request, $permission);
        if ($user instanceof Response) return $user;
        $id = (int) ($params[0] ?? 0);
        if (!$this->reports->allowed($id, (int) $user['id'], $this->isAdministrator($user))) return Response::forbidden();
        if (in_array($kind, ['partidas', 'atletas', 'atletas-documentos', 'sumulas'], true)) {
            $rows = $this->reports->rows($id, $kind); $csv = $this->csv($rows); $hash = hash('sha256', $csv);
            $this->reports->log($id, (int) $user['id'], $kind, count($rows), 'csv', [], [], 'prestacao-' . $kind . '-campeonato-' . $id . '.csv', $hash);
            $this->audit->record('accountability.exported', (int) $user['id'], 'championship', $id, ['kind' => $kind, 'rows' => count($rows), 'hash' => $hash], $request);
            return Response::download($csv, 'text/csv; charset=UTF-8', 'prestacao-' . $kind . '-campeonato-' . $id . '.csv');
        }
        try { $file = $this->exports->generate($id, $kind, $this->filters($request->query), (int) $user['id']); }
        catch (\Throwable $error) { $this->audit->record('accountability.export_failed', (int) $user['id'], 'championship', $id, ['format' => $kind, 'error' => $error->getMessage()], $request); return $this->errorPage('Prestação de contas', 'errors/500', ['message' => 'Não foi possível gerar este arquivo agora.'], 500); }
        return Response::download($file['body'], $file['mime'], $file['name']);
    }

    /** Download de um arquivo da partida (sumula, sumula assinada ou evidencia aprovada) pela prestacao de contas. */
    public function file(Request $request, array $params = []): Response
    {
        [$user, $match] = $this->detailAccess($request, $params);
        if ($user instanceof Response) return $user;
        if (!$match) return $this->errorPage('Prestação de contas', 'errors/404', ['path' => 'Partida não encontrada.'], 404);
        $kind = (string) ($params[2] ?? ''); $fileId = (int) ($params[3] ?? 0);
        $target = null;
        if ($kind === 'sumula' || $kind === 'assinada') {
            foreach ($match['versions'] as $version) {
                if ((int) $version['id'] !== $fileId) continue;
                $target = $kind === 'sumula'
                    ? ['path' => $version['storage_path'], 'name' => 'sumula-partida-' . (int) $match['id'] . '-v' . (int) $version['version_number'] . '.pdf']
                    : (!empty($version['signed_storage_path']) ? ['path' => $version['signed_storage_path'], 'name' => $version['signed_original_name'] ?: 'sumula-assinada-partida-' . (int) $match['id'] . '.pdf'] : null);
            }
        } elseif ($kind === 'evidencia') {
            foreach (array_merge($match['evidencias'], $match['evidencias_dia_evento']) as $media) {
                if ((int) $media['id'] === $fileId) $target = ['path' => $media['storage_path'], 'name' => $media['original_name']];
            }
        }
        if (!$target) return Response::html('Arquivo não encontrado.', 404);
        $file = $this->storage->read((string) $target['path']);
        if (!$file) return Response::html('Arquivo não encontrado no armazenamento.', 404);
        $this->audit->record('accountability.file_downloaded', (int) $user['id'], 'match', (int) $match['id'], ['kind' => $kind, 'id' => $fileId], $request);
        return Response::binary($file['body'], $file['mime'], (string) $target['name']);
    }

    /** ZIP com tudo da partida: sumula atual, assinada, fotos/documentos aprovados e observacoes. */
    public function matchPackage(Request $request, array $params = []): Response
    {
        [$user, $match] = $this->detailAccess($request, $params);
        if ($user instanceof Response) return $user;
        if (!$match) return $this->errorPage('Prestação de contas', 'errors/404', ['path' => 'Partida não encontrada.'], 404);
        $tmp = tempnam(sys_get_temp_dir(), 'partida-');
        $zip = new \ZipArchive();
        if ($tmp === false || $zip->open($tmp, \ZipArchive::OVERWRITE) !== true) return $this->errorPage('Prestação de contas', 'errors/500', ['message' => 'Não foi possível gerar o pacote.'], 500);
        $missing = [];
        $add = function (string $path, string $name) use ($zip, &$missing): void {
            $file = $this->storage->read($path);
            if ($file) $zip->addFromString($name, $file['body']); else $missing[] = $name;
        };
        $current = $match['versions'][0] ?? null;
        if ($current) {
            $add((string) $current['storage_path'], 'sumula-v' . (int) $current['version_number'] . '.pdf');
            if (!empty($current['signed_storage_path'])) $add((string) $current['signed_storage_path'], 'sumula-assinada.pdf');
        }
        $used = [];
        foreach (array_merge($match['evidencias'], $match['evidencias_dia_evento']) as $media) {
            $name = preg_replace('/[^\w.\-]+/u', '_', (string) $media['original_name']) ?: 'arquivo';
            $name = isset($used[$name]) ? (int) $media['id'] . '-' . $name : $name;
            $used[$name] = true;
            $add((string) $media['storage_path'], 'fotos-e-documentos/' . $name);
        }
        $notes = trim((string) ($match['internal_notes'] ?? ''));
        if ($notes !== '') $zip->addFromString('observacoes.txt', $notes . "\n");
        if ($missing !== []) $zip->addFromString('arquivos-pendentes.txt', "Arquivos não encontrados no armazenamento:\n" . implode("\n", $missing) . "\n");
        $zip->close();
        $body = (string) file_get_contents($tmp);
        @unlink($tmp);
        $this->audit->record('accountability.match_package_downloaded', (int) $user['id'], 'match', (int) $match['id'], [], $request);
        return Response::download($body, 'application/zip', 'partida-' . (int) $match['id'] . '-' . Slugger::make((string) $match['home_team_name']) . '-x-' . Slugger::make((string) $match['away_team_name']) . '.zip');
    }

    private function detailAccess(Request $request, array $params): array
    {
        $user = $this->guardAny($request, ['accountability.detail', 'accountability.view']);
        if ($user instanceof Response) return [$user, null];
        $championshipId = (int) ($params[0] ?? 0);
        if (!$this->reports->allowed($championshipId, (int) $user['id'], $this->isAdministrator($user))) return [Response::forbidden(), null];
        return [$user, $this->reports->matchDetail($championshipId, (int) ($params[1] ?? 0))];
    }

    public function uploadSigned(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'match_reports.signed_upload');
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessão expirou.');
        $championshipId = (int) ($params[0] ?? 0); $matchId = (int) ($params[1] ?? 0);
        if (!$this->reports->allowed($championshipId, (int) $user['id'], $this->isAdministrator($user))) return Response::forbidden();
        $match = $this->reports->matchDetail($championshipId, $matchId); $file = $request->files['signed_report'] ?? null;
        if (!$match || !is_array($file)) return $this->errorPage('Prestação de contas', 'errors/500', ['message' => 'Partida ou arquivo não encontrado.'], 422);
        try {
            $stored = $this->storage->store($file, 'accountability/signed', ['application/pdf'], 20971520);
            $hash = hash_file('sha256', dirname(__DIR__, 3) . '/storage/private/' . $stored['path']) ?: '';
            if (!$this->reports->attachSignedReport($championshipId, $matchId, $stored['path'], $stored['original_name'], $stored['mime'], $stored['size'], $hash, (int) $user['id'])) throw new \RuntimeException('Não existe súmula atual para anexar.');
            $this->audit->record('accountability.signed_report_attached', (int) $user['id'], 'match', $matchId, ['hash' => $hash], $request);
            Session::flash('accountability_message', 'Súmula assinada vinculada à versão atual.');
            return Response::redirect(Config::url('/prestacao/campeonatos/' . $championshipId . '/partidas/' . $matchId));
        } catch (\Throwable) { return $this->errorPage('Prestação de contas', 'errors/500', ['message' => 'Não foi possível anexar a súmula assinada.'], 422); }
    }

    private function filters(array $query): array { return array_intersect_key($query, array_flip(['phase_id', 'group_id', 'round_id', 'team_id', 'venue_id', 'city', 'event_day_id', 'from', 'to', 'document_status'])); }
    private function isAdministrator(array $user): bool { return in_array('administrator', $this->authorization->roleKeys($user), true); }
    private function csv(array $rows): string { $out = fopen('php://temp', 'r+'); fwrite($out, "\xEF\xBB\xBF"); if ($rows !== []) { fputcsv($out, array_keys($rows[0]), ';'); foreach ($rows as $row) fputcsv($out, $row, ';'); } rewind($out); return stream_get_contents($out) ?: ''; }
}
