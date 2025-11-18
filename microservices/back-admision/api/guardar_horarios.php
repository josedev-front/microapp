<?php
// microservices/back-admision/api/guardar_horarios.php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;
    $horariosData = $input['horarios'] ?? [];
    
    if (!$user_id) {
        throw new Exception('ID de usuario requerido');
    }
    
    require_once __DIR__ . '/../controllers/TeamController.php';
    $teamController = new TeamController();
    
    // Procesar los datos del formulario para convertirlos al formato esperado
    $horariosProcesados = [];
    
    // Días de la semana
    $dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    
    foreach ($dias_semana as $dia) {
        $clave_activo = "horarios[{$dia}][activo]";
        $clave_entrada = "horarios[{$dia}][hora_entrada]";
        $clave_salida = "horarios[{$dia}][hora_salida]";
        $clave_almuerzo_inicio = "horarios[{$dia}][hora_almuerzo_inicio]";
        $clave_almuerzo_fin = "horarios[{$dia}][hora_almuerzo_fin]";
        
        $horariosProcesados[$dia] = [
            'activo' => isset($horariosData[$clave_activo]) && $horariosData[$clave_activo] === '1',
            'hora_entrada' => $horariosData[$clave_entrada] ?? null,
            'hora_salida' => $horariosData[$clave_salida] ?? null,
            'hora_almuerzo_inicio' => $horariosData[$clave_almuerzo_inicio] ?? null,
            'hora_almuerzo_fin' => $horariosData[$clave_almuerzo_fin] ?? null
        ];
    }
    
    $resultado = $teamController->actualizarHorarios($user_id, $horariosProcesados);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Horarios actualizados correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar horarios');
    }
    
} catch (Exception $e) {
    error_log("Error en guardar_horarios.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>