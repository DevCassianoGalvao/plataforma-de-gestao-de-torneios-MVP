<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function show(Request $request, array $params = []): Response
    {
        try {
            $row = Database::connection()->query('SELECT status, checked_at FROM foundation_health WHERE id = 1')->fetch();
            if (!$row) {
                return Response::json(['status' => 'degraded', 'database' => 'foundation_not_migrated'], 503);
            }
            return Response::json(['status' => 'ok', 'database' => 'ok', 'checked_at' => $row['checked_at']]);
        } catch (\Throwable) {
            return Response::json(['status' => 'degraded', 'database' => 'unavailable'], 503);
        }
    }
}
