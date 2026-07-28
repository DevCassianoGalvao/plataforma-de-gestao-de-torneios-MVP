<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Policies\AuthPolicy;
use App\Services\ProductNavigationService;
use App\Services\ScopeService;
use App\Support\Database;
use App\Support\Security;
use App\Support\View;

final class ProductNavigationController
{
    private function navigation(): ProductNavigationService { return new ProductNavigationService(Database::connection()); }

    public function home(): never
    {
        $user = AuthPolicy::requireUser();
        Security::redirect($this->navigation()->landing($user));
    }

    public function global(array $params): string
    {
        $user = AuthPolicy::requireUser();
        $area = (string) $params['area'];
        if (!$this->navigation()->allowsGlobal($user, $area)) return $this->forbidden();
        return $this->renderGlobal($user, $area);
    }

    public function tournament(array $params): string
    {
        $user = AuthPolicy::requireUser();
        $tournament = $this->navigation()->tournament($user, (string) $params['championship']);
        if (!$tournament) return $this->notFound();
        return $this->renderTournament($user, $tournament, 'dashboard');
    }

    public function tournamentModule(array $params): string
    {
        $user = AuthPolicy::requireUser();
        $tournament = $this->navigation()->tournament($user, (string) $params['championship']);
        if (!$tournament) return $this->notFound();
        $module = (string) $params['module'];
        if (!$this->navigation()->module($module)) return $this->notFound();
        if (!$this->navigation()->canUseTournamentModule($user, $tournament, $module)) return $this->forbidden();
        return $this->renderTournament($user, $tournament, $module, $params['resource'] ?? null);
    }

    public function assignedMatches(): string
    {
        $user = AuthPolicy::requireUser();
        if (!$this->navigation()->allowsGlobal($user, 'partidas/atribuidas')) return $this->forbidden();
        $statement = Database::connection()->prepare('SELECT m.id,m.status,m.scheduled_at,h.name home_name,a.name away_name,t.name tournament_name,t.slug tournament_slug FROM match_operator_assignments x JOIN matches m ON m.id=x.match_id JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id JOIN tournaments t ON t.id=m.tournament_id WHERE x.user_id=? AND x.status="active" AND x.deleted_at IS NULL AND m.deleted_at IS NULL ORDER BY m.scheduled_at');
        $statement->execute([(int) $user['id']]);
        $records = $statement->fetchAll();
        foreach ($records as &$record) {
            $matchId = (int) $record['id'];
            unset($record['id']);
            $record['match_href'] = '/admin/partidas/'.$matchId;
            $record['operation_href'] = '/admin/partidas/'.$matchId.'/operar';
        }
        unset($record);
        return View::render('admin/product-page', ['title'=>'Partidas atribuidas','user'=>$user,'navigation'=>$this->navigation()->menu($user),'module'=>['title'=>'Partidas atribuidas','description'=>'Partidas liberadas para sua operacao.','action'=>'Abrir central'],'records'=>$records,'breadcrumbs'=>[['label'=>'Administracao','href'=>'/admin'],['label'=>'Partidas atribuidas','href'=>null]]]);
    }

    public function matchDetail(array $params): string
    {
        $matchId = (int) $params['match'];
        $user = AuthPolicy::requirePermission('view', 'matches', $matchId);
        return $this->renderMatch($user, $matchId, false);
    }

    public function matchOperation(array $params): string
    {
        $matchId = (int) $params['match'];
        $user = AuthPolicy::requirePermission('operate_match', 'matches', $matchId);
        return $this->renderMatch($user, $matchId, true);
    }

    public function legacyOperation(array $params): string
    {
        AuthPolicy::requireSuperAdmin();
        return (new TournamentOperationController())->dashboard($params);
    }

    public function legacyAction(array $params, string $action): mixed
    {
        AuthPolicy::requireSuperAdmin();
        $controller = new TournamentOperationController();
        return $controller->{$action}($params);
    }

    private function renderGlobal(array $user, string $area): string
    {
        $definitions = [
            'visao-geral'=>['title'=>'Visao geral','description'=>'Resumo do seu escopo e proximas acoes.','action'=>'Abrir campeonatos'],
            'organizacoes'=>['title'=>'Organizacoes','description'=>'Organizacoes cadastradas na plataforma.','action'=>'Nova organizacao'],
            'projetos'=>['title'=>'Projetos','description'=>'Projetos autorizados para seu perfil.','action'=>'Novo projeto'],
            'campeonatos'=>['title'=>'Campeonatos','description'=>'Campeonatos dentro do seu escopo.','action'=>'Abrir campeonato'],
            'equipes'=>['title'=>'Equipes','description'=>'Equipes dos projetos e campeonatos autorizados.','action'=>'Abrir campeonatos'],
            'usuarios'=>['title'=>'Usuarios','description'=>'Usuarios, perfis e atribuicoes de escopo.','action'=>'Gerenciar acessos'],
            'auditoria'=>['title'=>'Auditoria','description'=>'Historico de acoes e eventos administrativos.','action'=>'Abrir acessos'],
            'configuracoes'=>['title'=>'Configuracoes','description'=>'Preferencias da plataforma e do seu perfil.','action'=>'Editar configuracoes'],
            'conteudo'=>['title'=>'Conteudo publico','description'=>'Noticias, galerias, patrocinadores e publicacoes autorizadas.','action'=>'Abrir campeonatos'],
            'prestacao-de-contas'=>['title'=>'Prestacao de contas','description'=>'Indicadores, documentos, sumulas e exportacoes.','action'=>'Abrir indicadores'],
            'relatorios'=>['title'=>'Relatorios','description'=>'Relatorios e exportacoes autorizadas.','action'=>'Gerar relatorio'],
        ];
        $module = $definitions[$area] ?? null;
        if (!$module) return $this->notFound();
        $records = $area === 'campeonatos' ? $this->navigation()->tournaments((int) $user['id']) : [];
        return View::render('admin/product-page', ['title'=>$module['title'],'user'=>$user,'navigation'=>$this->navigation()->menu($user),'module'=>$module,'records'=>$records,'breadcrumbs'=>[['label'=>'Administracao','href'=>'/admin'],['label'=>$module['title'],'href'=>null]]]);
    }

    private function renderTournament(array $user, array $tournament, string $module, ?string $resource = null): string
    {
        $definition = $module === 'dashboard' ? ['title'=>'Dashboard do campeonato','description'=>'Acompanhe pendencias, competicao e proximas acoes.','action'=>'Abrir equipes'] : $this->navigation()->module($module);
        $records = $this->records((int) $tournament['id'], $module);
        $breadcrumbs = [['label'=>'Campeonatos','href'=>'/admin/campeonatos'],['label'=>$tournament['name'],'href'=>'/admin/campeonatos/'.$tournament['slug']]];
        if ($module !== 'dashboard') $breadcrumbs[] = ['label'=>$definition['title'],'href'=>null];
        if ($resource !== null) $breadcrumbs[] = ['label'=>'Detalhe','href'=>null];
        return View::render('admin/product-page', ['title'=>$definition['title'],'user'=>$user,'tournament'=>$tournament,'navigation'=>$this->navigation()->menu($user, $tournament),'module'=>$definition,'records'=>$records,'breadcrumbs'=>$breadcrumbs,'resource'=>$resource]);
    }

    private function records(int $tournamentId, string $module): array
    {
        $db = Database::connection();
        $queries = [
            'dashboard'=>'SELECT "Equipes" label, COUNT(*) value FROM team_tournament_entries WHERE tournament_id=? AND deleted_at IS NULL UNION ALL SELECT "Inscricoes",COUNT(*) FROM registrations WHERE tournament_id=? AND deleted_at IS NULL UNION ALL SELECT "Partidas",COUNT(*) FROM matches WHERE tournament_id=? AND deleted_at IS NULL',
            'equipes'=>'SELECT t.name,t.short_name,e.status FROM team_tournament_entries e JOIN teams t ON t.id=e.team_id WHERE e.tournament_id=? AND e.deleted_at IS NULL ORDER BY t.name LIMIT 12',
            'atletas'=>'SELECT COALESCE(p.public_name,p.full_name) name,t.name team_name,r.status FROM registrations r JOIN people p ON p.id=r.person_id JOIN teams t ON t.id=r.team_id WHERE r.tournament_id=? AND r.registration_type="athlete" AND r.deleted_at IS NULL ORDER BY p.full_name LIMIT 12',
            'inscricoes'=>'SELECT COALESCE(p.public_name,p.full_name) name,t.name team_name,r.status FROM registrations r JOIN people p ON p.id=r.person_id JOIN teams t ON t.id=r.team_id WHERE r.tournament_id=? AND r.deleted_at IS NULL ORDER BY r.updated_at DESC LIMIT 12',
            'partidas'=>'SELECT h.name home_name,a.name away_name,m.status,m.scheduled_at FROM matches m JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id WHERE m.tournament_id=? AND m.deleted_at IS NULL ORDER BY m.scheduled_at LIMIT 12',
            'homologacoes'=>'SELECT h.name home_name,a.name away_name,m.status,m.scheduled_at FROM matches m JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id WHERE m.tournament_id=? AND m.status IN ("awaiting_homologation","homologated","rectified") AND m.deleted_at IS NULL ORDER BY m.scheduled_at LIMIT 12',
            'sumulas'=>'SELECT h.name home_name,a.name away_name,m.status,m.scheduled_at FROM matches m JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id WHERE m.tournament_id=? AND m.deleted_at IS NULL ORDER BY m.scheduled_at DESC LIMIT 12',
        ];
        if (!isset($queries[$module])) return [];
        $statement = $db->prepare($queries[$module]);
        $args = $module === 'dashboard' ? [$tournamentId,$tournamentId,$tournamentId] : [$tournamentId];
        $statement->execute($args);
        return $statement->fetchAll();
    }

    private function renderMatch(array $user, int $matchId, bool $operation): string
    {
        $statement = Database::connection()->prepare('SELECT m.status,m.scheduled_at,m.home_score,m.away_score,t.id tournament_id,t.name tournament_name,t.slug tournament_slug,h.name home_name,a.name away_name,s.name stage_name,r.name round_name FROM matches m JOIN tournaments t ON t.id=m.tournament_id JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id LEFT JOIN stages s ON s.id=m.stage_id LEFT JOIN rounds r ON r.id=m.round_id WHERE m.id=? AND m.deleted_at IS NULL');
        $statement->execute([$matchId]);
        $match = $statement->fetch();
        if (!$match) return $this->notFound();
        $tournament = $this->navigation()->tournament($user, (string) $match['tournament_slug']);
        if (!$tournament) return $this->notFound();
        $match['detail_href'] = '/admin/partidas/'.$matchId;
        $match['operation_href'] = '/admin/partidas/'.$matchId.'/operar';
        $scopes = new ScopeService(Database::connection());
        $canOperate = $scopes->allows((int) $user['id'], 'operate_match', $scopes->context('matches', $matchId));
        $module = $operation ? 'central-da-partida' : 'partida';
        if (!$this->navigation()->canUseTournamentModule($user, $tournament, $module)) return $this->forbidden();
        $definition = $this->navigation()->module($module);
        $breadcrumbs = [
            ['label'=>'Campeonatos','href'=>'/admin/campeonatos'],
            ['label'=>$tournament['name'],'href'=>'/admin/campeonatos/'.$tournament['slug']],
            ['label'=>'Partidas','href'=>'/admin/campeonatos/'.$tournament['slug'].'/partidas'],
            ['label'=>$operation ? 'Operar partida' : 'Detalhe da partida','href'=>null],
        ];
        return View::render('admin/product-page', [
            'title'=>$definition['title'],
            'user'=>$user,
            'tournament'=>$tournament,
            'navigation'=>$this->navigation()->menu($user, $tournament),
            'module'=>$definition,
            'records'=>[],
            'match'=>$match,
            'operation'=>$operation,
            'canOperate'=>$canOperate,
            'breadcrumbs'=>$breadcrumbs,
        ]);
    }

    private function forbidden(): string { http_response_code(403); return View::render('errors/403',['title'=>'Acesso negado']); }
    private function notFound(): string { http_response_code(404); return View::render('errors/404',['title'=>'Pagina nao encontrada']); }
}
