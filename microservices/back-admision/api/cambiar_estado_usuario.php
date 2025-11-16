<?php
// microservices/back-admision/api/cambiar_estado_usuario.php

// HEADERS PRIMERO
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// INICIAR SESIÓN (igual que en las vistas que funcionan)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CARGAR INIT PARA ACCEDER A backAdmision (IMPORTANTE)
require_once __DIR__ . '/../init.php';

// DEBUG: Verificar sesión
error_log("🎯 === CAMBIAR ESTADO API ===");
error_log("👤 User ID en session: " . ($_SESSION['user_id'] ?? 'NO'));
error_log("🎭 User Role en session: " . ($_SESSION['user_role'] ?? 'NO'));
error_log("🏢 Work Area en session: " . ($_SESSION['work_area'] ?? 'NO'));

// VERIFICAR AUTENTICACIÓN USANDO backAdmision (igual que las vistas)
if (!isset($backAdmision) || !$backAdmision->getUserId()) {
    error_log("❌ API: No autenticado o backAdmision no disponible");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado. Por favor inicia sesión nuevamente.',
        'redirect_url' => '/dashboard/vsm/microapp/public/?vista=login'
    ]);
    exit();
}

// VERIFICAR PERMISOS (igual que en panel-asignaciones)
$supervisor_id = $backAdmision->getUserId();
$user_role = $backAdmision->getUserRole();
$work_area = $backAdmision->getUserArea();

error_log("🔍 API - Verificando permisos: User {$supervisor_id}, Role {$user_role}, Area {$work_area}");

$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    error_log("❌ API - Sin permisos: Rol {$user_role} no permitido");
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => "Sin permisos. Rol actual: {$user_role}. Se requieren: " . implode(', ', $roles_permitidos)
    ]);
    exit();
}

// PROCESAR LA PETICIÓN
try {
    // Leer datos JSON
    $json_input = file_get_contents('php://input');
    $input = json_decode($json_input, true);
    
    error_log("📨 API - Datos recibidos: " . $json_input);

    // Validar datos requeridos
    if (!isset($input['user_id']) || !isset($input['estado'])) {
        throw new Exception('Datos incompletos: user_id y estado son requeridos');
    }

    $user_id_target = intval($input['user_id']);
    $nuevo_estado = $input['estado'];

    // Validar estado
    $estados_permitidos = ['activo', 'inactivo', 'colacion'];
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        throw new Exception('Estado no válido. Permitidos: ' . implode(', ', $estados_permitidos));
    }

    // Conectar a la base de datos
    $db = getBackAdmisionDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Verificar que el usuario target exista
    $stmt = $db->prepare("SELECT user_id, nombre_completo FROM horarios_usuarios WHERE user_id = ? AND activo = 1");
    $stmt->execute([$user_id_target]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception('Usuario no encontrado o inactivo: ' . $user_id_target);
    }

    // Obtener estado anterior
    $stmt = $db->prepare("SELECT estado FROM estado_usuarios WHERE user_id = ?");
    $stmt->execute([$user_id_target]);
    $estado_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    $estado_anterior = $estado_actual ? $estado_actual['estado'] : 'inactivo';

    // Actualizar estado
    $stmt = $db->prepare("
        INSERT INTO estado_usuarios (user_id, estado, ultima_actualizacion) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            ultima_actualizacion = VALUES(ultima_actualizacion)
    ");

    $result = $stmt->execute([$user_id_target, $nuevo_estado]);

    if (!$result) {
        throw new Exception('Error al actualizar estado en la base de datos');
    }

    // Éxito
    error_log("✅ API - Estado cambiado: User {$user_id_target} de '{$estado_anterior}' a '{$nuevo_estado}' por {$user_role} {$supervisor_id}");

    echo json_encode([
        'success' => true,
        'message' => "✅ Estado de {$usuario['nombre_completo']} cambiado de '{$estado_anterior}' a '{$nuevo_estado}'",
        'data' => [
            'nuevo_estado' => $nuevo_estado,
            'estado_anterior' => $estado_anterior,
            'usuario' => $usuario['nombre_completo'],
            'supervisor' => [
                'id' => $supervisor_id,
                'nombre' => $backAdmision->getUserName(),
                'role' => $user_role
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("💥 API - Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit();
?>