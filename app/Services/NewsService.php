<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\NewsRepository;

final class NewsService
{
    public function __construct(private readonly NewsRepository $news, private readonly NewsImageService $images, private readonly StorageService $storage, private readonly AuditService $audit)
    {
    }

    public function save(array $user, array $input, ?array $file, ?int $id = null): array
    {
        $record = $id ? $this->news->find($id) : null;
        $errors = [];
        $title = trim((string) ($input['title'] ?? '')); $slug = Slugger::make((string) ($input['slug'] ?? '') ?: $title); $summary = trim((string) ($input['summary'] ?? '')); $content = trim((string) ($input['content'] ?? ''));
        $championshipId = (int) ($input['championship_id'] ?? 0); $status = (string) ($input['status'] ?? 'draft'); $publishedAt = $this->dateValue($input['published_at'] ?? null);
        if ($championshipId <= 0) $errors[] = 'Selecione um campeonato.';
        if (mb_strlen($title) < 3 || mb_strlen($title) > 255) $errors[] = 'O titulo deve ter entre 3 e 255 caracteres.';
        if ($slug === '') $errors[] = 'Informe um slug valido.';
        if (mb_strlen($summary) > 600) $errors[] = 'O resumo deve ter no maximo 600 caracteres.';
        if ($content === '') $errors[] = 'O conteudo e obrigatorio.';
        if (!in_array($status, NewsRepository::STATUSES, true)) $errors[] = 'Status de noticia invalido.';
        if ($this->news->slugExists($championshipId, $slug, $id)) $errors[] = 'Ja existe noticia com este slug no campeonato.';
        if ($status === 'scheduled' && (!$publishedAt || strtotime($publishedAt) <= time())) $errors[] = 'Agendamento deve estar no futuro.';
        if ($status === 'published' && $publishedAt && strtotime($publishedAt) > time()) $errors[] = 'Publicacao futura deve usar o status agendado.';
        if ($errors) return ['ok' => false, 'errors' => $errors, 'record' => array_merge($input, ['slug' => $slug])];
        $coverPath = $record['cover_image_path'] ?? null; $newCover = null;
        try {
            if ($file && (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) { $newCover = $this->images->store($file); $coverPath = $newCover['path']; }
            if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');
            $now = date('Y-m-d H:i:s'); $data = ['championship_id' => $championshipId, 'author_id' => $record['author_id'] ?? (int) $user['id'], 'related_team_id' => $this->nullableInt($input['related_team_id'] ?? null), 'related_match_id' => $this->nullableInt($input['related_match_id'] ?? null), 'title' => $title, 'slug' => $slug, 'summary' => $summary, 'content' => $content, 'cover_image_path' => $coverPath, 'status' => $status, 'featured' => !empty($input['featured']) ? 1 : 0, 'published_at' => $publishedAt, 'created_at' => $record['created_at'] ?? $now, 'updated_at' => $now];
            if ($id) { $this->news->update($id, $data); $savedId = $id; } else { $savedId = $this->news->create($data); }
            if ($newCover && !empty($record['cover_image_path'])) $this->storage->delete((string) $record['cover_image_path']);
            $this->audit->record($id ? 'news.updated' : 'news.created', (int) $user['id'], 'news_article', $savedId, ['championship_id' => $championshipId, 'status' => $status]);
            return ['ok' => true, 'id' => $savedId, 'errors' => []];
        } catch (\Throwable $exception) {
            if ($newCover) $this->storage->delete($newCover['path']);
            $duplicate = $exception instanceof \PDOException && is_array($exception->errorInfo) && (int) ($exception->errorInfo[1] ?? 0) === 1062;
            if ($duplicate) $errors[] = 'Ja existe noticia com este slug no campeonato.'; else $errors[] = $exception->getMessage();
            return ['ok' => false, 'errors' => $errors, 'record' => array_merge($input, ['slug' => $slug])];
        }
    }

    public function publish(int $id, int $userId): void { $this->news->setStatus($id, 'published', date('Y-m-d H:i:s')); $this->audit->record('news.published', $userId, 'news_article', $id); }
    public function unpublish(int $id, int $userId): void { $this->news->setStatus($id, 'unpublished', date('Y-m-d H:i:s')); $this->audit->record('news.unpublished', $userId, 'news_article', $id); }
    public function delete(int $id, int $userId): void { $this->news->softDelete($id); $this->audit->record('news.deleted', $userId, 'news_article', $id); }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string) $value); if ($value === '') return null; $timestamp = strtotime(str_replace('T', ' ', $value)); return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
    private function nullableInt(mixed $value): ?int { return ((int) $value) > 0 ? (int) $value : null; }
}
