<?php
declare(strict_types=1);
namespace Tests\Integration;
use App\Core\Database;
use App\Repositories\EvidenceChecklistRepository;
use App\Repositories\MatchMediaRepository;
use function Tests\assert_same; use function Tests\assert_true;

final class EvidenceChecklistIntegrationTest
{
    public static function run(): void
    {
        $pdo=Database::connection(); $admin=(int)$pdo->query("SELECT id FROM users WHERE email='admin@torneios.local' LIMIT 1")->fetchColumn(); $championships=$pdo->query('SELECT id FROM championships WHERE deleted_at IS NULL ORDER BY id LIMIT 2')->fetchAll();
        assert_true(count($championships)>=1,'Campeonato de teste ausente'); $champ=(int)$championships[0]['id']; $repo=new EvidenceChecklistRepository($pdo); $media=new MatchMediaRepository($pdo);
        $base=['name'=>'Equipe mandante perfilada','description'=>'Registro antes da partida','is_required'=>1,'is_active'=>1,'display_order'=>2,'expected_moment'=>'before_match','allowed_mime_types'=>'image/jpeg,image/png,image/webp','min_files'=>1,'max_files'=>2,'max_file_size_bytes'=>1048576,'notes_required'=>1,'blocks_operation_start'=>1,'blocks_approval_submission'=>1,'blocks_document_completion'=>1,'show_in_accountability'=>1];
        $id=$repo->save($champ,$base,$admin); $optional=$repo->save($champ,array_merge($base,['name'=>'Torcida','is_required'=>0,'display_order'=>1,'blocks_operation_start'=>0]),$admin);
        $items=$repo->items($champ); assert_same($optional,(int)$items[0]['id'],'Reordenação inicial do checklist falhou');
        $repo->reorder($champ,[$id,$optional]); assert_same($id,(int)$repo->items($champ)[0]['id'],'Reordenação do checklist falhou');
        $repo->toggle($id,$champ,false); assert_same(0,(int)$repo->item($id,$champ)['is_active'],'Desativação do item falhou'); $repo->toggle($id,$champ,true);
        $repo->delete($optional,$champ,$admin); assert_true($repo->item($optional,$champ)===null,'Exclusão lógica do item falhou'); assert_true($repo->restore($optional,$champ),'Restauração do item falhou');
        $matchId=(int)$pdo->query('SELECT id FROM matches WHERE championship_id='.$champ.' ORDER BY id LIMIT 1')->fetchColumn(); if($matchId>0){assert_same([$repo->item($id,$champ)['id']],array_map(static fn(array $x):int=>(int)$x['id'],$media->missing($champ,$matchId,'start')),'Bloqueio de início não identificou item obrigatório');}
        if(count($championships)>=2){
            $source=$champ; $target=(int)$championships[1]['id'];
            if($repo->activeCount($target)===0){
                $eventDay=$repo->save($source,array_merge($base,['scope'=>'event_day','name'=>'Equipe de trabalho','expected_moment'=>'during_match']),$admin);
                $copied=$repo->duplicate($source,$target,$admin); assert_true(is_int($copied)&&$copied>=3,'Duplicação configurável do checklist falhou');
                $targetItems=$repo->items($target); assert_true(count(array_filter($targetItems,static fn(array $item):bool=>($item['scope']??'match')==='event_day'))===1,'Escopo de dia de evento não foi preservado');
                assert_true($repo->duplicate($source,$target,$admin)===null,'Duplicação repetida deveria ser bloqueada');
            }
        }
    }
}
