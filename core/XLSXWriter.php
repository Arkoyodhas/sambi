<?php
/** Penulis XLSX minimal tanpa dependensi (untuk export & template Excel). */
class XLSXWriter {

    /** Kirim file XLSX ke browser. $rows = array baris; setiap sel string/angka. */
    public static function download(string $filename, array $header, array $rows): void {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        self::build($tmp, $header, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    public static function build(string $dest, array $header, array $rows): void {
        $zip = new ZipArchive();
        $zip->open($dest, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>');
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2"><font/><font><b/></font></fonts>' .
            '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf/></cellStyleXfs>' .
            '<cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $sheet .= self::rowXml($header, 1, true);
        $r = 2;
        foreach ($rows as $row) $sheet .= self::rowXml($row, $r++, false);
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
    }

    private static function rowXml(array $cells, int $rowNum, bool $bold): string {
        $xml = '<row r="' . $rowNum . '">';
        $col = 0;
        foreach ($cells as $v) {
            $ref = self::colLetter($col++) . $rowNum;
            $style = $bold ? ' s="1"' : '';
            // semua nilai ditulis sebagai inline string agar NIK/No KK tidak rusak presisi
            $xml .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">'
                . htmlspecialchars((string) $v, ENT_XML1, 'UTF-8') . '</t></is></c>';
        }
        return $xml . '</row>';
    }

    private static function colLetter(int $i): string {
        $s = '';
        while ($i >= 0) { $s = chr($i % 26 + 65) . $s; $i = intdiv($i, 26) - 1; }
        return $s;
    }
}
