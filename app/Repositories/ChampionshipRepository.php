<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ChampionshipRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForUser(int $userId, bool $administrator, array $filters = []): array
    {
        $conditions = ['c.deleted_at IS NULL'];
        $params = [];
        $join = '';
        if (!$administrator) {
            $join = ' INNER JOIN championship_user_assignments cua ON cua.championship_id = c.id AND cua.user_id = ? AND cua.assignment_type = \'organizer\'';
            $params[] = $userId;
        }
        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(c.name LIKE ? OR c.short_name LIKE ? OR c.slug LIKE ?)';
            $term = '%' . trim((string) $filters['search']) . '%';
            array_push($params, $term, $term, $term);
        }
        foreach (['status' => 'c.status', 'season_id' => 'c.season_id', 'category_id' => 'c.category_id'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $conditions[] = $column . ' = ?';
                $params[] = $filters[$key];
            }
        }
        $sql = 'SELECT c.*, s.name AS season_name, cat.name AS category_name FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id' . $join . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY c.starts_at IS NULL, c.starts_at, c.name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId, bool $administrator): ?array
    {
        $join = $administrator ? '' : ' INNER JOIN championship_user_assignments cua ON cua.championship_id = c.id AND cua.user_id = ? AND cua.assignment_type = \'organizer\'';
        $params = $administrator ? [$id] : [$userId, $id];
        $statement = $this->pdo->prepare('SELECT c.*, s.name AS season_name, s.year AS season_year, cat.name AS category_name, cat.slug AS category_slug, cat.minimum_age, cat.maximum_age, cat.gender_rule FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id' . $join . ' WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1');
        $statement->execute($params);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findForUserBySlug(string $slug, int $userId, bool $administrator): ?array
    {
        $join = $administrator ? '' : ' INNER JOIN championship_user_assignments cua ON cua.championship_id = c.id AND cua.user_id = ? AND cua.assignment_type = \'organizer\'';
        $params = $administrator ? [$slug] : [$userId, $slug];
        $statement = $this->pdo->prepare('SELECT c.*, s.name AS season_name, s.year AS season_year, cat.name AS category_name, cat.slug AS category_slug, cat.minimum_age, cat.maximum_age, cat.gender_rule FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id' . $join . ' WHERE c.slug = ? AND c.deleted_at IS NULL LIMIT 1');
        $statement->execute($params);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $statement = $this->pdo->prepare('INSERT INTO championships (name, short_name, slug, description, season_id, category_id, starts_at, ends_at, registration_starts_at, registration_ends_at, status, visibility, default_theme, primary_color, secondary_color, accent_color, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['name'], $data['short_name'], $data['slug'], $data['description'], $data['season_id'], $data['category_id'], $data['starts_at'], $data['ends_at'], $data['registration_starts_at'], $data['registration_ends_at'], $data['status'], $data['visibility'], $data['default_theme'], $data['primary_color'], $data['secondary_color'], $data['accent_color'], $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateGeneral(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE championships SET name = ?, short_name = ?, slug = ?, description = ?, season_id = ?, category_id = ?, starts_at = ?, ends_at = ?, registration_starts_at = ?, registration_ends_at = ?, visibility = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['name'], $data['short_name'], $data['slug'], $data['description'], $data['season_id'], $data['category_id'], $data['starts_at'], $data['ends_at'], $data['registration_starts_at'], $data['registration_ends_at'], $data['visibility'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateIdentity(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE championships SET default_theme = ?, primary_color = ?, secondary_color = ?, accent_color = ?, logo_path = ?, logo_light_path = ?, logo_dark_path = ?, banner_path = ?, favicon_path = ?, social_image_path = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['default_theme'], $data['primary_color'], $data['secondary_color'], $data['accent_color'], $data['logo_path'], $data['logo_light_path'], $data['logo_dark_path'], $data['banner_path'], $data['favicon_path'], $data['social_image_path'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE championships SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    public function assignments(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT cua.*, u.name, u.email FROM championship_user_assignments cua INNER JOIN users u ON u.id = cua.user_id WHERE cua.championship_id = ? ORDER BY u.name');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function assign(int $championshipId, int $userId, string $type, int $createdBy): void
    {
        $statement = $this->pdo->prepare('INSERT INTO championship_user_assignments (championship_id, user_id, assignment_type, created_at, created_by) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([$championshipId, $userId, $type, date('Y-m-d H:i:s'), $createdBy]);
    }

    public function unassign(int $championshipId, int $userId, string $type): void
    {
        $statement = $this->pdo->prepare('DELETE FROM championship_user_assignments WHERE championship_id = ? AND user_id = ? AND assignment_type = ?');
        $statement->execute([$championshipId, $userId, $type]);
    }
}
