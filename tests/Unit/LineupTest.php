<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LineupRules;
use function Tests\assert_true;

final class LineupTest
{
    public static function run(): void
    {
        assert_true(LineupRules::canEdit('draft'), 'Rascunho deveria aceitar edicao');
        assert_true(!LineupRules::canEdit('confirmed'), 'Escalacao confirmada aceitou edicao comum');
        assert_true(LineupRules::validRole('starter') && LineupRules::validRole('reserve'), 'Funcoes de jogador invalidas');
        assert_true(!LineupRules::validRole('coach'), 'Comissao foi aceita como jogador');
    }
}
