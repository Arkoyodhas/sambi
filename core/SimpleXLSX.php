<?php
/** Pembaca XLSX minimal tanpa dependensi (untuk import data penduduk). */
class SimpleXLSX {

    /** Kembalikan seluruh baris sheet pertama sebagai array 2 dimensi, atau null jika gagal. */
    public static function rows(string $file): ?array {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) return null;

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = @simplexml_load_string($xml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) { $shared[] = (string) $si->t; }
                    else { $t = ''; foreach ($si->r as $r) $t .= (string) $r->t; $shared[] = $t; }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $n)) { $sheetXml = $zip->getFromName($n); break; }
            }
        }
        $zip->close();
        if (!$sheetXml) return null;

        $sx = @simplexml_load_string($sheetXml);
        if (!$sx || !isset($sx->sheetData)) return null;

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $col = self::colIndex($m[1] ?? 'A');
                $type = (string) $c['t'];
                $v = isset($c->v) ? (string) $c->v : '';
                if ($type === 's') $v = $shared[(int) $v] ?? '';
                elseif ($type === 'inlineStr') $v = (string) $c->is->t;
                $r[$col] = trim($v);
            }
            if ($r) {
                $max = max(array_keys($r));
                $full = array_fill(0, $max + 1, '');
                foreach ($r as $k => $v) $full[$k] = $v;
                $rows[] = $full;
            } else {
                $rows[] = [];
            }
        }
        return $rows;
    }

    private static function colIndex(string $letters): int {
        $n = 0;
        foreach (str_split($letters) as $ch) $n = $n * 26 + (ord($ch) - 64);
        return $n - 1;
    }

    /** Konversi nilai tanggal Excel (serial number atau teks) ke Y-m-d, null jika tidak valid. */
    public static function toDate(string $v): ?string {
        $v = trim($v);
        if ($v === '') return null;
        if (is_numeric($v) && (float) $v > 1000) {
            $ts = ((float) $v - 25569) * 86400; // serial Excel -> unix
            return gmdate('Y-m-d', (int) $ts);
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $v);
            if ($d && $d->format($fmt) === $v) return $d->format('Y-m-d');
        }
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
