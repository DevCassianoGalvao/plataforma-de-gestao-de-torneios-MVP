<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class FormationService
{
    public static function presets(): array
    {
        return [
            '4-4-2' => ['4-4-2', ['goalkeeper','left_back','center_back','center_back','right_back','left_midfielder','central_midfielder','central_midfielder','right_midfielder','forward','forward']],
            '4-3-3' => ['4-3-3', ['goalkeeper','left_back','center_back','center_back','right_back','central_midfielder','central_midfielder','attacking_midfielder','left_winger','forward','right_winger']],
            '4-2-3-1' => ['4-2-3-1', ['goalkeeper','left_back','center_back','center_back','right_back','defensive_midfielder','defensive_midfielder','left_winger','attacking_midfielder','right_winger','forward']],
            '4-1-4-1' => ['4-1-4-1', ['goalkeeper','left_back','center_back','center_back','right_back','defensive_midfielder','left_midfielder','central_midfielder','central_midfielder','right_midfielder','forward']],
            '4-1-2-1-2' => ['4-1-2-1-2', ['goalkeeper','left_back','center_back','center_back','right_back','defensive_midfielder','central_midfielder','central_midfielder','attacking_midfielder','forward','forward']],
            '4-3-2-1' => ['4-3-2-1', ['goalkeeper','left_back','center_back','center_back','right_back','central_midfielder','central_midfielder','central_midfielder','attacking_midfielder','attacking_midfielder','forward']],
            '4-5-1' => ['4-5-1', ['goalkeeper','left_back','center_back','center_back','right_back','left_midfielder','central_midfielder','central_midfielder','attacking_midfielder','right_midfielder','forward']],
            '4-4-1-1' => ['4-4-1-1', ['goalkeeper','left_back','center_back','center_back','right_back','left_midfielder','central_midfielder','central_midfielder','right_midfielder','attacking_midfielder','forward']],
            '3-5-2' => ['3-5-2', ['goalkeeper','center_back','center_back','center_back','left_midfielder','central_midfielder','central_midfielder','central_midfielder','right_midfielder','forward','forward']],
            '3-4-3' => ['3-4-3', ['goalkeeper','center_back','center_back','center_back','left_midfielder','central_midfielder','central_midfielder','right_midfielder','left_winger','forward','right_winger']],
            '3-4-2-1' => ['3-4-2-1', ['goalkeeper','center_back','center_back','center_back','left_midfielder','central_midfielder','central_midfielder','right_midfielder','attacking_midfielder','attacking_midfielder','forward']],
            '5-4-1' => ['5-4-1', ['goalkeeper','left_back','center_back','center_back','center_back','right_back','left_midfielder','central_midfielder','central_midfielder','right_midfielder','forward']],
            '5-3-2' => ['5-3-2', ['goalkeeper','left_back','center_back','center_back','center_back','right_back','central_midfielder','central_midfielder','attacking_midfielder','forward','forward']],
            '5-2-3' => ['5-2-3', ['goalkeeper','left_back','center_back','center_back','center_back','right_back','central_midfielder','central_midfielder','left_winger','forward','right_winger']],
            '5-2-1-2' => ['5-2-1-2', ['goalkeeper','left_back','center_back','center_back','center_back','right_back','defensive_midfielder','central_midfielder','attacking_midfielder','forward','forward']],
        ];
    }

    public static function ensurePresets(PDO $db): void
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::presets() as $code => [$name, $positions]) {
            $find = $db->prepare('SELECT id FROM tactical_formations WHERE code=? AND deleted_at IS NULL');
            $find->execute([$code]);
            $formationId = (int) $find->fetchColumn();
            if (!$formationId) {
                $insert = $db->prepare('INSERT INTO tactical_formations(code,name,player_count,status,created_at,updated_at) VALUES(?,?,? ,"active",?,?)');
                $insert->execute([$code, $name, count($positions), $now, $now]);
                $formationId = (int) $db->lastInsertId();
            }
            foreach ($positions as $order => $positionCode) {
                $slotKey = $positionCode.'-'.($order + 1);
                $slot = self::slotDefinition($positionCode, $order);
                $insert = $db->prepare('INSERT INTO formation_slots(formation_id,slot_key,position_name,position_code,position_group,abbreviation,role_label,horizontal,vertical,slot_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE position_name=VALUES(position_name),position_code=VALUES(position_code),position_group=VALUES(position_group),abbreviation=VALUES(abbreviation),role_label=VALUES(role_label),horizontal=VALUES(horizontal),vertical=VALUES(vertical),slot_order=VALUES(slot_order),updated_at=VALUES(updated_at)');
                $insert->execute([$formationId,$slotKey,$slot['name'],$positionCode,$slot['group'],$slot['abbreviation'],$slot['role'],$slot['horizontal'],$slot['vertical'],$order+1,$now,$now]);
            }
        }
    }

    public static function assign(array $slots, array $athletes): array
    {
        $remaining = array_values($athletes);
        $assignments = [];
        foreach ($slots as $slot) {
            $bestIndex = null;
            $bestScore = PHP_INT_MAX;
            foreach ($remaining as $index => $athlete) {
                $score = self::compatibilityScore($slot, $athlete);
                if ($score < $bestScore) { $bestScore = $score; $bestIndex = $index; }
            }
            if ($bestIndex === null) continue;
            $athlete = $remaining[$bestIndex];
            array_splice($remaining, $bestIndex, 1);
            $assignments[] = [
                'slot_id' => $slot['id'] ?? $slot['slot_key'],
                'person_id' => (int) $athlete['person_id'],
                'position_source' => match ($bestScore) { 0 => 'primary', 1 => 'secondary', 2 => 'group', default => 'fallback' },
                'is_out_of_position' => self::normalize((string)($athlete['primary_position'] ?? '')) !== self::normalize((string)($slot['position_code'] ?? '')) ? 1 : 0,
                'manual_override' => 0,
            ];
        }
        return ['assignments' => $assignments, 'unassigned' => array_values($remaining)];
    }

    public function persistAutoAssignment(int $matchId, int $teamId, int $formationId, array $assignments): void
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $lineup = $db->prepare('SELECT id FROM match_lineups WHERE match_id=? AND team_id=? AND person_id=? AND lineup_role="starter" AND deleted_at IS NULL');
            $insert = $db->prepare('INSERT INTO match_lineup_positions(match_lineup_id,match_id,team_id,formation_id,slot_id,person_id,position_source,is_out_of_position,manual_override,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())');
            $db->prepare('DELETE FROM match_lineup_positions WHERE match_id=? AND team_id=?')->execute([$matchId,$teamId]);
            foreach ($assignments as $assignment) {
                $lineup->execute([$matchId,$teamId,(int)$assignment['person_id']]);
                $lineupId = (int) $lineup->fetchColumn();
                if (!$lineupId) throw new RuntimeException('Atleta precisa estar como titular antes da posicao.');
                $insert->execute([$lineupId,$matchId,$teamId,$formationId,$assignment['slot_id'],$assignment['person_id'],$assignment['position_source'],(int)$assignment['is_out_of_position'],0]);
            }
            $db->commit();
        } catch (\Throwable $error) { $db->rollBack(); throw $error; }
    }

    private static function compatibilityScore(array $slot, array $athlete): int
    {
        $primary = self::normalize((string)($athlete['primary_position'] ?? ''));
        $slotCode = self::normalize((string)($slot['position_code'] ?? ''));
        if ($primary === $slotCode) return 0;
        $secondary = $athlete['secondary_positions'] ?? [];
        if (is_string($secondary)) $secondary = array_filter(array_map('trim', explode(',', $secondary)));
        foreach ((array)$secondary as $position) if (self::normalize((string)$position) === $slotCode) return 1;
        if (self::group($primary) === self::group((string)($slot['position_group'] ?? ''))) return 2;
        return 3;
    }

    private static function group(string $position): string
    {
        $position = self::normalize($position);
        if (in_array($position,['goalkeeper','gk','goleiro'],true)) return 'goalkeeper';
        if (in_array($position,['defender','center_back','left_back','right_back','fullback','zagueiro','lateral_esquerdo','lateral_direito'],true)) return 'defender';
        if (in_array($position,['midfielder','central_midfielder','defensive_midfielder','attacking_midfielder','left_midfielder','right_midfielder','volante','meio-campista','meia'],true)) return 'midfield';
        if (in_array($position,['winger','left_winger','right_winger','ponta'],true)) return 'winger';
        if (in_array($position,['forward','striker','attacker','atacante'],true)) return 'forward';
        return $position;
    }

    private static function normalize(string $value): string
    {
        return strtolower(str_replace([' ','-'],['_','_'],trim($value)));
    }

    private static function slotDefinition(string $code, int $order): array
    {
        $names=['goalkeeper'=>['Goleiro','GOL','goalkeeper','Goleiro'],'left_back'=>['Lateral esquerdo','LE','defender','Lateral esquerdo'],'center_back'=>['Zagueiro','ZAG','defender','Zagueiro'],'right_back'=>['Lateral direito','LD','defender','Lateral direito'],'defensive_midfielder'=>['Volante','VOL','midfield','Volante'],'central_midfielder'=>['Meio-campista','MC','midfield','Meio-campista'],'attacking_midfielder'=>['Meia ofensivo','MEI','midfield','Meia ofensivo'],'left_midfielder'=>['Meia esquerda','ME','midfield','Meia esquerda'],'right_midfielder'=>['Meia direita','MD','midfield','Meia direita'],'left_winger'=>['Ponta esquerda','PE','winger','Ponta esquerda'],'right_winger'=>['Ponta direita','PD','winger','Ponta direita'],'forward'=>['Atacante','ATA','forward','Atacante']];
        $item=$names[$code]??['Posicao','POS','field','Posicao'];
        return ['name'=>$item[0],'abbreviation'=>$item[1],'group'=>$item[2],'role'=>$item[3],'horizontal'=>50+(($order%5)-2)*10,'vertical'=>90-(int)(($order/5)*25)];
    }

    public function __construct(private PDO $db) {}
}
