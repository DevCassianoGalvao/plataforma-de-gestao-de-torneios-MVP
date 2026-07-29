<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MatchReportRepository;

final class MatchReportService
{
    public function __construct(private readonly MatchReportRepository $reports, private readonly StorageService $storage, private readonly AuditService $audit, private readonly MatchReportHtmlRenderer $html = new MatchReportHtmlRenderer(), private readonly MatchReportPdf $pdf = new MatchReportPdf())
    {
    }

    public function generateForHomologatedMatch(array $match, int $userId): array
    {
        if (($match['status'] ?? '') !== 'homologated') return $this->fail('Sumula so pode ser gerada para partida homologada.');
        $payload = $this->reports->payload((int) $match['id']);
        if (!$payload) return $this->fail('Dados da partida nao encontrados.');
        $source = $payload; unset($source['generated_at']);
        $hash = hash('sha256', json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $reportId = $this->reports->ensureReport((int) $match['id'], (int) $match['championship_id'], $userId);
        $current = $this->reports->reportForMatch((int) $match['id']);
        if ($current && ($current['content_hash'] ?? '') === $hash && !empty($current['version_id'])) return ['ok' => true, 'errors' => [], 'version' => $current, 'created' => false];
        $versions = $this->reports->versions((int) $match['id']);
        $number = count($versions) + 1;
        $previous = $versions[0] ?? null;
        $verificationCode = strtoupper(substr(hash('sha256', $reportId . ':' . $number . ':' . $hash . ':' . bin2hex(random_bytes(8))), 0, 32));
        $html = $this->html->render($payload, $number, $verificationCode);
        $pdf = $this->pdf->render($payload, $number, $verificationCode);
        $stored = null;
        try {
            $stored = $this->storage->storeContents($pdf, 'reports/matches/' . (int) $match['id'], 'pdf', 'application/pdf');
            $this->reports->begin();
            $versionId = $this->reports->insertVersion($reportId, ['version_number' => $number, 'verification_code' => $verificationCode, 'content_hash' => $hash, 'storage_path' => $stored['path'], 'original_name' => 'sumula-partida-' . $match['id'] . '-v' . $number . '.pdf', 'mime_type' => 'application/pdf', 'file_size' => $stored['size'], 'html_snapshot' => $html, 'created_by' => $userId, 'supersedes_version_id' => $previous['id'] ?? null]);
            $this->reports->setCurrentVersion($reportId, $versionId);
            $this->reports->commit();
        } catch (\Throwable $exception) {
            $this->reports->rollBack();
            if ($stored) $this->storage->delete($stored['path']);
            throw $exception;
        }
        $version = $this->reports->version($versionId);
        $this->audit->record('match_report.version_created', $userId, 'match_report_version', $versionId, ['match_id' => $match['id'], 'version' => $number, 'verification_code' => $verificationCode], null);
        return ['ok' => true, 'errors' => [], 'version' => $version, 'created' => true];
    }

    public function html(int $matchId): ?string
    {
        $report = $this->reports->reportForMatch($matchId);
        return $report && !empty($report['html_snapshot']) ? (string) $report['html_snapshot'] : null;
    }

    public function package(array $versions, string $name, int $userId): array
    {
        if (!class_exists('ZipArchive')) return $this->fail('Extensao ZIP nao disponivel.');
        $temporary = tempnam(sys_get_temp_dir(), 'reports_');
        if ($temporary === false) return $this->fail('Nao foi possivel preparar o pacote.');
        $zip = new \ZipArchive();
        if ($zip->open($temporary, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) { @unlink($temporary); return $this->fail('Nao foi possivel criar o pacote.'); }
        $count = 0;
        foreach ($versions as $version) {
            $file = $this->storage->read($version['storage_path']);
            if (!$file) continue;
            $zip->addFromString('sumulas/' . ($version['original_name'] ?: ('partida-' . $version['match_id'] . '.pdf')), $file['body']); $count++;
        }
        $zip->close();
        if ($count === 0) { @unlink($temporary); return $this->fail('Nenhuma sumula disponivel para o pacote.'); }
        $contents = file_get_contents($temporary); @unlink($temporary);
        if ($contents === false) return $this->fail('Nao foi possivel ler o pacote.');
        $stored = $this->storage->storeContents($contents, 'reports/packages', 'zip', 'application/zip');
        $this->audit->record('match_report.package_created', $userId, 'match_report_package', $name, ['count' => $count], null);
        return ['ok' => true, 'errors' => [], 'file' => $stored, 'count' => $count, 'name' => $name . '.zip'];
    }

    private function fail(string $message): array { return ['ok' => false, 'errors' => [$message]]; }
}
