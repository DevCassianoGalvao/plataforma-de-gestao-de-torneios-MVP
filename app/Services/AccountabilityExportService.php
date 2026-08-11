<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AccountabilityRepository;
use ZipArchive;

final class AccountabilityExportService
{
    public function __construct(private readonly AccountabilityRepository $repository, private readonly StorageService $storage, private readonly AuditService $audit) {}

    public function generate(int $championshipId, string $format, array $filters, int $userId): array
    {
        $format = strtolower($format);
        if ($format === 'atletas-documentos-zip') {
            $rows = $this->repository->rows($championshipId, 'atletas-documentos');
            $body = $this->athleteDocumentsZip($championshipId, $rows);
            $hash = hash('sha256', $body);
            $name = 'prestacao-atletas-documentos-campeonato-' . $championshipId . '-' . date('Ymd-His') . '.zip';
            $this->repository->log($championshipId, $userId, 'atletas-documentos', count($rows), 'zip', [], [], $name, $hash);
            $this->audit->record('accountability.exported', $userId, 'championship', $championshipId, ['format' => 'atletas-documentos-zip', 'rows' => count($rows), 'hash' => $hash], null);
            return ['body' => $body, 'mime' => 'application/zip', 'name' => $name, 'hash' => $hash, 'count' => count($rows)];
        }
        $rows = $this->repository->exportRows($championshipId, $filters);
        $matchIds = array_map(static fn (array $row): int => (int) $row['partida'], $rows);
        $kind = 'consolidado';
        if ($format === 'csv') { $body = $this->csv($rows); $mime = 'text/csv; charset=UTF-8'; $extension = 'csv'; }
        elseif ($format === 'xlsx') { $body = $this->xlsx($rows); $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $extension = 'xlsx'; }
        elseif ($format === 'pdf') { $body = $this->pdf($rows, $championshipId); $mime = 'application/pdf'; $extension = 'pdf'; }
        elseif ($format === 'zip') { $body = $this->zip($championshipId, $filters, $rows); $mime = 'application/zip'; $extension = 'zip'; $kind = 'pacote'; }
        else throw new \InvalidArgumentException('Formato de prestação não permitido.');
        $name = 'prestacao-campeonato-' . $championshipId . '-' . date('Ymd-His') . '.' . $extension;
        $hash = hash('sha256', $body);
        $this->repository->log($championshipId, $userId, $kind, count($rows), $format, $filters, $matchIds, $name, $hash);
        $this->audit->record('accountability.exported', $userId, 'championship', $championshipId, ['format' => $format, 'rows' => count($rows), 'hash' => $hash], null);
        return ['body' => $body, 'mime' => $mime, 'name' => $name, 'hash' => $hash, 'count' => count($rows)];
    }

    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        if ($rows !== []) {
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $row) fputcsv($out, $row, ';');
        }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }

    private function xlsx(array $rows): string
    {
        if (!class_exists(ZipArchive::class)) throw new \RuntimeException('A extensão ZIP do PHP é necessária para gerar Excel.');
        $headers = $rows === [] ? ['partida', 'fase', 'grupo', 'rodada', 'mandante', 'visitante', 'data', 'horario', 'publicacao', 'documentacao'] : array_keys($rows[0]);
        $all = [$headers, ...array_map(static fn (array $row): array => array_values($row), $rows)];
        $sheet = '';
        foreach ($all as $index => $row) {
            $sheet .= '<row r="' . ($index + 1) . '">';
            foreach (array_values($row) as $column => $value) {
                $ref = $this->columnName($column + 1) . ($index + 1);
                $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $this->xml((string) $value) . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheet . '</sheetData></worksheet>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Prestação" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
        $tmp = tempnam(sys_get_temp_dir(), 'torneio-xlsx-');
        if ($tmp === false) throw new \RuntimeException('Não foi possível preparar a planilha.');
        try {
            $zip = new ZipArchive();
            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Não foi possível criar a planilha.');
            $zip->addFromString('[Content_Types].xml', $types);
            $zip->addFromString('_rels/.rels', $rootRels);
            $zip->addFromString('xl/workbook.xml', $workbook);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->close();
            return (string) file_get_contents($tmp);
        } finally { @unlink($tmp); }
    }

    private function zip(int $championshipId, array $filters, array $rows): string
    {
        if (!class_exists(ZipArchive::class)) throw new \RuntimeException('A extensão ZIP do PHP é necessária para gerar o pacote.');
        $tmp = tempnam(sys_get_temp_dir(), 'torneio-accountability-');
        if ($tmp === false) throw new \RuntimeException('Não foi possível preparar o pacote.');
        try {
            $zip = new ZipArchive();
            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Não foi possível criar o pacote.');
            $zip->addFromString('dados.csv', $this->csv($rows));
            $manifest = ['Pacote privado de prestação de contas', 'Campeonato: ' . $championshipId, 'Gerado em: ' . date('c'), 'Filtros: ' . json_encode($filters, JSON_UNESCAPED_UNICODE), 'Os documentos abaixo são cópias oficiais ou evidências aprovadas.'];
            foreach ($this->repository->packageFiles($championshipId, $filters)['files'] as $file) {
                $stored = $this->storage->read((string) $file['path']);
                if (!$stored) { $manifest[] = 'PENDENTE: ' . $file['name']; continue; }
                $zip->addFromString($file['name'], $stored['body']);
                $manifest[] = 'INCLUÍDO: ' . $file['name'] . ' sha256=' . hash('sha256', $stored['body']);
            }
            $zip->addFromString('manifesto.txt', implode("\n", $manifest));
            $zip->close();
            return (string) file_get_contents($tmp);
        } finally { @unlink($tmp); }
    }

    private function athleteDocumentsZip(int $championshipId, array $rows): string
    {
        if (!class_exists(ZipArchive::class)) throw new \RuntimeException('A extensão ZIP do PHP é necessária para gerar o pacote.');
        $tmp = tempnam(sys_get_temp_dir(), 'torneio-athletes-');
        if ($tmp === false) throw new \RuntimeException('Não foi possível preparar o pacote.');
        try {
            $zip = new ZipArchive();
            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Não foi possível criar o pacote.');
            $zip->addFromString('atletas-documentos.csv', $this->csv($rows));
            $manifest = ['Pacote privado de atletas e documentos', 'Campeonato: ' . $championshipId, 'Gerado em: ' . date('c'), 'Somente documentos aprovados e atletas com inscrição aprovada.'];
            foreach ($this->repository->athleteDocumentFiles($championshipId) as $file) {
                $stored = $this->storage->read((string) $file['path']);
                $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                $suffix = $extension !== '' ? '.' . preg_replace('/[^a-z0-9]+/', '', $extension) : '';
                $athleteName = $this->safeFilePart((string) $file['athlete_name']);
                $documentType = $this->safeFilePart((string) $file['document_type']);
                $entry = 'documentos/Atleta - ' . $athleteName . ' - ' . $documentType . ' - ' . (int) $file['id'] . $suffix;
                if (!$stored) { $manifest[] = 'PENDENTE: ' . $entry; continue; }
                $zip->addFromString($entry, $stored['body']);
                $manifest[] = 'INCLUÍDO: ' . $entry . ' sha256=' . hash('sha256', $stored['body']);
            }
            $zip->addFromString('manifesto.txt', implode("\n", $manifest));
            $zip->close();
            return (string) file_get_contents($tmp);
        } finally { @unlink($tmp); }
    }

    private function safeFilePart(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value)) ?: trim($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?: 'sem-nome';
        return trim($value, '-') ?: 'sem-nome';
    }

    private function pdf(array $rows, int $championshipId): string
    {
        $lines = ['PRESTACAO DE CONTAS - CAMPEONATO ' . $championshipId, 'Gerado em ' . date('Y-m-d H:i:s'), 'Somente partidas aprovadas e documentos autorizados.', ''];
        foreach ($rows as $row) $lines[] = sprintf('#%s | %s | %s x %s | %s | %s', $row['partida'], $row['fase'], $row['mandante'], $row['visitante'], $row['data'], $row['documentacao']);
        $pages = array_chunk($lines, 42);
        if ($pages === []) $pages = [[]];
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageIds = [];
        $next = 4;
        foreach ($pages as $page) { $pageIds[] = $next; $next += 2; }
        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds));
        $objects[] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        foreach ($pages as $page) {
            $content = "BT\n/F1 9 Tf\n50 790 Td\n";
            foreach ($page as $line) { $content .= '(' . $this->pdfText((string) $line) . ") Tj\n0 -17 Td\n"; }
            $content .= "ET\n";
            $contentId = count($objects) + 1;
            $pageId = $contentId - 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . ($contentId + 1) . ' 0 R >>';
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
        }
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[$i + 1] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n \n', $offsets[$i]);
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) { $remainder = ($number - 1) % 26; $name = chr(65 + $remainder) . $name; $number = intdiv($number - 1, 26); }
        return $name;
    }

    private function xml(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private function pdfText(string $value): string { $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value; return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr($value, 0, 180)); }
}
