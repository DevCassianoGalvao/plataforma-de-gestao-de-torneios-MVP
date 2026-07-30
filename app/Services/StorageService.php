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
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage($uploadError));
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
        $relativeDirectory = self::safeRelativePath($directory, true);
        $absoluteDirectory = $this->root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);
        $this->prepareDirectory($absoluteDirectory);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;
        if (is_uploaded_file($temporary)) {
            $moved = move_uploaded_file($temporary, $absolutePath);
        } else {
            $moved = copy($temporary, $absolutePath);
        }
        if (!$moved) {
            throw new \RuntimeException('Nao foi possivel armazenar o arquivo. Verifique a permissao de escrita da pasta storage/private no servidor.');
        }
        @chmod($absolutePath, 0640);
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

    public function storeContents(string $contents, string $directory, string $extension, string $mime): array
    {
        if ($contents === '' || !preg_match('/^[a-z0-9]{1,8}$/i', $extension)) {
            throw new \RuntimeException('Conteudo ou extensao de arquivo invalida.');
        }
        $relativeDirectory = self::safeRelativePath($directory, true);
        $absoluteDirectory = $this->root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);
        $this->prepareDirectory($absoluteDirectory);
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($absolutePath, $contents, LOCK_EX) === false) {
            throw new \RuntimeException('Nao foi possivel armazenar o arquivo gerado.');
        }
        @chmod($absolutePath, 0640);
        return ['path' => str_replace('\\', '/', $relativeDirectory . '/' . $filename), 'mime' => $mime, 'size' => strlen($contents), 'original_name' => $filename];
    }

    public function delete(string $relativePath): void
    {
        $path = $this->absolutePath($relativePath);
        if ($path && is_file($path)) unlink($path);
    }

    private function absolutePath(string $relativePath): ?string
    {
        try {
            $relativePath = self::safeRelativePath($relativePath);
        } catch (\Throwable) {
            return null;
        }
        if ($relativePath === '') {
            return null;
        }
        $root = realpath($this->root);
        $path = realpath($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $path;
    }

    private static function safeRelativePath(string $path, bool $directory = false): string
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, "\0") || preg_match('#^[A-Za-z]:#', $path) === 1 || str_starts_with($path, '/')) {
            throw new \RuntimeException('Caminho de armazenamento invalido.');
        }
        $path = trim($path, '/');
        $parts = preg_split('#[/\\\\]+#', $path) ?: [];
        if (in_array('..', $parts, true) || in_array('', $parts, true)) {
            throw new \RuntimeException('Caminho de armazenamento invalido.');
        }
        if (!$directory && count($parts) < 1) {
            throw new \RuntimeException('Arquivo de armazenamento invalido.');
        }
        return implode('/', $parts);
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

    private function prepareDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('Nao foi possivel preparar o armazenamento privado.');
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException('O armazenamento privado nao tem permissao de escrita. Ajuste as permissoes de storage/private no servidor.');
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite permitido pelo servidor.',
            UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_FILE => 'Selecione um arquivo para enviar.',
            UPLOAD_ERR_NO_TMP_DIR => 'O servidor nao possui uma pasta temporaria para uploads.',
            UPLOAD_ERR_CANT_WRITE => 'O servidor nao conseguiu gravar o arquivo enviado.',
            UPLOAD_ERR_EXTENSION => 'O envio foi bloqueado por uma extensao do PHP.',
            default => 'Nao foi possivel receber o arquivo enviado.',
        };
    }
}
