<?php
// microservices/back-admision/api/descargar_excel.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/ReportController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = [
        'fecha_desde' => $_POST['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days')),
        'fecha_hasta' => $_POST['fecha_hasta'] ?? date('Y-m-d'),
        'tipo_reporte' => $_POST['tipo_reporte'] ?? 'completo'
    ];
    
    $reportController = new ReportController();
    $resultado = $reportController->generarReporteExcel($filtros);
    
    if ($resultado && file_exists($resultado['filepath'])) {
        echo json_encode([
            'success' => true,
            'download_url' => $resultado['download_url'],
            'filename' => $resultado['filename']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al generar el archivo Excel'
        ]);
    }
}
?>