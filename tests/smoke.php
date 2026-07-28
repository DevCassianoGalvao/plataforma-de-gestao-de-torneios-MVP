<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
try {
    $html=(new App\Controllers\PublicController())->tournament(['slug'=>'copa-brasil-de-talentos-2026']);
    echo 'PUBLIC_OK '.(str_contains($html,'Copa Brasil de Talentos')?'name':'missing').PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL.$e->getTraceAsString().PHP_EOL);
    exit(1);
}
