<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class PositionSeed
{
    public static function run(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $pdo->prepare('INSERT INTO positions (`code`, name, position_group, display_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, \'active\', ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), position_group = VALUES(position_group), display_order = VALUES(display_order), status = \'active\', updated_at = VALUES(updated_at)');
        foreach ([
            ['goalkeeper', 'Goleiro', 'goalkeeper'],
            ['center_back', 'Zagueiro', 'defender'],
            ['right_back', 'Lateral direito', 'fullback'],
            ['left_back', 'Lateral esquerdo', 'fullback'],
            ['right_wingback', 'Ala direito', 'wingback'],
            ['left_wingback', 'Ala esquerdo', 'wingback'],
            ['defensive_midfielder', 'Volante', 'defensive_midfielder'],
            ['central_midfielder', 'Meio-campista', 'central_midfielder'],
            ['attacking_midfielder', 'Meia ofensivo', 'attacking_midfielder'],
            ['right_winger', 'Ponta direita', 'winger'],
            ['left_winger', 'Ponta esquerda', 'winger'],
            ['second_forward', 'Segundo atacante', 'forward'],
            ['center_forward', 'Centroavante', 'forward'],
        ] as $index => [$code, $name, $group]) {
            $statement->execute([$code, $name, $group, $index + 1, $now, $now]);
        }
    }
}
