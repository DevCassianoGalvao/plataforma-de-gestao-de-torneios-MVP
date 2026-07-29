<?php
declare(strict_types=1);

namespace App\Services;

final class NewsImageService
{
    public function __construct(private readonly StorageService $storage)
    {
    }

    public function store(array $file): array
    {
        $validated = UploadRules::validate($file, ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']], 5242880);
        $dimensions = @getimagesize((string) $file['tmp_name']);
        if ($dimensions === false || (int) $dimensions[0] < 1 || (int) $dimensions[1] < 1 || (int) $dimensions[0] > 6000 || (int) $dimensions[1] > 6000) throw new \RuntimeException('A imagem possui dimensoes invalidas.');
        $source = @imagecreatefromstring((string) file_get_contents((string) $file['tmp_name']));
        if (!$source) throw new \RuntimeException('Nao foi possivel processar a imagem.');
        $width = (int) $dimensions[0]; $height = (int) $dimensions[1]; $scale = min(1, 1600 / $width, 1000 / $height); $newWidth = max(1, (int) round($width * $scale)); $newHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($newWidth, $newHeight); imagealphablending($target, true); imagesavealpha($target, false); $white = imagecolorallocate($target, 255, 255, 255); imagefill($target, 0, 0, $white); imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $temporary = tempnam(sys_get_temp_dir(), 'news-cover-');
        if ($temporary === false || !imagejpeg($target, $temporary, 82)) { imagedestroy($source); imagedestroy($target); if ($temporary) @unlink($temporary); throw new \RuntimeException('Nao foi possivel otimizar a imagem.'); }
        imagedestroy($source); imagedestroy($target);
        try {
            return $this->storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'capa.jpg'], 'news/covers', ['image/jpeg'], 5242880);
        } finally {
            @unlink($temporary);
        }
    }
}
