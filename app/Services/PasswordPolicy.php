<?php
declare(strict_types=1);

namespace App\Services;

final class PasswordPolicy
{
    public static function validate(string $password, string $confirmation = ''): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um numero.';
        }
        if ($confirmation !== '' && $password !== $confirmation) {
            $errors[] = 'A confirmacao da senha nao confere.';
        }
        return $errors;
    }
}
