<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
final class RegistrationService { public function __construct(private PDO $db) {} public function eligible(int $tournamentId,int $teamId,int $personId): bool {$s=$this->db->prepare("SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND person_id=? AND status IN ('approved','active') AND team_id<>? AND deleted_at IS NULL");$s->execute([$tournamentId,$personId,$teamId]);return (int)$s->fetchColumn()===0;} }
