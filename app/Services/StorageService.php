<?php
declare(strict_types=1);

namespace App\Services;

use finfo;

final class StorageService
{
    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?: dirname(__DIR__, 2) . '/storage/private';
    }

    public function store(array $file, string $directory, array $allowedMimeTypes, int $maxBytes = 5242880): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Nenhum arquivo valido foi enviado.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new \RuntimeException('O arquivo excede o tamanho permitido.');
        }
        $temporary = (string) ($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_file($temporary) || !is_readable($temporary)) {
            throw new \RuntimeException('Arquivo de upload invalido.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
        if (!is_string($mime) || !in_array($mime, $allowedMimeTypes, true)) {
            throw new \RuntimeException('Tipo de arquivo nao permitido.');
        }
        $extension = self::extensionForMime($mime);
        $relativeDirectory = trim($directory, '/\\');
        $absoluteDirectory = $this->root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0750, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException('Nao foi possivel preparar o armazenamento.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;
        if (is_uploaded_file($temporary)) {
            $moved = move_uploaded_file($temporary, $absolutePath);
        } else {
            $moved = copy($temporary, $absolutePath);
        }
        if (!$moved) {
            throw new \RuntimeException('Nao foi possivel armazenar o arquivo.');
        }
        return ['path' => str_replace('\\', '/', $relativeDirectory . '/' . $filename), 'mime' => $mime, 'size' => $size, 'original_name' => basename((string) ($file['name'] ?? 'arquivo'))];
    }

    public function read(string $relativePath): ?array
    {
        $path = $this->absolutePath($relativePath);
        if (!$path || !is_file($path) || !is_readable($path)) {
            return null;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        return ['body' => (string) file_get_contents($path), 'mime' => $mime, 'name' => basename($path)];
    }

    public function delete(string $relativePath): void
    {
        $path = $this->absolutePath($relativePath);
        if ($path && is_file($path)) unlink($path);
    }

    private function absolutePath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');
        $root = realpath($this->root);
        $path = realpath($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $path;
    }

    private static function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            'application/pdf' => 'pdf',
            default => throw new \RuntimeException('Extensao de arquivo nao permitida.'),
        };
    }
}
