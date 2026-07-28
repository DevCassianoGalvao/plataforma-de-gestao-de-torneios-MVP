<?php
declare(strict_types=1);
namespace App\Validators;

use InvalidArgumentException;

final class EntityValidator
{
    private const ID_FIELDS = ['organization_id','project_id','tournament_id','team_id','person_id','match_id','stage_id','group_id','round_id','venue_id','home_team_id','away_team_id','from_team_id','to_team_id'];
    private const INTEGER_FIELDS = ['season','stage_order','minute','home_score','away_score','home_penalties','away_penalties','match_count','matches_total','matches_served','light_enabled','dark_enabled'];
    private const STATUS_FIELDS = ['status','state','visibility','registration_type','person_type','role','stage_key','event_type','period','record_type'];

    public static function validate(string $entity, array $input, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = trim((string) $input[$field]);
            if ($value === '') {
                continue;
            }
            if (in_array($field, self::ID_FIELDS, true)) {
                if (filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) === false) {
                    throw new InvalidArgumentException("{$field} inválido.");
                }
                $data[$field] = (int) $value;
                continue;
            }
            if (in_array($field, self::INTEGER_FIELDS, true)) {
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    throw new InvalidArgumentException("{$field} deve ser numérico.");
                }
                $data[$field] = (int) $value;
                continue;
            }
            if ($field === 'slug' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
                throw new InvalidArgumentException('Slug deve usar minúsculas, números e hífen.');
            }
            if (str_ends_with($field, '_color') && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                throw new InvalidArgumentException("{$field} deve ser uma cor hexadecimal.");
            }
            if (in_array($field, ['birth_date'], true) && !self::validDate($value, 'Y-m-d')) {
                throw new InvalidArgumentException("{$field} inválida.");
            }
            if (in_array($field, ['scheduled_at','published_at'], true) && !self::validDateTime($value)) {
                throw new InvalidArgumentException("{$field} deve usar data e hora válidas.");
            }
            if ($field === 'settings_json' && json_decode($value, true) === null) {
                throw new InvalidArgumentException('Configurações devem usar JSON válido.');
            }
            if (in_array($field, self::STATUS_FIELDS, true) && !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $value)) {
                throw new InvalidArgumentException("{$field} inválido.");
            }
            if (mb_strlen($value) > 10000) {
                throw new InvalidArgumentException("{$field} excede o tamanho permitido.");
            }
            $data[$field] = $value;
        }
        if ($data === []) {
            throw new InvalidArgumentException('Preencha ao menos um campo válido.');
        }
        return $data;
    }

    private static function validDate(string $value, string $format): bool
    {
        $date = \DateTimeImmutable::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }

    private static function validDateTime(string $value): bool
    {
        return (bool) \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value)
            || (bool) \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value);
    }
}
