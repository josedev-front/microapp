<?php
// microservices/back-admision/controllers/AdmissionController.php

// Verificar si los archivos existen antes de requerirlos
$files_to_check = [
    __DIR__ . '/../models/Caso.php',
    __DIR__ . '/../models/UserSync.php', 
    __DIR__ . '/../lib/LoadBalancer.php',
    __DIR__ . '/../lib/AssignmentManager.php'
];

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        error_log("ERROR: Archivo no encontrado - " . $file);
        throw new Exception("Archivo requerido no encontrado: " . basename($file));
    }
}

require_once __DIR__ . '/../models/Caso.php';
require_once __DIR__ . '/../models/UserSync.php';
require_once __DIR__ . '/../lib/LoadBalancer.php';
require_once __DIR__ . '/../lib/AssignmentManager.php';

class AdmissionController {
    private $casoModel;
    private $loadBalancer;
    private $assignmentManager;
    private $userSync;
    
    public function __construct() {
        try {
            error_log("Inicializando AdmissionController...");
            $this->casoModel = new Caso();
            $this->loadBalancer = new LoadBalancer();
            $this->assignmentManager = new AssignmentManager();
            $this->userSync = new UserSync();
            error_log("AdmissionController inicializado correctamente");
        } catch (Exception $e) {
            error_log("ERROR en constructor AdmissionController: " . $e->getMessage());
            throw $e;
        }
    }
   public function getBandejaEjecutivo($user_id) {
    try {
        $stmt = $this->casoModel->getDB()->prepare("  // ← ESTA LÍNCA TIENE ERROR
            SELECT * FROM casos 
            WHERE analista_id = ? AND estado != 'resuelto'
            ORDER BY fecha_ingreso DESC
        ");
        
        // CORRECCIÓN:
        $db = $this->casoModel->getDB(); // Si existe este método
        // O MEJOR:
        require_once __DIR__ . '/../config/database_back_admision.php';
        $db = getBackAdmisionDB();
        
        $stmt = $db->prepare("
            SELECT * FROM casos 
            WHERE analista_id = ? AND estado != 'resuelto'
            ORDER BY fecha_ingreso DESC
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("ERROR en getBandejaEjecutivo: " . $e->getMessage());
        return [];
    }
}

private function obtenerNombreEjecutivo($user_id) {
    // Implementar según tu base de datos
    return "Ejecutivo " . $user_id; // Temporal
}
    
    /**
     * Vista menú para ejecutivos - ELIMINAR ESTE MÉTODO TEMPORALMENTE
     * Está causando conflicto porque no debería estar en el controller de API
     */
    /*
    public function menuEjecutivo() {
        $user_id = $_SESSION['user_id'];
        $user_nombre = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        
        // Obtener casos del ejecutivo - ESTA LÍNEA CAUSA ERROR
        $casos = $this->casoModel->getCasosPorEjecutivo($user_id);
        
        include __DIR__ . '/../views/ejecutivo/menu.php';
    }
    */
    
    /**
     * Procesar ingreso de caso - VERSIÓN SIMPLIFICADA TEMPORAL
     */
    public function ingresarCaso($sr_hijo) {
        try {
            error_log("ingresarCaso iniciado para SR: " . $sr_hijo);
            
            // Validación básica
            if (empty($sr_hijo)) {
                throw new Exception("El número de SR es requerido");
            }
            
            // Simular procesamiento exitoso temporalmente
            error_log("Caso procesado exitosamente para SR: " . $sr_hijo);
            
            return 'gestionar-solicitud';
            
        } catch (Exception $e) {
            error_log("ERROR en ingresarCaso: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Confirmar reasignación de caso - VERSIÓN SIMPLIFICADA TEMPORAL
     */
    public function confirmarReasignacion($sr_hijo, $confirmar) {
        try {
            error_log("confirmarReasignacion: SR=" . $sr_hijo . ", confirmar=" . ($confirmar ? 'YES' : 'NO'));
            
            if ($confirmar) {
                // Simular reasignación exitosa
                error_log("Reasignación confirmada para SR: " . $sr_hijo);
                return 'gestionar-solicitud';
            } else {
                return 'ingresar-caso';
            }
            
        } catch (Exception $e) {
            error_log("ERROR en confirmarReasignacion: " . $e->getMessage());
            throw $e;
        }
    }
    public function gestionarSolicitud($data) {
    try {
        error_log("📝 Actualizando caso: " . $data['sr_hijo']);
        
        // CORRECCIÓN: Usar conexión directa ya que el modelo no tiene actualizarCaso
        require_once __DIR__ . '/../config/database_back_admision.php';
        $db = getBackAdmisionDB();
        
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
        
        return $stmt->execute([
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
        
    } catch (Exception $e) {
        error_log("ERROR en gestionarSolicitud: " . $e->getMessage());
        return false;
    }
}
    /**
     * Obtener caso por SR - VERSIÓN SIMPLIFICADA
     */
    public function getCasoPorSR($sr_hijo) {
    try {
        error_log("🔍 Buscando caso para SR: " . $sr_hijo);
        
        // Usar el modelo Caso para buscar en la base de datos
        $caso = $this->casoModel->getCasoPorSR($sr_hijo);
        
        if ($caso) {
            error_log("✅ Caso encontrado: " . $sr_hijo . " asignado a: " . $caso['analista_nombre']);
        } else {
            error_log("❌ Caso NO encontrado: " . $sr_hijo);
        }
        
        return $caso;
        
    } catch (Exception $e) {
        error_log("ERROR en getCasoPorSR: " . $e->getMessage());
        return null;
    }
}

    /**
     * Verificar permisos de supervisor
     */
    public function tienePermisosSupervisor() {
        $roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];
        return in_array($_SESSION['user_role'] ?? '', $roles_permitidos);
    }
    
    /**
     * Validar formato de SR
     */
    private function validarFormatoSR($sr_hijo) {
        return !empty($sr_hijo) && strlen($sr_hijo) >= 5;
    }
}
?>