<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\NewsRepository;
use App\Services\Slugger;
use function Tests\assert_true;

final class NewsTest
{
    public static function run(): void
    {
        assert_true(Slugger::make('Final da Copa: 2026!') === 'final-da-copa-2026', 'Slug de noticia nao foi normalizado');
        assert_true(in_array('draft', NewsRepository::STATUSES, true) && in_array('scheduled', NewsRepository::STATUSES, true) && in_array('published', NewsRepository::STATUSES, true) && in_array('unpublished', NewsRepository::STATUSES, true) && in_array('archived', NewsRepository::STATUSES, true), 'Status editoriais incompletos');
    }
}
