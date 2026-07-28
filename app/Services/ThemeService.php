<?php
declare(strict_types=1);
namespace App\Services;
final class ThemeService { public static function validColor(string $value): bool { return (bool)preg_match('/^#[0-9a-fA-F]{6}$/',$value); } public static function allowed(array $data): array { $out=[];foreach(['primary_color','secondary_color','accent_color'] as $key){if(isset($data[$key])&&self::validColor($data[$key]))$out[$key]=strtoupper($data[$key]);}foreach(['light_enabled','dark_enabled'] as $key)if(isset($data[$key]))$out[$key]=(int)(bool)$data[$key];return $out; } }
