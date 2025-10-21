<?php
namespace Middy\Lib;

require_once __DIR__ . '/../init.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class FileIndex {
    private $baseDir;

    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/');
        
        // Verificar que el directorio existe
        if (!file_exists($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
    }

    public function readTextFile($filename) {
        $fullPath = $this->baseDir . '/' . $filename;
        
        Helpers::log("Leyendo archivo TXT", ['file' => $filename, 'path' => $fullPath]);
        
        if (!file_exists($fullPath)) {
            Helpers::log("Archivo no encontrado", ['path' => $fullPath]);
            return '';
        }
        
        // Verificar permisos
        if (!is_readable($fullPath)) {
            Helpers::log("Archivo no readable", ['path' => $fullPath]);
            return '';
        }
        
        $content = file_get_contents($fullPath);
        if ($content === false) {
            Helpers::log("Error leyendo archivo", ['path' => $fullPath]);
            return '';
        }
        
        Helpers::log("Archivo leído exitosamente", [
            'file' => $filename, 
            'size' => strlen($content)
        ]);
        
        return $content;
    }

    public function readExcel($filename) {
        $fullPath = $this->baseDir . '/' . $filename;
        
        Helpers::log("Intentando leer Excel", ['file' => $filename]);
        
        if (!file_exists($fullPath)) {
            Helpers::log("Archivo Excel no encontrado", ['path' => $fullPath]);
            return [];
        }
        
        // Para Windows, verificar si la extensión está disponible
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            Helpers::log("PhpSpreadsheet no disponible");
            return $this->readExcelAsCSV($fullPath);
        }
        
        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = [];
            
            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                $rows[] = $rowData;
            }
            
            Helpers::log("Excel leído exitosamente", [
                'file' => $filename,
                'rows' => count($rows)
            ]);
            
            return $rows;
            
        } catch (Exception $e) {
            Helpers::log("Error leyendo Excel, intentando como CSV", [
                'error' => $e->getMessage(),
                'file' => $filename
            ]);
            
            // Fallback a CSV
            return $this->readExcelAsCSV($fullPath);
        }
    }

    // Método fallback para leer como CSV
    private function readExcelAsCSV($fullPath) {
        $rows = [];
        
        // Intentar leer como CSV primero
        if (($handle = fopen($fullPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
            
            Helpers::log("Archivo leído como CSV", [
                'file' => basename($fullPath),
                'rows' => count($rows)
            ]);
        }
        
        return $rows;
    }

    public function searchInText($text, $query, $window = 400) {
        if (empty($text) || empty($query)) {
            return '';
        }
        
        $pos = stripos($text, $query);
        if ($pos === false) {
            return '';
        }
        
        $start = max(0, $pos - $window / 2);
        $fragment = substr($text, $start, $window);
        
        if ($start > 0) {
            $fragment = '...' . $fragment;
        }
        if ($start + $window < strlen($text)) {
            $fragment = $fragment . '...';
        }
        
        return $fragment;
    }

    // Nuevo método para listar archivos disponibles
    public function listAvailableFiles() {
        $files = [];
        
        $txtFiles = glob($this->baseDir . '/*.txt');
        $excelFiles = glob($this->baseDir . '/*.{xlsx,xls,csv}', GLOB_BRACE);
        
        foreach ($txtFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'type' => 'txt',
                'size' => filesize($file)
            ];
        }
        
        foreach ($excelFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION),
                'size' => filesize($file)
            ];
        }
        
        return $files;
    }
}