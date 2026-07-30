<?php
declare(strict_types=1);

namespace App\Services;

final class NewsImageService
{
    public function __construct(private readonly StorageService $storage)
    {
    }

    public function store(array $file, string $directory = 'news/covers'): array
    {
        return $this->storage->storeOptimizedImage($file, $directory, ['max_width' => 1600, 'max_height' => 1000]);
    }
}
