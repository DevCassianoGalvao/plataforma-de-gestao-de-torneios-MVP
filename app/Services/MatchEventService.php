<?php
declare(strict_types=1);
namespace App\Services;
use PDO;

final class MatchEventService {
    public function __construct(private PDO $db) {}
    public function record(int $matchId,array $data): int { $s=$this->db->prepare('INSERT INTO match_events(match_id,team_id,person_id,assist_person_id,event_type,minute,period,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,NOW(),NOW())');$s->execute([$matchId,$data['team_id']??null,$data['person_id']??null,$data['assist_person_id']??null,$data['event_type'],isset($data['minute'])?(int)$data['minute']:null,$data['period']??null,json_encode($data['metadata']??[],JSON_UNESCAPED_UNICODE)]);return (int)$this->db->lastInsertId();}
}
