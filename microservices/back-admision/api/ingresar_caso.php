<?php
// microservices/back-admision/api/ingresar_caso.php

// INICIAR SESIÓN SI NO ESTÁ INICIADA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// HEADER JSON INMEDIATAMENTE
header('Content-Type: application/json');

// DESACTIVAR OUTPUT BUFFERING
if (ob_get_level()) ob_clean();

try {
    // VERIFICAR MÉTODO POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido. Se requiere POST.");
    }

    // VERIFICAR AUTENTICACIÓN
    if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
        throw new Exception("Usuario no autenticado.");
    }

    // CARGAR CONEXIÓN A BD Y LIBRERÍAS
    require_once __DIR__ . '/../config/database_back_admision.php';
    require_once __DIR__ . '/../lib/LoadBalancer.php';
    require_once __DIR__ . '/../controllers/AdmissionController.php';
    require_once __DIR__ . '/../models/Caso.php';

    $db = getBackAdmisionDB();
    $admissionController = new AdmissionController();
    $loadBalancer = new LoadBalancer();

    // OBTENER DATOS DEL USUARIO
    $user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    $user_nombre = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    $user_area = $_SESSION['work_area'] ?? 'Depto Micro&SOHO';

    $sr_hijo = trim($_POST['sr_hijo'] ?? '');
    $confirmar_reasignacion = isset($_POST['confirmar_reasignacion']) && $_POST['confirmar_reasignacion'] == '1';

    // VALIDACIÓN BÁSICA
    if (empty($sr_hijo)) {
        throw new Exception("El número de SR es requerido");
    }

    // VERIFICAR SI LA SR YA EXISTE
    $caso_existente = $admissionController->getCasoPorSR($sr_hijo);
    
    // 🔄 REGLA 1: Si la SR YA EXISTE y está asignada al USUARIO ACTUAL
    if ($caso_existente && $caso_existente['analista_id'] == $user_id) {
        error_log("✅ SR $sr_hijo ya asignada al usuario actual");
        
        $response = [
            'success' => true, 
            'message' => 'Este caso ya se encontraba asignado a tu cuenta',
            'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo),
            'tipo' => 'info_existente_propio'
        ];
    }
    // 🔄 REGLA 2: Si la SR YA EXISTE y está asignada a OTRO EJECUTIVO
    else if ($caso_existente && $caso_existente['analista_id'] != $user_id) {
        
        // Si viene de confirmación de reasignación
        if ($confirmar_reasignacion) {
            error_log("🔄 Reasignando SR $sr_hijo de {$caso_existente['analista_nombre']} a $user_nombre");
            
            // Actualizar asignación
            $stmt = $db->prepare("
                UPDATE casos 
                SET analista_id = ?, analista_nombre = ?, fecha_actualizacion = NOW() 
                WHERE sr_hijo = ?
            ");
            
            $actualizado = $stmt->execute([$user_id, $user_nombre, $sr_hijo]);
            
            if (!$actualizado) {
                throw new Exception("Error al reasignar el caso");
            }
            
            $response = [
                'success' => true, 
                'message' => '✅ Caso reasignado correctamente a tu cuenta',
                'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo),
                'tipo' => 'reasignado_exitoso'
            ];
            
        } else {
            // Solicitar confirmación de reasignación
            error_log("⚠️ SR $sr_hijo asignada a otro ejecutivo: {$caso_existente['analista_nombre']}");
            
            $response = [
                'success' => false, 
                'message' => 'confirmar_reasignacion',
                'detalles' => "Este caso ya se encontraba asignado a {$caso_existente['analista_nombre']}. ¿Desea reasignarlo a su cuenta?",
                'sr_hijo' => $sr_hijo,
                'tipo' => 'necesita_confirmacion'
            ];
        }
    }
    // 🔄 REGLA 3: SR NUEVA - ASIGNACIÓN EQUILIBRADA
    else {
        error_log("🆕 SR $sr_hijo es nueva - asignando equilibradamente");
        
        // ASIGNAR AL EJECUTIVO CON MENOR CARGA (no auto-asignar)
        $analista_asignado_id = $loadBalancer->asignarCasoEquilibrado($sr_hijo, $user_area);
        
        if (!$analista_asignado_id) {
            throw new Exception("No hay ejecutivos disponibles para asignar el caso en el área $user_area");
        }
        
        // Obtener nombre del ejecutivo asignado
        $analista_asignado_nombre = $this->obtenerNombreEjecutivo($analista_asignado_id, $db);
        
        // INSERTAR EN BASE DE DATOS CON EJECUTIVO BALANCEADO
        $stmt = $db->prepare("
            INSERT INTO casos 
            (sr_hijo, analista_id, analista_nombre, area_ejecutivo, estado, tipo_negocio, fecha_ingreso, asignacion_automatica)
            VALUES (?, ?, ?, ?, 'en_curso', 'Solicitud de revisión por backoffice', NOW(), 1)
        ");
        
        $insertado = $stmt->execute([
            $sr_hijo,
            $analista_asignado_id,      // ← EJECUTIVO BALANCEADO, NO USUARIO ACTUAL
            $analista_asignado_nombre,  // ← NOMBRE DEL EJECUTIVO BALANCEADO  
            $user_area
        ]);

        if (!$insertado) {
            throw new Exception("Error al guardar en base de datos");
        }

        // Si el caso fue asignado al usuario actual, redirigir a gestión
        if ($analista_asignado_id == $user_id) {
            $redirect_url = './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo);
            $mensaje = '✅ Caso asignado correctamente a tu bandeja';
        } else {
            // Si fue asignado a otro ejecutivo, redirigir al menú
            $redirect_url = './?vista=back-admision';
            $mensaje = "✅ Caso asignado a $analista_asignado_nombre (sistema balanceado)";
        }
        
        $response = [
            'success' => true, 
            'message' => $mensaje,
            'redirect' => $redirect_url,
            'debug' => [
                'bd_affected' => $stmt->rowCount(),
                'sr_guardada' => $sr_hijo,
                'analista_asignado' => $analista_asignado_nombre,
                'asignacion_automatica' => true
            ]
        ];
    }

} catch (Exception $e) {
    error_log("❌ ERROR en ingresar_caso.php: " . $e->getMessage());
    $response = [
        'success' => false, 
        'message' => '❌ ' . $e->getMessage()
    ];
}

// ENVIAR Y TERMINAR
echo json_encode($response);
exit;

/**
 * Función auxiliar para obtener nombre del ejecutivo
 */
function obtenerNombreEjecutivo($user_id, $db) {
    try {
        // Primero intentar con la tabla de horarios
        $stmt = $db->prepare("
            SELECT nombre_completo FROM horarios_usuarios 
            WHERE user_id = ? LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['nombre_completo'])) {
            return $result['nombre_completo'];
        }
        
        // Si no existe, buscar en la base de datos principal
        require_once __DIR__ . '/../../app_core/config/database.php';
        $db_core = getDB();
        
        $stmt = $db_core->prepare("
            SELECT first_name, last_name FROM core_customuser 
            WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return trim($result['first_name'] . ' ' . $result['last_name']);
        }
        
        return "Ejecutivo $user_id";
        
    } catch (Exception $e) {
        error_log("Error obteniendo nombre ejecutivo: " . $e->getMessage());
        return "Ejecutivo $user_id";
    }
}
?>