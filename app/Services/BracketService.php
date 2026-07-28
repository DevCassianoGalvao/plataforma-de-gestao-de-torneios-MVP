<?php
declare(strict_types=1);
namespace App\Services;
final class BracketService { public function pairings(array $qualified): array { return [['stage'=>'quarterfinal','pairings'=>['A1-B4','A2-B3','B1-A4','B2-A3']],['stage'=>'semifinal','pairings'=>['QF1-QF2','QF3-QF4']],['stage'=>'final','pairings'=>['SF1-SF2']]]; } }
