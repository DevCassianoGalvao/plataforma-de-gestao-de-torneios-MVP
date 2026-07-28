<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ColorRules;
use App\Services\Slugger;
use App\Services\TeamRules;
use App\Services\TeamStatusService;
use function Tests\assert_true;

final class TeamTest
{
    public static function run(): void
    {
        assert_true(Slugger::make('Estrela Norte FC') === 'estrela-norte-fc', 'Slug de equipe nao normalizado');
        assert_true(ColorRules::valid('#12AB90'), 'Cor de equipe valida rejeitada');
        assert_true(TeamRules::validate(['name' => 'Equipe', 'short_name' => 'Eq', 'slug' => 'equipe', 'abbreviation' => 'EQ', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'status' => 'draft']) === [], 'Equipe valida rejeitada');
        assert_true(TeamRules::validate(['name' => 'Equipe', 'short_name' => 'Eq', 'slug' => 'equipe', 'abbreviation' => 'E', 'primary_color' => 'blue', 'secondary_color' => '#D9A441', 'status' => 'draft']) !== [], 'Equipe invalida aceita');
        assert_true(TeamRules::validateStaff(['full_name' => 'Pessoa da Comissao', 'staff_role_id' => 1, 'email' => 'pessoa@example.test', 'status' => 'active']) === [], 'Comissao valida rejeitada');
        $status = new TeamStatusService();
        assert_true($status->transition(['status' => 'draft'], 'active')['ok'], 'Draft nao pode virar ativa');
        assert_true($status->transition(['status' => 'active'], 'withdrawn')['ok'], 'Equipe ativa nao pode ser retirada');
        assert_true(!$status->transition(['status' => 'withdrawn'], 'active')['ok'], 'Equipe retirada voltou sem procedimento');
        assert_true($status->transition(['status' => 'archived'], 'active')['ok'], 'Equipe arquivada nao pode ser restaurada');
    }
}
