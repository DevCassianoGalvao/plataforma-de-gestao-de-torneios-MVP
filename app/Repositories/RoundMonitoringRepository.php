<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RoundMonitoringRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function championshipsFor(int $userId, bool $administrator): array
    {
        if ($administrator) return $this->pdo->query('SELECT id, name FROM championships WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
        $s = $this->pdo->prepare("SELECT c.id, c.name FROM championships c INNER JOIN championship_user_assignments a ON a.championship_id=c.id WHERE a.user_id=? AND a.assignment_type IN ('accountability', 'organizer') AND c.deleted_at IS NULL ORDER BY c.name");
        $s->execute([$userId]); return $s->fetchAll();
    }

    public function allowed(int $championshipId, int $userId, bool $administrator): bool
    {
        if ($administrator) return true;
        $s = $this->pdo->prepare("SELECT 1 FROM championship_user_assignments WHERE championship_id=? AND user_id=? AND assignment_type IN ('accountability', 'organizer') LIMIT 1");
        $s->execute([$championshipId, $userId]); return (bool) $s->fetchColumn();
    }

    public function deadline(int $championshipId): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM championship_document_deadlines WHERE championship_id=? LIMIT 1');
        $s->execute([$championshipId]); return $s->fetch() ?: null;
    }

    public function saveDeadline(int $championshipId, string $mode, ?int $customValue, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $s = $this->pdo->prepare('INSERT INTO championship_document_deadlines (championship_id,deadline_mode,custom_value,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE deadline_mode=VALUES(deadline_mode),custom_value=VALUES(custom_value),updated_at=VALUES(updated_at)');
        $s->execute([$championshipId, $mode, $customValue, $userId, $now, $now]);
    }

    public function filters(): array
    {
        return [
            'championship_id' => '', 'phase_id' => '', 'group_id' => '', 'round_id' => '', 'from' => '', 'to' => '',
            'sport_status' => '', 'approval_status' => '', 'publication_status' => '', 'document_status' => '',
            'team_id' => '', 'operator_id' => '', 'only_pending' => '', 'overdue' => '',
        ];
    }

    public function options(int $userId, bool $administrator): array
    {
        $scope = $administrator ? '1=1' : "EXISTS (SELECT 1 FROM championship_user_assignments cua WHERE cua.championship_id=c.id AND cua.user_id=" . (int) $userId . " AND cua.assignment_type IN ('accountability', 'organizer'))";
        return [
            'phases' => $this->pdo->query("SELECT p.id,p.name,c.name AS championship_name FROM competition_phases p INNER JOIN championships c ON c.id=p.championship_id WHERE c.deleted_at IS NULL AND $scope ORDER BY c.name,p.sequence_number")->fetchAll(),
            'groups' => $this->pdo->query("SELECT g.id,g.name,p.name AS phase_name FROM competition_groups g INNER JOIN competition_phases p ON p.id=g.phase_id INNER JOIN championships c ON c.id=p.championship_id WHERE c.deleted_at IS NULL AND $scope ORDER BY p.sequence_number,g.display_order")->fetchAll(),
            'teams' => $this->pdo->query("SELECT t.id,t.name FROM teams t INNER JOIN championships c ON c.id=t.championship_id WHERE t.deleted_at IS NULL AND $scope ORDER BY t.name")->fetchAll(),
            'operators' => $this->pdo->query("SELECT DISTINCT u.id,u.name FROM users u INNER JOIN match_operator_assignments moa ON moa.user_id=u.id AND moa.status='active' INNER JOIN matches m ON m.id=moa.match_id INNER JOIN championships c ON c.id=m.championship_id WHERE c.deleted_at IS NULL AND $scope ORDER BY u.name")->fetchAll(),
            'rounds' => $this->pdo->query("SELECT r.id,r.round_number,g.name AS group_name,p.name AS phase_name,c.name AS championship_name FROM competition_rounds r INNER JOIN competition_groups g ON g.id=r.group_id INNER JOIN competition_phases p ON p.id=r.phase_id INNER JOIN championships c ON c.id=p.championship_id WHERE c.deleted_at IS NULL AND $scope ORDER BY c.name,p.sequence_number,g.display_order,r.round_number")->fetchAll(),
        ];
    }

    public function rounds(array $filters, int $userId, bool $administrator): array
    {
        $conditions = ['c.deleted_at IS NULL']; $params = [];
        foreach (['championship_id' => 'c.id', 'phase_id' => 'p.id', 'group_id' => 'g.id', 'round_id' => 'r.id'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') { $conditions[] = $column . '=?'; $params[] = (int) $filters[$key]; }
        }
        if (!$administrator) { $conditions[] = "EXISTS (SELECT 1 FROM championship_user_assignments cua WHERE cua.championship_id=c.id AND cua.user_id=? AND cua.assignment_type IN ('accountability', 'organizer'))"; $params[] = $userId; }
        if (!empty($filters['from'])) { $conditions[] = '(r.period_end IS NULL OR r.period_end >= ?)'; $params[] = $filters['from']; }
        if (!empty($filters['to'])) { $conditions[] = '(r.period_start IS NULL OR r.period_start <= ?)'; $params[] = $filters['to']; }
        if (!empty($filters['team_id'])) { $conditions[] = 'EXISTS (SELECT 1 FROM matches mt WHERE mt.round_id=r.id AND (mt.home_team_id=? OR mt.away_team_id=?))'; $params[]=(int)$filters['team_id']; $params[]=(int)$filters['team_id']; }
        if (!empty($filters['operator_id'])) { $conditions[] = "EXISTS (SELECT 1 FROM matches mo INNER JOIN match_operator_assignments oa ON oa.match_id=mo.id AND oa.status='active' WHERE mo.round_id=r.id AND oa.user_id=?)"; $params[]=(int)$filters['operator_id']; }
        $sql = 'SELECT r.id, r.round_number, r.period_start, r.period_end, r.status AS round_status, c.id AS championship_id, c.name AS championship_name, p.id AS phase_id, p.name AS phase_name, g.id AS group_id, g.name AS group_name, d.deadline_mode, d.custom_value,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id) AS matches_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status IN (\'draft\',\'scheduled\',\'confirmed\')) AS scheduled_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.status=\'open\' AND o.first_half_started_at IS NOT NULL) AS in_progress_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.status IN (\'awaiting_homologation\',\'homologated\')) AS finished_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status IN (\'finished\',\'homologated\') AND NOT EXISTS (SELECT 1 FROM match_operation_events e WHERE e.match_id=m.id AND e.valid=1)) AS events_missing_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.review_status=\'awaiting_review\') AS review_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.status=\'awaiting_homologation\') AS approval_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.status=\'homologated\') AS approved_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_publications mp ON mp.match_id=m.id WHERE m.round_id=r.id AND mp.status=\'scheduled\') AS publication_scheduled_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_publications mp ON mp.match_id=m.id WHERE m.round_id=r.id AND mp.status=\'published\') AS published_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status=\'postponed\') AS postponed_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status=\'cancelled\') AS cancelled_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status=\'wo\') AS wo_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND EXISTS (SELECT 1 FROM administrative_decisions ad WHERE ad.match_id=m.id AND ad.decision_type=\'abandoned\')) AS abandoned_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND NOT EXISTS (SELECT 1 FROM match_reports mr WHERE mr.match_id=m.id AND mr.current_version_id IS NOT NULL)) AS reports_not_started_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status IN (\'finished\',\'homologated\') AND NOT EXISTS (SELECT 1 FROM match_reports mr WHERE mr.match_id=m.id AND mr.current_version_id IS NOT NULL)) AS reports_missing_count,
        (SELECT COUNT(*) FROM matches m INNER JOIN match_operations o ON o.match_id=m.id WHERE m.round_id=r.id AND o.status=\'open\') AS reports_in_progress_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND EXISTS (SELECT 1 FROM match_reports mr WHERE mr.match_id=m.id AND mr.current_version_id IS NOT NULL)) AS reports_generated_count,
        (SELECT COUNT(*) FROM matches m WHERE m.round_id=r.id AND m.status IN (\'finished\',\'homologated\') AND EXISTS (SELECT 1 FROM championship_evidence_checklist_items ci WHERE ci.championship_id=m.championship_id AND ci.is_active=1 AND ci.is_required=1 AND ci.deleted_at IS NULL) AND EXISTS (SELECT 1 FROM championship_evidence_checklist_items ci WHERE ci.championship_id=m.championship_id AND ci.is_active=1 AND ci.is_required=1 AND ci.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM match_media mm WHERE mm.match_id=m.id AND mm.checklist_item_id=ci.id AND mm.deleted_at IS NULL AND mm.review_status=\'approved\'))) AS evidence_missing_count
        FROM competition_rounds r INNER JOIN competition_phases p ON p.id=r.phase_id INNER JOIN competition_groups g ON g.id=r.group_id INNER JOIN championships c ON c.id=p.championship_id LEFT JOIN championship_document_deadlines d ON d.championship_id=c.id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY r.period_start IS NULL, r.period_start, c.name, p.sequence_number, g.display_order, r.round_number';
        $s=$this->pdo->prepare($sql); $s->execute($params); $rows=$s->fetchAll();
        return array_values(array_filter(array_map(fn(array $row): array => $this->decorate($row), $rows), fn(array $row): bool => $this->matchesFilters($row,$filters)));
    }

    public function matches(int $roundId): array
    {
        $sql = "SELECT m.*, ht.name AS home_team_name, at.name AS away_team_name, v.name AS venue_name, o.status AS operation_status, o.review_status, o.reviewed_at, ap.name AS approved_by_name, mp.status AS publication_status, mp.scheduled_at, mr.current_version_id, oa.name AS operator_name,
        (SELECT COUNT(*) FROM match_operation_events e WHERE e.match_id=m.id AND e.valid=1) AS event_count,
        (SELECT COUNT(*) FROM championship_evidence_checklist_items ci WHERE m.status IN ('finished','homologated') AND ci.championship_id=m.championship_id AND ci.is_active=1 AND ci.is_required=1 AND ci.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM match_media mm WHERE mm.match_id=m.id AND mm.checklist_item_id=ci.id AND mm.deleted_at IS NULL AND mm.review_status='approved')) AS evidence_missing_count
        FROM matches m INNER JOIN teams ht ON ht.id=m.home_team_id INNER JOIN teams at ON at.id=m.away_team_id LEFT JOIN venues v ON v.id=m.venue_id LEFT JOIN match_operations o ON o.match_id=m.id LEFT JOIN users ap ON ap.id=o.homologated_by LEFT JOIN match_publications mp ON mp.match_id=m.id LEFT JOIN (SELECT moa.match_id,u.name FROM match_operator_assignments moa INNER JOIN users u ON u.id=moa.user_id WHERE moa.status='active' AND moa.assignment_type='operator') oa ON oa.match_id=m.id LEFT JOIN match_reports mr ON mr.match_id=m.id WHERE m.round_id=? ORDER BY m.match_date,m.match_time,m.id";
        $s=$this->pdo->prepare($sql); $s->execute([$roundId]); return $s->fetchAll();
    }

    public function round(int $id): ?array
    {
        $s=$this->pdo->prepare('SELECT r.*, p.championship_id, p.name AS phase_name, g.name AS group_name, c.name AS championship_name FROM competition_rounds r INNER JOIN competition_phases p ON p.id=r.phase_id INNER JOIN competition_groups g ON g.id=r.group_id INNER JOIN championships c ON c.id=p.championship_id WHERE r.id=? LIMIT 1');
        $s->execute([$id]); return $s->fetch() ?: null;
    }

    public function exportRows(int $roundId): array
    {
        return array_map(static fn(array $m): array => ['partida'=>$m['home_team_name'].' x '.$m['away_team_name'],'data'=>$m['match_date'],'horario'=>$m['match_time'],'status_esportivo'=>$m['status'],'revisao'=>$m['review_status'] ?: 'nao iniciada','aprovacao'=>$m['operation_status'] ?: 'nao iniciada','publicacao'=>$m['publication_status'] ?: 'interna','sumula'=>!empty($m['current_version_id'])?'gerada':'pendente','evidencias_pendentes'=>$m['evidence_missing_count'],'operador'=>$m['operator_name'] ?: 'nao atribuido'], $this->matches($roundId));
    }

    public function packageFiles(int $roundId): array
    {
        $sql = "SELECT m.id AS match_id, rv.storage_path AS report_path, rv.original_name AS report_name, mm.storage_path AS evidence_path, mm.original_name AS evidence_name
        FROM matches m
        LEFT JOIN match_reports mr ON mr.match_id=m.id
        LEFT JOIN match_report_versions rv ON rv.id=mr.current_version_id
        LEFT JOIN match_media mm ON mm.match_id=m.id AND mm.deleted_at IS NULL AND mm.review_status='approved' AND mm.visibility IN ('accountability','private')
        WHERE m.round_id=? ORDER BY m.id, mm.id";
        $s=$this->pdo->prepare($sql);$s->execute([$roundId]);return $s->fetchAll();
    }

    private function decorate(array $r): array
    {
        $total=(int)$r['matches_count']; $approved=(int)$r['approved_count']; $missing=(int)$r['reports_missing_count']+(int)$r['evidence_missing_count'];
        $deadline = $this->deadlineAt($r); $now=new \DateTimeImmutable('now'); $overdue=$deadline && $now>$deadline && ($approved<$total || $missing>0);
        $r['deadline_at']=$deadline?->format('Y-m-d H:i:s'); $r['is_overdue']=$overdue; $r['document_complete']=$total>0 && $missing===0 && $approved===$total;
        $r['indicator']=$total===0?'sem_partidas':($overdue?'atrasada':($missing>0?'pendencia_critica':($approved===$total?'completa':'parcial')));
        $r['ready_for_accountability']=$r['document_complete']; $r['ready_for_publication']=$approved===$total && $missing===0 && (int)$r['published_count']<$total; $r['all_published']=$total>0 && (int)$r['published_count']===$total;
        return $r;
    }

    private function matchesFilters(array $r,array $f): bool
    {
        if (($f['sport_status']??'')==='approved' && (int)$r['approved_count']!==(int)$r['matches_count']) return false;
        if (($f['approval_status']??'')==='pending' && (int)$r['approval_count']===0) return false;
        if (($f['publication_status']??'')==='published' && !$r['all_published']) return false;
        if (($f['document_status']??'')==='complete' && !$r['document_complete']) return false;
        if (!empty($f['only_pending']) && $r['document_complete']) return false;
        if (!empty($f['overdue']) && !$r['is_overdue']) return false;
        return true;
    }

    private function deadlineAt(array $r): ?\DateTimeImmutable
    {
        if (empty($r['period_end'])) return null; $base=new \DateTimeImmutable($r['period_end'].' 23:59:59'); $mode=$r['deadline_mode'] ?? 'next_day';
        return match($mode) { 'same_day'=>$base, 'hours'=>$base->modify('+'.max(1,(int)$r['custom_value']).' hours'), 'days'=>$base->modify('+'.max(1,(int)$r['custom_value']).' days'), default=>$base->modify('+1 day') };
    }
}
