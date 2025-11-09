<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';

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
            $sheet->setCellValue('A' . $row, $caso['sr_hijo']);
            $sheet->setCellValue('B' . $row, $caso['srp']);
            $sheet->setCellValue('C' . $row, $this->traducirEstado($caso['estado']));
            $sheet->setCellValue('D' . $row, $caso['fecha_ingreso']);
            $sheet->setCellValue('E' . $row, $caso['tiket']);
            $sheet->setCellValue('F' . $row, $caso['analista_nombre']);
            $sheet->setCellValue('G' . $row, $caso['motivo_tiket']);
            $sheet->setCellValue('H' . $row, $caso['tipo_negocio']);
            $sheet->setCellValue('I' . $row, $caso['observaciones']);
            $sheet->setCellValue('J' . $row, $caso['biometria']);
            $sheet->setCellValue('K' . $row, $caso['acreditacion']);
            $sheet->setCellValue('L' . $row, $caso['inicio_actividades']);
            $row++;
        }
        
        // Autoajustar columnas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Estilos
        $sheet->getStyle('A5:L5')->getFont()->setBold(true);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        
        // Guardar archivo
        $filename = 'reporte_admision_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = __DIR__ . '/../data/excel/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'download_url' => '/microservices/back-admision/data/excel/' . $filename
        ];
    }
    
    private function obtenerDatosReporte($filtros) {
        $sql = "SELECT * FROM casos WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(fecha_ingreso) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(fecha_ingreso) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        $sql .= " ORDER BY fecha_ingreso DESC";
        
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
}
?>