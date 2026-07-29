<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class AthleteDocumentTypeSeed
{
    public static function run(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $pdo->prepare('INSERT INTO athlete_document_types (`key`, name, description, guardian_applicable, required_for_minor, active, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), guardian_applicable = VALUES(guardian_applicable), required_for_minor = VALUES(required_for_minor), active = 1, display_order = VALUES(display_order), updated_at = VALUES(updated_at)');
        foreach ([
            ['athlete_document', 'Documento do atleta', 'Documento pessoal do atleta.', 0, 0],
            ['guardian_authorization', 'Autorizacao do responsavel', 'Autorizacao vinculada ao responsavel legal.', 1, 1],
            ['proof', 'Comprovante', 'Comprovante solicitado pela organizacao.', 0, 0],
            ['medical_certificate', 'Atestado', 'Atestado ou comprovacao de aptidao.', 0, 0],
            ['photo', 'Foto', 'Foto para identificacao esportiva.', 0, 0],
            ['other', 'Outro', 'Outro documento permitido.', 1, 0],
        ] as $index => [$key, $name, $description, $guardianApplicable, $minorRequired]) {
            $statement->execute([$key, $name, $description, $guardianApplicable, $minorRequired, $index + 1, $now, $now]);
        }
    }
}
