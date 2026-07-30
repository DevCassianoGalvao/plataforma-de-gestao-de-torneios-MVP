<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\NewsRepository;
use App\Repositories\PublicPortalRepository;
use App\Repositories\TransferRepository;
use App\Services\StorageService;

final class PublicPortalController
{
    public function __construct(private readonly PublicPortalRepository $portal, private readonly NewsRepository $news, private readonly TransferRepository $transfers, private readonly StorageService $storage) {}

    public function home(Request $request, array $params = []): Response
    {
        $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $id = (int) $championship['id']; return $this->page($request, $championship['name'], 'public/portal/home', ['championship' => $championship, 'currentPhase' => $this->portal->currentPhase($id), 'nextMatches' => $this->portal->nextMatches($id), 'results' => $this->portal->results($id, 6), 'standings' => $this->portal->standings($id), 'knockout' => $this->portal->knockout($id), 'scorers' => $this->portal->leaderboard($id, 'goals', 5), 'assists' => $this->portal->leaderboard($id, 'assists', 5), 'news' => $this->publishedNews($championship), 'transfers' => $this->publishedTransfers($id), 'sponsors' => $this->portal->sponsors($id)]);
    }

    public function nextMatches(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Proximos jogos', 'next', $this->portal->nextMatches((int) $this->id($params[0] ?? ''))); }
    public function results(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Resultados', 'results', $this->portal->results((int) $this->id($params[0] ?? ''), 50)); }
    public function standings(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Classificacao', 'standings', $this->portal->standings((int) $this->id($params[0] ?? ''))); }
    public function groups(Request $request, array $params = []): Response { return Response::redirect(Config::url('/campeonatos/' . rawurlencode((string) ($params[0] ?? '')) . '/classificacao')); }
    public function knockout(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $items = $this->portal->knockout((int) $championship['id']); if (!$items) return Response::redirect(Config::url('/campeonatos/' . rawurlencode((string) $championship['slug']) . '/classificacao')); return $this->page($request, 'Mata-mata', 'public/portal/list', ['championship' => $championship, 'kind' => 'knockout', 'items' => $items]); }
    public function teams(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $search = trim((string) ($request->query['q'] ?? '')); return $this->page($request, 'Equipes', 'public/portal/list', ['championship' => $championship, 'kind' => 'teams', 'search' => $search, 'items' => $this->portal->teams((int) $championship['id'], $search)]); }
    public function team(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $page = max(1, (int) ($request->query['page'] ?? 1)); $perPage = 16; $team = $this->portal->team((int) $championship['id'], (string) ($params[1] ?? ''), $perPage, ($page - 1) * $perPage); if (!$team) return $this->notFound('Equipe nao encontrada.'); return $this->page($request, $team['name'], 'public/portal/team', ['championship' => $championship, 'team' => $team, 'page' => $page, 'pages' => max(1, (int) ceil(((int) $team['athletes_total']) / $perPage))]); }
    public function athletes(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $search = trim((string) ($request->query['q'] ?? '')); return $this->page($request, 'Atletas', 'public/portal/list', ['championship' => $championship, 'kind' => 'athletes', 'search' => $search, 'items' => $this->portal->athletes((int) $championship['id'], $search)]); }
    public function athlete(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $athlete = $this->portal->athlete((int) $championship['id'], (int) ($params[1] ?? 0)); if (!$athlete) return $this->notFound('Atleta nao encontrado.'); return $this->page($request, $athlete['sporting_name'] ?: $athlete['full_name'], 'public/portal/athlete', ['championship' => $championship, 'athlete' => $athlete]); }
    public function scorers(Request $request, array $params = []): Response { return $this->leaderboardPage($request, $params, 'Artilharia', 'goals'); }
    public function assists(Request $request, array $params = []): Response { return $this->leaderboardPage($request, $params, 'Assistencias', 'assists'); }
    public function cards(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Cartoes', 'cards', $this->portal->cards((int) $this->id($params[0] ?? ''), 50)); }
    public function suspensions(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; return $this->page($request, 'Suspensoes', 'public/portal/suspensions', ['championship' => $championship, 'items' => $this->portal->suspensions((int) $championship['id'])]); }
    public function regulation(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Regulamento', 'regulation', $this->portal->regulation((int) $this->id($params[0] ?? ''))); }
    public function champion(Request $request, array $params = []): Response { return $this->listPage($request, $params, 'Campeao e vice', 'champion', $this->portal->champion((int) $this->id($params[0] ?? ''))); }
    public function match(Request $request, array $params = []): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $match = $this->portal->match((int) $championship['id'], (int) ($params[1] ?? 0)); if (!$match) return $this->notFound('Partida nao encontrada.'); return $this->page($request, 'Partida', 'public/portal/match', ['championship' => $championship, 'match' => $match]); }

    public function asset(Request $request, array $params = []): Response
    {
        $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $map = ['logo' => 'logo_path', 'logo-light' => 'logo_light_path', 'logo-dark' => 'logo_dark_path', 'banner' => 'banner_path', 'favicon' => 'favicon_path', 'social' => 'social_image_path']; $field = $map[(string) ($params[1] ?? '')] ?? null; if (!$field || empty($championship[$field])) return $this->notFound('Asset nao encontrado.'); $file = $this->storage->read((string) $championship[$field]); if (!$file) return $this->notFound('Asset nao encontrado.'); return new Response($file['body'], 200, ['Content-Type' => $file['mime'], 'Cache-Control' => 'public, max-age=3600']);
    }

    public function teamAsset(Request $request, array $params = []): Response
    {
        $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $team = $this->portal->team((int) $championship['id'], (string) ($params[1] ?? '')); if (!$team || empty($team['shield_path'])) return $this->notFound('Escudo nao encontrado.'); $file = $this->storage->read((string) $team['shield_path']); if (!$file) return $this->notFound('Escudo nao encontrado.'); return new Response($file['body'], 200, ['Content-Type' => $file['mime'], 'Cache-Control' => 'public, max-age=3600']);
    }

    public function athleteAsset(Request $request, array $params = []): Response
    {
        $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; $athlete = $this->portal->athlete((int) $championship['id'], (int) ($params[1] ?? 0)); if (!$athlete || empty($athlete['photo_path'])) return $this->notFound('Foto nao encontrada.'); $file = $this->storage->read((string) $athlete['photo_path']); if (!$file) return $this->notFound('Foto nao encontrada.'); return new Response($file['body'], 200, ['Content-Type' => $file['mime'], 'Cache-Control' => 'public, no-cache, must-revalidate']);
    }

    public function sitemap(Request $request, array $params = []): Response
    {
        $urls = []; foreach ($this->portal->publicChampionships() as $championship) { $base = Config::absoluteUrl('/campeonatos/' . rawurlencode($championship['slug'])); foreach (['', '/proximos-jogos', '/resultados', '/classificacao', '/equipes', '/atletas', '/artilharia', '/assistencias', '/cartoes', '/suspensoes', '/noticias', '/vai-e-vem', '/regulamento', '/campeao'] as $path) $urls[] = '<url><loc>' . $this->xml($base . $path) . '</loc><lastmod>' . $this->xml(substr((string) $championship['updated_at'], 0, 10)) . '</lastmod></url>'; if ($this->portal->knockout((int) $championship['id'])) $urls[] = '<url><loc>' . $this->xml($base . '/mata-mata') . '</loc><lastmod>' . $this->xml(substr((string) $championship['updated_at'], 0, 10)) . '</lastmod></url>'; } return new Response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode('', $urls) . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Request $request, array $params = []): Response { return new Response("User-agent: *\nAllow: /\nSitemap: " . Config::absoluteUrl('/sitemap.xml') . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']); }

    private function listPage(Request $request, array $params, string $title, string $kind, mixed $items): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; return $this->page($request, $title, 'public/portal/list', ['championship' => $championship, 'kind' => $kind, 'items' => $items]); }
    private function leaderboardPage(Request $request, array $params, string $title, string $kind): Response { $championship = $this->championship($params[0] ?? ''); if ($championship instanceof Response) return $championship; return $this->page($request, $title, 'public/portal/list', ['championship' => $championship, 'kind' => $kind, 'items' => $this->portal->leaderboard((int) $championship['id'], $kind, 50)]); }
    private function championship(string $slug): array|Response { $championship = $this->portal->championship($slug); return $championship ?: $this->notFound('Campeonato nao encontrado.'); }
    private function id(string $slug): int { $championship = $this->portal->championship($slug); return (int) ($championship['id'] ?? 0); }
    private function publishedNews(array $championship): array { return $this->news->listPublished((int) $championship['id'], '', 4); }
    private function publishedTransfers(int $championshipId): array { return $this->transfers->listPublic($championshipId, [], 4); }
    private function page(Request $request, string $title, string $view, array $data): Response { $championship = $data['championship']; $base = Config::url('/campeonatos/' . $championship['slug']); $image = !empty($championship['social_image_path']) ? Config::absoluteUrl('/campeonatos/' . $championship['slug'] . '/assets/social') : (!empty($championship['logo_path']) ? Config::absoluteUrl('/campeonatos/' . $championship['slug'] . '/assets/logo') : null); $data['seo'] = ['title' => $title . ' | ' . $championship['name'], 'description' => trim(mb_substr((string) ($championship['description'] ?: 'Portal oficial de ' . $championship['name']), 0, 155)), 'canonical' => Config::absoluteUrl(Config::stripBasePath($request->path)), 'image' => $image, 'favicon' => !empty($championship['favicon_path']) ? Config::absoluteUrl('/campeonatos/' . $championship['slug'] . '/assets/favicon') : null, 'base' => $base]; return Response::html(View::render('layouts/public', ['title' => $title, 'content' => View::render($view, $data), 'championship' => $championship, 'hasKnockout' => !empty($data['hasKnockout']) || $this->portal->knockout((int) $championship['id']) !== [], 'seo' => $data['seo']])); }
    private function notFound(string $message): Response { return Response::html(View::render('errors/404', ['path' => $message]), 404); }
    private function xml(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
}
