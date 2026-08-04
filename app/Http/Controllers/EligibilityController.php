<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\EligibilityRepository;
use App\Repositories\ScheduleRepository;
use App\Services\EligibilityService;

final class EligibilityController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly ScheduleRepository $schedules, private readonly EligibilityRepository $eligibility, private readonly EligibilityService $service)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function grant(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'regulations.grant_exception');
        if ($user instanceof Response) return $user;
        if (!in_array('administrator', $this->authorization->roleKeys($user), true) || !$this->validCsrf($request)) return Response::forbidden('Apenas administrador pode liberar excecao.');
        $match = $this->schedules->matchById((int) ($params[0] ?? 0));
        if (!$match) return Response::html('Partida nao encontrada.', 404);
        $ruleId = (int) ($request->body['rule_id'] ?? 0);
        $rule = $this->eligibility->ruleForMatch($match, $ruleId);
        $athleteId = (int) ($request->body['athlete_id'] ?? 0);
        $teamId = (int) ($request->body['team_id'] ?? 0);
        if (!$rule || empty($rule['allow_exception']) || !$this->eligibility->registration((int) $match['championship_id'], $teamId, $athleteId)) return Response::html('Excecao invalida para esta partida.', 422);
        $result = $this->service->grant($user, ['championship_id' => (int) $match['championship_id'], 'athlete_id' => $athleteId, 'team_id' => $teamId, 'rule_id' => $ruleId, 'match_id' => (int) $match['id'], 'phase_id' => (int) $match['phase_id'], 'ignored_rule' => 'eligibility_rule', 'reason' => (string) ($request->body['reason'] ?? ''), 'expires_at' => $request->body['expires_at'] ?: null]);
        return $result['ok'] ? Response::html('Excecao registrada.', 201) : Response::html(implode(' ', $result['errors']), 422);
    }
}
