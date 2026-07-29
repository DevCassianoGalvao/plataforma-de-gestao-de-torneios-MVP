<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\TransferRepository;
use App\Services\TransferRules;
use function Tests\assert_same;
use function Tests\assert_true;

final class TransferTest
{
    public static function run(): void
    {
        assert_same(['contratacao', 'transferencia', 'emprestimo', 'retorno', 'renovacao', 'saida'], TransferRepository::TYPES, 'Tipos de transferencia incorretos'); assert_same(['draft', 'pending', 'approved', 'published', 'cancelled'], TransferRepository::STATUSES, 'Status de transferencia incorretos'); assert_true(TransferRules::canTransition('pending', 'approved'), 'Aprovacao deveria ser permitida'); assert_true(TransferRules::canTransition('approved', 'published'), 'Publicacao deveria ser permitida'); assert_true(!TransferRules::canTransition('published', 'approved'), 'Retorno silencioso de publicado foi permitido'); assert_true(!TransferRules::canTransition('cancelled', 'draft'), 'Cancelado deveria ser terminal');
    }
}
