<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

final class MailService
{
    public static array $testMessages = [];

    public function sendPasswordReset(string $email, string $name, string $url): void
    {
        if (Config::get('MAIL_TRANSPORT', 'log') === 'test') {
            self::$testMessages[] = compact('email', 'name', 'url');
            return;
        }
        Logger::message('Mensagem de recuperacao gerada para ' . hash('sha256', strtolower($email)));
    }

    public static function resetTestMessages(): void
    {
        self::$testMessages = [];
    }
}
