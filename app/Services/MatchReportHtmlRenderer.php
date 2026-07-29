<?php
declare(strict_types=1);

namespace App\Services;

final class MatchReportHtmlRenderer
{
    public function render(array $payload, int $version, string $verificationCode): string
    {
        $e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $match = $payload['match']; $score = $payload['score']; $operation = $payload['operation'];
        $teamBlocks = '';
        foreach ($payload['lineups'] as $lineup) {
            $players = '';
            foreach ($lineup['players'] as $player) {
                $stats = $player['report_stats'];
                $players .= '<tr><td>' . $e($player['shirt_number'] ?: '-') . '</td><td>' . $e($player['sporting_name'] ?: $player['full_name']) . '</td><td>' . ($stats['yellow'] ? 'AM' : '') . '</td><td>' . ($stats['red'] ? 'VM' : '') . '</td><td>' . $e($stats['goals']) . '</td><td>' . ($player['role'] === 'starter' ? 'Titular' : 'Reserva') . '</td></tr>';
            }
            $captain = '-';
            foreach ($lineup['players'] as $player) {
                if ((int) ($player['is_captain'] ?? 0) === 1) {
                    $captain = (string) ($player['sporting_name'] ?: $player['full_name']);
                    break;
                }
            }
            $staff = implode(', ', array_map(static fn (array $member): string => (string) ($member['role_name'] ?: 'Comissao') . ': ' . (string) ($member['display_name'] ?: $member['full_name']), $lineup['staff']));
            $teamBlocks .= '<section class="report-team"><h3>' . $e($lineup['team_name']) . '</h3><p>Formacao: ' . $e($lineup['formation_name']) . '</p><table><thead><tr><th>N</th><th>Atleta</th><th>AM</th><th>VM</th><th>G</th><th>Funcao</th></tr></thead><tbody>' . $players . '</tbody></table><p><strong>Capitao:</strong> ' . $e($captain) . '<br><strong>Comissao:</strong> ' . $e($staff ?: '-') . '</p></section>';
        }
        $officials = implode(' | ', array_map(static fn (array $official): string => (string) $official['role'] . ': ' . (string) $official['display_name'], $payload['officials']));
        $occurrences = '';
        foreach ($payload['occurrences'] as $event) $occurrences .= '<li>' . $e($event['minute'] !== null ? $event['minute'] . "' - " : '') . $e($event['notes'] ?: 'Ocorrencia registrada') . '</li>';
        foreach ($payload['decisions'] as $decision) $occurrences .= '<li>Decisao administrativa: ' . $e($decision['notes']) . '</li>';
        if ($occurrences === '') $occurrences = '<li>Nenhuma ocorrencia registrada.</li>';
        return '<article class="match-report" data-version="' . $e($version) . '" data-verification="' . $e($verificationCode) . '"><section class="report-page report-page-main"><header><p class="eyebrow">SUMULA OFICIAL</p><h1>' . $e($match['championship_name']) . '</h1><p>' . $e($match['season_name'] ?: '-') . ' | ' . $e($match['category_name'] ?: '-') . '</p><p>Partida #' . $e($match['id']) . ' | ' . $e($match['phase_name']) . ' | Rodada ' . $e($match['round_number']) . '</p></header><div class="report-score"><strong>' . $e($match['home_team_name']) . '</strong><b>' . $e($score['home']) . ' x ' . $e($score['away']) . '</b><strong>' . $e($match['away_team_name']) . '</strong></div><dl class="report-meta"><div><dt>Data</dt><dd>' . $e($match['match_date'] ?: '-') . '</dd></div><div><dt>Horario</dt><dd>' . $e($match['match_time'] ?: '-') . '</dd></div><div><dt>Local</dt><dd>' . $e($match['venue_name'] ?: '-') . '</dd></div><div><dt>Penaltis</dt><dd>' . $e($score['home_penalties']) . ' x ' . $e($score['away_penalties']) . '</dd></div></dl><div class="report-periods"><span>1T inicio: ' . $e($operation['first_half_started_at'] ?? '-') . '</span><span>1T fim: ' . $e($operation['first_half_ended_at'] ?? '-') . '</span><span>2T inicio: ' . $e($operation['second_half_started_at'] ?? '-') . '</span><span>2T fim: ' . $e($operation['second_half_ended_at'] ?? '-') . '</span></div><div class="report-teams">' . $teamBlocks . '</div><section class="report-officials"><h2>Arbitragem e organizacao</h2><p>' . $e($officials ?: '-') . '</p></section><footer>Versao ' . $e($version) . ' | Verificacao ' . $e($verificationCode) . '</footer></section><section class="report-page report-page-occurrences"><header><p class="eyebrow">VERSO DA SUMULA</p><h2>Ocorrencias e confirmacoes</h2><p>Partida #' . $e($match['id']) . ' | Versao ' . $e($version) . '</p></header><section><h3>Ocorrencias</h3><ul>' . $occurrences . '</ul></section><section><h3>Substituicoes</h3><ul>' . ($payload['substitutions'] ? implode('', array_map(static fn (array $sub): string => '<li>' . htmlspecialchars($sub['team_name'] . ': ' . $sub['athlete_out_name'] . ' por ' . $sub['athlete_in_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>', $payload['substitutions'])) : '<li>Nenhuma substituicao registrada.</li>') . '</ul></section><div class="report-confirmations"><div>Confirmacao tecnico mandante: __________________________</div><div>Confirmacao tecnico visitante: __________________________</div><div>Mesario: _______________________________________________</div><div>Organizacao: ___________________________________________</div></div><p class="verification">Codigo de verificacao: <strong>' . $e($verificationCode) . '</strong><br>Gerado em: ' . $e($payload['generated_at']) . '</p></section></article>';
    }
}
