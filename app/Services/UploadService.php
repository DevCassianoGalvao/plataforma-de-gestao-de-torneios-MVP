<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

final class UploadService
{
    private const MAX_BYTES = 5_242_880;
    private const MIME_EXTENSIONS = [
        'application/pdf'=>'pdf',
        'image/jpeg'=>'jpg',
        'image/png'=>'png',
        'image/webp'=>'webp',
    ];

    public function store(array $upload, string $visibility='private'): string
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($upload['tmp_name'])) {
            throw new RuntimeException('Envie um arquivo válido.');
        }
        if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Arquivo deve ter até 5 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            throw new RuntimeException('Formato não permitido. Use PDF, JPG, PNG ou WEBP.');
        }
        $isPublic = $visibility === 'public';
        $targetDir = dirname(__DIR__, 2).($isPublic ? '/public/uploads-public' : '/storage/private');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento.');
        }
        $name = bin2hex(random_bytes(20)).'.'.self::MIME_EXTENSIONS[$mime];
        $target = $targetDir.'/'.$name;
        $moved = is_uploaded_file((string) $upload['tmp_name'])
            ? move_uploaded_file((string) $upload['tmp_name'], $target)
            : rename((string) $upload['tmp_name'], $target);
        if (!$moved) {
            throw new RuntimeException('Não foi possível armazenar o arquivo.');
        }
        return $isPublic ? 'uploads-public/'.$name : 'private/'.$name;
    }
}
