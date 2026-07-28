<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$read=static function(string $path)use($root):string{$value=file_get_contents($root.DIRECTORY_SEPARATOR.$path);if($value===false)throw new RuntimeException('Unable to read '.$path);return $value;};
$assert=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('UI foundation failed: '.$message);};
foreach(['tokens.css','themes.css','layout.css','components.css','foundation.css']as$file)$assert(is_file($root.'/public/assets/css/'.$file),'missing stylesheet '.$file);
$tokens=$read('public/assets/css/tokens.css');foreach(['--font-display','--font-body','--primary','--secondary','--accent','--bg','--card','--radius-xl','--space-6']as$token)$assert(str_contains($tokens,$token),'missing token '.$token);
$themes=$read('public/assets/css/themes.css');$assert(str_contains($themes,'[data-theme=dark]'),'dark theme absent');
$foundation=$read('public/assets/css/foundation.css');$assert(str_contains($foundation,':focus-visible'),'visible focus absent');$assert(str_contains($foundation,'prefers-reduced-motion'),'reduced-motion support absent');
$layout=$read('public/assets/css/layout.css');$assert(str_contains($layout,'@media (max-width:800px)'),'responsive sidebar rule absent');$assert(str_contains($layout,'.sidebar.is-open'),'mobile drawer rule absent');$assert(str_contains($layout,'--champ-secondary'),'championship component scope absent');
$components=$read('public/assets/css/components.css');foreach(['.button','.badge','.status','.score','.table-wrap','.tabs','.modal','.drawer','.skeleton']as$component)$assert(str_contains($components,$component),'missing component '.$component);
$base=$read('app/Views/layouts/base.php');foreach(['tokens.css','themes.css','layout.css','components.css','foundation.css','skip-link','data-theme']as$fragment)$assert(str_contains($base,$fragment),'base layout missing '.$fragment);
$portal=$read('app/Views/public/portal.php');foreach(['ThemeService::allowed','--champ-primary','--champ-secondary','--champ-accent']as$fragment)$assert(str_contains($portal,$fragment),'championship theme missing '.$fragment);
$script=$read('public/assets/js/app.js');$assert(str_contains($script,'data-theme-toggle'),'theme control absent');$assert(str_contains($script,'data-drawer-toggle'),'drawer controller absent');$assert(str_contains($script,'data-drawer-close'),'drawer close controller absent');$assert(str_contains($script,"event.key==='Escape'"),'drawer keyboard close absent');
echo "UI_FOUNDATION_E2E_OK\n";
