<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MatchReportHtmlRenderer;
use App\Services\MatchReportPdf;
use function Tests\assert_true;

final class MatchReportTest
{
    public static function run(): void
    {
        $payload = ['match' => ['id' => 7, 'championship_name' => 'Copa Teste', 'season_name' => '2026', 'category_name' => 'Sub-15', 'phase_name' => 'Grupos', 'round_number' => 1, 'home_team_name' => 'Equipe A', 'away_team_name' => 'Equipe B', 'match_date' => '2026-07-28', 'match_time' => '10:00', 'venue_name' => 'Campo Central'], 'operation' => [], 'score' => ['home' => 2, 'away' => 1, 'home_penalties' => 0, 'away_penalties' => 0, 'administrative' => false], 'lineups' => [], 'officials' => [], 'occurrences' => [], 'decisions' => [], 'substitutions' => [], 'generated_at' => '2026-07-28 10:00:00'];
        $pdf = (new MatchReportPdf())->render($payload, 1, 'ABC123');
        assert_true(str_starts_with($pdf, '%PDF-1.4'), 'PDF nao possui assinatura real');
        assert_true(str_contains($pdf, '/Count 2') && str_contains($pdf, 'VERSO DA SUMULA'), 'PDF nao possui duas paginas estruturais');
        $html = (new MatchReportHtmlRenderer())->render($payload, 1, 'ABC123');
        assert_true(str_contains($html, 'Equipe A') && str_contains($html, 'Ocorrencias e confirmacoes') && str_contains($html, 'ABC123'), 'HTML da sumula nao preservou estrutura e verificacao');
    }
}
