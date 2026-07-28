<?php
declare(strict_types=1);
namespace App\Services;
final class PdfReportService {
    public function create(string $title, array $lines): string {
        $text = $this->textLine($title, 14, 50, 790);
        $y = 765;
        foreach ($lines as $line) {
            if ($y < 40) break;
            $text .= $this->textLine((string)$line, 10, 50, $y);
            $y -= 16;
        }
        $objects=["<< /Type /Catalog /Pages 2 0 R >>","<< /Type /Pages /Kids [3 0 R] /Count 1 >>","<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>","<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>","<< /Length ".strlen($text)." >>\nstream\n$text\nendstream"];$pdf="%PDF-1.4\n";$offset=[0];foreach($objects as $i=>$o){$offset[] = strlen($pdf);$pdf.=($i+1)." 0 obj\n$o\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";for($i=1;$i<=5;$i++)$pdf.=sprintf('%010d 00000 n ', $offset[$i])."\n";$pdf.="trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
    }
    private function textLine(string $text, int $size, int $x, int $y): string { return "BT /F1 $size Tf $x $y Td (".$this->esc($text).") Tj ET\n"; }
    private function esc(string $s): string{return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(', '\\)',' ',' '],$s);}
}
