<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SimulationRepository;

final class SimulationService
{
    public function __construct(private readonly SimulationRepository $repository, private readonly StandingsCalculator $calculator, private readonly AuditService $audit) {}

    public function create(array $input, int $userId): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $championshipId = (int) ($input['championship_id'] ?? 0); $phaseId = (int) ($input['phase_id'] ?? 0); $groupId = (int) ($input['group_id'] ?? 0); $roundId = (int) ($input['round_id'] ?? 0);
        if ($name === '' || mb_strlen($name) > 160 || !$championshipId || !$phaseId) return ['ok'=>false,'errors'=>['Informe nome, campeonato e fase.']];
        $phases = $this->repository->phases($championshipId); if (!in_array($phaseId, array_map(static fn(array $phase): int => (int)$phase['id'], $phases), true)) return ['ok'=>false,'errors'=>['Fase invalida para o campeonato.']];
        if ($groupId && !in_array($groupId, array_map(static fn(array $group): int => (int)$group['id'], $this->repository->groups($phaseId)), true)) return ['ok'=>false,'errors'=>['Grupo invalido para a fase.']];
        if ($roundId && !in_array($roundId, array_map(static fn(array $round): int => (int)$round['id'], $this->repository->rounds($phaseId,$groupId?:null)), true)) return ['ok'=>false,'errors'=>['Rodada invalida para o recorte.']];
        $id=$this->repository->create(['championship_id'=>$championshipId,'phase_id'=>$phaseId,'group_id'=>$groupId,'round_id'=>$roundId,'name'=>$name,'description'=>trim((string)($input['description']??'')),'assumptions'=>trim((string)($input['assumptions']??'')),'notes'=>trim((string)($input['notes']??'')),'created_by'=>$userId]);
        $this->audit->record('simulation.created',$userId,'simulation_scenario',$id,['championship_id'=>$championshipId,'phase_id'=>$phaseId],null); return ['ok'=>true,'id'=>$id];
    }

    public function addReference(int $scenarioId, int $referenceMatchId, int $userId): array
    {
        $scenario=$this->repository->scenario($scenarioId); if(!$scenario)return $this->fail('Cenario nao encontrado.');
        foreach($this->repository->officialMatches((int)$scenario['phase_id']) as $match) if((int)$match['id']===$referenceMatchId){$id=$this->repository->upsertMatch($scenarioId,['reference_match_id'=>$match['id'],'phase_id'=>$match['phase_id'],'group_id'=>$match['group_id'],'round_id'=>$match['round_id'],'home_team_id'=>$match['home_team_id'],'away_team_id'=>$match['away_team_id'],'source_type'=>'reference']);$this->audit->record('simulation.match_added',$userId,'simulation_match',$id,['reference_match_id'=>$referenceMatchId],null);return ['ok'=>true,'id'=>$id];} return $this->fail('Partida de referencia invalida.');
    }

    public function addHypothetical(int $scenarioId,array $input,int $userId): array
    {
        $scenario=$this->repository->scenario($scenarioId);if(!$scenario)return $this->fail('Cenario nao encontrado.');$groupId=(int)($input['group_id']??$scenario['group_id']??0);$home=(int)($input['home_team_id']??0);$away=(int)($input['away_team_id']??0);if(!$groupId||!$home||!$away||$home===$away)return $this->fail('Informe grupo e duas equipes diferentes.');$teams=array_column($this->repository->teams($groupId),'team_id');if(!in_array($home,array_map('intval',$teams),true)||!in_array($away,array_map('intval',$teams),true))return $this->fail('Equipes fora do grupo da simulacao.');$id=$this->repository->upsertMatch($scenarioId,['phase_id'=>$scenario['phase_id'],'group_id'=>$groupId,'round_id'=>(int)($input['round_id']??0),'home_team_id'=>$home,'away_team_id'=>$away,'source_type'=>'hypothetical','notes'=>trim((string)($input['notes']??''))]);$this->audit->record('simulation.hypothetical_match_added',$userId,'simulation_match',$id,[],null);return['ok'=>true,'id'=>$id];
    }

    public function score(int $scenarioId,int $matchId,array $input,int $userId): array
    {
        $match=$this->repository->match($matchId);if(!$match||(int)$match['scenario_id']!==$scenarioId)return $this->fail('Partida simulada nao encontrada.');$home=$this->scoreValue($input['home_score']??null);$away=$this->scoreValue($input['away_score']??null);if($home===null||$away===null)return $this->fail('Placar deve ficar entre 0 e 99.');$wo=(int)($input['wo_winner_team_id']??0);if($wo&&!in_array($wo,[(int)$match['home_team_id'],(int)$match['away_team_id']],true))return $this->fail('Equipe de W.O. invalida.');if($wo){$reg=$this->repository->regulation((int)$match['championship_id']);$home=$wo===(int)$match['home_team_id']?(int)$reg['wo_winner_goals']:(int)$reg['wo_loser_goals'];$away=$wo===(int)$match['away_team_id']?(int)$reg['wo_winner_goals']:(int)$reg['wo_loser_goals'];}$this->repository->upsertMatch($scenarioId,array_merge($match,['home_score'=>$home,'away_score'=>$away,'home_penalties'=>$this->scoreValue($input['home_penalties']??null),'away_penalties'=>$this->scoreValue($input['away_penalties']??null),'wo_winner_team_id'=>$wo?:null,'notes'=>trim((string)($input['notes']??''))]));$this->audit->record('simulation.score_saved',$userId,'simulation_match',$matchId,['home_score'=>$home,'away_score'=>$away],null);return['ok'=>true];
    }

    public function simulateRound(int $scenarioId,int $roundId,int $userId): array { $scenario=$this->repository->scenario($scenarioId);if(!$scenario)return $this->fail('Cenario nao encontrado.');$count=0;foreach($this->repository->officialMatches((int)$scenario['phase_id'],null,$roundId) as $match){if($this->repository->matchByReferenceOrId($scenarioId,0,(int)$match['id'])){$count++;continue;}$this->repository->upsertMatch($scenarioId,['reference_match_id'=>$match['id'],'phase_id'=>$match['phase_id'],'group_id'=>$match['group_id'],'round_id'=>$match['round_id'],'home_team_id'=>$match['home_team_id'],'away_team_id'=>$match['away_team_id'],'source_type'=>'reference','home_score'=>0,'away_score'=>0]);$count++;}$this->audit->record('simulation.round_simulated',$userId,'simulation_scenario',$scenarioId,['round_id'=>$roundId,'matches'=>$count],null);return['ok'=>true,'count'=>$count]; }
    public function restoreMatch(int $scenarioId,int $matchId,int $userId): array { $match=$this->repository->match($matchId);if(!$match||(int)$match['scenario_id']!==$scenarioId)return $this->fail('Partida simulada nao encontrada.');$this->repository->removeMatch($matchId);$this->audit->record('simulation.match_restored',$userId,'simulation_match',$matchId,[],null);return['ok'=>true]; }
    public function addEvent(int $scenarioId,int $matchId,array $input,int $userId): array {$match=$this->repository->match($matchId);if(!$match||(int)$match['scenario_id']!==$scenarioId)return $this->fail('Partida simulada nao encontrada.');$team=(int)($input['team_id']??0);$type=(string)($input['event_type']??'');if(!in_array($team,[(int)$match['home_team_id'],(int)$match['away_team_id']],true)||!in_array($type,['goal','yellow_card','red_card','penalty_scored'],true))return $this->fail('Evento simulado invalido.');$minute=isset($input['minute'])&&$input['minute']!==''?max(0,min(180,(int)$input['minute'])):null;$this->repository->addEvent($matchId,$team,(int)($input['athlete_id']??0)?:null,$type,$minute);$this->audit->record('simulation.event_added',$userId,'simulation_match',$matchId,['event_type'=>$type],null);return['ok'=>true];}
    public function projection(int $scenarioId): array
    {
        $scenario=$this->repository->scenario($scenarioId);if(!$scenario)return[];$reg=$this->repository->regulation((int)$scenario['championship_id']);$overrides=[];foreach($this->repository->matches($scenarioId) as $match)if($match['reference_match_id'])$overrides[(int)$match['reference_match_id']]=$match;$groups=$scenario['group_id']?[$scenario['group_id']]:array_column($this->repository->groups((int)$scenario['phase_id']),'id');$result=[];
        foreach($groups as $groupId){$matches=[];foreach($this->repository->officialMatches((int)$scenario['phase_id'],(int)$groupId) as $official){$override=$overrides[(int)$official['id']]??null;if($override&&$override['home_score']!==null&&$override['away_score']!==null)$matches[]=['home_team_id'=>$override['home_team_id'],'away_team_id'=>$override['away_team_id'],'home_score'=>$override['home_score'],'away_score'=>$override['away_score']];elseif($official['status']==='homologated')$matches[]=['home_team_id'=>$official['home_team_id'],'away_team_id'=>$official['away_team_id'],'home_score'=>$official['home_score'],'away_score'=>$official['away_score']];}foreach($this->repository->matches($scenarioId) as $sim)if($sim['source_type']==='hypothetical'&&(int)$sim['group_id']===(int)$groupId&&$sim['home_score']!==null&&$sim['away_score']!==null)$matches[]=['home_team_id'=>$sim['home_team_id'],'away_team_id'=>$sim['away_team_id'],'home_score'=>$sim['home_score'],'away_score'=>$sim['away_score']];$rows=$this->calculator->calculate($this->repository->teams((int)$groupId),$matches,$reg,(int)$reg['qualified_per_group']);$official=$this->repository->officialStandings((int)$groupId);$officialBy=array_column($official,null,'team_id');foreach($rows as &$row){$base=$officialBy[$row['team_id']]??null;$row['team_name']=array_values(array_filter($this->repository->teams((int)$groupId),static fn(array $team):bool=>(int)$team['team_id']===(int)$row['team_id']))[0]['team_name']??'';$row['official_position']=$base['position']??null;$row['points_difference']=$base?(int)$row['points']-(int)$base['points']:(int)$row['points'];$row['position_change']=$base?(int)$base['position']-(int)$row['position']:0;}unset($row);$result[(int)$groupId]=['official'=>$official,'simulated'=>$rows];}return$result;
    }
    public function compare(int $scenarioId, int $otherScenarioId): array
    {
        $current=$this->projection($scenarioId);$other=$this->projection($otherScenarioId);
        foreach($current as $groupId=>&$tables){$baseline=array_column($other[$groupId]['simulated']??[],null,'team_id');foreach($tables['simulated'] as &$row){$base=$baseline[$row['team_id']]??null;if(!$base)continue;$row['points_difference']=(int)$row['points']-(int)$base['points'];$row['position_change']=(int)$base['position']-(int)$row['position'];}unset($row);}unset($tables);return$current;
    }
    public function possibleCrossings(int $scenarioId): array
    {
        $scenario=$this->repository->scenario($scenarioId);if(!$scenario)return[];$projection=$this->projection($scenarioId);$groups=[];
        foreach($this->repository->groups((int)$scenario['phase_id']) as $group)$groups[strtoupper((string)$group['code'])]=array_column($projection[(int)$group['id']]['simulated']??[],null,'position');
        $regulation=$this->repository->regulation((int)$scenario['championship_id']);$crossings=[];
        foreach($this->repository->knockoutPairings((int)$regulation['id']) as $pair){$home=$this->sourceTeam((string)$pair['home_source'],$groups);$away=$this->sourceTeam((string)$pair['away_source'],$groups);if($home||$away)$crossings[]=['label'=>'Quartas de final '.$pair['tie_number'],'home_source'=>$pair['home_source'],'away_source'=>$pair['away_source'],'home_team'=>$home,'away_team'=>$away];}
        return$crossings;
    }
    private function sourceTeam(string $source,array $groups): ?array {if(!preg_match('/^([A-Z]+)([1-9][0-9]*)$/',$source,$parts))return null;return$groups[$parts[1]][(int)$parts[2]]??null;}
    private function scoreValue(mixed $value): ?int {if($value===null||$value==='')return null;if(filter_var($value,FILTER_VALIDATE_INT)===false)return null;$value=(int)$value;return $value>=0&&$value<=99?$value:null;}
    private function fail(string $error):array{return['ok'=>false,'errors'=>[$error]];}
}
