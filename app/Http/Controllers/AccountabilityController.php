<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AccountabilityRepository;

final class AccountabilityController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly AccountabilityRepository $reports)
    { parent::__construct($users, $authorization, $audit); }
    public function index(Request $request, array $params = []): Response
    { $user = $this->guard($request, 'accountability.view'); if ($user instanceof Response) return $user; return $this->page('Prestacao de contas', 'admin/accountability/index', ['user' => $user, 'championships' => $this->reports->championshipsFor((int) $user['id'], in_array('administrator', $this->authorization->roleKeys($user), true))]); }
    public function show(Request $request, array $params = []): Response
    { $user = $this->guard($request, 'accountability.view'); if ($user instanceof Response) return $user; $id = (int) ($params[0] ?? 0); if (!$this->reports->allowed($id, (int) $user['id'], in_array('administrator', $this->authorization->roleKeys($user), true))) return Response::forbidden(); return $this->page('Prestacao de contas', 'admin/accountability/show', ['user' => $user, 'championshipId' => $id, 'summary' => $this->reports->summary($id)]); }
    public function export(Request $request, array $params = []): Response
    { $user = $this->guard($request, 'accountability.export'); if ($user instanceof Response) return $user; $id = (int) ($params[0] ?? 0); $kind = (string) ($params[1] ?? ''); if (!$this->reports->allowed($id, (int) $user['id'], in_array('administrator', $this->authorization->roleKeys($user), true)) || !in_array($kind, ['partidas','atletas','sumulas','evidencias'], true)) return Response::forbidden(); $rows = $this->reports->rows($id, $kind); $csv = $this->csv($rows); $this->reports->log($id, (int) $user['id'], $kind, count($rows)); $this->audit->record('accountability.exported', (int) $user['id'], 'championship', $id, ['kind' => $kind, 'rows' => count($rows)], $request); return new Response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="prestacao-' . $kind . '-campeonato-' . $id . '.csv"', 'Cache-Control' => 'private, no-store']); }
    private function csv(array $rows): string { if ($rows === []) return "\xEF\xBB\xBF\n"; $out = fopen('php://temp', 'r+'); fwrite($out, "\xEF\xBB\xBF"); fputcsv($out, array_keys($rows[0]), ';'); foreach ($rows as $row) fputcsv($out, $row, ';'); rewind($out); return stream_get_contents($out) ?: ''; }
}
