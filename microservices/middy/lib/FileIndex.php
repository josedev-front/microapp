<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileIndex {
    private $baseDir;

    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/');
    }

    // Leer ficheros TXT (retorna texto)
    public function readTextFile($path) {
        $full = $this->baseDir . '/' . $path;
        if (!file_exists($full)) return '';
        return file_get_contents($full);
    }

    // Leer excel: devuelve array de filas (primer sheet)
    public function readExcel($path) {
        $full = $this->baseDir . '/' . $path;
        if (!file_exists($full)) return [];
        $spreadsheet = IOFactory::load($full);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $r = [];
            foreach ($cellIterator as $cell) {
                $r[] = $cell->getValue();
            }
            $rows[] = $r;
        }
        return $rows;
    }

    // Extrae fragmentos relevantes por palabra clave (ej simple)
    public function searchInText($text, $query, $window = 400) {
        $pos = stripos($text, $query);
        if ($pos === false) return '';
        $start = max(0, $pos - $window/2);
        return substr($text, $start, $window);
    }
}
