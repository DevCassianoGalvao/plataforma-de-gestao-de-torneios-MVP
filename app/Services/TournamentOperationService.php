<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use PDO;
use RuntimeException;

final class TournamentOperationService
{
    public function __construct(private PDO $db) {}

    public function registerAthlete(int $tournamentId, int $teamId, int $personId, ?string $shirtNumber=null): int
    {
        $this->assertTournamentTeam($tournamentId, $teamId);
        $person=$this->one('SELECT id,birth_date FROM people WHERE id=? AND person_type="athlete" AND status="active" AND deleted_at IS NULL',[$personId],'Atleta inválido.');
        if (!$this->scalar('SELECT COUNT(*) FROM team_memberships WHERE team_id=? AND person_id=? AND role="athlete" AND status="active" AND deleted_at IS NULL',[$teamId,$personId])) throw new RuntimeException('Atleta não pertence à equipe selecionada.');
        $rules=(new RuleConfigurationService($this->db))->active($tournamentId);
        $max=(int)($rules['registration']['max_players'] ?? $rules['roster']['max_registered_players'] ?? 30);
        $count=$this->scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND team_id=? AND registration_type="athlete" AND status IN ("draft","submitted","under_review","pending","approved") AND deleted_at IS NULL',[$tournamentId,$teamId]);
        if($count >= $max) throw new RuntimeException('Limite de elenco atingido.');
        if(!empty($person['birth_date']) && !$this->withinAge((string)$person['birth_date'],$rules)) throw new RuntimeException('Atleta fora da faixa etária configurada.');
        if($this->scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND person_id=? AND deleted_at IS NULL AND status NOT IN ("rejected","cancelled")',[$tournamentId,$personId])) throw new RuntimeException('Atleta já possui inscrição neste campeonato.');
        $this->db->prepare('INSERT INTO registrations(tournament_id,team_id,person_id,registration_type,status,shirt_number,created_at,updated_at) VALUES(?,?,?,"athlete","draft",?,NOW(),NOW())')->execute([$tournamentId,$teamId,$personId,$shirtNumber]);
        return (int)$this->db->lastInsertId();
    }

    public function setRegistrationStatus(int $registrationId, string $status, ?int $userId, ?string $reason=null): void
    {
        if(!in_array($status,['draft','submitted','under_review','pending','approved','rejected','suspended','cancelled'],true)) throw new RuntimeException('Status de inscrição inválido.');
        $row=$this->one('SELECT * FROM registrations WHERE id=? AND deleted_at IS NULL',[$registrationId],'Inscrição não encontrada.');
        if(in_array($status,['approved','rejected','suspended','cancelled'],true) && $status==='rejected' && trim((string)$reason)==='') throw new RuntimeException('Motivo obrigatório para rejeição.');
        $this->db->prepare('UPDATE registrations SET status=?,rejection_reason=?,reviewed_by=?,reviewed_at=IF(? IN ("approved","rejected","suspended","cancelled"),NOW(),reviewed_at),submitted_at=IF(?="submitted",NOW(),submitted_at),updated_at=NOW() WHERE id=?')->execute([$status,$reason,$userId,$status,$status,$registrationId]);
        AuditService::record('registration_'.$status,'registrations',$registrationId,$row,['status'=>$status,'reason'=>$reason]);
    }

    public function createGroup(int $stageId, string $name, string $code, int $maxTeams): int
    {
        $stage=$this->one('SELECT id FROM stages WHERE id=? AND deleted_at IS NULL',[$stageId],'Fase inválida.');
        if($maxTeams<2) throw new RuntimeException('Grupo deve aceitar ao menos duas equipes.');
        $this->db->prepare('INSERT INTO groups_competition(stage_id,name,group_code,group_order,max_teams,status,created_at,updated_at) VALUES(?,?,?,?,?,"draft",NOW(),NOW())')->execute([$stage['id'],$name,strtoupper($code),(int)$this->scalar('SELECT COALESCE(MAX(group_order),0)+1 FROM groups_competition WHERE stage_id=?',[$stageId]),$maxTeams]);
        return (int)$this->db->lastInsertId();
    }

    public function assignTeam(int $groupId, int $teamId): void
    {
        $g=$this->one('SELECT g.*,s.tournament_id FROM groups_competition g JOIN stages s ON s.id=g.stage_id WHERE g.id=? AND g.deleted_at IS NULL',[$groupId],'Grupo inválido.');
        $this->assertTournamentTeam((int)$g['tournament_id'],$teamId);
        if($this->scalar('SELECT COUNT(*) FROM group_team_assignments WHERE group_id=? AND deleted_at IS NULL',[$groupId]) >= (int)$g['max_teams']) throw new RuntimeException('Limite do grupo atingido.');
        if($this->scalar('SELECT COUNT(*) FROM group_team_assignments a JOIN groups_competition x ON x.id=a.group_id JOIN stages s ON s.id=x.stage_id WHERE s.tournament_id=? AND a.team_id=? AND a.deleted_at IS NULL',[(int)$g['tournament_id'],$teamId])) throw new RuntimeException('Equipe já distribuída em grupo.');
        $this->db->prepare('INSERT INTO group_team_assignments(group_id,team_id,display_order,status,created_at,updated_at) VALUES(?,?,?,"active",NOW(),NOW())')->execute([$groupId,$teamId,(int)$this->scalar('SELECT COALESCE(MAX(display_order),0)+1 FROM group_team_assignments WHERE group_id=?',[$groupId])]);
    }

    public function removeTeamFromGroup(int $groupId, int $teamId): void
    {
        $group=$this->one('SELECT g.id FROM groups_competition g WHERE g.id=? AND g.deleted_at IS NULL',[$groupId],'Grupo inválido.');
        if ($this->scalar('SELECT COUNT(*) FROM matches WHERE group_id=? AND deleted_at IS NULL',[$groupId]) > 0) throw new RuntimeException('Não é possível alterar grupo com partidas geradas.');
        $s=$this->db->prepare('UPDATE group_team_assignments SET deleted_at=NOW(),updated_at=NOW() WHERE group_id=? AND team_id=? AND deleted_at IS NULL');$s->execute([$group['id'],$teamId]);
        if(!$s->rowCount()) throw new RuntimeException('Equipe não está neste grupo.');
        AuditService::record('remove_group_team','groups_competition',$groupId,[],['team_id'=>$teamId]);
    }

    public function generateGroupMatches(int $groupId, ?string $startAt=null): array
    {
        $g=$this->one('SELECT g.*,s.tournament_id,s.id stage_id FROM groups_competition g JOIN stages s ON s.id=g.stage_id WHERE g.id=? AND g.deleted_at IS NULL',[$groupId],'Grupo inválido.');
        if($this->scalar('SELECT COUNT(*) FROM matches WHERE group_id=? AND deleted_at IS NULL',[$groupId])) throw new RuntimeException('Grupo já possui partidas.');
        $q=$this->db->prepare('SELECT team_id FROM group_team_assignments WHERE group_id=? AND status="active" AND deleted_at IS NULL ORDER BY display_order');$q->execute([$groupId]);$teams=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        $rules=(new RuleConfigurationService($this->db))->active((int)$g['tournament_id']);
        $double=(($rules['format']['group_round_robin'] ?? 'single') === 'double');
        $rounds=(new ScheduleGenerationService())->roundRobin($teams,$double);
        $this->db->beginTransaction();
        try {
            $created=[];$base=$startAt?new \DateTimeImmutable($startAt):null;
            foreach($rounds as $number=>$games){$this->db->prepare('INSERT INTO rounds(stage_id,name,round_order,status,created_at,updated_at) VALUES(?,?,?,"draft",NOW(),NOW())')->execute([(int)$g['stage_id'],$g['name'].' - Rodada '.($number+1),$number+1]);$roundId=(int)$this->db->lastInsertId();foreach($games as $order=>$game){$at=$base?$base->modify('+'.($number*7).' days +'.($order*2).' hours')->format('Y-m-d H:i:s'):null;$this->db->prepare('INSERT INTO matches(tournament_id,stage_id,group_id,round_id,home_team_id,away_team_id,scheduled_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,"scheduled",NOW(),NOW())')->execute([(int)$g['tournament_id'],(int)$g['stage_id'],$groupId,$roundId,$game['home'],$game['away'],$at]);$created[]=(int)$this->db->lastInsertId();}}
            $this->db->prepare('UPDATE groups_competition SET status="published",updated_at=NOW() WHERE id=?')->execute([$groupId]);$this->db->commit();return $created;
        } catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function saveLineup(int $matchId, int $teamId, array $players): void
    {
        $match=$this->match($matchId);if(!in_array($teamId,[(int)$match['home_team_id'],(int)$match['away_team_id']],true)) throw new RuntimeException('Equipe não participa da partida.');
        $rules=(new RuleConfigurationService($this->db))->active((int)$match['tournament_id']);$min=(int)($rules['match']['minimum_players'] ?? $rules['registration']['min_players'] ?? 7);$max=(int)($rules['roster']['max_match_squad'] ?? 26);
        if(count($players)<$min || count($players)>$max) throw new RuntimeException('Quantidade de atletas inválida para a súmula.');
        $seen=[];$numbers=[];foreach($players as $p){$person=(int)($p['person_id']??0);if(!$person||isset($seen[$person]))throw new RuntimeException('Atleta duplicado na escalação.');$seen[$person]=true;$number=(string)($p['shirt_number']??'');if($number!==''&&isset($numbers[$number]))throw new RuntimeException('Numeração duplicada na equipe.');$numbers[$number]=true;if(!$this->scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND team_id=? AND person_id=? AND status="approved" AND deleted_at IS NULL',[(int)$match['tournament_id'],$teamId,$person]))throw new RuntimeException('Atleta sem inscrição aprovada.');if($this->scalar('SELECT COUNT(*) FROM suspensions WHERE tournament_id=? AND person_id=? AND status="active" AND matches_served<matches_total AND deleted_at IS NULL',[(int)$match['tournament_id'],$person]))throw new RuntimeException('Atleta suspenso.');}
        $this->db->beginTransaction();try{$this->db->prepare('DELETE FROM match_lineups WHERE match_id=? AND team_id=?')->execute([$matchId,$teamId]);$i=$this->db->prepare('INSERT INTO match_lineups(match_id,team_id,person_id,lineup_role,is_captain,is_goalkeeper,shirt_number,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,NOW())');foreach($players as $p)$i->execute([$matchId,$teamId,(int)$p['person_id'],in_array($p['role']??'substitute',['starter','substitute'],true)?$p['role']:'substitute',!empty($p['captain'])?1:0,!empty($p['goalkeeper'])?1:0,$p['shirt_number']??null,date('Y-m-d H:i:s')]);$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function event(int $matchId, array $event, ?int $userId): int
    {
        $match=$this->match($matchId);if(!in_array($match['status'],['confirmed','in_progress','halftime','scheduled','rectified'],true))throw new RuntimeException('Partida não aceita eventos neste estado.');$teamId=(int)($event['team_id']??0);if($teamId&&!in_array($teamId,[(int)$match['home_team_id'],(int)$match['away_team_id']],true))throw new RuntimeException('Equipe inválida para o evento.');$personId=(int)($event['person_id']??0);if($personId&&!$this->scalar('SELECT COUNT(*) FROM match_lineups WHERE match_id=? AND team_id=? AND person_id=? AND deleted_at IS NULL',[$matchId,$teamId,$personId]))throw new RuntimeException('Atleta não está escalado.');$types=['start','halftime','second_half','end','goal','own_goal','assist','yellow','second_yellow','red','substitution','penalty_scored','penalty_missed','penalty_shootout','stoppage','incident','player_of_match'];if(!in_array($event['event_type']??'',$types,true))throw new RuntimeException('Tipo de evento inválido.');$id=(new MatchReportService($this->db))->addEvent($matchId,$event,$userId);if(($event['event_type']??'')==='start')$this->setMatchStatus($matchId,'in_progress');if(($event['event_type']??'')==='halftime')$this->setMatchStatus($matchId,'halftime');if(($event['event_type']??'')==='second_half')$this->setMatchStatus($matchId,'in_progress');AuditService::record('match_event','match_events',$id,[], $event);return $id;
    }

    public function finish(int $matchId): void
    {
        $m=$this->match($matchId);$rules=(new RuleConfigurationService($this->db))->active((int)$m['tournament_id']);$minimum=(int)($rules['match']['minimum_players']??$rules['registration']['min_players']??7);foreach([(int)$m['home_team_id'],(int)$m['away_team_id']] as $team)if($this->scalar('SELECT COUNT(*) FROM match_lineups WHERE match_id=? AND team_id=? AND lineup_role="starter" AND deleted_at IS NULL',[$matchId,$team])<$minimum)throw new RuntimeException('Quantidade mínima de titulares não atingida.');$this->setMatchStatus($matchId,'awaiting_homologation');$this->db->prepare('INSERT INTO match_reports(match_id,state,submitted_at,created_at,updated_at) VALUES(?,"awaiting_homologation",NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE state="awaiting_homologation",submitted_at=NOW(),updated_at=NOW()')->execute([$matchId]);AuditService::record('finish_match','matches',$matchId,['status'=>$m['status']],['status'=>'awaiting_homologation']);
    }

    public function homologate(int $matchId, ?int $userId): void
    {
        $m=$this->match($matchId);if(!in_array($m['status'],['awaiting_homologation','rectified'],true))throw new RuntimeException('Partida não está pronta para homologação.');$this->db->beginTransaction();try{$this->setMatchStatus($matchId,'homologated');$this->db->prepare('UPDATE match_reports SET state="homologated",homologated_at=NOW(),updated_at=NOW() WHERE match_id=?')->execute([$matchId]);$this->recalculateStatistics((int)$m['tournament_id']);(new StandingsService($this->db))->recalculate((int)$m['tournament_id']);$rules=new SportsRulesService($this->db);$rules->refreshSuspensions((int)$m['tournament_id']);$rules->serveSuspensions($matchId);$this->advanceBracket((int)$m['tournament_id']);$path=$this->generatePdf($matchId);$this->db->prepare('UPDATE matches SET report_pdf_path=?,homologated_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$path,$matchId]);(new RectificationService($this->db))->snapshot($matchId,$userId,'homologated');$this->db->commit();AuditService::record('homologate','matches',$matchId,['status'=>$m['status']],['status'=>'homologated','pdf'=>$path]);}catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function rectify(int $matchId, string $reason, ?int $userId): void
    {
        if(trim($reason)==='')throw new RuntimeException('Motivo obrigatório.');$m=$this->match($matchId);if($m['status']!=='homologated')throw new RuntimeException('Somente partida homologada pode ser retificada.');$report=$this->one('SELECT * FROM match_reports WHERE match_id=?',[$matchId],'Súmula inexistente.');$snapshot=['match'=>$m,'events'=>$this->many('SELECT * FROM match_events WHERE match_id=? AND deleted_at IS NULL',[$matchId])];$version=(int)$this->scalar('SELECT COALESCE(MAX(version_number),0)+1 FROM match_report_versions WHERE match_report_id=?',[(int)$report['id']]);$this->db->prepare('INSERT INTO match_report_versions(match_report_id,version_number,snapshot_json,reason,status,created_by,created_at) VALUES(?,?,?,?,"superseded",?,NOW())')->execute([(int)$report['id'],$version,json_encode($snapshot,JSON_UNESCAPED_UNICODE),$reason,$userId]);$this->setMatchStatus($matchId,'rectified');$this->db->prepare('UPDATE match_reports SET state="rectified",retification_reason=?,updated_at=NOW() WHERE id=?')->execute([$reason,$report['id']]);AuditService::record('rectify','matches',$matchId,['status'=>'homologated'],['status'=>'rectified','reason'=>$reason]);
    }

    public function generateKnockout(int $tournamentId): array
    {
        $rules=(new RuleConfigurationService($this->db))->active($tournamentId);$pairs=$rules['knockout']['pairings']??[];if(!$pairs)throw new RuntimeException('Cruzamentos não configurados.');if($this->scalar('SELECT COUNT(*) FROM stages WHERE tournament_id=? AND stage_key="quarterfinal" AND deleted_at IS NULL',[$tournamentId]))throw new RuntimeException('Quartas já geradas.');$groups=$this->many('SELECT g.group_code,g.id FROM groups_competition g JOIN stages s ON s.id=g.stage_id WHERE s.tournament_id=? AND s.stage_key="group" AND g.deleted_at IS NULL',[$tournamentId]);$rankings=[];foreach($groups as $g){$rows=(new StandingsService($this->db))->forGroup($tournamentId,(int)$g['id']);foreach($rows as $r)$rankings[strtoupper($g['group_code']).$r['position']]=(int)$r['team_id'];}$stage=$this->createStage($tournamentId,'Quartas de final','quarterfinal',2);$round=$this->createRound($stage,'Quartas de final',1);$created=[];foreach($pairs as $pair){[$left,$right]=explode('-',str_replace(' ','',$pair),2);if(empty($rankings[$left])||empty($rankings[$right]))throw new RuntimeException('Classificação insuficiente para '.$pair);$created[]=$this->createMatch($tournamentId,$stage,null,$round,$rankings[$left],$rankings[$right]);}return $created;
    }

    public function advanceBracket(int $tournamentId): void
    {
        foreach([['quarterfinal','semifinal','Semifinais'],['semifinal','final','Final']] as [$sourceKey,$targetKey,$targetName]){$source=$this->many('SELECT * FROM matches WHERE tournament_id=? AND stage_id=(SELECT id FROM stages WHERE tournament_id=? AND stage_key=? AND deleted_at IS NULL LIMIT 1) AND deleted_at IS NULL ORDER BY id',[$tournamentId,$tournamentId,$sourceKey]);if(!$source||count(array_filter($source,fn($m)=>$m['status']==='homologated'))!==count($source))continue;if($this->scalar('SELECT COUNT(*) FROM stages WHERE tournament_id=? AND stage_key=? AND deleted_at IS NULL',[$tournamentId,$targetKey]))continue;$winners=array_map(fn($m)=>$this->winner($m),$source);$stage=$this->createStage($tournamentId,$targetName,$targetKey,$targetKey==='semifinal'?3:4);$round=$this->createRound($stage,$targetName,1);for($i=0;$i<count($winners);$i+=2)$this->createMatch($tournamentId,$stage,null,$round,$winners[$i],$winners[$i+1]);} $final=$this->many('SELECT m.* FROM matches m JOIN stages s ON s.id=m.stage_id WHERE m.tournament_id=? AND s.stage_key="final" AND m.status="homologated" AND m.deleted_at IS NULL',[$tournamentId]);if($final){$winner=$this->winner($final[0]);$runner=$winner===(int)$final[0]['home_team_id']?(int)$final[0]['away_team_id']:(int)$final[0]['home_team_id'];$this->db->prepare('UPDATE tournaments SET status="completed",updated_at=NOW() WHERE id=?')->execute([$tournamentId]);foreach([['Campeão',$winner],['Vice-campeão',$runner]] as [$title,$team])$this->db->prepare('INSERT INTO awards(tournament_id,title,team_id,status,created_at,updated_at) VALUES(?,?,?,"published",NOW(),NOW())')->execute([$tournamentId,$title,$team]);}
    }

    public function generatePdf(int $matchId): string
    {
        $m=$this->one('SELECT m.*,t.name tournament_name,h.name home_name,a.name away_name FROM matches m JOIN tournaments t ON t.id=m.tournament_id JOIN teams h ON h.id=m.home_team_id JOIN teams a ON a.id=m.away_team_id WHERE m.id=?',[$matchId],'Partida inválida.');$events=$this->many('SELECT event_type,minute,period FROM match_events WHERE match_id=? AND is_cancelled=0 AND deleted_at IS NULL ORDER BY id',[$matchId]);$lines=['Partida: '.$m['home_name'].' '.$m['home_score'].' x '.$m['away_score'].' '.$m['away_name'],'Status: '.$m['status'],'Data: '.($m['scheduled_at']??'não definida'),'Eventos:'];foreach($events as $e)$lines[]=(($e['minute']??'')."' ".$e['event_type'].' '.$e['period']);$pdf=(new PdfReportService())->create('Súmula - '.$m['tournament_name'],$lines);$dir=dirname(__DIR__,2).'/storage/private/reports';if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Não foi possível preparar relatórios.');$relative='private/reports/match-'.$matchId.'-'.bin2hex(random_bytes(6)).'.pdf';file_put_contents(dirname(__DIR__,2).'/storage/'.$relative,$pdf);return $relative;
    }

    private function recalculateStatistics(int $tournamentId): void { $this->db->prepare('DELETE FROM player_statistics WHERE tournament_id=?')->execute([$tournamentId]);$events=$this->many('SELECT e.* FROM match_events e JOIN matches m ON m.id=e.match_id WHERE m.tournament_id=? AND m.status="homologated" AND e.is_cancelled=0 AND e.deleted_at IS NULL',[$tournamentId]);$stats=[];foreach($events as $e){foreach([(int)($e['person_id']??0),(int)($e['assist_person_id']??0)] as $person)if($person&&!isset($stats[$person]))$stats[$person]=['goals'=>0,'own_goals'=>0,'assists'=>0,'yellow_cards'=>0,'red_cards'=>0,'penalties_scored'=>0,'penalties_missed'=>0];$p=(int)($e['person_id']??0);if(!$p)continue;match($e['event_type']){'goal'=>$stats[$p]['goals']++,'own_goal'=>$stats[$p]['own_goals']++,'yellow'=>$stats[$p]['yellow_cards']++,'second_yellow','red'=>$stats[$p]['red_cards']++,'penalty_scored'=>$stats[$p]['penalties_scored']++,'penalty_missed'=>$stats[$p]['penalties_missed']++,default=>null};if((int)($e['assist_person_id']??0))$stats[(int)$e['assist_person_id']]['assists']++;}$i=$this->db->prepare('INSERT INTO player_statistics(tournament_id,person_id,goals,own_goals,assists,yellow_cards,red_cards,penalties_scored,penalties_missed,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())');foreach($stats as $person=>$s)$i->execute([$tournamentId,$person,...array_values($s)]); }
    private function generateSuspensions(int $tournamentId): void { $rules=(new RuleConfigurationService($this->db))->active($tournamentId);$limit=(int)($rules['discipline']['yellow_limit']??$rules['cards']['yellow_cards_for_suspension']??3);$this->db->prepare('UPDATE suspensions SET deleted_at=NOW() WHERE tournament_id=? AND reason LIKE "Automática:%" AND deleted_at IS NULL',[$tournamentId]);$stats=$this->many('SELECT * FROM player_statistics WHERE tournament_id=?',[$tournamentId]);$i=$this->db->prepare('INSERT INTO suspensions(tournament_id,person_id,reason,matches_total,matches_served,status,created_at,updated_at) VALUES(?,?,?,1,0,"active",NOW(),NOW())');foreach($stats as $s)if((int)$s['red_cards']>0||((int)$s['yellow_cards']>=$limit))$i->execute([$tournamentId,$s['person_id'],'Automática: cartões']); }
    private function winner(array $m): int { $home=(int)$m['home_score'];$away=(int)$m['away_score'];if($home===$away){$hp=(int)$m['home_penalties'];$ap=(int)$m['away_penalties'];if($hp===$ap)throw new RuntimeException('Mata-mata empatado sem decisão por pênaltis.');return $hp>$ap?(int)$m['home_team_id']:(int)$m['away_team_id'];}return $home>$away?(int)$m['home_team_id']:(int)$m['away_team_id']; }
    private function createStage(int $tid,string $name,string $key,int $order): int {$this->db->prepare('INSERT INTO stages(tournament_id,name,stage_key,stage_order,status,created_at,updated_at) VALUES(?,?,?,?,"published",NOW(),NOW())')->execute([$tid,$name,$key,$order]);return (int)$this->db->lastInsertId();}
    private function createRound(int $stage,string $name,int $order): int {$this->db->prepare('INSERT INTO rounds(stage_id,name,round_order,status,created_at,updated_at) VALUES(?,?,?,"published",NOW(),NOW())')->execute([$stage,$name,$order]);return (int)$this->db->lastInsertId();}
    private function createMatch(int $tid,int $stage,?int $group,int $round,int $home,int $away): int {if($home===$away)throw new RuntimeException('Confronto inválido.');$this->db->prepare('INSERT INTO matches(tournament_id,stage_id,group_id,round_id,home_team_id,away_team_id,status,created_at,updated_at) VALUES(?,?,?,?,?,?,"scheduled",NOW(),NOW())')->execute([$tid,$stage,$group,$round,$home,$away]);return(int)$this->db->lastInsertId();}
    private function setMatchStatus(int $id,string $status):void{$this->db->prepare('UPDATE matches SET status=?,updated_at=NOW() WHERE id=?')->execute([$status,$id]);}
    private function match(int $id):array{return $this->one('SELECT * FROM matches WHERE id=? AND deleted_at IS NULL',[$id],'Partida não encontrada.');}
    private function assertTournamentTeam(int $tid,int $team):void{if(!$this->scalar('SELECT COUNT(*) FROM team_tournament_entries WHERE tournament_id=? AND team_id=? AND deleted_at IS NULL',[$tid,$team]))throw new RuntimeException('Equipe não inscrita no campeonato.');}
    private function withinAge(string $birth,array $rules):bool{$min=$rules['registration']['minimum_age']??null;$max=$rules['registration']['maximum_age']??null;if($min===null&&$max===null)return true;$age=(new \DateTimeImmutable($birth))->diff(new \DateTimeImmutable('today'))->y;return ($min===null||$age>=(int)$min)&&($max===null||$age<=(int)$max);}
    private function one(string $sql,array $p,string $message):array{$s=$this->db->prepare($sql);$s->execute($p);return $s->fetch()?:throw new RuntimeException($message);}
    private function many(string $sql,array $p=[]):array{$s=$this->db->prepare($sql);$s->execute($p);return $s->fetchAll();}
    private function scalar(string $sql,array $p=[]):int{$s=$this->db->prepare($sql);$s->execute($p);return(int)$s->fetchColumn();}
}
