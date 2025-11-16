<?php
// microservices/back-admision/lib/ExcelGenerator.php

// CORRECCIÓN: Ruta correcta para el autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelGenerator {
    private $db;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Generar reporte de asignaciones en Excel
     */
    public function generarReporteAsignaciones($filtros = []) {
        try {
            // Obtener datos
            $casos = $this->obtenerDatosReporte($filtros);
            
            // Crear spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Encabezados
            $sheet->setCellValue('A1', 'Reporte de Back de Admisión');
            $sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i'));
            $sheet->setCellValue('A3', 'Filtros aplicados: ' . json_encode($filtros));
            
            // Títulos de columnas
            $headers = [
                'SR Hijo', 'SRP', 'Estado', 'Fecha Ingreso', 'Ticket', 
                'Analista', 'Motivo Ticket', 'Tipo Negocio', 'Observaciones',
                'Biometría', 'Acreditación', 'Inicio Actividades'
            ];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '5', $header);
                $col++;
            }
            
            // Datos
            $row = 6;
            foreach ($casos as $caso) {
                $sheet->setCellValue('A' . $row, $caso['sr_hijo'] ?? '');
                $sheet->setCellValue('B' . $row, $caso['srp'] ?? '');
                $sheet->setCellValue('C' . $row, $this->traducirEstado($caso['estado'] ?? ''));
                $sheet->setCellValue('D' . $row, $caso['fecha_ingreso'] ?? '');
                $sheet->setCellValue('E' . $row, $caso['tiket'] ?? '');
                $sheet->setCellValue('F' . $row, $caso['analista_nombre'] ?? '');
                $sheet->setCellValue('G' . $row, $caso['motivo_tiket'] ?? '');
                $sheet->setCellValue('H' . $row, $caso['tipo_negocio'] ?? '');
                $sheet->setCellValue('I' . $row, $caso['observaciones'] ?? '');
                $sheet->setCellValue('J' . $row, $caso['biometria'] ?? '');
                $sheet->setCellValue('K' . $row, $caso['acreditacion'] ?? '');
                $sheet->setCellValue('L' . $row, $caso['inicio_actividades'] ?? '');
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'L') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Estilos
            $sheet->getStyle('A5:L5')->getFont()->setBold(true);
            $sheet->getStyle('A1:A3')->getFont()->setBold(true);
            
            // Asegurar que el directorio existe
            $directory = __DIR__ . '/../data/excel/';
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            
            // Guardar archivo
            $filename = 'reporte_admision_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = $directory . $filename;
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => '/microservices/back-admision/data/excel/' . $filename,
                'message' => 'Reporte generado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("Error al generar Excel: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al generar el archivo Excel: ' . $e->getMessage(),
                'filename' => null,
                'filepath' => null
            ];
        }
    }
    
    /**
     * Generar reporte de casos - método alternativo
     */
    public function generarReporteCasos($filtros = []) {
        return $this->generarReporteAsignaciones($filtros);
    }
    
    private function obtenerDatosReporte($filtros) {
        $sql = "SELECT 
                    c.*,
                    hu.nombre_completo as analista_nombre
                FROM casos c
                LEFT JOIN horarios_usuarios hu ON c.analista_id = hu.user_id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(c.fecha_ingreso) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(c.fecha_ingreso) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND c.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['analista_id'])) {
            $sql .= " AND c.analista_id = ?";
            $params[] = $filtros['analista_id'];
        }
        
        $sql .= " ORDER BY c.fecha_ingreso DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function traducirEstado($estado) {
        $traducciones = [
            'en_curso' => 'En Curso',
            'en_espera' => 'En Espera', 
            'resuelto' => 'Resuelto',
            'cancelado' => 'Cancelado'
        ];
        
        return $traducciones[$estado] ?? $estado;
    }
    
    /**
     * Método de respaldo en caso de que PhpSpreadsheet no esté disponible
     */
    public function generarCSVTemporal($datos, $nombre_archivo) {
        $ruta_archivo = __DIR__ . '/../data/excel/' . $nombre_archivo . '.csv';
        
        // Asegurar que el directorio existe
        if (!is_dir(dirname($ruta_archivo))) {
            mkdir(dirname($ruta_archivo), 0777, true);
        }
        
        // Crear archivo CSV básico
        $archivo = fopen($ruta_archivo, 'w');
        
        if ($archivo) {
            // Escribir encabezados si los datos son un array
            if (!empty($datos) && is_array($datos[0])) {
                fputcsv($archivo, array_keys($datos[0]));
            }
            
            // Escribir datos
            foreach ($datos as $fila) {
                fputcsv($archivo, $fila);
            }
            
            fclose($archivo);
            
            return [
                'success' => true,
                'message' => 'Archivo CSV generado temporalmente',
                'file_path' => $ruta_archivo
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al generar archivo CSV temporal'
        ];
    }
}
?>