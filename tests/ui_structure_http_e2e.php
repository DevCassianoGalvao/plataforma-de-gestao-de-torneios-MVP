<?php
declare(strict_types=1);
$base=rtrim((string)(getenv('UI_TEST_URL')?:'http://127.0.0.1:18080/copa-online'),'/');
$html=file_get_contents($base.'/login');
if($html===false||!str_contains($html,'tokens.css')||str_contains($html,'assets/css/app.css'))throw new RuntimeException('Pilha CSS consolidada nao foi carregada.');
if(!str_contains($html,'Bricolage')||!str_contains($html,'Inter'))throw new RuntimeException('Fontes base ausentes.');
echo "ui_structure_http_e2e: OK\n";
