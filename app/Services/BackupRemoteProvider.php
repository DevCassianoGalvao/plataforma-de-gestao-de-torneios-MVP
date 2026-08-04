<?php
declare(strict_types=1);
namespace App\Services;
interface BackupRemoteProvider { public function upload(string $path,string $name,string $hash): array; public function delete(string $remoteId): bool; public function exists(string $remoteId): bool; public function list(): array; public function testConnection(): array; }
