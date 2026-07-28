<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegulationRepository;
use App\Services\AuditService;
use App\Services\ChampionshipAccessService;
use App\Services\RegulationRules;
use App\Services\RegulationService;
use App\Services\StorageService;

final class RegulationController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, AuditService $audit, private readonly ChampionshipRepository $championships, private readonly RegulationRepository $regulations, private readonly ChampionshipAccessService $access, private readonly RegulationService $regulationService, private readonly StorageService $storage)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function show(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.view');
        if ($guard instanceof Response) return $guard;
        $current = $this->regulations->published((int) $championship['id']) ?: $this->regulations->draft((int) $championship['id']);
        return $this->page('Regulamento', 'admin/regulations/show', ['user' => $guard, 'championship' => $championship, 'regulation' => $current, 'versions' => $this->regulations->list((int) $championship['id']), 'message' => Session::consumeFlash('regulation_message')]);
    }

    public function edit(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.update', true);
        if ($guard instanceof Response) return $guard;
        $id = $this->regulationService->ensureDraft((int) $championship['id'], (int) $guard['id'], $request);
        $regulation = $this->regulations->findWithSettings($id);
        return $this->page('Editar regulamento', 'admin/regulations/form', ['user' => $guard, 'championship' => $championship, 'regulation' => $regulation, 'documents' => $this->regulations->documents((int) $regulation['id']), 'criteria' => RegulationRules::CRITERIA, 'errors' => []]);
    }

    public function save(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.update', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $data = $this->input($request);
        $result = $this->regulationService->save((int) $championship['id'], (int) $guard['id'], $data, $request);
        if (!$result['ok']) {
            $draftId = $this->regulationService->ensureDraft((int) $championship['id'], (int) $guard['id'], $request);
            return $this->errorPage('Editar regulamento', 'admin/regulations/form', ['user' => $guard, 'championship' => $championship, 'regulation' => array_merge(['name' => $data['name'], 'effective_from' => $data['effective_from']], $data), 'criteria' => RegulationRules::CRITERIA, 'errors' => $result['errors']], 422);
        }
        Session::flash('regulation_message', 'Rascunho do regulamento salvo.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/regulamento'));
    }

    public function preset(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.update', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->regulationService->applyPreset((int) $championship['id'], (int) $guard['id'], $request);
        Session::flash('regulation_message', 'Preset aplicado ao rascunho. Revise antes de publicar.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/regulamento/editar'));
    }

    public function review(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.view');
        if ($guard instanceof Response) return $guard;
        $draft = $this->regulations->draft((int) $championship['id']);
        return $this->page('Revisar regulamento', 'admin/regulations/review', ['user' => $guard, 'championship' => $championship, 'regulation' => $draft ? $this->regulations->findWithSettings((int) $draft['id']) : null, 'canPublish' => $this->authorization->can($guard, 'regulations.publish')]);
    }

    public function publish(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.publish', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->regulationService->publish((int) $championship['id'], (int) $guard['id'], $request);
        if (!$result['ok']) return $this->errorPage('Publicar regulamento', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('regulation_message', 'Regulamento publicado. Versao anterior, se houver, foi preservada.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/regulamento'));
    }

    public function document(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.update', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $draftId = $this->regulationService->ensureDraft((int) $championship['id'], (int) $guard['id'], $request);
        $file = $request->files['regulation_document'] ?? [];
        try {
            $stored = $this->storage->store($file, 'regulations/' . $draftId, ['application/pdf'], 10485760);
        } catch (\Throwable $exception) {
            return $this->errorPage('Documento do regulamento', 'errors/simple', ['message' => $exception->getMessage()], 422);
        }
        $id = $this->regulations->addDocument($draftId, ['storage_path' => $stored['path'], 'original_name' => $stored['original_name'], 'version_label' => 'Rascunho ' . $this->regulations->find($draftId)['version_number'], 'visibility' => 'private']);
        $this->audit->record('regulations.document_uploaded', (int) $guard['id'], 'regulation_document', $id, ['championship_id' => (int) $championship['id']], $request);
        Session::flash('regulation_message', 'Documento privado anexado ao rascunho.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/regulamento/editar'));
    }

    public function documentAsset(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.view');
        if ($guard instanceof Response) return $guard;
        $document = $this->regulations->document((int) ($params[1] ?? 0));
        if (!$document || (int) $document['championship_id'] !== (int) $championship['id']) return Response::html('Documento nao encontrado.', 404);
        $file = $this->storage->read((string) $document['storage_path']);
        if (!$file) return Response::html('Documento nao encontrado.', 404);
        return Response::binary($file['body'], $file['mime'], $file['name']);
    }

    public function versions(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.version_history');
        if ($guard instanceof Response) return $guard;
        return $this->page('Versoes do regulamento', 'admin/regulations/versions', ['user' => $guard, 'championship' => $championship, 'versions' => $this->regulations->list((int) $championship['id'])]);
    }

    public function version(Request $request, array $params = []): Response
    {
        [$guard, $championship] = $this->context($request, $params[0] ?? '', 'regulations.version_history');
        if ($guard instanceof Response) return $guard;
        $version = null;
        foreach ($this->regulations->list((int) $championship['id']) as $item) if ((int) $item['version_number'] === (int) ($params[1] ?? 0)) $version = $item;
        if (!$version) return Response::html('Versao nao encontrada.', 404);
        return $this->page('Versao do regulamento', 'admin/regulations/version', ['user' => $guard, 'championship' => $championship, 'regulation' => $this->regulations->findWithSettings((int) $version['id'])]);
    }

    private function context(Request $request, string $value, string $permission, bool $mutation = false): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $administrator = $this->access->isAdministrator($guard);
        $championship = ctype_digit($value) ? $this->championships->findForUser((int) $value, (int) $guard['id'], $administrator) : $this->championships->findForUserBySlug($value, (int) $guard['id'], $administrator);
        if (!$championship) return [Response::forbidden(), null];
        if ($mutation && $championship['status'] === 'archived') return [Response::forbidden('Campeonato arquivado e somente para consulta.'), null];
        return [$guard, $championship];
    }

    private function input(Request $request): array
    {
        $body = $request->body;
        $tiebreakers = [];
        foreach (RegulationRules::CRITERIA as $criterion) {
            $item = (array) (($body['tiebreakers'] ?? [])[$criterion] ?? []);
            $tiebreakers[] = ['criterion' => $criterion, 'priority' => (int) ($item['priority'] ?? 0), 'enabled' => isset($item['enabled']) ? 1 : 0];
        }
        $number = static fn (string $key, int $default = 0): int => (int) ($body[$key] ?? $default);
        $check = static fn (string $key): int => isset($body[$key]) ? 1 : 0;
        return ['name' => trim((string) ($body['name'] ?? 'Regulamento')), 'effective_from' => $body['effective_from'] ?? '', 'format' => ['group_count' => $number('group_count'), 'teams_per_group' => $number('teams_per_group'), 'qualified_per_group' => $number('qualified_per_group'), 'group_rounds' => (string) ($body['group_rounds'] ?? 'single'), 'home_and_away' => $check('home_and_away'), 'knockout_starts_at' => (string) ($body['knockout_starts_at'] ?? 'quarterfinals'), 'third_place_match' => $check('third_place_match'), 'final_format' => (string) ($body['final_format'] ?? 'single_match')], 'points' => ['points_win' => $number('points_win'), 'points_draw' => $number('points_draw'), 'points_loss' => $number('points_loss'), 'wo_winner_goals' => $number('wo_winner_goals'), 'wo_loser_goals' => $number('wo_loser_goals')], 'discipline' => ['yellow_cards_for_suspension' => $number('yellow_cards_for_suspension'), 'yellow_suspension_matches' => $number('yellow_suspension_matches'), 'red_card_automatic_suspension' => $check('red_card_automatic_suspension'), 'red_card_suspension_matches' => $number('red_card_suspension_matches'), 'reset_cards_enabled' => $check('reset_cards_enabled'), 'reset_cards_stage' => (string) ($body['reset_cards_stage'] ?? '')], 'match' => ['regular_time_minutes' => $number('regular_time_minutes'), 'halftime_minutes' => $number('halftime_minutes'), 'substitutions_allowed' => $number('substitutions_allowed'), 'substitution_windows' => $number('substitution_windows'), 'extra_time_enabled' => $check('extra_time_enabled'), 'extra_time_minutes' => $number('extra_time_minutes'), 'penalty_shootout_enabled' => $check('penalty_shootout_enabled'), 'direct_penalties' => $check('direct_penalties')], 'tiebreakers' => $tiebreakers];
    }
}
