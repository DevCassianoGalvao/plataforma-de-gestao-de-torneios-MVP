<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class HomeController
{
    public function index(Request $request, array $params = []): Response
    {
        return Response::html(View::page('Fundacao tecnica', View::render('home')));
    }
}
