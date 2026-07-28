<?php
declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$login=$read('app/Views/auth/login.php');foreach(['data-password','data-password-toggle','data-loading-form','remember','senha/esqueci']as$needle)$assert(str_contains($login,$needle),'Login UI missing '.$needle);
$dashboard=$read('app/Views/admin/dashboard.php');foreach(['metrics-grid','quick-actions','activity-list','data-drawer','data-theme-toggle']as$needle)$assert(str_contains($dashboard,$needle),'Global dashboard missing '.$needle);
$tournament=$read('app/Views/admin/tournament-operations.php');foreach(['tournament-overview','tournament-hero','overview-grid','Pendências','Suspensões']as$needle)$assert(str_contains($tournament,$needle),'Tournament dashboard missing '.$needle);
$css=$read('public/assets/css/dashboard.css');foreach(['.auth-page','.metrics-grid','.dashboard-grid','.tournament-hero','@media(max-width:800px)']as$needle)$assert(str_contains($css,$needle),'Dashboard CSS missing '.$needle);
$js=$read('public/assets/js/app.js');foreach(['data-password-toggle','data-loading-form']as$needle)$assert(str_contains($js,$needle),'Interaction missing '.$needle);
$controller=$read('app/Controllers/AdminController.php');$assert(str_contains($controller,'entity_type AS entity'),'Dashboard audit query must use persisted entity_type column');
echo "DASHBOARD_UI_E2E_OK\n";
