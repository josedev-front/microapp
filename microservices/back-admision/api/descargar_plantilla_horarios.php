<?php
require_once __DIR__ . '/../init.php';

// Verificar permisos
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    http_response_code(403);
    echo 'No tienes permisos para descargar plantillas';
    exit;
}

$user_id = $_GET['user_id'] ?? 0;

// Cargar PhpSpreadsheet
require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título
    $sheet->setTitle('Plantilla Horarios');
    $sheet->setCellValue('A1', 'PLANTILLA DE HORARIOS - BACK ADMISIÓN');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Encabezados
    $headers = [
        'Día', 'Activo (SI/NO)', 'Hora Entrada', 'Hora Salida', 
        'Inicio Colación', 'Fin Colación', 'Observaciones'
    ];

    $sheet->fromArray($headers, null, 'A3');
    $sheet->getStyle('A3:G3')->getFont()->setBold(true);
    $sheet->getStyle('A3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');

    // Datos de ejemplo
    $dias_semana = [
        ['LUNES', 'SI', '09:00', '18:00', '13:00', '14:00', 'Jornada completa'],
        ['MARTES', 'SI', '09:00', '18:00', '13:00', '14:00', 'Jornada completa'],
        ['MIERCOLES', 'SI', '09:00', '18:00', '13:00', '14:00', 'Jornada completa'],
        ['JUEVES', 'SI', '09:00', '18:00', '13:00', '14:00', 'Jornada completa'],
        ['VIERNES', 'SI', '09:00', '18:00', '13:00', '14:00', 'Jornada completa'],
        ['SABADO', 'NO', '', '', '', '', 'Fin de semana'],
        ['DOMINGO', 'NO', '', '', '', '', 'Fin de semana']
    ];

    $sheet->fromArray($dias_semana, null, 'A4');

    // Estilos
    $sheet->getStyle('A4:G10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A3:G10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Autoajustar columnas
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Instrucciones
    $sheet->setCellValue('A12', 'INSTRUCCIONES:');
    $sheet->getStyle('A12')->getFont()->setBold(true);
    $sheet->setCellValue('A13', '1. "Activo": Usar SI para días laborales, NO para días no laborales');
    $sheet->setCellValue('A14', '2. Formato de horas: HH:MM (24 horas)');
    $sheet->setCellValue('A15', '3. No modificar la columna "Día"');
    $sheet->setCellValue('A16', '4. Guardar el archivo y usar la función de importar');

    // Headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="plantilla_horarios_usuario_' . $user_id . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al generar plantilla: ' . $e->getMessage();
}
?>