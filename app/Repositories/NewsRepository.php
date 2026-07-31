<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NewsRepository
{
    public const STATUSES = ['draft', 'scheduled', 'published', 'unpublished', 'archived'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function championshipsForUser(int $userId, array $roles, bool $administrator): array
    {
        if ($administrator) {
            return $this->pdo->query('SELECT c.id, c.name, c.slug FROM championships c WHERE c.deleted_at IS NULL ORDER BY c.name')->fetchAll();
        }
        return [];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT n.*, c.name AS championship_name, c.slug AS championship_slug, c.visibility AS championship_visibility, u.name AS author_name FROM news_articles n INNER JOIN championships c ON c.id = n.championship_id INNER JOIN users u ON u.id = n.author_id WHERE n.id = ? AND n.deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function listAdmin(?array $championshipIds, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = $this->adminWhere($championshipIds, $filters);
        $sql = 'SELECT n.*, c.name AS championship_name, c.slug AS championship_slug, u.name AS author_name FROM news_articles n INNER JOIN championships c ON c.id = n.championship_id INNER JOIN users u ON u.id = n.author_id WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(n.published_at, n.updated_at) DESC, n.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countAdmin(?array $championshipIds, array $filters = []): int
    {
        [$where, $params] = $this->adminWhere($championshipIds, $filters);
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM news_articles n WHERE ' . implode(' AND ', $where));
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function slugExists(int $championshipId, string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM news_articles WHERE championship_id = ? AND slug = ? AND deleted_at IS NULL';
        $params = [$championshipId, $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO news_articles (championship_id, author_id, related_team_id, related_match_id, title, slug, summary, content, cover_image_path, status, featured, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['author_id'], $data['related_team_id'], $data['related_match_id'], $data['title'], $data['slug'], $data['summary'], $data['content'], $data['cover_image_path'], $data['status'], $data['featured'], $data['published_at'], $data['created_at'], $data['updated_at']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE news_articles SET championship_id = ?, related_team_id = ?, related_match_id = ?, title = ?, slug = ?, summary = ?, content = ?, cover_image_path = ?, status = ?, featured = ?, published_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['championship_id'], $data['related_team_id'], $data['related_match_id'], $data['title'], $data['slug'], $data['summary'], $data['content'], $data['cover_image_path'], $data['status'], $data['featured'], $data['published_at'], $data['updated_at'], $id]);
    }

    public function setStatus(int $id, string $status, ?string $publishedAt): void
    {
        $statement = $this->pdo->prepare('UPDATE news_articles SET status = ?, published_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, $publishedAt, date('Y-m-d H:i:s'), $id]);
    }

    public function softDelete(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('UPDATE news_articles SET status = \'archived\', deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL')->execute([$now, $now, $id]);
    }

    public function publicChampionship(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT c.*, s.name AS season_name, cat.name AS category_name FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id WHERE c.slug = ? AND c.visibility = \'public\' AND c.status <> \'draft\' AND c.deleted_at IS NULL LIMIT 1');
        $statement->execute([$slug]);
        return $statement->fetch() ?: null;
    }

    public function listPublished(int $championshipId, string $search = '', int $limit = 12, int $offset = 0): array
    {
        [$where, $params] = $this->publicWhere($championshipId, $search);
        $sql = 'SELECT n.*, c.name AS championship_name, c.slug AS championship_slug, u.name AS author_name FROM news_articles n INNER JOIN championships c ON c.id = n.championship_id INNER JOIN users u ON u.id = n.author_id WHERE ' . implode(' AND ', $where) . ' ORDER BY n.featured DESC, n.published_at DESC, n.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countPublished(int $championshipId, string $search = ''): int
    {
        [$where, $params] = $this->publicWhere($championshipId, $search);
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM news_articles n INNER JOIN championships c ON c.id = n.championship_id WHERE ' . implode(' AND ', $where));
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function publicFind(string $championshipSlug, string $newsSlug): ?array
    {
        $statement = $this->pdo->prepare('SELECT n.*, c.name AS championship_name, c.slug AS championship_slug, u.name AS author_name FROM news_articles n INNER JOIN championships c ON c.id = n.championship_id INNER JOIN users u ON u.id = n.author_id WHERE c.slug = ? AND c.visibility = \'public\' AND c.status <> \'draft\' AND c.deleted_at IS NULL AND n.slug = ? AND n.status IN (\'published\', \'scheduled\') AND n.published_at IS NOT NULL AND n.published_at <= ? AND n.deleted_at IS NULL LIMIT 1');
        $statement->execute([$championshipSlug, $newsSlug, date('Y-m-d H:i:s')]);
        return $statement->fetch() ?: null;
    }

    private function adminWhere(?array $championshipIds, array $filters): array
    {
        $where = ['n.deleted_at IS NULL']; $params = [];
        if ($championshipIds !== null) {
            if ($championshipIds === []) return ['0 = 1', []];
            $where[] = 'n.championship_id IN (' . implode(', ', array_fill(0, count($championshipIds), '?')) . ')';
            array_push($params, ...array_map('intval', $championshipIds));
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(n.title LIKE ? OR n.summary LIKE ?)';
            $term = '%' . trim((string) $filters['q']) . '%';
            array_push($params, $term, $term);
        }
        if (($filters['status'] ?? '') !== '' && in_array($filters['status'], self::STATUSES, true)) { $where[] = 'n.status = ?'; $params[] = $filters['status']; }
        if (($filters['championship_id'] ?? '') !== '') { $where[] = 'n.championship_id = ?'; $params[] = (int) $filters['championship_id']; }
        return [$where, $params];
    }

    private function publicWhere(int $championshipId, string $search): array
    {
        $where = ['n.championship_id = ?', 'n.status IN (\'published\', \'scheduled\')', 'n.published_at IS NOT NULL', 'n.published_at <= ?', 'n.deleted_at IS NULL', 'c.visibility = \'public\'', 'c.status <> \'draft\'', 'c.deleted_at IS NULL'];
        $params = [$championshipId, date('Y-m-d H:i:s')];
        if (trim($search) !== '') { $where[] = '(n.title LIKE ? OR n.summary LIKE ?)'; $term = '%' . trim($search) . '%'; array_push($params, $term, $term); }
        return [$where, $params];
    }
}
