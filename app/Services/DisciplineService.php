<?php
declare(strict_types=1);
namespace App\Services;
use PDO;

final class DisciplineService { public function __construct(private PDO $db) {} public function yellowCount(int $tournamentId,int $personId): int {$s=$this->db->prepare("SELECT COUNT(*) FROM match_events e JOIN matches m ON m.id=e.match_id WHERE m.tournament_id=? AND e.person_id=? AND e.event_type='yellow' AND e.is_cancelled=0 AND m.status='homologated'");$s->execute([$tournamentId,$personId]);return (int)$s->fetchColumn();} public function activeSuspensions(int $tournamentId): array {$s=$this->db->prepare("SELECT s.*,p.public_name,p.full_name FROM suspensions s JOIN people p ON p.id=s.person_id WHERE s.tournament_id=? AND s.status='active' AND s.matches_served<s.matches_total");$s->execute([$tournamentId]);return $s->fetchAll();} }
