<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

final class AthleteRules
{
    public const STATUSES = ['draft', 'active', 'inactive', 'blocked', 'transferred', 'archived'];
    public const FEET = ['left', 'right', 'both'];
    public const GENDERS = ['male', 'female', 'other'];
    public const GUARDIAN_STATUSES = ['active', 'inactive', 'revoked'];
    public const AUTHORIZATION_STATUSES = ['pending', 'authorized', 'rejected', 'revoked'];
    public const DOCUMENT_STATUSES = ['pending', 'approved', 'rejected', 'expired', 'replaced', 'archived'];

    public static function age(string $birthDate, ?DateTimeImmutable $today = null): int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        if (!$date || $date->format('Y-m-d') !== $birthDate) throw new \InvalidArgumentException('Data de nascimento invalida.');
        $today ??= new DateTimeImmutable('today');
        if ($date > $today) throw new \InvalidArgumentException('Data de nascimento nao pode estar no futuro.');
        return $date->diff($today)->y;
    }

    public static function isMinor(string $birthDate, ?DateTimeImmutable $today = null): bool
    {
        return self::age($birthDate, $today) < 18;
    }

    public static function validate(array $data, ?array $team = null): array
    {
        $errors = [];
        if (mb_strlen($data['full_name'] ?? '') < 3) $errors[] = 'Nome completo e obrigatorio.';
        if (mb_strlen($data['sporting_name'] ?? '') > 120) $errors[] = 'Nome esportivo muito longo.';
        try {
            $age = self::age((string) ($data['birth_date'] ?? ''));
            if ($team) {
                $minimum = $team['minimum_age'];
                $maximum = $team['maximum_age'];
                if ($minimum !== null && $age < (int) $minimum && !self::adultCategoryAllowsMinor($age, (int) $minimum, $team)) $errors[] = 'Atleta abaixo da idade minima da categoria.';
                if ($maximum !== null && $age > (int) $maximum) $errors[] = 'Atleta acima da idade maxima da categoria.';
                $rule = strtolower(trim((string) ($team['gender_rule'] ?? '')));
                $gender = strtolower(trim((string) ($data['gender'] ?? '')));
                if (in_array($rule, ['male', 'masculino', 'female', 'feminino'], true) && $gender !== self::genderKey($rule)) $errors[] = 'Genero incompativel com a categoria.';
            }
        } catch (\InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }
        if (!in_array((string) ($data['gender'] ?? ''), array_merge([''], self::GENDERS), true)) $errors[] = 'Genero invalido.';
        if ((int) ($data['primary_position_id'] ?? 0) <= 0) $errors[] = 'Posicao principal e obrigatoria.';
        $number = $data['preferred_number'] ?? '';
        if ($number !== '' && ((int) $number < 1 || (int) $number > 99)) $errors[] = 'Numero preferido deve estar entre 1 e 99.';
        if (($data['dominant_foot'] ?? '') !== '' && !in_array($data['dominant_foot'], self::FEET, true)) $errors[] = 'Pe dominante invalido.';
        if (!in_array((string) ($data['status'] ?? ''), self::STATUSES, true)) $errors[] = 'Status de atleta invalido.';
        return $errors;
    }

    public static function validateGuardian(array $data): array
    {
        $errors = [];
        if (mb_strlen($data['full_name'] ?? '') < 3) $errors[] = 'Nome do responsavel e obrigatorio.';
        if (trim((string) ($data['relationship'] ?? '')) === '') $errors[] = 'Parentesco e obrigatorio.';
        if (trim((string) ($data['phone'] ?? '')) === '') $errors[] = 'Telefone do responsavel e obrigatorio.';
        if (trim((string) ($data['document_number'] ?? '')) === '') $errors[] = 'Documento do responsavel e obrigatorio.';
        if (($data['email'] ?? '') !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail do responsavel invalido.';
        if (!in_array((string) ($data['status'] ?? 'active'), self::GUARDIAN_STATUSES, true)) $errors[] = 'Status do responsavel invalido.';
        if (!in_array((string) ($data['authorization_status'] ?? 'pending'), self::AUTHORIZATION_STATUSES, true)) $errors[] = 'Autorizacao invalida.';
        return $errors;
    }

    public static function validateDocumentStatus(string $status): bool
    {
        return in_array($status, self::DOCUMENT_STATUSES, true);
    }

    public static function transition(string $current, string $next): bool
    {
        $allowed = [
            'draft' => ['active', 'blocked', 'archived'],
            'active' => ['inactive', 'blocked', 'transferred', 'archived'],
            'inactive' => ['active', 'blocked', 'transferred', 'archived'],
            'blocked' => ['active', 'inactive', 'archived'],
            'transferred' => ['active', 'archived'],
            'archived' => ['active'],
        ];
        return in_array($next, $allowed[$current] ?? [], true);
    }

    private static function genderKey(string $rule): string
    {
        return in_array($rule, ['male', 'masculino'], true) ? 'male' : 'female';
    }

    private static function adultCategoryAllowsMinor(int $age, int $minimumAge, array $team): bool
    {
        return !empty($team['allow_underage_athletes']) && $age < 18 && $minimumAge >= 18;
    }
}
