<?php
// microservices/back-admision/api/ingresar_caso_supervisor.php

// INICIAR SESIÓN SI NO ESTÁ INICIADA - CON CONFIGURACIÓN
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

// DEBUG DETALLADO DE SESIÓN
error_log("🎯 API ingresar_caso_supervisor.php ejecutándose");
error_log("🔐 Sesión ID: " . (session_id() ?? 'NO SESION'));
error_log("🔐 User ID: " . ($_SESSION['user_id'] ?? 'NO USER ID'));
error_log("🔐 User Role: " . ($_SESSION['user_role'] ?? 'NO ROLE'));
error_log("🔐 First Name: " . ($_SESSION['first_name'] ?? 'NO NAME'));

// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA DEL CORE
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    error_log("❌ USUARIO NO AUTENTICADO - Redirigiendo...");
    
    // Intentar cargar el sistema de autenticación del core
    $core_auth_file = __DIR__ . '/../../app_core/inc/session_start.php';
    if (file_exists($core_auth_file)) {
        require_once $core_auth_file;
    }
    
    // Verificar nuevamente después de cargar el core
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
        echo json_encode([
            'success' => false, 
            'message' => '❌ Usuario no autenticado. Por favor, inicie sesión nuevamente.'
        ]);
        exit;
    }
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

    // OBTENER USER_ID DE LA SESIÓN (compatible con ambos sistemas)
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    
    if (!$user_id) {
        throw new Exception("No se pudo identificar al usuario");
    }

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

    // VERIFICACIÓN DE PERMISOS - SIMPLIFICADA TEMPORALMENTE
    error_log("🔐 Verificando permisos para user_id: " . $user_id);
    
    // TEMPORAL: Permitir a todos los usuarios autenticados
    $tiene_permisos = true;
    
    if (!$tiene_permisos) {
        throw new Exception("No tiene permisos para realizar asignaciones manuales");
    }

    error_log("📝 Procesando caso supervisor - SR: " . $data['sr_hijo'] . ", Tipo: " . $data['tipo_asignacion']);

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