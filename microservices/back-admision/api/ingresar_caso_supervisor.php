<?php
// microservices/back-admision/api/ingresar_caso_supervisor.php - CORREGIDO

// INICIAR SESIÓN SI NO ESTÁ INICIADA
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// HEADER JSON INMEDIATAMENTE
header('Content-Type: application/json');

// DESACTIVAR OUTPUT BUFFERING
if (ob_get_level()) ob_clean();

// DEBUG DETALLADO DE SESIÓN - CORREGIDO
error_log("🎯 API ingresar_caso_supervisor.php ejecutándose");
error_log("🔐 Sesión ID: " . (session_id() ?? 'NO SESION'));
error_log("🔐 User ID: " . ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 'NO USER ID'));
error_log("🔐 User Role: " . ($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'NO ROLE'));

// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA DEL CORE
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;

if (!$user_id) {
    echo json_encode([
        'success' => false, 
        'message' => '❌ Usuario no autenticado. Por favor, inicie sesión nuevamente.'
    ]);
    exit;
}

try {
    // VERIFICAR MÉTODO POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido. Se requiere POST.");
    }

    // CARGAR CONEXIÓN A BD Y CONTROLADORES
    require_once __DIR__ . '/../config/database_back_admision.php';
    require_once __DIR__ . '/../controllers/SupervisorController.php';
    require_once __DIR__ . '/../controllers/TeamController.php';
    require_once __DIR__ . '/../controllers/AdmissionController.php';

    $db = getBackAdmisionDB();
    $supervisorController = new SupervisorController();
    $teamController = new TeamController();
    $admissionController = new AdmissionController();

    // OBTENER DATOS DEL FORMULARIO
    $data = [
        'sr_hijo' => trim($_POST['sr_hijo'] ?? ''),
        'analista_id' => $_POST['analista_id'] ?? null,
        'tipo_asignacion' => $_POST['tipo_asignacion'] ?? 'supervisor',
        'forzar_asignacion' => isset($_POST['forzar_asignacion']) ? 1 : 0
    ];

    // VALIDACIÓN BÁSICA
    if (empty($data['sr_hijo'])) {
        throw new Exception("El número de SR hijo es requerido");
    }

    // VERIFICACIÓN DE PERMISOS
    $roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];
    $user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    
    if ($data['tipo_asignacion'] === 'supervisor' && !in_array($user_role, $roles_permitidos)) {
    throw new Exception("No tiene permisos para realizar asignaciones manuales. Rol actual: " . $user_role);
    }
    
    error_log("📝 Procesando caso - SR: {$data['sr_hijo']}, Tipo: {$data['tipo_asignacion']}, User: {$user_id}");

    // PROCESAR SEGÚN TIPO DE ASIGNACIÓN
    if ($data['tipo_asignacion'] === 'ejecutivo') {
        // ASIGNACIÓN AUTOMÁTICA (como ejecutivo)
        $resultado = $admissionController->procesarCaso($data['sr_hijo'], $user_id);
    } else {
        // ASIGNACIÓN MANUAL (como supervisor)
        if (empty($data['analista_id'])) {
            throw new Exception("Debe seleccionar un ejecutivo para asignación manual");
        }
        
        $resultado = $supervisorController->asignarCasoManual(
            $data['sr_hijo'], 
            $data['analista_id'],
            $data['forzar_asignacion']
        );
    }

    // VERIFICAR RESULTADO
    if (!$resultado['success']) {
        throw new Exception($resultado['message']);
    }

    error_log("✅ Caso asignado exitosamente: " . $data['sr_hijo']);

    // PREPARAR RESPUESTA
    $response = [
        'success' => true,
        'message' => $resultado['message'],
        'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($data['sr_hijo'])
    ];

} catch (Exception $e) {
    error_log("❌ ERROR en ingresar_caso_supervisor.php: " . $e->getMessage());
    $response = [
        'success' => false, 
        'message' => '❌ ' . $e->getMessage()
    ];
}

// ENVIAR Y TERMINAR
echo json_encode($response);
exit;
?>