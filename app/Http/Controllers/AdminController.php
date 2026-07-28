<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;

final class AdminController extends Controller
{
    public function dashboard(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'system.access');
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page('Painel administrativo', 'admin/dashboard', ['user' => $guard, 'role' => $this->authorization->primaryRole($guard)]);
    }
}
