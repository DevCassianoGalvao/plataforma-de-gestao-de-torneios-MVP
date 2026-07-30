<?php
declare(strict_types=1);

namespace App\Core;

final class DeploymentIssue
{
    public static function requiresDatabaseUpdate(\Throwable $exception): bool
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if (!$current instanceof \PDOException) {
                continue;
            }

            $sqlState = (string) $current->getCode();
            $driverCode = (int) ($current->errorInfo[1] ?? 0);
            if (in_array($sqlState, ['42S02', '42S22'], true) || in_array($driverCode, [1054, 1146], true)) {
                return true;
            }
        }

        return false;
    }
}
