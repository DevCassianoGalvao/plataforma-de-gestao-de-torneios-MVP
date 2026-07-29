<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RegistrationRules;
use function Tests\assert_true;

final class RegistrationTest
{
    public static function run(): void
    {
        assert_true(RegistrationRules::transition('draft', 'submitted'), 'Rascunho nao pode ser enviado');
        assert_true(RegistrationRules::transition('submitted', 'under_review'), 'Inscricao enviada nao entrou em analise');
        assert_true(RegistrationRules::transition('under_review', 'pending_correction'), 'Pendencia nao pode ser solicitada');
        assert_true(RegistrationRules::transition('pending_correction', 'submitted'), 'Correcao nao pode ser reenviada');
        assert_true(RegistrationRules::transition('under_review', 'approved'), 'Inscricao valida nao pode ser aprovada');
        assert_true(!RegistrationRules::transition('approved', 'draft'), 'Elenco aprovado voltou a rascunho');
        assert_true(RegistrationRules::validNumber(null) && RegistrationRules::validNumber(99), 'Numero valido rejeitado');
        assert_true(!RegistrationRules::validNumber(0) && !RegistrationRules::validNumber(100), 'Numero invalido aceito');
        $date = new \DateTimeImmutable('2026-07-28');
        assert_true(RegistrationRules::windowOpen(['registration_starts_at' => '2026-01-01', 'registration_ends_at' => '2026-12-31'], $date), 'Periodo aberto rejeitado');
        assert_true(!RegistrationRules::windowOpen(['registration_starts_at' => '2026-01-01', 'registration_ends_at' => '2026-07-27'], $date), 'Periodo fechado aceito');
    }
}
