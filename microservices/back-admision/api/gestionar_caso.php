<?php
// microservices/back-admision/api/gestionar_caso.php

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

    // CARGAR CONEXIÓN A BD Y CONTROLADOR
    require_once __DIR__ . '/../config/database_back_admision.php';
    require_once __DIR__ . '/../controllers/AdmissionController.php';

    $db = getBackAdmisionDB();
    $admissionController = new AdmissionController();

    // OBTENER DATOS DEL FORMULARIO
    $data = [
        'sr_hijo' => trim($_POST['sr_hijo'] ?? ''),
        'srp' => trim($_POST['srp'] ?? ''),
        'estado' => $_POST['estado'] ?? 'en_curso',
        'tiket' => trim($_POST['tiket'] ?? ''),
        'motivo_tiket' => trim($_POST['motivo_tiket'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? ''),
        'biometria' => $_POST['biometria'] ?? null,
        'inicio_actividades' => $_POST['inicio_actividades'] ?? null,
        'acreditacion' => $_POST['acreditacion'] ?? null,
        'accion' => $_POST['accion'] ?? 'guardar'
    ];

    // VALIDACIÓN BÁSICA
    if (empty($data['sr_hijo'])) {
        throw new Exception("El número de SR hijo es requerido");
    }

    // ACTUALIZAR EN BASE DE DATOS
    $stmt = $db->prepare("
        UPDATE casos 
        SET 
            srp = ?,
            estado = ?,
            tiket = ?,
            motivo_tiket = ?,
            observaciones = ?,
            biometria = ?,
            inicio_actividades = ?,
            acreditacion = ?,
            fecha_actualizacion = NOW()
        WHERE sr_hijo = ?
    ");
    
    $actualizado = $stmt->execute([
        $data['srp'],
        $data['estado'],
        $data['tiket'],
        $data['motivo_tiket'],
        $data['observaciones'],
        $data['biometria'],
        $data['inicio_actividades'],
        $data['acreditacion'],
        $data['sr_hijo']
    ]);

    if (!$actualizado) {
        throw new Exception("Error al guardar los cambios en la base de datos");
    }

    // PREPARAR RESPUESTA SEGÚN LA ACCIÓN
    if ($data['accion'] === 'guardar_cerrar') {
        $response = [
            'success' => true, 
            'message' => '✅ Cambios guardados correctamente. Redirigiendo a la bandeja...',
            'redirect' => './?vista=back-admision'
        ];
    } else {
        $response = [
            'success' => true, 
            'message' => '✅ Cambios guardados correctamente',
            'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($data['sr_hijo'])
        ];
    }

} catch (Exception $e) {
    error_log("❌ ERROR en gestionar_caso.php: " . $e->getMessage());
    $response = [
        'success' => false, 
        'message' => '❌ ' . $e->getMessage()
    ];
}

// ENVIAR Y TERMINAR
echo json_encode($response);
exit;
?>