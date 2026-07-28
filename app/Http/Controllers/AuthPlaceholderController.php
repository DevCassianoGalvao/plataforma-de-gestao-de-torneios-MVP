<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Core\View;

final class AuthPlaceholderController
{
    public function show(Request $request, array $params = []): Response
    {
        return Response::html(View::page('Login futuro', View::render('auth/login-placeholder')));
    }

    public function submit(Request $request, array $params = []): Response
    {
        Security::verifyCsrf($request->body['_csrf'] ?? null);
        return Response::html(View::page('Login futuro', View::render('auth/login-placeholder', [
            'message' => 'Autenticacao completa sera implementada na etapa 2.',
        ])), 501);
    }
}
