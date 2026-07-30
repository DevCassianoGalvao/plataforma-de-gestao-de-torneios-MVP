<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class ImageUploadTest
{
    public static function run(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'mvp-image-source-');
        if ($source === false) throw new \RuntimeException('Arquivo temporario indisponivel.');
        $image = imagecreatetruecolor(2000, 1000);
        $background = imagecolorallocate($image, 18, 60, 50);
        imagefill($image, 0, 0, $background);
        imagejpeg($image, $source, 96);
        imagedestroy($image);

        $storage = new StorageService();
        $stored = $storage->storeOptimizedImage(['error' => UPLOAD_ERR_OK, 'size' => filesize($source), 'tmp_name' => $source, 'name' => 'foto-grande.jpg'], 'image-test/' . uniqid(), ['max_width' => 800, 'max_height' => 800]);
        $saved = $storage->read($stored['path']);
        $dimensions = $saved ? getimagesizefromstring($saved['body']) : false;
        assert_same('image/webp', $stored['mime'], 'Imagem otimizada nao foi salva em WebP');
        assert_true(str_ends_with($stored['path'], '.webp'), 'Imagem otimizada nao recebeu extensao WebP');
        assert_true($dimensions !== false && (int) $dimensions[0] === 800 && (int) $dimensions[1] === 400, 'Imagem otimizada nao respeitou limite proporcional');
        $storage->delete($stored['path']);
        @unlink($source);
    }
}
