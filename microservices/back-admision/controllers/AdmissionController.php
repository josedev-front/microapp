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
        return "Ejecutivo " . $user_id;
    }
    
    /**
     * Procesar ingreso de caso
     */
    public function procesarCaso($sr_hijo, $user_id = null) {
        try {
            error_log("procesarCaso iniciado para SR: " . $sr_hijo);
            
            if (empty($sr_hijo)) {
                return ['success' => false, 'message' => 'El número de SR es requerido'];
            }
            
            // Si no se proporciona user_id, usar el de la sesión
            if (!$user_id && isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
            }
            
            // Verificar si la SR ya existe
            $caso_existente = $this->casoModel->getCasoPorSR($sr_hijo);
            
            if ($caso_existente) {
                // Si el caso ya está asignado al mismo usuario
                if ($caso_existente['analista_id'] == $user_id) {
                    return [
                        'success' => true,
                        'message' => 'Este caso ya se encontraba asignado a tu cuenta',
                        'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo)
                    ];
                } else {
                    // Caso asignado a otro ejecutivo
                    return [
                        'success' => false,
                        'message' => 'Este caso ya se encontraba asignado a otro ejecutivo: ' . $caso_existente['analista_nombre'],
                        'caso_existente' => $caso_existente,
                        'necesita_confirmacion' => true
                    ];
                }
            } else {
                // Asignar caso nuevo
                $user_data = $this->userSync->getEjecutivoPorId($user_id);
                
                if (!$user_data) {
                    return ['success' => false, 'message' => 'Usuario no encontrado'];
                }
                
                $caso_data = [
                    'sr_hijo' => $sr_hijo,
                    'analista_id' => $user_id,
                    'analista_nombre' => $user_data['nombre_completo'],
                    'area_ejecutivo' => $user_data['area'],
                    'estado' => 'en_curso',
                    'tipo_negocio' => 'Solicitud de revisión por backoffice'
                ];
                
                if ($this->casoModel->crearCaso($caso_data)) {
                    $this->assignmentManager->logAsignacion(
                        $sr_hijo,
                        $user_id,
                        'asignacion_automatica',
                        ['analista' => $user_data['nombre_completo']]
                    );
                    
                    return [
                        'success' => true,
                        'message' => 'Caso asignado correctamente',
                        'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo)
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Error al procesar el caso'];
            
        } catch (Exception $e) {
            error_log("ERROR en procesarCaso: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Confirmar reasignación de caso
     */
    public function confirmarReasignacion($sr_hijo, $user_id) {
        try {
            error_log("confirmarReasignacion: SR=" . $sr_hijo . ", user_id=" . $user_id);
            
            $user_data = $this->userSync->getEjecutivoPorId($user_id);
            $caso_actual = $this->casoModel->getCasoPorSR($sr_hijo);
            
            if (!$user_data) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
            
            if (!$caso_actual) {
                return ['success' => false, 'message' => 'Caso no encontrado'];
            }
            
            // Reasignar caso
            $actualizado = $this->casoModel->reasignarCaso(
                $sr_hijo, 
                $user_id, 
                $user_data['nombre_completo']
            );
            
            if ($actualizado) {
                $this->assignmentManager->logAsignacion(
                    $sr_hijo,
                    $user_id,
                    'reasignacion_manual',
                    [
                        'analista_anterior' => $caso_actual['analista_nombre'],
                        'analista_nuevo' => $user_data['nombre_completo']
                    ]
                );
                
                return [
                    'success' => true,
                    'message' => 'Caso reasignado correctamente a ' . $user_data['nombre_completo'],
                    'redirect' => './?vista=back-admision&action=gestionar-solicitud&sr=' . urlencode($sr_hijo)
                ];
            }
            
            return ['success' => false, 'message' => 'Error al reasignar el caso'];
            
        } catch (Exception $e) {
            error_log("ERROR en confirmarReasignacion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    public function gestionarSolicitud($data) {
        try {
            error_log("📝 Actualizando caso: " . $data['sr_hijo']);
            
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
     * Obtener caso por SR
     */
    public function getCasoPorSR($sr_hijo) {
        try {
            error_log("🔍 Buscando caso para SR: " . $sr_hijo);
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
     * Verificar permisos de supervisor - CORREGIDO
     */
    public function tienePermisosSupervisor() {
        // Obtener rol del usuario desde la sesión o base de datos
        $user_role = $this->obtenerRolUsuario();
        
        $roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];
        
        error_log("🔐 Verificando permisos - Rol: " . $user_role . ", Permitidos: " . implode(', ', $roles_permitidos));
        
        return in_array($user_role, $roles_permitidos);
    }
    
    /**
     * Obtener rol del usuario - NUEVO MÉTODO
     */
    private function obtenerRolUsuario() {
        // Primero intentar desde la sesión
        if (isset($_SESSION['role'])) {
            return $_SESSION['role'];
        }
        
        // Si no está en sesión, intentar desde los datos de usuario
        if (isset($_SESSION['user_id'])) {
            try {
                $user_data = $this->userSync->getEjecutivoPorId($_SESSION['user_id']);
                if ($user_data && isset($user_data['role'])) {
                    return $user_data['role'];
                }
            } catch (Exception $e) {
                error_log("Error obteniendo rol desde BD: " . $e->getMessage());
            }
        }
        
        // Si no se puede determinar, usar el rol por defecto de la sesión
        return $_SESSION['user_role'] ?? 'ejecutivo';
    }

    /**
     * Validar formato de SR
     */
    private function validarFormatoSR($sr_hijo) {
        return !empty($sr_hijo) && strlen($sr_hijo) >= 5;
    }
}
?>