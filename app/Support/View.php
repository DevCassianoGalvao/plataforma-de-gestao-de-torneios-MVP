<?php
declare(strict_types=1);
namespace App\Support;

final class View {
    public static function render(string $name, array $data = [], string $layout = 'layouts/base'): string {
        extract($data); ob_start(); require __DIR__.'/../Views/'.$name.'.php'; $content = (string) ob_get_clean();
        if ($layout === '') return $content;
        ob_start(); require __DIR__.'/../Views/'.$layout.'.php'; $html=(string)ob_get_clean();
        $base=rtrim((string)Env::get('APP_BASE_PATH',''),'/');
        if ($base!=='') $html=str_replace(['href="/','action="/','src="/'],['href="'.$base.'/','action="'.$base.'/','src="'.$base.'/'],$html);
        return $html;
    }
    public static function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}
