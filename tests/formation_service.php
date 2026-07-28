<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Services/FormationService.php';

use App\Services\FormationService;

$slots=[
    ['id'=>1,'slot_key'=>'gk-1','position_code'=>'goalkeeper','position_group'=>'goalkeeper'],
    ['id'=>2,'slot_key'=>'cb-1','position_code'=>'center_back','position_group'=>'defender'],
    ['id'=>3,'slot_key'=>'forward-1','position_code'=>'forward','position_group'=>'forward'],
];
$result=FormationService::assign($slots,[
    ['person_id'=>10,'primary_position'=>'forward','secondary_positions'=>[]],
    ['person_id'=>11,'primary_position'=>'goalkeeper','secondary_positions'=>[]],
    ['person_id'=>12,'primary_position'=>'defender','secondary_positions'=>[]],
]);
$bySlot=[]; foreach($result['assignments'] as $row)$bySlot[$row['slot_id']]=$row;
if($bySlot[1]['person_id']!==11 || $bySlot[3]['person_id']!==10 || $bySlot[2]['person_id']!==12) throw new RuntimeException('Prioridade de distribuicao falhou.');
if($bySlot[2]['is_out_of_position']!==1) throw new RuntimeException('Incompatibilidade nao registrada.');
if(count($result['unassigned'])!==0) throw new RuntimeException('Atleta elegivel ficou sem vaga.');
echo "FORMATION_SERVICE_OK\n";
