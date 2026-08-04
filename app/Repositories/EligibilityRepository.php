<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EligibilityRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function rulesForMatch(array $match): array
    {
        $sql = "SELECT er.* FROM regulation_eligibility_rules er INNER JOIN regulations r ON r.id=er.regulation_id WHERE r.championship_id=? AND r.status='published' AND er.destination_phase_id=? AND er.status='active'";
        $statement = $this->pdo->prepare($sql); $statement->execute([(int)$match['championship_id'], (int)$match['phase_id']]); return $statement->fetchAll();
    }
    public function ruleForMatch(array $match, int $ruleId): ?array
    {
        foreach ($this->rulesForMatch($match) as $rule) if ((int) $rule['id'] === $ruleId) return $rule;
        return null;
    }
    public function participation(int $athleteId, int $teamId, int $phaseId, string $type): int
    {
        $role = $type === 'starter' ? " AND mlp.role='starter'" : '';
        if ($type === 'played') {
            $sql = "SELECT COUNT(DISTINCT x.match_id) FROM (SELECT ml.match_id FROM match_lineups ml INNER JOIN match_lineup_players mlp ON mlp.lineup_id=ml.id INNER JOIN matches m ON m.id=ml.match_id WHERE mlp.athlete_id=? AND ml.team_id=? AND m.phase_id=? AND m.status IN ('finished','homologated') UNION SELECT ms.match_id FROM match_substitutions ms INNER JOIN matches m ON m.id=ms.match_id WHERE ms.athlete_in_id=? AND ms.team_id=? AND m.phase_id=? AND ms.valid=1 AND m.status IN ('finished','homologated')) x";
            $statement=$this->pdo->prepare($sql);$statement->execute([$athleteId,$teamId,$phaseId,$athleteId,$teamId,$phaseId]);return(int)$statement->fetchColumn();
        }
        $statement=$this->pdo->prepare("SELECT COUNT(DISTINCT ml.match_id) FROM match_lineups ml INNER JOIN match_lineup_players mlp ON mlp.lineup_id=ml.id INNER JOIN matches m ON m.id=ml.match_id WHERE mlp.athlete_id=? AND ml.team_id=? AND m.phase_id=? AND ml.status='confirmed' AND m.status IN ('finished','homologated')".$role);$statement->execute([$athleteId,$teamId,$phaseId]);return(int)$statement->fetchColumn();
    }
    public function registration(int $championshipId,int $teamId,int $athleteId): ?array { $s=$this->pdo->prepare("SELECT * FROM athlete_registrations WHERE championship_id=? AND team_id=? AND athlete_id=? AND status='approved' LIMIT 1");$s->execute([$championshipId,$teamId,$athleteId]);return$s->fetch()?:null; }
    public function activeSuspension(int $championshipId,int $athleteId,int $matchId): bool { $s=$this->pdo->prepare("SELECT EXISTS(SELECT 1 FROM discipline_suspensions WHERE championship_id=? AND athlete_id=? AND person_type='athlete' AND status='active' AND fulfilled_matches < total_matches AND (generating_match_id IS NULL OR generating_match_id <> ?))");$s->execute([$championshipId,$athleteId,$matchId]);return(bool)$s->fetchColumn(); }
    public function documentsComplete(int $regulationId, int $athleteId): bool { $sql="SELECT NOT EXISTS(SELECT 1 FROM regulation_required_documents rrd WHERE rrd.regulation_id=? AND NOT EXISTS (SELECT 1 FROM athlete_documents ad WHERE ad.athlete_id=? AND ad.document_type_id=rrd.document_type_id AND ad.status='approved' AND (ad.expires_at IS NULL OR ad.expires_at>=CURDATE())))"; $s=$this->pdo->prepare($sql);$s->execute([$regulationId,$athleteId]);return(bool)$s->fetchColumn(); }
    public function exception(int $championshipId,int $teamId,int $athleteId,?int $ruleId,int $matchId): bool { $s=$this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM regulation_eligibility_exceptions WHERE championship_id=? AND team_id=? AND athlete_id=? AND (rule_id=? OR rule_id IS NULL) AND (match_id=? OR match_id IS NULL) AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at>=NOW()))');$s->execute([$championshipId,$teamId,$athleteId,$ruleId,$matchId]);return(bool)$s->fetchColumn(); }
    public function grant(array $data): int { $s=$this->pdo->prepare('INSERT INTO regulation_eligibility_exceptions (championship_id,athlete_id,team_id,rule_id,match_id,phase_id,ignored_rule,reason,expires_at,granted_by,granted_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');$now=date('Y-m-d H:i:s');$s->execute([$data['championship_id'],$data['athlete_id'],$data['team_id'],$data['rule_id']?:null,$data['match_id']?:null,$data['phase_id']?:null,$data['ignored_rule'],$data['reason'],$data['expires_at']?:null,$data['granted_by'],$now,$now,$now]);return(int)$this->pdo->lastInsertId(); }
}
