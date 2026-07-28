<?php
declare(strict_types=1);

namespace App\Services;

final class DateRules
{
    public static function validate(array $dates): array
    {
        $errors = [];
        $values = [];
        foreach ($dates as $key => $value) {
            if ($value === null || $value === '') {
                $values[$key] = null;
                continue;
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $errors[] = 'A data de ' . $key . ' e invalida.';
                $values[$key] = null;
                continue;
            }
            $values[$key] = $date;
        }
        $pairs = [
            ['starts_at', 'ends_at', 'A data final deve ser posterior ou igual a inicial.'],
            ['registration_starts_at', 'registration_ends_at', 'O periodo de inscricoes e invalido.'],
        ];
        foreach ($pairs as [$start, $end, $message]) {
            if (($values[$start] ?? null) && ($values[$end] ?? null) && $values[$end] < $values[$start]) {
                $errors[] = $message;
            }
        }
        return $errors;
    }
}
