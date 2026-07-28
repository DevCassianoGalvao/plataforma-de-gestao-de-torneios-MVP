<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AthleteRules;
use App\Services\UploadRules;
use DateTimeImmutable;
use function Tests\assert_true;

final class AthleteTest
{
    public static function run(): void
    {
        assert_true(AthleteRules::age('2010-02-28', new DateTimeImmutable('2026-02-27')) === 15, 'Idade antes do aniversario calculada incorretamente');
        assert_true(AthleteRules::age('2010-02-28', new DateTimeImmutable('2026-02-28')) === 16, 'Idade no aniversario calculada incorretamente');
        assert_true(AthleteRules::isMinor('2012-05-01', new DateTimeImmutable('2026-07-28')), 'Menor de idade nao identificado');
        $valid = ['full_name' => 'Atleta Valido', 'sporting_name' => 'Valido', 'birth_date' => '2012-05-01', 'gender' => 'male', 'primary_position_id' => 1, 'preferred_number' => 10, 'dominant_foot' => 'right', 'status' => 'draft'];
        assert_true(AthleteRules::validate($valid, ['minimum_age' => 12, 'maximum_age' => 15, 'gender_rule' => 'male']) === [], 'Atleta valido rejeitado');
        assert_true(AthleteRules::validate(array_merge($valid, ['birth_date' => '2010-05-01']), ['minimum_age' => 12, 'maximum_age' => 15, 'gender_rule' => 'male']) !== [], 'Categoria aceitou idade invalida');
        assert_true(AthleteRules::validateGuardian(['full_name' => 'Responsavel Valido', 'relationship' => 'Mae', 'phone' => '11999990000', 'document_number' => 'DOC-1', 'status' => 'active', 'authorization_status' => 'pending']) === [], 'Responsavel valido rejeitado');
        assert_true(AthleteRules::transition('active', 'blocked'), 'Transicao de atleta ativa para bloqueada falhou');
        assert_true(!AthleteRules::transition('transferred', 'blocked'), 'Transicao invalida de atleta aceita');
        $temporary = tempnam(sys_get_temp_dir(), 'mvp-athlete-unit-');
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        assert_true(UploadRules::validate(['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'foto.png'], ['image/png' => ['png']], 5242880)['mime'] === 'image/png', 'Upload valido rejeitado');
        try {
            UploadRules::validate(['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'foto.pdf'], ['image/png' => ['png']], 5242880);
            throw new \RuntimeException('Extensao incoerente aceita');
        } catch (\RuntimeException) {
        }
        unlink($temporary);
    }
}
