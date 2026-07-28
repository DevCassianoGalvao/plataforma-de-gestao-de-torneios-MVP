<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/** Writes administrative records using a persisted tournament context, never submitted scope IDs. */
final class AssistedAdministrationService
{
    public function __construct(private PDO $db) {}

    public function createTeam(int $tournamentId, array $input): int
    {
        $tournament=$this->one('SELECT project_id FROM tournaments WHERE id=? AND deleted_at IS NULL', [$tournamentId]);
        $name=trim((string)($input['name'] ?? ''));
        if ($name === '') throw new RuntimeException('Nome da equipe é obrigatório.');
        $slug=$this->slug((string)($input['slug'] ?? $name));
        if ($slug === '') throw new RuntimeException('Informe um slug válido para a equipe.');
        $categoryId=(int)($input['category_id'] ?? 0) ?: null;
        if ($categoryId && !$this->exists('SELECT id FROM categories WHERE id=? AND status="active" AND deleted_at IS NULL', [$categoryId])) throw new RuntimeException('Categoria inválida.');
        $this->db->beginTransaction();
        try {
            $sql='INSERT INTO teams(project_id,status,name,short_name,acronym,slug,logo_path,primary_color,secondary_color,city,contact_name,contact_phone,contact_email,created_at,updated_at) VALUES(?,"active",?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())';
            $this->db->prepare($sql)->execute([(int)$tournament['project_id'],$name,trim((string)($input['short_name'] ?? '')) ?: null,trim((string)($input['acronym'] ?? '')) ?: null,$slug,$input['logo_path'] ?? null,$this->color($input['primary_color'] ?? null),$this->color($input['secondary_color'] ?? null),trim((string)($input['city'] ?? '')) ?: null,trim((string)($input['contact_name'] ?? '')) ?: null,trim((string)($input['contact_phone'] ?? '')) ?: null,trim((string)($input['contact_email'] ?? '')) ?: null]);
            $teamId=(int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO team_tournament_entries(tournament_id,team_id,category_id,status,created_at,updated_at) VALUES(?,?,?,"pending",NOW(),NOW())')->execute([$tournamentId,$teamId,$categoryId]);
            $this->db->commit();
            AuditService::record('create_team','teams',$teamId,[],['tournament_id'=>$tournamentId,'name'=>$name]);
            return $teamId;
        } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function createAthlete(int $tournamentId, array $input): int
    {
        $teamId=(int)($input['team_id'] ?? 0);
        if (!$this->exists('SELECT id FROM team_tournament_entries WHERE tournament_id=? AND team_id=? AND deleted_at IS NULL', [$tournamentId,$teamId])) throw new RuntimeException('Equipe não participa deste campeonato.');
        $name=trim((string)($input['full_name'] ?? ''));
        if ($name === '') throw new RuntimeException('Nome completo é obrigatório.');
        $birth=trim((string)($input['birth_date'] ?? ''));
        if ($birth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) throw new RuntimeException('Data de nascimento inválida.');
        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO people(status,full_name,public_name,person_type,birth_date,phone,email,created_at,updated_at) VALUES("active",?, ?,"athlete",?,?,?,NOW(),NOW())')->execute([$name,trim((string)($input['public_name'] ?? '')) ?: null,$birth ?: null,trim((string)($input['phone'] ?? '')) ?: null,trim((string)($input['email'] ?? '')) ?: null]);
            $personId=(int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO person_profiles(person_id,primary_position,dominant_foot,preferred_number,photo_path,updated_at) VALUES(?,?,?,?,?,NOW())')->execute([$personId,trim((string)($input['position'] ?? '')) ?: null,trim((string)($input['dominant_foot'] ?? '')) ?: null,(int)($input['preferred_number'] ?? 0) ?: null,$input['photo_path'] ?? null]);
            $this->db->prepare('INSERT INTO team_memberships(team_id,person_id,role,starts_at,status,created_at,updated_at) VALUES(?,?,"athlete",CURDATE(),"active",NOW(),NOW())')->execute([$teamId,$personId]);
            $this->db->prepare('INSERT INTO team_membership_history(person_id,team_id,tournament_id,starts_at,reason,source,status,created_at) VALUES(?,?,?,CURDATE(),"Cadastro assistido","admin","active",NOW())')->execute([$personId,$teamId,$tournamentId]);
            if (trim((string)($input['guardian_name'] ?? '')) !== '') $this->db->prepare('INSERT INTO legal_guardians(person_id,full_name,document_number,phone,email,relationship_type,authorizations_json,terms_accepted_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),"active",NOW(),NOW())')->execute([$personId,trim((string)$input['guardian_name']),trim((string)($input['guardian_document'] ?? '')) ?: null,trim((string)($input['guardian_phone'] ?? '')) ?: null,trim((string)($input['guardian_email'] ?? '')) ?: null,trim((string)($input['guardian_relationship'] ?? 'Responsável')) ?: 'Responsável',json_encode(['registration_authorized'=>!empty($input['guardian_authorized'])])]);
            $this->db->commit();
            AuditService::record('create_athlete','people',$personId,[],['tournament_id'=>$tournamentId,'team_id'=>$teamId]);
            return $personId;
        } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function createStaff(int $tournamentId, array $input): int
    {
        $teamId=(int)($input['team_id'] ?? 0); if(!$this->exists('SELECT id FROM team_tournament_entries WHERE tournament_id=? AND team_id=? AND deleted_at IS NULL',[$tournamentId,$teamId])) throw new RuntimeException('Equipe não participa deste campeonato.');
        $name=trim((string)($input['full_name']??''));$role=trim((string)($input['role']??'')); if($name===''||$role==='')throw new RuntimeException('Nome e função são obrigatórios.');
        $allowed=['Treinador','Auxiliar','Preparador físico','Fisioterapeuta','Médico','Massagista','Dirigente','Responsável da equipe','Outro'];if(!in_array($role,$allowed,true))throw new RuntimeException('Função de comissão inválida.');
        $this->db->beginTransaction();try{$this->db->prepare('INSERT INTO people(status,full_name,person_type,phone,email,created_at,updated_at) VALUES("active",?,"staff",?,?,NOW(),NOW())')->execute([$name,trim((string)($input['phone']??''))?:null,trim((string)($input['email']??''))?:null]);$personId=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO team_memberships(team_id,person_id,role,starts_at,status,created_at,updated_at) VALUES(?,?,?,CURDATE(),"active",NOW(),NOW())')->execute([$teamId,$personId,$role]);$this->db->commit();AuditService::record('create_staff','people',$personId,[],['tournament_id'=>$tournamentId,'team_id'=>$teamId,'role'=>$role]);return $personId;}catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function setTeamStatus(int $tournamentId, int $teamId, string $action): void
    {
        if (!$this->exists('SELECT id FROM team_tournament_entries WHERE tournament_id=? AND team_id=?', [$tournamentId,$teamId])) throw new RuntimeException('Equipe não participa deste campeonato.');
        if (!in_array($action,['activate','deactivate','delete','restore'],true)) throw new RuntimeException('Ação inválida.');
        $before=$this->one('SELECT status,deleted_at FROM teams WHERE id=?',[$teamId]);
        $set=match($action) {
            'activate' => ['active',null], 'deactivate' => ['inactive',null], 'delete' => ['inactive',date('Y-m-d H:i:s')], 'restore' => ['active',null],
        };
        $this->db->prepare('UPDATE teams SET status=?,deleted_at=?,updated_at=NOW() WHERE id=?')->execute([$set[0],$set[1],$teamId]);
        AuditService::record('team_'.$action,'teams',$teamId,$before,['status'=>$set[0]]);
    }

    private function one(string $sql,array $params): array { $s=$this->db->prepare($sql);$s->execute($params);$row=$s->fetch();if(!$row)throw new RuntimeException('Recurso não encontrado.');return $row; }
    private function exists(string $sql,array $params): bool { $s=$this->db->prepare($sql);$s->execute($params);return(bool)$s->fetchColumn(); }
    private function color(mixed $value): ?string { $value=trim((string)$value);return preg_match('/^#[0-9a-fA-F]{6}$/',$value)?$value:null; }
    private function slug(string $value): string { $value=iconv('UTF-8','ASCII//TRANSLIT',$value) ?: $value;$value=strtolower((string)preg_replace('/[^a-zA-Z0-9]+/','-',$value));return trim($value,'-'); }
}
