<?php
declare(strict_types=1);
namespace App\Services;
use RuntimeException;

final class ScheduleGenerationService {
    public function roundRobin(array $teamIds,bool $doubleRound=false): array {
        $teams=array_values($teamIds);if(count($teams)<2)throw new RuntimeException('São necessárias ao menos duas equipes.');if(count($teams)%2)$teams[]=null;
        $fixed=array_shift($teams);$rounds=[];$total=count($teams)+1;
        for($round=0;$round<$total-1;$round++){
            $order=array_merge([$fixed],$teams);$games=[];
            for($i=0;$i<$total/2;$i++){ $home=$order[$i];$away=$order[$total-1-$i];if($home!==null&&$away!==null)$games[]=$round%2===0?['home'=>$home,'away'=>$away]:['home'=>$away,'away'=>$home]; }
            $rounds[]=$games;$last=array_pop($teams);array_unshift($teams,$last);
        }
        if($doubleRound){$return=[];foreach($rounds as $games)$return[]=array_map(fn($g)=>['home'=>$g['away'],'away'=>$g['home']],$games);$rounds=array_merge($rounds,$return);}return $rounds;
    }
}
