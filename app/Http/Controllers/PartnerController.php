<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\PartnerRepository;
use App\Services\StorageService;

final class PartnerController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly PartnerRepository $partners, private readonly ChampionshipRepository $championships, private readonly \App\Services\ChampionshipAccessService $access, private readonly StorageService $storage)
    { parent::__construct($users, $authorization, $audit); }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.manage_identity'); if ($user instanceof Response) return $user;
        $championship = $this->championship($user, (string) ($params[0] ?? '')); if (!$championship) return Response::forbidden();
        return $this->page('Parceiros do campeonato', 'admin/championships/partners', ['user' => $user, 'championship' => $championship, 'items' => $this->partners->listForChampionship((int) $championship['id']), 'message' => Session::consumeFlash('partner_message'), 'errors' => []]);
    }

    public function save(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.manage_identity'); if ($user instanceof Response) return $user;
        $championship = $this->championship($user, (string) ($params[0] ?? '')); if (!$championship || !$this->validCsrf($request)) return Response::forbidden('Sua sessão expirou.');
        $existing = !empty($request->body['partner_id']) ? $this->partners->find((int) $request->body['partner_id']) : null;
        if ($existing && (int) $existing['championship_id'] !== (int) $championship['id']) return Response::forbidden();
        $data = ['championship_id' => (int) $championship['id'], 'partner_type' => (string) ($request->body['partner_type'] ?? 'sponsor'), 'name' => trim((string) ($request->body['name'] ?? '')), 'website_url' => trim((string) ($request->body['website_url'] ?? '')), 'display_order' => max(1, (int) ($request->body['display_order'] ?? 1)), 'status' => (string) ($request->body['status'] ?? 'active'), 'logo_path' => $existing['logo_path'] ?? null];
        $errors = [];
        if (!in_array($data['partner_type'], ['sponsor', 'supporter', 'organizer'], true)) $errors[] = 'Tipo de parceiro inválido.';
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 180) $errors[] = 'Informe o nome do parceiro.';
        if ($data['website_url'] !== '' && !filter_var($data['website_url'], FILTER_VALIDATE_URL)) $errors[] = 'Informe um site válido ou deixe em branco.';
        if (!in_array($data['status'], ['active', 'inactive'], true)) $errors[] = 'Status inválido.';
        $newLogoPath = null;
        if (isset($request->files['logo']) && ($request->files['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) try { $stored = $this->storage->storeOptimizedImage($request->files['logo'], 'partners/' . $championship['id'], ['max_width' => 1200, 'max_height' => 600, 'quality' => 86]); $data['logo_path'] = $stored['path']; $newLogoPath = $stored['path']; } catch (\Throwable $exception) { $errors[] = $exception->getMessage(); }
        if ($errors !== [] && $newLogoPath) $this->storage->delete($newLogoPath);
        if ($errors !== []) return $this->errorPage('Parceiros do campeonato', 'admin/championships/partners', ['user' => $user, 'championship' => $championship, 'items' => $this->partners->listForChampionship((int) $championship['id']), 'errors' => $errors], 422);
        if ($existing) { $this->partners->update((int) $existing['id'], $data); $id = (int) $existing['id']; $action = 'updated'; if ($newLogoPath && !empty($existing['logo_path']) && $existing['logo_path'] !== $newLogoPath) $this->storage->delete((string) $existing['logo_path']); } else { $id = $this->partners->create($data, (int) $user['id']); $action = 'created'; }
        $this->audit->record('championship_partner.' . $action, (int) $user['id'], 'championship_sponsor', $id, ['championship_id' => $championship['id'], 'partner_type' => $data['partner_type']], $request);
        Session::flash('partner_message', 'Parceiro salvo.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/parceiros'));
    }

    public function delete(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.manage_identity'); if ($user instanceof Response) return $user;
        $championship = $this->championship($user, (string) ($params[0] ?? '')); $partner = $this->partners->find((int) ($params[1] ?? 0));
        if (!$championship || !$partner || (int) $partner['championship_id'] !== (int) $championship['id'] || !$this->validCsrf($request)) return Response::forbidden();
        $this->partners->softDelete((int) $partner['id']); $this->audit->record('championship_partner.deleted', (int) $user['id'], 'championship_sponsor', (int) $partner['id'], [], $request);
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/parceiros'));
    }

    private function championship(array $user, string $slug): ?array { return $this->championships->findForUserBySlug($slug, (int) $user['id'], $this->access->isAdministrator($user)); }
}
