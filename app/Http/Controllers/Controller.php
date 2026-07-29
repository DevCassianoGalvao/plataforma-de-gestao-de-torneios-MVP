<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;

abstract class Controller
{
    public function __construct(protected readonly UserRepository $users, protected readonly AuthorizationService $authorization, protected readonly AuditService $audit)
    {
    }

    protected function guard(Request $request, ?string $permission = null): array|Response
    {
        $user = Auth::user();
        if (!$user) {
            return Response::redirect(Config::url('/login') . '?next=' . urlencode($request->path));
        }
        if ($permission !== null && $this->authorization->cannot($user, $permission)) {
            $this->audit->record('authorization.denied', (int) $user['id'], 'permission', $permission, [], $request);
            return Response::forbidden();
        }
        return $user;
    }

    protected function page(string $title, string $view, array $data = []): Response
    {
        return Response::html(View::page($title, View::render($view, $data)));
    }

    protected function errorPage(string $title, string $view, array $data, int $status = 422): Response
    {
        return Response::html(View::page($title, View::render($view, $data)), $status);
    }

    protected function publicPage(Request $request, string $title, array $championship, string $view, array $data = []): Response
    {
        $base = Config::url('/campeonatos/' . $championship['slug']); $image = !empty($championship['social_image_path']) ? Config::url('/campeonatos/' . $championship['slug'] . '/assets/social') : (!empty($championship['logo_path']) ? Config::url('/campeonatos/' . $championship['slug'] . '/assets/logo') : null); $seo = ['title' => $title . ' | ' . $championship['name'], 'description' => trim(mb_substr((string) ($championship['description'] ?: 'Portal oficial de ' . $championship['name']), 0, 155)), 'canonical' => Config::url(Config::stripBasePath($request->path)), 'image' => $image, 'favicon' => !empty($championship['favicon_path']) ? Config::url('/campeonatos/' . $championship['slug'] . '/assets/favicon') : null, 'base' => $base]; return Response::html(View::render('layouts/public', array_merge($data, ['title' => $title, 'content' => View::render($view, $data), 'championship' => $championship, 'seo' => $seo])));
    }

    protected function validCsrf(Request $request): bool
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
