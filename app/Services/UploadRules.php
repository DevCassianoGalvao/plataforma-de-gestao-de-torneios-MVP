<?php
declare(strict_types=1);

namespace App\Services;

use finfo;

final class UploadRules
{
    public static function validate(array $file, array $mimeExtensions, int $maxBytes): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException('Nenhum arquivo valido foi enviado.');
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) throw new \RuntimeException('O arquivo excede o tamanho permitido.');
        $temporary = (string) ($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_file($temporary) || !is_readable($temporary)) throw new \RuntimeException('Arquivo de upload invalido.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
        if (!is_string($mime) || !isset($mimeExtensions[$mime])) throw new \RuntimeException('Tipo de arquivo nao permitido.');
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $mimeExtensions[$mime], true)) throw new \RuntimeException('A extensao nao corresponde ao tipo real do arquivo.');
        return ['mime' => $mime, 'size' => $size, 'extension' => $extension];
    }
}
