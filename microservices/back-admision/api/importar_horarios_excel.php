<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar permisos
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para importar horarios']);
    exit;
}

try {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se ha subido ningún archivo o hay un error en la carga');
    }

    $user_id = $_POST['user_id'] ?? 0;
    if (!$user_id) {
        throw new Exception('ID de usuario no válido');
    }

    // Cargar PhpSpreadsheet
    require_once __DIR__ . '/../../../../vendor/autoload.php';

    $inputFileName = $_FILES['archivo']['tmp_name'];
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();

    $horarios = [];
    $dias_map = [
        'LUNES' => 'lunes',
        'MARTES' => 'martes', 
        'MIERCOLES' => 'miercoles',
        'JUEVES' => 'jueves',
        'VIERNES' => 'viernes',
        'SABADO' => 'sabado',
        'DOMINGO' => 'domingo'
    ];

    // Leer datos (filas 4 a 10)
    for ($row = 4; $row <= 10; $row++) {
        $dia_excel = $sheet->getCell('A' . $row)->getValue();
        $activo = strtoupper($sheet->getCell('B' . $row)->getValue()) === 'SI';
        $entrada = $sheet->getCell('C' . $row)->getValue();
        $salida = $sheet->getCell('D' . $row)->getValue();
        $almuerzo_inicio = $sheet->getCell('E' . $row)->getValue();
        $almuerzo_fin = $sheet->getCell('F' . $row)->getValue();

        if (isset($dias_map[$dia_excel])) {
            $dia = $dias_map[$dia_excel];
            $horarios[$dia] = [
                'activo' => $activo,
                'hora_entrada' => $entrada ?: '09:00',
                'hora_salida' => $salida ?: '18:00',
                'hora_almuerzo_inicio' => $almuerzo_inicio ?: '13:00',
                'hora_almuerzo_fin' => $almuerzo_fin ?: '14:00'
            ];
        }
    }

    // Guardar horarios
    require_once __DIR__ . '/../controllers/TeamController.php';
    $teamController = new TeamController();
    $result = $teamController->guardarHorariosUsuario($user_id, $horarios);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Horarios importados correctamente',
            'horarios_importados' => count($horarios)
        ]);
    } else {
        throw new Exception('Error al guardar los horarios importados');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al importar: ' . $e->getMessage()
    ]);
}
?>