<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;

final class PlaceholderController extends Controller
{
    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page((string) ($params[0] ?? 'Modulo'), 'placeholders/module', ['title' => (string) ($params[0] ?? 'Modulo')]);
    }
}
