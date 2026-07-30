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

    /**
     * Validates, orients, scales and converts a user supplied image to WebP before storage.
     * Original files are deliberately not retained to keep private storage and transfer costs low.
     */
    public function storeOptimizedImage(array $file, string $directory, array $options = []): array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            throw new \RuntimeException('O servidor precisa da extensao GD com suporte a WebP para receber imagens.');
        }

        $maxBytes = (int) ($options['max_bytes'] ?? self::imageUploadLimit());
        $maxWidth = max(1, (int) ($options['max_width'] ?? 1920));
        $maxHeight = max(1, (int) ($options['max_height'] ?? 1440));
        $maxPixels = max(1, (int) ($options['max_pixels'] ?? 12000000));
        $quality = min(100, max(1, (int) ($options['quality'] ?? 82)));
        $validated = UploadRules::validate($file, ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']], $maxBytes);
        $temporary = (string) $file['tmp_name'];
        $dimensions = @getimagesize($temporary);
        if ($dimensions === false || (int) $dimensions[0] < 1 || (int) $dimensions[1] < 1 || ((int) $dimensions[0] * (int) $dimensions[1]) > $maxPixels) {
            throw new \RuntimeException('A imagem possui dimensoes invalidas ou excede o limite de 12 megapixels.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($temporary));
        if ($source === false) {
            throw new \RuntimeException('Nao foi possivel processar a imagem enviada.');
        }

        $optimized = null;
        $output = tempnam(sys_get_temp_dir(), 'torneio-image-');
        if ($output === false) {
            imagedestroy($source);
            throw new \RuntimeException('Nao foi possivel preparar a otimizacao da imagem.');
        }

        try {
            $source = $this->orientImage($source, $temporary, $validated['mime']);
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, $maxWidth / $width, $maxHeight / $height);
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $optimized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($optimized, false);
            imagesavealpha($optimized, true);
            $transparent = imagecolorallocatealpha($optimized, 0, 0, 0, 127);
            imagefill($optimized, 0, 0, $transparent);
            if (!imagecopyresampled($optimized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height) || !imagewebp($optimized, $output, $quality)) {
                throw new \RuntimeException('Nao foi possivel otimizar a imagem enviada.');
            }
            clearstatcache(true, $output);
            $stored = $this->store(['error' => UPLOAD_ERR_OK, 'size' => (int) filesize($output), 'tmp_name' => $output, 'name' => 'imagem.webp'], $directory, ['image/webp'], $maxBytes);
            $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo(basename((string) ($file['name'] ?? 'imagem')), PATHINFO_FILENAME)) ?: 'imagem';
            $baseName = trim($baseName, '.-') ?: 'imagem';
            $stored['original_name'] = $baseName . '.webp';
            return $stored;
        } finally {
            imagedestroy($source);
            if ($optimized !== null) imagedestroy($optimized);
            @unlink($output);
        }
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

    private static function imageUploadLimit(): int
    {
        $configured = filter_var(getenv('IMAGE_UPLOAD_MAX_BYTES') ?: 12582912, FILTER_VALIDATE_INT);
        return is_int($configured) && $configured >= 1048576 && $configured <= 20971520 ? $configured : 12582912;
    }

    private function orientImage($image, string $path, string $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path, 'IFD0');
        $orientation = (int) (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1);
        if ($orientation === 2 && function_exists('imageflip')) imageflip($image, IMG_FLIP_HORIZONTAL);
        if ($orientation === 3) return $this->rotateImage($image, 180);
        if ($orientation === 4 && function_exists('imageflip')) imageflip($image, IMG_FLIP_VERTICAL);
        if ($orientation === 5 && function_exists('imageflip')) { imageflip($image, IMG_FLIP_VERTICAL); return $this->rotateImage($image, -90); }
        if ($orientation === 6) return $this->rotateImage($image, -90);
        if ($orientation === 7 && function_exists('imageflip')) { imageflip($image, IMG_FLIP_HORIZONTAL); return $this->rotateImage($image, -90); }
        if ($orientation === 8) return $this->rotateImage($image, 90);
        return $image;
    }

    private function rotateImage($image, int $degrees)
    {
        $rotated = imagerotate($image, $degrees, 0);
        if ($rotated === false) {
            throw new \RuntimeException('Nao foi possivel corrigir a orientacao da imagem.');
        }
        imagedestroy($image);
        return $rotated;
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
