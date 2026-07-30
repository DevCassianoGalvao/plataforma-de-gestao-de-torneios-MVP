<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class PortalEngagementSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        if (!$championshipId || !$adminId) throw new \RuntimeException('Campeonato ou administrador ficticio nao encontrado para o seed do portal.');

        $now = date('Y-m-d H:i:s');
        $officials = [
            ['Marina Azevedo', 'Árbitra principal'],
            ['Rafael Nunes', 'Assistente'],
            ['Camila Torres', 'Mesária'],
        ];
        $findOfficial = $pdo->prepare('SELECT id FROM championship_officials WHERE championship_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
        $insertOfficial = $pdo->prepare("INSERT INTO championship_officials (championship_id, full_name, public_name, role, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', ?, ?, ?)");
        foreach ($officials as [$name, $role]) {
            $findOfficial->execute([$championshipId, $name]);
            if (!$findOfficial->fetchColumn()) $insertOfficial->execute([$championshipId, $name, $name, $role, $adminId, $now, $now]);
        }

        $partners = [
            ['sponsor', 'Patrocinador oficial', 'https://example.com', 1],
            ['supporter', 'Apoiador esportivo', null, 1],
            ['organizer', 'Organização do campeonato', null, 1],
        ];
        $findPartner = $pdo->prepare('SELECT id FROM championship_sponsors WHERE championship_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $insertPartner = $pdo->prepare("INSERT INTO championship_sponsors (championship_id, partner_type, name, website_url, display_order, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)");
        foreach ($partners as [$type, $name, $website, $order]) {
            $findPartner->execute([$championshipId, $name]);
            if (!$findPartner->fetchColumn()) $insertPartner->execute([$championshipId, $type, $name, $website, $order, $adminId, $now, $now]);
        }
    }
}
