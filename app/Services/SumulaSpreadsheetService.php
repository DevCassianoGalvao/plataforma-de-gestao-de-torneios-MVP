<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Gera a planilha oficial de sumula (docs/REFERENCIA_SUMULA.xlsx) preenchida com os nomes
 * dos atletas do elenco oficial de cada equipe de uma partida. Nao altera layout, bordas,
 * cabecalhos ou qualquer outra celula do modelo: apenas o titulo, os dois nomes de equipe e
 * ate 25 nomes de atleta por lado ficam em branco de proposito (placar, cartoes, horarios e
 * assinaturas continuam para preenchimento manual no dia do jogo).
 */
final class SumulaSpreadsheetService
{
    private const MAX_ATHLETES = 25;
    private const SHEET_ENTRY = 'xl/worksheets/sheet1.xml';
    /** As outras duas abas do modelo trazem dados reais (inclusive CPF) de outro campeonato; ficam vazias. */
    private const OTHER_SHEET_ENTRIES = ['xl/worksheets/sheet2.xml', 'xl/worksheets/sheet3.xml'];

    public function __construct(private readonly string $templatePath)
    {
    }

    /**
     * @param array{home_team_name: string, away_team_name: string, championship_name?: string} $match
     * @param array<int, array{full_name?: string, sporting_name?: string}> $homeAthletes
     * @param array<int, array{full_name?: string, sporting_name?: string}> $awayAthletes
     * @return string caminho do arquivo .xlsx temporario gerado (apagar apos o uso)
     */
    public function build(array $match, array $homeAthletes, array $awayAthletes): string
    {
        if (!is_file($this->templatePath)) {
            throw new \RuntimeException('Modelo de sumula (docs/REFERENCIA_SUMULA.xlsx) nao encontrado.');
        }
        $output = tempnam(sys_get_temp_dir(), 'sumula-');
        if ($output === false || !copy($this->templatePath, $output)) {
            throw new \RuntimeException('Nao foi possivel preparar a planilha de sumula.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($output) !== true) {
            @unlink($output);
            throw new \RuntimeException('Nao foi possivel abrir o modelo de sumula.');
        }
        $sheetXml = $zip->getFromName(self::SHEET_ENTRY);
        if ($sheetXml === false) {
            $zip->close();
            @unlink($output);
            throw new \RuntimeException('Modelo de sumula em formato inesperado.');
        }

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        if (!$doc->loadXML($sheetXml)) {
            $zip->close();
            @unlink($output);
            throw new \RuntimeException('Nao foi possivel ler o modelo de sumula.');
        }
        $xpath = new \DOMXPath($doc);

        $title = 'SUMULA OFICIAL - ' . (string) ($match['championship_name'] ?? '') . ' - '
            . (string) $match['home_team_name'] . ' x ' . (string) $match['away_team_name'];
        $this->setCell($doc, $xpath, 'A1', $title);
        $this->setCell($doc, $xpath, 'A7', (string) $match['home_team_name']);
        $this->setCell($doc, $xpath, 'W7', (string) $match['away_team_name']);

        $homeNames = $this->athleteNames($homeAthletes);
        $awayNames = $this->athleteNames($awayAthletes);
        for ($i = 0; $i < self::MAX_ATHLETES; $i++) {
            $row = 8 + $i;
            $this->setCell($doc, $xpath, 'A' . $row, $homeNames[$i] ?? '');
            $this->setCell($doc, $xpath, 'W' . $row, $awayNames[$i] ?? '');
        }
        // Linha 33 do modelo original ainda carrega nomes reais do torneio de referencia
        // (o lado direito nem sempre tem celula ali); apaga os dois lados por garantia, mesmo
        // fora do intervalo de dados que preenchemos, para nenhum resto da planilha antiga vazar.
        $this->setCell($doc, $xpath, 'A33', '');
        $this->setCell($doc, $xpath, 'W33', '');

        $zip->addFromString(self::SHEET_ENTRY, (string) $doc->saveXML());

        foreach (self::OTHER_SHEET_ENTRIES as $entry) {
            $otherXml = $zip->getFromName($entry);
            if ($otherXml === false) {
                continue;
            }
            $otherDoc = new \DOMDocument();
            $otherDoc->preserveWhiteSpace = true;
            if (!$otherDoc->loadXML($otherXml)) {
                continue;
            }
            $otherXpath = new \DOMXPath($otherDoc);
            $sheetData = $otherXpath->query("//*[local-name()='sheetData']")->item(0);
            if ($sheetData !== null) {
                while ($sheetData->firstChild !== null) {
                    $sheetData->removeChild($sheetData->firstChild);
                }
            }
            $zip->addFromString($entry, (string) $otherDoc->saveXML());
        }

        $zip->close();

        return $output;
    }

    /** @param array<int, array<string, mixed>> $athletes @return list<string> */
    private function athleteNames(array $athletes): array
    {
        $names = [];
        foreach ($athletes as $athlete) {
            $name = trim((string) ($athlete['sporting_name'] ?? '')) ?: trim((string) ($athlete['athlete_name'] ?? $athlete['full_name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return $names;
    }

    private function setCell(\DOMDocument $doc, \DOMXPath $xpath, string $ref, string $value): void
    {
        $nodes = $xpath->query("//*[local-name()='c'][@r='" . $ref . "']");
        if ($nodes === false || $nodes->length === 0) {
            return;
        }
        $cell = $nodes->item(0);
        foreach (['v', 'is', 'f'] as $tag) {
            foreach (iterator_to_array($xpath->query("*[local-name()='" . $tag . "']", $cell)) as $child) {
                $cell->removeChild($child);
            }
        }
        if ($value === '') {
            if ($cell->hasAttribute('t')) {
                $cell->removeAttribute('t');
            }
            return;
        }
        $cell->setAttribute('t', 'inlineStr');
        $is = $doc->createElement('is');
        $t = $doc->createElement('t');
        $t->setAttribute('xml:space', 'preserve');
        $t->appendChild($doc->createTextNode($value));
        $is->appendChild($t);
        $cell->appendChild($is);
    }
}
