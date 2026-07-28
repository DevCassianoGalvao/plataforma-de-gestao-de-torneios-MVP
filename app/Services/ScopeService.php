<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

/** Resolves a resource to its ownership chain; callers never trust submitted scope ids. */
final class ScopeService
{
    public function __construct(private PDO $db) {}

    public function context(string $entity, int $id): ?array
    {
        $queries = [
            'organizations' => 'SELECT id organization_id,NULL project_id,NULL tournament_id,NULL team_id FROM organizations WHERE id=? AND deleted_at IS NULL',
            'projects' => 'SELECT organization_id,id project_id,NULL tournament_id,NULL team_id FROM projects WHERE id=? AND deleted_at IS NULL',
            'tournaments' => 'SELECT p.organization_id,t.project_id,t.id tournament_id,NULL team_id FROM tournaments t JOIN projects p ON p.id=t.project_id WHERE t.id=? AND t.deleted_at IS NULL',
            'teams' => 'SELECT p.organization_id,t.project_id,NULL tournament_id,t.id team_id FROM teams t JOIN projects p ON p.id=t.project_id WHERE t.id=? AND t.deleted_at IS NULL',
            'venues' => 'SELECT p.organization_id,v.project_id,NULL tournament_id,NULL team_id FROM venues v JOIN projects p ON p.id=v.project_id WHERE v.id=? AND v.deleted_at IS NULL',
            'team_tournament_entries' => 'SELECT p.organization_id,t.project_id,e.tournament_id,e.team_id FROM team_tournament_entries e JOIN tournaments t ON t.id=e.tournament_id JOIN projects p ON p.id=t.project_id WHERE e.id=? AND e.deleted_at IS NULL',
            'team_memberships' => 'SELECT p.organization_id,t.project_id,NULL tournament_id,m.team_id FROM team_memberships m JOIN teams t ON t.id=m.team_id JOIN projects p ON p.id=t.project_id WHERE m.id=? AND m.deleted_at IS NULL',
            'registrations' => 'SELECT p.organization_id,t.project_id,r.tournament_id,r.team_id FROM registrations r JOIN tournaments t ON t.id=r.tournament_id JOIN projects p ON p.id=t.project_id WHERE r.id=? AND r.deleted_at IS NULL',
            'stages' => 'SELECT p.organization_id,t.project_id,s.tournament_id,NULL team_id FROM stages s JOIN tournaments t ON t.id=s.tournament_id JOIN projects p ON p.id=t.project_id WHERE s.id=? AND s.deleted_at IS NULL',
            'groups_competition' => 'SELECT p.organization_id,t.project_id,s.tournament_id,NULL team_id FROM groups_competition g JOIN stages s ON s.id=g.stage_id JOIN tournaments t ON t.id=s.tournament_id JOIN projects p ON p.id=t.project_id WHERE g.id=? AND g.deleted_at IS NULL',
            'rounds' => 'SELECT p.organization_id,t.project_id,s.tournament_id,NULL team_id FROM rounds r JOIN stages s ON s.id=r.stage_id JOIN tournaments t ON t.id=s.tournament_id JOIN projects p ON p.id=t.project_id WHERE r.id=? AND r.deleted_at IS NULL',
            'matches' => 'SELECT p.organization_id,t.project_id,m.tournament_id,NULL team_id,m.id match_id,m.home_team_id,m.away_team_id FROM matches m JOIN tournaments t ON t.id=m.tournament_id JOIN projects p ON p.id=t.project_id WHERE m.id=? AND m.deleted_at IS NULL',
            'match_events' => 'SELECT p.organization_id,t.project_id,m.tournament_id,e.team_id FROM match_events e JOIN matches m ON m.id=e.match_id JOIN tournaments t ON t.id=m.tournament_id JOIN projects p ON p.id=t.project_id WHERE e.id=? AND e.deleted_at IS NULL',
            'match_reports' => 'SELECT p.organization_id,t.project_id,m.tournament_id,NULL team_id FROM match_reports r JOIN matches m ON m.id=r.match_id JOIN tournaments t ON t.id=m.tournament_id JOIN projects p ON p.id=t.project_id WHERE r.id=? AND r.deleted_at IS NULL',
            'match_lineups' => 'SELECT p.organization_id,t.project_id,m.tournament_id,l.team_id FROM match_lineups l JOIN matches m ON m.id=l.match_id JOIN tournaments t ON t.id=m.tournament_id JOIN projects p ON p.id=t.project_id WHERE l.id=? AND l.deleted_at IS NULL',
            'documents' => 'SELECT p.organization_id,t.project_id,d.tournament_id,d.team_id FROM documents d JOIN tournaments t ON t.id=d.tournament_id JOIN projects p ON p.id=t.project_id WHERE d.id=? AND d.deleted_at IS NULL',
            'news_posts' => 'SELECT p.organization_id,t.project_id,n.tournament_id,NULL team_id FROM news_posts n JOIN tournaments t ON t.id=n.tournament_id JOIN projects p ON p.id=t.project_id WHERE n.id=? AND n.deleted_at IS NULL',
            'galleries' => 'SELECT p.organization_id,t.project_id,g.tournament_id,NULL team_id FROM galleries g JOIN tournaments t ON t.id=g.tournament_id JOIN projects p ON p.id=t.project_id WHERE g.id=? AND g.deleted_at IS NULL',
            'transfers' => 'SELECT p.organization_id,t.project_id,x.tournament_id,x.to_team_id team_id FROM transfers x JOIN tournaments t ON t.id=x.tournament_id JOIN projects p ON p.id=t.project_id WHERE x.id=? AND x.deleted_at IS NULL',
            'disciplinary_records' => 'SELECT p.organization_id,t.project_id,d.tournament_id,NULL team_id FROM disciplinary_records d JOIN tournaments t ON t.id=d.tournament_id JOIN projects p ON p.id=t.project_id WHERE d.id=? AND d.deleted_at IS NULL',
            'suspensions' => 'SELECT p.organization_id,t.project_id,s.tournament_id,NULL team_id FROM suspensions s JOIN tournaments t ON t.id=s.tournament_id JOIN projects p ON p.id=t.project_id WHERE s.id=? AND s.deleted_at IS NULL',
            'awards' => 'SELECT p.organization_id,t.project_id,a.tournament_id,a.team_id FROM awards a JOIN tournaments t ON t.id=a.tournament_id JOIN projects p ON p.id=t.project_id WHERE a.id=? AND a.deleted_at IS NULL',
            'tournament_settings' => 'SELECT p.organization_id,t.project_id,s.tournament_id,NULL team_id FROM tournament_settings s JOIN tournaments t ON t.id=s.tournament_id JOIN projects p ON p.id=t.project_id WHERE s.id=? AND s.deleted_at IS NULL',
            'tournament_themes' => 'SELECT p.organization_id,t.project_id,x.tournament_id,NULL team_id FROM tournament_themes x JOIN tournaments t ON t.id=x.tournament_id JOIN projects p ON p.id=t.project_id WHERE x.id=? AND x.deleted_at IS NULL',
        ];
        if ($entity === 'people') return $this->personContext($id);
        if (!isset($queries[$entity])) return null;
        $s=$this->db->prepare($queries[$entity]); $s->execute([$id]); $row=$s->fetch();
        if (!$row) return null;
        if (isset($row['home_team_id'])) $row['team_ids']=array_values(array_filter([(int)$row['home_team_id'],(int)$row['away_team_id']]));
        return $row;
    }

    public function fromPayload(string $entity, array $data): ?array
    {
        foreach (['tournament_id'=>'tournaments','project_id'=>'projects','team_id'=>'teams','organization_id'=>'organizations'] as $field=>$target) {
            if (isset($data[$field]) && (int)$data[$field]>0) return $this->context($target,(int)$data[$field]);
        }
        if ($entity === 'organizations') return ['organization_id'=>null,'project_id'=>null,'tournament_id'=>null,'team_id'=>null];
        return null;
    }

    public function allows(int $userId, string $permission, ?array $context): bool
    {
        if ($this->isSuperAdmin($userId)) return true;
        if (!$context) return false;
        $sql='SELECT a.organization_id,a.project_id,a.tournament_id,a.team_id FROM user_role_assignments a JOIN role_permission_assignments rp ON rp.role_id=a.role_id JOIN permissions p ON p.id=rp.permission_id WHERE a.user_id=? AND a.status="active" AND a.deleted_at IS NULL AND p.permission_key=?';
        $s=$this->db->prepare($sql); $s->execute([$userId,$permission]);
        foreach ($s->fetchAll() as $assignment) {
            foreach (['organization_id','project_id','tournament_id','team_id'] as $field) {
                if ($assignment[$field] === null) continue;
                $listKey=$field==='team_id'?'team_ids':$field.'_ids';
                $values=$context[$listKey] ?? [$context[$field] ?? 0];
                if (!in_array((int)$assignment[$field],array_map('intval',(array)$values),true)) continue 2;
            }
            if (!$this->matchOperatorAllows($userId,$permission,$context)) continue;
            return true;
        }
        return false;
    }

    public function isSuperAdmin(int $userId): bool
    {
        $s=$this->db->prepare('SELECT 1 FROM user_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.user_id=? AND r.role_key="superadmin" AND a.status="active" AND a.deleted_at IS NULL LIMIT 1');
        $s->execute([$userId]); return (bool)$s->fetchColumn();
    }

    private function personContext(int $id): ?array
    {
        $exists=$this->db->prepare('SELECT id FROM people WHERE id=? AND deleted_at IS NULL'); $exists->execute([$id]); if (!$exists->fetchColumn()) return null;
        $s=$this->db->prepare('SELECT DISTINCT p.organization_id,t.project_id,e.tournament_id,m.team_id FROM team_memberships m JOIN teams t ON t.id=m.team_id JOIN projects p ON p.id=t.project_id LEFT JOIN team_tournament_entries e ON e.team_id=m.team_id AND e.deleted_at IS NULL WHERE m.person_id=? AND m.deleted_at IS NULL');
        $s->execute([$id]); $rows=$s->fetchAll(); if (!$rows) return ['organization_id'=>null,'project_id'=>null,'tournament_id'=>null,'team_id'=>null];
        $first=$rows[0]; foreach (['organization_id','project_id','tournament_id','team_id'] as $field) { $key=$field==='team_id'?'team_ids':$field.'_ids'; $first[$key]=array_values(array_unique(array_filter(array_map(fn($r)=>(int)($r[$field]??0),$rows)))); }
        return $first;
    }

    private function matchOperatorAllows(int $userId,string $permission,array $context): bool
    {
        if (!isset($context['match_id']) || !in_array($permission,['view','manage_lineup','operate_match','finish_match','request_rectification'],true)) return true;
        $operator=$this->db->prepare('SELECT 1 FROM user_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.user_id=? AND r.role_key="match_operator" AND a.status="active" AND a.deleted_at IS NULL LIMIT 1');
        $operator->execute([$userId]); if (!$operator->fetchColumn()) return true;
        $assigned=$this->db->prepare('SELECT 1 FROM match_operator_assignments WHERE user_id=? AND match_id=? AND status="active" AND deleted_at IS NULL LIMIT 1');
        $assigned->execute([$userId,(int)$context['match_id']]); if ($assigned->fetchColumn()) return true;
        $teams=array_values(array_filter(array_map('intval',(array)($context['team_ids']??[])))); if (!$teams) return false;
        $placeholders=implode(',',array_fill(0,count($teams),'?'));
        $teamScope=$this->db->prepare('SELECT 1 FROM user_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.user_id=? AND r.role_key="match_operator" AND a.team_id IN ('.$placeholders.') AND a.status="active" AND a.deleted_at IS NULL LIMIT 1');
        $teamScope->execute([$userId,...$teams]); return (bool)$teamScope->fetchColumn();
    }
}
