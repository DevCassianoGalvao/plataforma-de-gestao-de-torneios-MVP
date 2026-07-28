<?php
declare(strict_types=1);

namespace App\Services;

final class TeamRules
{
    public const STATUSES = ['draft', 'active', 'inactive', 'withdrawn', 'archived'];
    public const ASSIGNMENT_TYPES = ['manager', 'head_coach', 'assistant_coach', 'viewer'];
    public const STAFF_STATUSES = ['draft', 'active', 'inactive', 'ended'];

    public static function validate(array $data): array
    {
        $errors = [];
        if (mb_strlen(trim((string) ($data['name'] ?? ''))) < 2) $errors[] = 'Nome da equipe e obrigatorio.';
        if (mb_strlen(trim((string) ($data['short_name'] ?? ''))) < 2) $errors[] = 'Nome curto da equipe e obrigatorio.';
        if (!preg_match('/^[A-Z0-9]{2,8}$/', (string) ($data['abbreviation'] ?? ''))) $errors[] = 'A sigla deve ter de 2 a 8 letras ou numeros.';
        if ((string) ($data['slug'] ?? '') === '') $errors[] = 'Slug da equipe e obrigatorio.';
        if (!in_array((string) ($data['status'] ?? 'draft'), self::STATUSES, true)) $errors[] = 'Status de equipe invalido.';
        foreach (['primary_color', 'secondary_color'] as $color) {
            if (!ColorRules::valid((string) ($data[$color] ?? ''))) $errors[] = 'Use cores no formato #RRGGBB.';
        }
        return array_values(array_unique($errors));
    }

    public static function validateStaff(array $data): array
    {
        $errors = [];
        if (mb_strlen(trim((string) ($data['full_name'] ?? ''))) < 2) $errors[] = 'Nome completo e obrigatorio.';
        if ((int) ($data['staff_role_id'] ?? 0) < 1) $errors[] = 'Escolha uma funcao da comissao.';
        if (($data['email'] ?? '') !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail da comissao invalido.';
        if (!in_array((string) ($data['status'] ?? 'active'), self::STAFF_STATUSES, true)) $errors[] = 'Status da comissao invalido.';
        $errors = array_merge($errors, DateRules::validate(['starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null]));
        return array_values(array_unique($errors));
    }
}
