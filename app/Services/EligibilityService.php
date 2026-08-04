<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\EligibilityRepository;

final class EligibilityService
{
    public function __construct(private readonly EligibilityRepository $eligibility, private readonly AuditService $audit) {}
    public function issues(array $match,int $teamId,int $athleteId): array
    {
        $issues=[];foreach($this->eligibility->rulesForMatch($match) as $rule){if($this->eligibility->exception((int)$match['championship_id'],$teamId,$athleteId,(int)$rule['id'],(int)$match['id']))continue;$registration=$this->eligibility->registration((int)$match['championship_id'],$teamId,$athleteId);if(!$registration){$issues[]='Atleta sem inscricao aprovada para esta equipe.';continue;}if($rule['registration_approved_before']&&substr((string)($registration['decided_at']??''),0,10)>$rule['registration_approved_before'])$issues[]='Inscricao aprovada apos data limite.';if((int)$rule['minimum_participations']>$this->eligibility->participation($athleteId,$teamId,(int)$rule['source_phase_id'],(string)$rule['participation_type']))$issues[]='Participacao minima na fase anterior nao atingida.';if((int)$rule['require_complete_documents']===1&&!$this->eligibility->documentsComplete((int)$rule['regulation_id'],$athleteId))$issues[]='Documentacao obrigatoria incompleta ou vencida.';if((int)$rule['require_no_suspension']===1&&$this->eligibility->activeSuspension((int)$match['championship_id'],$athleteId,(int)$match['id']))$issues[]='Atleta com suspensao ativa.';}return array_values(array_unique($issues));
    }
    public function grant(array $user,array $data): array { $reason=trim((string)($data['reason']??''));if($reason==='')return['ok'=>false,'errors'=>['Informe motivo da excecao.']];$id=$this->eligibility->grant(array_merge($data,['granted_by'=>(int)$user['id']]));$this->audit->record('eligibility.exception_granted',(int)$user['id'],'regulation_eligibility_exception',$id,['championship_id'=>$data['championship_id']],null);return['ok'=>true,'id'=>$id,'errors'=>[]]; }
}
