<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class TeamSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championship = $pdo->prepare("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1");
        $championship->execute();
        $championshipId = (int) $championship->fetchColumn();
        if (!$championshipId) throw new \RuntimeException('Campeonato necessario ao seed de equipes nao encontrado.');
        $adminId = self::userId($pdo, 'admin@torneios.local');
        $trainerId = self::userId($pdo, 'treinador@torneios.local');
        $managerId = self::userId($pdo, 'gestor@torneios.local');
        $formations = self::idMap($pdo, 'tactical_formations', 'slug');
        $roles = self::idMap($pdo, 'staff_roles', 'key');
        $teams = [
            ['Estrela Norte FC', 'Estrela Norte', 'estrela-norte-fc', 'ENF', 'Sao Paulo', 'SP', '#123C32', '#D9A441', '4-4-2'],
            ['Aurora Futebol Clube', 'Aurora FC', 'aurora-futebol-clube', 'AFC', 'Campinas', 'SP', '#164B8A', '#E8C547', '4-3-3'],
            ['Vila Nova Talentos', 'Vila Nova', 'vila-nova-talentos', 'VNT', 'Goiania', 'GO', '#B0182B', '#F2D27A', '4-2-3-1'],
            ['Ribeirao Academy', 'Ribeirao', 'ribeirao-academy', 'RAC', 'Ribeirao Preto', 'SP', '#1F5E3B', '#F4F1DE', '4-1-4-1'],
            ['Litoral Esporte Clube', 'Litoral EC', 'litoral-esporte-clube', 'LEC', 'Santos', 'SP', '#0E7490', '#E6FFFB', '4-5-1'],
            ['Serra Azul Futebol', 'Serra Azul', 'serra-azul-futebol', 'SAF', 'Caxias do Sul', 'RS', '#2455A4', '#F5F5F5', '3-5-2'],
            ['Ponte Verde FC', 'Ponte Verde', 'ponte-verde-fc', 'PVF', 'Belo Horizonte', 'MG', '#2F855A', '#F6E05E', '3-4-3'],
            ['Capital Oeste', 'Capital Oeste', 'capital-oeste', 'CPO', 'Brasilia', 'DF', '#4A2C6D', '#E6C87A', '5-3-2'],
            ['Vale do Sol', 'Vale do Sol', 'vale-do-sol', 'VDS', 'Curitiba', 'PR', '#C05621', '#FFF5EB', '5-4-1'],
            ['Imperio Juvenil', 'Imperio', 'imperio-juvenil', 'IMJ', 'Recife', 'PE', '#7B341E', '#FBD38D', '4-4-2'],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($teams as $index => [$name, $short, $slug, $abbreviation, $city, $state, $primary, $secondary, $formationSlug]) {
            $find = $pdo->prepare('SELECT id FROM teams WHERE championship_id = ? AND slug = ? AND deleted_at IS NULL LIMIT 1');
            $find->execute([$championshipId, $slug]);
            $teamId = (int) $find->fetchColumn();
            $formationId = $formations[$formationSlug] ?? 0;
            if (!$formationId) throw new \RuntimeException('Formacao necessaria ao seed nao encontrada: ' . $formationSlug);
            if (!$teamId) {
                $create = $pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, description, city, state, primary_color, secondary_color, status, default_tactical_formation_id, default_formation_changed_at, default_formation_changed_by, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\', ?, ?, ?, ?, ?, ?)');
                $create->execute([$championshipId, $name, $short, $slug, $abbreviation, 'Equipe ficticia de demonstracao da Etapa 4.', $city, $state, $primary, $secondary, $formationId, $now, $adminId, $adminId, $now, $now]);
                $teamId = (int) $pdo->lastInsertId();
            }
            $userId = $index < 5 ? $trainerId : $managerId;
            $assignment = $pdo->prepare("SELECT id FROM team_user_assignments WHERE team_id = ? AND user_id = ? AND assignment_type = 'head_coach' AND status = 'active' LIMIT 1");
            $assignment->execute([$teamId, $userId]);
            if (!$assignment->fetchColumn()) {
                $insertAssignment = $pdo->prepare("INSERT INTO team_user_assignments (team_id, user_id, assignment_type, status, starts_at, created_by, created_at, updated_at) VALUES (?, ?, 'head_coach', 'active', ?, ?, ?, ?)");
                $insertAssignment->execute([$teamId, $userId, date('Y-m-d'), $adminId, $now, $now]);
            }
            self::staff($pdo, $teamId, $roles['head_coach'], 'Treinador ' . $short, 'Coach ' . $short, $userId, $now);
            self::staff($pdo, $teamId, $roles['physical_trainer'], 'Preparador fisico ' . $short, 'Fisico ' . $short, null, $now);
        }
    }

    private static function staff(PDO $pdo, int $teamId, int $roleId, string $fullName, string $displayName, ?int $userId, string $now): void
    {
        $find = $pdo->prepare('SELECT id FROM team_staff WHERE team_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute([$teamId, $fullName]);
        if ($find->fetchColumn()) return;
        $insert = $pdo->prepare("INSERT INTO team_staff (team_id, staff_role_id, user_id, full_name, display_name, status, starts_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)");
        $insert->execute([$teamId, $roleId, $userId, $fullName, $displayName, date('Y-m-d'), $now, $now]);
    }

    private static function idMap(PDO $pdo, string $table, string $keyColumn): array
    {
        $rows = $pdo->query('SELECT id, `' . $keyColumn . '` AS item_key FROM ' . $table)->fetchAll();
        $map = [];
        foreach ($rows as $row) $map[(string) $row['item_key']] = (int) $row['id'];
        return $map;
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $id = (int) $statement->fetchColumn();
        if (!$id) throw new \RuntimeException('Usuario necessario ao seed nao encontrado: ' . $email);
        return $id;
    }
}
