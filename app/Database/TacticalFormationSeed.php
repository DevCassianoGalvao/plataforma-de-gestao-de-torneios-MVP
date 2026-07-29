<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class TacticalFormationSeed
{
    public static function run(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');
        $roleStatement = $pdo->prepare('INSERT INTO staff_roles (`key`, name, description, active, display_order, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), display_order = VALUES(display_order), updated_at = VALUES(updated_at)');
        foreach ([
            ['head_coach', 'Treinador', 'Responsavel tecnico principal.', 1],
            ['assistant_coach', 'Auxiliar tecnico', 'Apoio tecnico ao treinador.', 2],
            ['physical_trainer', 'Preparador fisico', 'Acompanha preparacao fisica.', 3],
            ['goalkeeper_coach', 'Preparador de goleiros', 'Acompanha os goleiros.', 4],
            ['physiotherapist', 'Fisioterapeuta', 'Apoia a recuperacao fisica.', 5],
            ['doctor', 'Medico', 'Responsavel medico.', 6],
            ['masseur', 'Massagista', 'Apoio de massoterapia.', 7],
            ['director', 'Dirigente', 'Representacao administrativa.', 8],
            ['supervisor', 'Supervisor', 'Apoio de operacao da equipe.', 9],
            ['kit_manager', 'Roupeiro', 'Cuida dos materiais esportivos.', 10],
            ['other', 'Outro', 'Outra funcao autorizada.', 11],
        ] as [$key, $name, $description, $order]) {
            $roleStatement->execute([$key, $name, $description, $order, $now, $now]);
        }

        $formationStatement = $pdo->prepare('INSERT INTO tactical_formations (name, slug, description, player_count, active, created_at, updated_at) VALUES (?, ?, ?, 11, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), player_count = VALUES(player_count), active = 1, updated_at = VALUES(updated_at)');
        $slotStatement = $pdo->prepare('INSERT INTO tactical_formation_slots (tactical_formation_id, slot_key, position_code, label, position_group, horizontal_position, vertical_position, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE position_code = VALUES(position_code), label = VALUES(label), position_group = VALUES(position_group), horizontal_position = VALUES(horizontal_position), vertical_position = VALUES(vertical_position), display_order = VALUES(display_order), updated_at = VALUES(updated_at)');
        foreach (self::formations() as $formation) {
            $formationStatement->execute([$formation['name'], $formation['slug'], $formation['description'], $now, $now]);
            $find = $pdo->prepare('SELECT id FROM tactical_formations WHERE slug = ? LIMIT 1');
            $find->execute([$formation['slug']]);
            $id = (int) $find->fetchColumn();
            foreach ($formation['slots'] as $slot) {
                $slotStatement->execute([$id, $slot['slot_key'], $slot['position_code'], $slot['label'], $slot['position_group'], $slot['horizontal_position'], $slot['vertical_position'], $slot['display_order'], $now, $now]);
            }
        }
    }

    private static function slot(string $key, string $code, string $label, string $group, int $horizontal, int $vertical, int $order): array
    {
        return ['slot_key' => $key, 'position_code' => $code, 'label' => $label, 'position_group' => $group, 'horizontal_position' => $horizontal, 'vertical_position' => $vertical, 'display_order' => $order];
    }

    private static function formations(): array
    {
        $gk = static fn (int $order = 1): array => self::slot('gk', 'goalkeeper', 'Goleiro', 'goalkeeper', 50, 8, $order);
        return [
            ['name' => '4-4-2', 'slug' => '4-4-2', 'description' => 'Quatro defensores, quatro meio-campistas e dois atacantes.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 15, 27, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 38, 25, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 62, 25, 4), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 85, 27, 5), self::slot('lm', 'left_midfielder', 'Meia esquerda', 'midfielder', 12, 52, 6), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 38, 50, 7), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 62, 50, 8), self::slot('rm', 'right_midfielder', 'Meia direito', 'midfielder', 88, 52, 9), self::slot('lst', 'forward', 'Atacante esquerdo', 'forward', 38, 78, 10), self::slot('rst', 'forward', 'Atacante direito', 'forward', 62, 78, 11)]],
            ['name' => '4-3-3', 'slug' => '4-3-3', 'description' => 'Linha de quatro, meio-campo com tres jogadores e tres atacantes.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 15, 27, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 38, 25, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 62, 25, 4), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 85, 27, 5), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 25, 50, 6), self::slot('cm', 'central_midfielder', 'Meio-campista central', 'central_midfielder', 50, 47, 7), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 75, 50, 8), self::slot('lw', 'left_winger', 'Ponta esquerda', 'winger', 15, 78, 9), self::slot('st', 'forward', 'Atacante central', 'forward', 50, 82, 10), self::slot('rw', 'right_winger', 'Ponta direita', 'winger', 85, 78, 11)]],
            ['name' => '4-2-3-1', 'slug' => '4-2-3-1', 'description' => 'Linha de quatro, dois volantes, tres meias e um atacante.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 15, 27, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 38, 25, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 62, 25, 4), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 85, 27, 5), self::slot('ldm', 'defensive_midfielder', 'Volante esquerdo', 'defensive_midfielder', 38, 46, 6), self::slot('rdm', 'defensive_midfielder', 'Volante direito', 'defensive_midfielder', 62, 46, 7), self::slot('lam', 'attacking_midfielder', 'Meia ofensivo esquerdo', 'attacking_midfielder', 25, 66, 8), self::slot('cam', 'attacking_midfielder', 'Meia ofensivo central', 'attacking_midfielder', 50, 63, 9), self::slot('ram', 'attacking_midfielder', 'Meia ofensivo direito', 'attacking_midfielder', 75, 66, 10), self::slot('st', 'forward', 'Atacante', 'forward', 50, 84, 11)]],
            ['name' => '4-1-4-1', 'slug' => '4-1-4-1', 'description' => 'Linha de quatro, um volante, quatro meias e um atacante.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 15, 27, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 38, 25, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 62, 25, 4), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 85, 27, 5), self::slot('dm', 'defensive_midfielder', 'Volante', 'defensive_midfielder', 50, 42, 6), self::slot('lm', 'left_midfielder', 'Meia esquerda', 'midfielder', 12, 62, 7), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 38, 60, 8), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 62, 60, 9), self::slot('rm', 'right_midfielder', 'Meia direito', 'midfielder', 88, 62, 10), self::slot('st', 'forward', 'Atacante', 'forward', 50, 83, 11)]],
            ['name' => '4-5-1', 'slug' => '4-5-1', 'description' => 'Linha de quatro, cinco jogadores no meio e um atacante.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 15, 27, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 38, 25, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 62, 25, 4), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 85, 27, 5), self::slot('lm', 'left_midfielder', 'Meia esquerda', 'midfielder', 12, 56, 6), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 31, 53, 7), self::slot('cm', 'central_midfielder', 'Meio-campista central', 'central_midfielder', 50, 50, 8), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 69, 53, 9), self::slot('rm', 'right_midfielder', 'Meia direito', 'midfielder', 88, 56, 10), self::slot('st', 'forward', 'Atacante', 'forward', 50, 83, 11)]],
            ['name' => '3-5-2', 'slug' => '3-5-2', 'description' => 'Tres zagueiros, alas, tres meio-campistas e dois atacantes.', 'slots' => [$gk(), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 28, 25, 2), self::slot('cb', 'center_back', 'Zagueiro central', 'defender', 50, 23, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 72, 25, 4), self::slot('lwb', 'left_wingback', 'Ala esquerdo', 'wingback', 8, 52, 5), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 31, 50, 6), self::slot('dm', 'defensive_midfielder', 'Volante', 'defensive_midfielder', 50, 47, 7), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 69, 50, 8), self::slot('rwb', 'right_wingback', 'Ala direito', 'wingback', 92, 52, 9), self::slot('lst', 'forward', 'Atacante esquerdo', 'forward', 38, 79, 10), self::slot('rst', 'forward', 'Atacante direito', 'forward', 62, 79, 11)]],
            ['name' => '3-4-3', 'slug' => '3-4-3', 'description' => 'Tres zagueiros, quatro no meio e tres atacantes.', 'slots' => [$gk(), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 28, 25, 2), self::slot('cb', 'center_back', 'Zagueiro central', 'defender', 50, 23, 3), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 72, 25, 4), self::slot('lm', 'left_midfielder', 'Meia esquerdo', 'midfielder', 15, 53, 5), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 38, 50, 6), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 62, 50, 7), self::slot('rm', 'right_midfielder', 'Meia direito', 'midfielder', 85, 53, 8), self::slot('lw', 'left_winger', 'Ponta esquerda', 'winger', 15, 78, 9), self::slot('st', 'forward', 'Atacante central', 'forward', 50, 82, 10), self::slot('rw', 'right_winger', 'Ponta direita', 'winger', 85, 78, 11)]],
            ['name' => '5-3-2', 'slug' => '5-3-2', 'description' => 'Cinco defensores, tres meio-campistas e dois atacantes.', 'slots' => [$gk(), self::slot('lb', 'left_back', 'Lateral esquerdo', 'fullback', 8, 30, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 30, 24, 3), self::slot('cb', 'center_back', 'Zagueiro central', 'defender', 50, 22, 4), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 70, 24, 5), self::slot('rb', 'right_back', 'Lateral direito', 'fullback', 92, 30, 6), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 30, 53, 7), self::slot('dm', 'defensive_midfielder', 'Volante', 'defensive_midfielder', 50, 50, 8), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 70, 53, 9), self::slot('lst', 'forward', 'Atacante esquerdo', 'forward', 38, 80, 10), self::slot('rst', 'forward', 'Atacante direito', 'forward', 62, 80, 11)]],
            ['name' => '5-4-1', 'slug' => '5-4-1', 'description' => 'Cinco defensores, quatro meio-campistas e um atacante.', 'slots' => [$gk(), self::slot('lwb', 'left_wingback', 'Ala esquerdo', 'wingback', 8, 30, 2), self::slot('lcb', 'center_back', 'Zagueiro esquerdo', 'defender', 30, 24, 3), self::slot('cb', 'center_back', 'Zagueiro central', 'defender', 50, 22, 4), self::slot('rcb', 'center_back', 'Zagueiro direito', 'defender', 70, 24, 5), self::slot('rwb', 'right_wingback', 'Ala direito', 'wingback', 92, 30, 6), self::slot('lm', 'left_midfielder', 'Meia esquerdo', 'midfielder', 15, 58, 7), self::slot('lcm', 'central_midfielder', 'Meio-campista esquerdo', 'central_midfielder', 38, 55, 8), self::slot('rcm', 'central_midfielder', 'Meio-campista direito', 'central_midfielder', 62, 55, 9), self::slot('rm', 'right_midfielder', 'Meia direito', 'midfielder', 85, 58, 10), self::slot('st', 'forward', 'Atacante', 'forward', 50, 83, 11)]],
        ];
    }
}
