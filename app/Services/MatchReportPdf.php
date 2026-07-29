<?php
declare(strict_types=1);

namespace App\Services;

final class MatchReportPdf
{
    private const WIDTH = 595.28;
    private const HEIGHT = 841.89;
    private array $pages = [];

    public function render(array $payload, int $version, string $verificationCode): string
    {
        $this->pages = [[]];
        $match = $payload['match']; $operation = $payload['operation']; $score = $payload['score'];
        $this->title('SUMULA OFICIAL', 26, true);
        $this->text($match['championship_name'] . ' | ' . ($match['season_name'] ?: '-') . ' | ' . ($match['category_name'] ?: '-'), 16, 42, 10);
        $this->text('Partida #' . $match['id'] . ' | ' . $match['phase_name'] . ' | Rodada ' . $match['round_number'], 16, 57, 9);
        $this->line(16, 66, 579, 66);
        $this->text($match['home_team_name'], 26, 90, 13, true); $this->text($match['away_team_name'], 390, 90, 13, true);
        $this->text($score['home'] . ' x ' . $score['away'], 270, 91, 18, true);
        $this->text('Data: ' . ($match['match_date'] ?: '-') . '   Horario: ' . ($match['match_time'] ?: '-') . '   Local: ' . ($match['venue_name'] ?: '-'), 16, 112, 8);
        $this->text('Penaltis: ' . $score['home_penalties'] . ' x ' . $score['away_penalties'] . ($score['administrative'] ? '   Resultado administrativo' : ''), 16, 126, 8);
        $this->text('1T: ' . ($operation['first_half_started_at'] ?? '-') . ' - ' . ($operation['first_half_ended_at'] ?? '-'), 16, 140, 8);
        $this->text('2T: ' . ($operation['second_half_started_at'] ?? '-') . ' - ' . ($operation['second_half_ended_at'] ?? '-'), 300, 140, 8);
        $this->drawTeamTable($payload['lineups'][0] ?? [], 16, 164, 270);
        $this->drawTeamTable($payload['lineups'][1] ?? [], 309, 164, 270);
        $this->text('Arbitragem e organizacao', 16, 630, 10, true);
        $officials = array_map(static fn (array $item): string => $item['role'] . ': ' . $item['display_name'], $payload['officials']);
        $this->textBox(implode(' | ', $officials) ?: '-', 16, 646, 563, 25, 8);
        $this->footer($version, $verificationCode, 1);

        $this->newPage();
        $this->title('VERSO DA SUMULA', 26, true);
        $this->text('Partida #' . $match['id'] . ' | ' . $match['home_team_name'] . ' x ' . $match['away_team_name'], 16, 44, 10);
        $this->text('Ocorrencias', 16, 76, 12, true);
        $occurrences = [];
        foreach ($payload['occurrences'] as $event) $occurrences[] = ($event['minute'] !== null ? $event['minute'] . "' - " : '') . ($event['notes'] ?: 'Ocorrencia registrada');
        foreach ($payload['decisions'] as $decision) $occurrences[] = 'Decisao administrativa: ' . $decision['notes'];
        $this->bulletList($occurrences ?: ['Nenhuma ocorrencia registrada.'], 16, 94, 8);
        $y = 170;
        $this->text('Substituicoes', 16, $y, 12, true); $y += 20;
        $subs = [];
        foreach ($payload['substitutions'] as $sub) $subs[] = $sub['team_name'] . ': ' . $sub['athlete_out_name'] . ' por ' . $sub['athlete_in_name'] . ($sub['minute'] !== null ? ' (' . $sub['minute'] . "')" : '');
        $this->bulletList($subs ?: ['Nenhuma substituicao registrada.'], 16, $y, 8);
        $this->text('Confirmacoes', 16, 330, 12, true);
        $this->text('Tecnico mandante: ______________________________________________', 16, 355, 9);
        $this->text('Tecnico visitante: ______________________________________________', 16, 380, 9);
        $this->text('Auxiliar / capitao mandante: ____________________________________', 16, 405, 9);
        $this->text('Auxiliar / capitao visitante: ____________________________________', 16, 430, 9);
        $this->text('Mesario: ______________________________________________________', 16, 455, 9);
        $this->text('Organizacao: __________________________________________________', 16, 480, 9);
        $this->textBox('Versao imutavel ' . $version . ' | Codigo de verificacao: ' . $verificationCode . ' | Gerado em: ' . $payload['generated_at'], 16, 540, 563, 48, 8);
        $this->footer($version, $verificationCode, 2);
        return $this->document();
    }

    private function drawTeamTable(array $lineup, float $x, float $top, float $width): void
    {
        $this->text($lineup['team_name'] ?? '-', $x, $top, 10, true);
        $this->text('Formacao: ' . ($lineup['formation_name'] ?? '-'), $x, $top + 14, 7);
        $y = $top + 28; $this->rect($x, $y, $width, 18, false); $this->text('N', $x + 4, $y + 12, 7, true); $this->text('Atleta', $x + 22, $y + 12, 7, true); $this->text('AM', $x + 178, $y + 12, 7, true); $this->text('VM', $x + 201, $y + 12, 7, true); $this->text('G', $x + 224, $y + 12, 7, true); $this->text('Papel', $x + 244, $y + 12, 7, true); $y += 18;
        foreach ($lineup['players'] ?? [] as $player) {
            $this->rect($x, $y, $width, 16, false); $stats = $player['report_stats'] ?? ['yellow' => 0, 'red' => 0, 'goals' => 0];
            $this->text((string) ($player['shirt_number'] ?: '-'), $x + 4, $y + 11, 7); $this->text($this->truncate($player['sporting_name'] ?: $player['full_name'], 26), $x + 22, $y + 11, 6.8); $this->text($stats['yellow'] ? 'X' : '', $x + 181, $y + 11, 7); $this->text($stats['red'] ? 'X' : '', $x + 204, $y + 11, 7); $this->text((string) $stats['goals'], $x + 226, $y + 11, 7); $this->text($player['role'] === 'starter' ? 'Tit.' : 'Res.', $x + 244, $y + 11, 6.8); $y += 16;
        }
        $captain = '-';
        foreach ($lineup['players'] ?? [] as $player) {
            if ((int) ($player['is_captain'] ?? 0) === 1) {
                $captain = (string) ($player['sporting_name'] ?: $player['full_name']);
                break;
            }
        }
        $staff = implode(', ', array_map(static fn (array $member): string => ($member['role_name'] ?: 'Comissao') . ': ' . ($member['display_name'] ?: $member['full_name']), $lineup['staff'] ?? []));
        $this->text('Capitao: ' . $this->truncate($captain, 34), $x, $y + 12, 7);
        $this->text('Comissao: ' . $this->truncate($staff ?: '-', 34), $x, $y + 24, 7);
    }

    private function bulletList(array $items, float $x, float $top, float $size): void
    {
        foreach ($items as $item) { $this->text('- ' . $this->truncate((string) $item, 100), $x, $top, $size); $top += 18; }
    }

    private function title(string $value, float $top, bool $bold = false): void { $this->text($value, 16, $top, 15, $bold); }
    private function newPage(): void { $this->pages[] = []; }
    private function line(float $x1, float $y1, float $x2, float $y2): void { $this->pages[array_key_last($this->pages)][] = $x1 . ' ' . (self::HEIGHT - $y1) . ' m ' . $x2 . ' ' . (self::HEIGHT - $y2) . ' l S'; }
    private function rect(float $x, float $top, float $width, float $height, bool $fill): void { $command = $x . ' ' . (self::HEIGHT - $top - $height) . ' ' . $width . ' ' . $height . ' re ' . ($fill ? 'f' : 'S'); $this->pages[array_key_last($this->pages)][] = $command; }
    private function text(string $value, float $x, float $top, float $size, bool $bold = false): void { $font = $bold ? 'F2' : 'F1'; $this->pages[array_key_last($this->pages)][] = 'BT /' . $font . ' ' . $size . ' Tf ' . $x . ' ' . (self::HEIGHT - $top) . ' Td (' . $this->pdfText($value) . ') Tj ET'; }
    private function textBox(string $value, float $x, float $top, float $width, float $height, float $size): void { $words = preg_split('/\s+/', $value) ?: []; $line = ''; $y = $top; foreach ($words as $word) { $candidate = $line === '' ? $word : $line . ' ' . $word; if (strlen($candidate) > max(18, (int) ($width / ($size * 0.52)))) { $this->text($line, $x, $y, $size); $line = $word; $y += $size + 3; if ($y > $top + $height) break; } else $line = $candidate; } if ($line !== '' && $y <= $top + $height) $this->text($line, $x, $y, $size); }
    private function footer(int $version, string $code, int $page): void { $this->line(16, 806, 579, 806); $this->text('Versao ' . $version . ' | Verificacao ' . $code . ' | Pagina ' . $page, 16, 820, 7); }
    private function truncate(string $value, int $length): string { return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value; }
    private function pdfText(string $value): string { $converted = function_exists('iconv') ? iconv('UTF-8', 'Windows-1252//TRANSLIT', $value) : $value; $converted = $converted === false ? $value : $converted; return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $converted); }

    private function document(): string
    {
        $objects = []; $reserve = static function () use (&$objects): int { $objects[] = ''; return count($objects); }; $set = static function (int $id, string $value) use (&$objects): void { $objects[$id - 1] = $value; };
        $pagesId = $reserve(); $regularId = $reserve(); $boldId = $reserve(); $pageIds = [];
        foreach ($this->pages as $commands) { $content = implode("\n", $commands); $contentId = $reserve(); $set($contentId, '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream"); $pageId = $reserve(); $pageIds[] = $pageId; $set($pageId, '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 ' . self::WIDTH . ' ' . self::HEIGHT . '] /Resources << /Font << /F1 ' . $regularId . ' 0 R /F2 ' . $boldId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>'); }
        $set($pagesId, '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>'); $set($regularId, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'); $set($boldId, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'); $catalogId = $reserve(); $set($catalogId, '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>');
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = [0]; foreach ($objects as $index => $object) { $offsets[] = strlen($pdf); $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n"; } $xref = strlen($pdf); $pdf .= 'xref\n0 ' . (count($objects) + 1) . "\n0000000000 65535 f \n"; for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n"; $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF"; return $pdf;
    }
}
