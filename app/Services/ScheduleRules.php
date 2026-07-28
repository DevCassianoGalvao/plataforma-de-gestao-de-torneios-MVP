<?php
declare(strict_types=1);

namespace App\Services;

final class ScheduleRules
{
    public const MATCH_STATUSES = ['draft', 'scheduled', 'confirmed', 'postponed', 'cancelled', 'wo', 'finished', 'homologated'];
    public const PHASE_STATUSES = ['draft', 'published', 'in_progress', 'finished', 'archived'];

    public static function validatePhase(array $data): array
    {
        $errors = [];
        foreach (['name', 'slug'] as $field) if (trim((string) ($data[$field] ?? '')) === '') $errors[] = 'Nome e slug da fase sao obrigatorios.';
        foreach (['group_count', 'teams_per_group', 'qualified_per_group'] as $field) if ((int) ($data[$field] ?? 0) < 1) $errors[] = 'Configuracao de grupos invalida.';
        if ((int) ($data['qualified_per_group'] ?? 0) > (int) ($data['teams_per_group'] ?? 0)) $errors[] = 'Classificados nao podem superar equipes por grupo.';
        return array_values(array_unique($errors));
    }

    public static function validateSchedule(array $data): array
    {
        $errors = [];
        $start = trim((string) ($data['period_start'] ?? ''));
        $end = trim((string) ($data['period_end'] ?? ''));
        $startDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $endDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if (!$startDate || !$endDate || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $start > $end) $errors[] = 'Periodo da tabela invalido.';
        $days = array_filter(array_map('intval', (array) ($data['allowed_days'] ?? [])), static fn (int $day): bool => $day >= 1 && $day <= 7);
        if ($days === []) $errors[] = 'Selecione ao menos um dia permitido.';
        $time = trim((string) ($data['start_time'] ?? ''));
        $parsedTime = \DateTimeImmutable::createFromFormat('!H:i', $time);
        if (!$parsedTime || $parsedTime->format('H:i') !== $time) $errors[] = 'Horario inicial invalido.';
        if ((array) ($data['venue_ids'] ?? []) === []) $errors[] = 'Selecione ao menos um local.';
        return array_values(array_unique($errors));
    }

    public static function canTransitionMatch(string $from, string $to): bool
    {
        $allowed = [
            'draft' => ['scheduled', 'cancelled'],
            'scheduled' => ['confirmed', 'postponed', 'cancelled', 'wo'],
            'confirmed' => ['postponed', 'cancelled', 'wo'],
            'postponed' => ['scheduled', 'confirmed', 'cancelled', 'wo'],
            'cancelled' => [],
            'wo' => [],
            'finished' => ['homologated'],
            'homologated' => [],
        ];
        return in_array($to, $allowed[$from] ?? [], true);
    }
}
