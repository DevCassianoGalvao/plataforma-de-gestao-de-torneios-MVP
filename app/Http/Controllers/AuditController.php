<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;

final class AuditController extends Controller
{
    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'audit.view');
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page('Logs', 'admin/audit', ['user' => $guard, 'entries' => $this->audit->recent()]);
    }
}
