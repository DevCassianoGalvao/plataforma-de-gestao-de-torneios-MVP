<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Resolves product navigation from persisted role assignments and scope relations. */
final class ProductNavigationService
{
    public function __construct(private PDO $db) {}

    public function roles(int $userId): array
    {
        $statement = $this->db->prepare('SELECT DISTINCT r.role_key FROM user_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.user_id=? AND a.status="active" AND a.deleted_at IS NULL');
        $statement->execute([$userId]);
        return array_column($statement->fetchAll(), 'role_key');
    }

    public function landing(array $user): string
    {
        $roles = $this->roles((int) $user['id']);
        $tournament = $this->firstTournament((int) $user['id']);
        if (in_array('superadmin', $roles, true)) return '/admin/visao-geral';
        if (in_array('project_admin', $roles, true)) return '/admin/projetos';
        if (in_array('tournament_organizer', $roles, true) && $tournament) return '/admin/campeonatos/'.$tournament['slug'];
        if (in_array('team_manager', $roles, true) && $tournament) return '/admin/campeonatos/'.$tournament['slug'].'/minha-equipe';
        if (in_array('match_operator', $roles, true)) return '/admin/partidas/atribuidas';
        if (in_array('communication', $roles, true)) return '/admin/conteudo';
        if (in_array('auditor', $roles, true)) return '/admin/prestacao-de-contas';
        return '/admin/visao-geral';
    }

    public function allowsGlobal(array $user, string $area): bool
    {
        $roles = $this->roles((int) $user['id']);
        if (in_array('superadmin', $roles, true)) return true;
        $allowed = [
            'visao-geral' => ['project_admin'],
            'projetos' => ['project_admin'],
            'campeonatos' => ['project_admin', 'tournament_organizer'],
            'equipes' => ['project_admin'],
            'partidas/atribuidas' => ['match_operator'],
            'conteudo' => ['communication'],
            'prestacao-de-contas' => ['auditor'],
            'relatorios' => ['project_admin', 'auditor'],
            'usuarios' => [],
            'auditoria' => [],
            'configuracoes' => [],
        ];
        return (bool) array_intersect($roles, $allowed[$area] ?? []);
    }

    public function menu(array $user, ?array $tournament = null): array
    {
        $roles = $this->roles((int) $user['id']);
        $items = [];
        $add = static function (array &$items, string $label, string $href, string $key): void { $items[] = compact('label', 'href', 'key'); };
        if (in_array('superadmin', $roles, true)) {
            $add($items, 'Visao geral', '/admin/visao-geral', 'visao-geral');
            $add($items, 'Organizacoes', '/admin/organizacoes', 'organizacoes');
            $add($items, 'Projetos', '/admin/projetos', 'projetos');
            $add($items, 'Campeonatos', '/admin/campeonatos', 'campeonatos');
            $add($items, 'Usuarios', '/admin/usuarios', 'usuarios');
            $add($items, 'Permissoes', '/admin/access', 'permissoes');
            $add($items, 'Auditoria', '/admin/auditoria', 'auditoria');
            $add($items, 'Configuracoes', '/admin/configuracoes', 'configuracoes');
        } elseif (in_array('project_admin', $roles, true)) {
            $add($items, 'Dashboard', '/admin/visao-geral', 'visao-geral');
            $add($items, 'Projetos', '/admin/projetos', 'projetos');
            $add($items, 'Campeonatos', '/admin/campeonatos', 'campeonatos');
            $add($items, 'Equipes', '/admin/equipes', 'equipes');
            $add($items, 'Relatorios', '/admin/relatorios', 'relatorios');
        } elseif (in_array('tournament_organizer', $roles, true) && $tournament) {
            foreach ($this->tournamentMenu($tournament['slug'], ['equipes','inscricoes','grupos','rodadas','partidas','homologacoes','classificacao','mata-mata','sumulas']) as $item) $items[] = $item;
        } elseif (in_array('team_manager', $roles, true) && $tournament) {
            foreach ($this->tournamentMenu($tournament['slug'], ['minha-equipe','atletas','comissao','documentos','inscricoes','escalacoes','partidas']) as $item) $items[] = $item;
        } elseif (in_array('match_operator', $roles, true)) {
            $add($items, 'Partidas atribuidas', '/admin/partidas/atribuidas', 'partidas-atribuidas');
            $add($items, 'Central da partida', '/admin/partidas/atribuidas', 'central-da-partida');
            $add($items, 'Sumulas em preenchimento', '/admin/partidas/atribuidas', 'sumulas');
        } elseif (in_array('communication', $roles, true)) {
            $add($items, 'Noticias', '/admin/conteudo', 'noticias');
            $add($items, 'Galerias', '/admin/conteudo', 'galerias');
            $add($items, 'Craque da rodada', '/admin/conteudo', 'craque-da-rodada');
            $add($items, 'Patrocinadores', '/admin/conteudo', 'patrocinadores');
            $add($items, 'Conteudo publico', '/admin/conteudo', 'conteudo');
        } elseif (in_array('auditor', $roles, true)) {
            $add($items, 'Indicadores', '/admin/prestacao-de-contas', 'prestacao-de-contas');
            $add($items, 'Documentos', '/admin/prestacao-de-contas', 'documentos');
            $add($items, 'Sumulas', '/admin/prestacao-de-contas', 'sumulas');
            $add($items, 'Relatorios', '/admin/relatorios', 'relatorios');
            $add($items, 'Exportacoes', '/admin/prestacao-de-contas', 'exportacoes');
        }
        return $items;
    }

    public function tournaments(int $userId): array
    {
        if ((new ScopeService($this->db))->isSuperAdmin($userId)) {
            return $this->db->query('SELECT t.id,t.name,t.slug,t.season,t.status,p.name project_name FROM tournaments t JOIN projects p ON p.id=t.project_id WHERE t.deleted_at IS NULL ORDER BY t.name')->fetchAll();
        }
        $sql = 'SELECT DISTINCT t.id,t.name,t.slug,t.season,t.status,p.name project_name
            FROM tournaments t JOIN projects p ON p.id=t.project_id
            JOIN user_role_assignments a ON a.user_id=? AND a.status="active" AND a.deleted_at IS NULL
            LEFT JOIN team_tournament_entries e ON e.tournament_id=t.id AND e.team_id=a.team_id AND e.deleted_at IS NULL
            WHERE t.deleted_at IS NULL AND (a.organization_id IS NULL OR a.organization_id=p.organization_id)
              AND (a.project_id IS NULL OR a.project_id=t.project_id)
              AND (a.tournament_id IS NULL OR a.tournament_id=t.id)
              AND (a.team_id IS NULL OR e.id IS NOT NULL)
            ORDER BY t.name';
        $statement = $this->db->prepare($sql);
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function tournament(array $user, string $identifier): ?array
    {
        $statement = ctype_digit($identifier)
            ? $this->db->prepare('SELECT t.id,t.name,t.slug,t.season,t.status,p.name project_name,COALESCE(GROUP_CONCAT(DISTINCT c.name ORDER BY c.display_order SEPARATOR ", "),"") category_name FROM tournaments t JOIN projects p ON p.id=t.project_id LEFT JOIN tournament_categories tc ON tc.tournament_id=t.id AND tc.deleted_at IS NULL LEFT JOIN categories c ON c.id=tc.category_id AND c.deleted_at IS NULL WHERE t.id=? AND t.deleted_at IS NULL GROUP BY t.id')
            : $this->db->prepare('SELECT t.id,t.name,t.slug,t.season,t.status,p.name project_name,COALESCE(GROUP_CONCAT(DISTINCT c.name ORDER BY c.display_order SEPARATOR ", "),"") category_name FROM tournaments t JOIN projects p ON p.id=t.project_id LEFT JOIN tournament_categories tc ON tc.tournament_id=t.id AND tc.deleted_at IS NULL LEFT JOIN categories c ON c.id=tc.category_id AND c.deleted_at IS NULL WHERE t.slug=? AND t.deleted_at IS NULL GROUP BY t.id');
        $statement->execute([$identifier]);
        $tournament = $statement->fetch() ?: null;
        if (!$tournament) return null;
        foreach ($this->tournaments((int) $user['id']) as $allowed) if ((int) $allowed['id'] === (int) $tournament['id']) return $tournament;
        return null;
    }

    public function canUseTournamentModule(array $user, array $tournament, string $module): bool
    {
        $roles = $this->roles((int) $user['id']);
        if (in_array('superadmin', $roles, true) || in_array('project_admin', $roles, true)) return true;
        $areas = [
            'tournament_organizer' => ['dashboard','equipes','equipe','atletas','atleta','comissao','responsaveis','inscricoes','grupos','rodadas','partidas','partida','escalacoes','central-da-partida','homologacoes','retificacoes','classificacao','mata-mata','sumulas','noticias','galerias','transferencias','documentos','prestacao-de-contas','relatorios','configuracoes'],
            'team_manager' => ['dashboard','minha-equipe','equipes','equipe','atletas','atleta','comissao','responsaveis','inscricoes','documentos','escalacoes','partidas','partida','sumulas'],
            'match_operator' => ['partidas','partida','escalacoes','central-da-partida','sumulas'],
            'communication' => ['noticias','galerias','transferencias','documentos'],
            'auditor' => ['documentos','prestacao-de-contas','relatorios','sumulas'],
        ];
        foreach ($roles as $role) if (in_array($module, $areas[$role] ?? [], true)) return true;
        return false;
    }

    public function module(string $key): array
    {
        $modules = [
            'dashboard' => ['title'=>'Dashboard do campeonato','description'=>'Resumo, pendencias e proximas acoes do campeonato.','action'=>'Abrir configuracao'],
            'equipes' => ['title'=>'Equipes','description'=>'Gerencie equipes participantes e seus vinculos ao campeonato.','action'=>'Nova equipe'],
            'equipe' => ['title'=>'Equipe','description'=>'Identidade, elenco, comissao, documentos e jogos da equipe.','action'=>'Ver elenco'],
            'minha-equipe' => ['title'=>'Minha equipe','description'=>'Area de trabalho da equipe sob sua responsabilidade.','action'=>'Ver elenco'],
            'atletas' => ['title'=>'Atletas','description'=>'Cadastros, vinculos, inscricoes e situacao disciplinar.','action'=>'Novo atleta'],
            'atleta' => ['title'=>'Atleta','description'=>'Perfil esportivo, inscricao, documentos e historico.','action'=>'Ver inscricao'],
            'comissao' => ['title'=>'Comissao tecnica','description'=>'Integrantes autorizados, funcoes e vinculos por equipe.','action'=>'Novo integrante'],
            'responsaveis' => ['title'=>'Responsaveis legais','description'=>'Vinculos e autorizacoes de atletas menores.','action'=>'Novo responsavel'],
            'inscricoes' => ['title'=>'Inscricoes','description'=>'Fila de analise, pendencias, aprovacoes e recusas.','action'=>'Analisar inscricoes'],
            'grupos' => ['title'=>'Grupos','description'=>'Distribuicao de equipes e situacao da fase de grupos.','action'=>'Configurar grupos'],
            'rodadas' => ['title'=>'Rodadas e calendario','description'=>'Rodadas, datas, locais e publicacao de agenda.','action'=>'Gerar rodadas'],
            'partidas' => ['title'=>'Partidas','description'=>'Lista, filtros, situacao e acesso a operacao de cada partida.','action'=>'Ver partidas'],
            'partida' => ['title'=>'Partida','description'=>'Dados, escalações, cronologia e relatorio da partida.','action'=>'Abrir central'],
            'escalacoes' => ['title'=>'Escalacoes','description'=>'Preparacao e validacao de escalações antes da partida.','action'=>'Preparar escalação'],
            'central-da-partida' => ['title'=>'Central da partida','description'=>'Operacao ao vivo de uma partida atribuida.','action'=>'Selecionar partida'],
            'homologacoes' => ['title'=>'Homologacoes','description'=>'Conferencia de sumulas e publicacao de resultados oficiais.','action'=>'Revisar pendencias'],
            'retificacoes' => ['title'=>'Retificacoes','description'=>'Solicitacoes, impacto, versoes e decisoes administrativas.','action'=>'Ver solicitacoes'],
            'classificacao' => ['title'=>'Classificacao','description'=>'Tabela recalculada a partir de resultados homologados e regulamento ativo.','action'=>'Ver classificacao'],
            'mata-mata' => ['title'=>'Mata-mata','description'=>'Chave, classificados, avancos e impactos de retificacoes.','action'=>'Ver chave'],
            'sumulas' => ['title'=>'Sumulas','description'=>'Relatorios, versoes e PDFs privados das partidas.','action'=>'Ver sumulas'],
            'noticias' => ['title'=>'Noticias','description'=>'Conteudo editorial e publicacao por campeonato.','action'=>'Nova noticia'],
            'galerias' => ['title'=>'Galerias','description'=>'Fotos, capas, creditos e publicacao.','action'=>'Nova galeria'],
            'transferencias' => ['title'=>'Transferencias','description'=>'Solicitacoes, analises, decisoes e historico de movimentacoes.','action'=>'Ver movimentacoes'],
            'documentos' => ['title'=>'Documentos','description'=>'Biblioteca de arquivos, validade, aprovacao e acesso autorizado.','action'=>'Enviar documento'],
            'prestacao-de-contas' => ['title'=>'Prestacao de contas','description'=>'Indicadores, evidencias e exportacoes do campeonato.','action'=>'Abrir indicadores'],
            'relatorios' => ['title'=>'Relatorios','description'=>'Relatorios operacionais e exportacoes por escopo.','action'=>'Gerar relatorio'],
            'configuracoes' => ['title'=>'Configuracoes do campeonato','description'=>'Identidade, regulamento, categorias, temporada e publicacao.','action'=>'Configurar campeonato'],
        ];
        return $modules[$key] ?? [];
    }

    private function tournamentMenu(string $slug, array $modules): array
    {
        $items = [['label'=>'Dashboard','href'=>'/admin/campeonatos/'.$slug,'key'=>'dashboard']];
        foreach ($modules as $module) {
            $definition = $this->module($module);
            $items[] = ['label'=>$definition['title'] ?? ucfirst($module),'href'=>'/admin/campeonatos/'.$slug.'/'.$module,'key'=>$module];
        }
        return $items;
    }

    private function firstTournament(int $userId): ?array
    {
        return $this->tournaments($userId)[0] ?? null;
    }
}
