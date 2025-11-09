<?php
// microservices/back-admision/api/gestionar_caso.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/AdmissionController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admissionController = new AdmissionController();
    
    $data = [
        'sr_hijo' => $_POST['sr_hijo'] ?? '',
        'srp' => $_POST['srp'] ?? null,
        'estado' => $_POST['estado'] ?? 'en_curso',
        'tiket' => $_POST['tiket'] ?? null,
        'motivo_tiket' => $_POST['motivo_tiket'] ?? null,
        'observaciones' => $_POST['observaciones'] ?? null,
        'biometria' => $_POST['biometria'] ?? null,
        'inicio_actividades' => $_POST['inicio_actividades'] ?? null,
        'acreditacion' => $_POST['acreditacion'] ?? null
    ];
    
    if (empty($data['sr_hijo'])) {
        echo json_encode(['success' => false, 'message' => 'SR hijo es requerido']);
        exit;
    }
    
    $actualizado = $admissionController->gestionarSolicitud($data);
    
    if ($actualizado) {
        $accion = $_POST['accion'] ?? 'guardar';
        
        if ($accion === 'guardar_cerrar') {
            echo json_encode([
                'success' => true, 
                'message' => 'Cambios guardados correctamente',
                'redirect' => '/?vista=back-admision'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'Cambios guardados correctamente',
                'redirect' => '/?vista=admision-gestionar-solicitud&sr=' . urlencode($data['sr_hijo'])
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar los cambios'
        ]);
    }
}
?>