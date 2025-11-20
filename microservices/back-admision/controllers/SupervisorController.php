<?php
require_once __DIR__ . '/../models/Caso.php';
require_once __DIR__ . '/../models/UserSync.php';
require_once __DIR__ . '/../lib/AssignmentManager.php';

class SupervisorController {
    private $casoModel;
    private $userSync;
    private $assignmentManager;
    private $db;
    
    public function __construct() {
        $this->casoModel = new Caso();
        $this->userSync = new UserSync();
        $this->assignmentManager = new AssignmentManager();
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * 🔄 MÉTODO AUXILIAR: Obtener datos de usuario de sesión de forma segura
     */
    private function getSessionUserData() {
        return [
            'user_id' => $_SESSION['user_id'] ?? $_SESSION['id'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'work_area' => $_SESSION['work_area'] ?? '',
            'user_role' => $_SESSION['user_role'] ?? $_SESSION['role'] ?? ''
        ];
    }
    
    /**
     * 🔄 MÉTODO AUXILIAR: Obtener nombre completo del usuario de sesión
     */
    private function getSessionUserName() {
        $userData = $this->getSessionUserData();
        return trim($userData['first_name'] . ' ' . $userData['last_name']);
    }
    
    /**
     * Obtener estadísticas para el dashboard
     */
    public function getEstadisticasDashboard() {
        $db = getBackAdmisionDB();
        
        // Casos activos
        $stmt = $db->query("SELECT COUNT(*) as total FROM casos WHERE estado != 'resuelto'");
        $casos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Casos resueltos hoy
        $stmt = $db->query("SELECT COUNT(*) as total FROM casos WHERE estado = 'resuelto' AND DATE(fecha_actualizacion) = CURDATE()");
        $casos_resueltos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ejecutivos activos
        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM estado_usuarios WHERE estado = 'activo'");
        $ejecutivos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Promedio por ejecutivo
        $promedio = $ejecutivos_activos > 0 ? round($casos_activos / $ejecutivos_activos, 1) : 0;
        
        return [
            'casos_activos' => $casos_activos,
            'casos_resueltos' => $casos_resueltos,
            'ejecutivos_activos' => $ejecutivos_activos,
            'promedio_por_ejecutivo' => $promedio
        ];
    }
    
    /**
     * Asignación manual de caso por supervisor
     */
    public function asignarCasoManual($sr_hijo, $analista_id, $forzar_asignacion = false) {
        $userData = $this->getSessionUserData();
        
        // Verificar si la SR ya existe
        $caso_existente = $this->casoModel->getCasoPorSR($sr_hijo);
        
        // DEBUG TEMPORAL
        error_log("🔍 DEBUG asignarCasoManual:");
        error_log("SR: $sr_hijo, Analista: $analista_id, Forzar: $forzar_asignacion");
        error_log("Caso existente: " . ($caso_existente ? 'SÍ' : 'NO'));
        if ($caso_existente) {
            error_log("Analista actual: " . $caso_existente['analista_nombre']);
            error_log("Analista nuevo ID: $analista_id");
        }
        
        if ($caso_existente) {
            if (!$forzar_asignacion) {
                return [
                    'success' => false,
                    'message' => 'La SR ya está asignada a ' . $caso_existente['analista_nombre'],
                    'caso_existente' => $caso_existente
                ];
            } else {
                // Forzar reasignación
                return $this->reasignarCasoManual($sr_hijo, $analista_id);
            }
        } else {
            // Crear nuevo caso
            $analista_data = $this->userSync->getEjecutivoPorId($analista_id);
            
            if (!$analista_data) {
                return [
                    'success' => false,
                    'message' => 'Ejecutivo no encontrado'
                ];
            }
            
            $caso_data = [
                'sr_hijo' => $sr_hijo,
                'analista_id' => $analista_id,
                'analista_nombre' => $analista_data['nombre_completo'],
                'area_ejecutivo' => $analista_data['area'],
                'estado' => 'en_curso',
                'tipo_negocio' => 'Solicitud de revisión por backoffice',
                'asignacion_automatica' => false
            ];
            
            if ($this->casoModel->crearCaso($caso_data)) {
                // Log de asignación manual
                $this->assignmentManager->logAsignacion(
                    $sr_hijo,
                    $userData['user_id'],
                    'asignacion_manual',
                    [
                        'analista_asignado' => $analista_data['nombre_completo'],
                        'supervisor' => $this->getSessionUserName()
                    ]
                );
                
                return [
                    'success' => true,
                    'message' => 'Caso asignado correctamente a ' . $analista_data['nombre_completo']
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Error al asignar el caso'
        ];
    }
    
    /**
     * Reasignar caso manualmente
     */
    private function reasignarCasoManual($sr_hijo, $nuevo_analista_id) {
        $userData = $this->getSessionUserData();
        $analista_data = $this->userSync->getEjecutivoPorId($nuevo_analista_id);
        $caso_actual = $this->casoModel->getCasoPorSR($sr_hijo);
        
        if (!$analista_data) {
            return [
                'success' => false,
                'message' => 'Ejecutivo no encontrado'
            ];
        }
        
        // Verificar si ya está asignado al mismo ejecutivo
        if ($caso_actual['analista_id'] == $nuevo_analista_id) {
            return [
                'success' => false,
                'message' => 'La SR ya está asignada a este ejecutivo (' . $analista_data['nombre_completo'] . ')'
            ];
        }
        
        $actualizado = $this->casoModel->reasignarCaso(
            $sr_hijo, 
            $nuevo_analista_id, 
            $analista_data['nombre_completo']
        );
        
        if ($actualizado) {
            // Log de reasignación manual
            $this->assignmentManager->logAsignacion(
                $sr_hijo,
                $userData['user_id'],
                'reasignacion_manual',
                [
                    'analista_anterior' => $caso_actual['analista_nombre'],
                    'analista_nuevo' => $analista_data['nombre_completo'],
                    'supervisor' => $this->getSessionUserName()
                ]
            );
            
            return [
                'success' => true,
                'message' => 'Caso reasignado correctamente de ' . $caso_actual['analista_nombre'] . ' a ' . $analista_data['nombre_completo']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al reasignar el caso'
        ];
    }
    
    /**
     * Asignación equilibrada por supervisor
     */
    public function asignacionEquilibrada($sr_hijo) {
        require_once __DIR__ . '/../lib/LoadBalancer.php';
        
        $userData = $this->getSessionUserData();
        $loadBalancer = new LoadBalancer();
        $area = $userData['work_area'] ?? 'Depto Micro&amp;SOHO';
        
        $analista_id = $loadBalancer->asignarCasoEquilibrado($sr_hijo, $area);
        
        if ($analista_id) {
            $analista_data = $this->userSync->getEjecutivoPorId($analista_id);
            
            $caso_data = [
                'sr_hijo' => $sr_hijo,
                'analista_id' => $analista_id,
                'analista_nombre' => $analista_data['nombre_completo'],
                'area_ejecutivo' => $area,
                'estado' => 'en_curso',
                'tipo_negocio' => 'Solicitud de revisión por backoffice'
            ];
            
            if ($this->casoModel->crearCaso($caso_data)) {
                // Log de asignación equilibrada
                $this->assignmentManager->logAsignacion(
                    $sr_hijo,
                    $userData['user_id'],
                    'asignacion_equilibrada',
                    [
                        'analista_asignado' => $analista_data['nombre_completo'],
                        'supervisor' => $this->getSessionUserName()
                    ]
                );
                
                return [
                    'success' => true,
                    'message' => 'Caso asignado automáticamente a ' . $analista_data['nombre_completo'],
                    'analista' => $analista_data
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'No hay ejecutivos disponibles para asignación automática'
        ];
    }
    
    public function cambiarEstadoUsuario($user_id, $nuevo_estado, $supervisor_id = null) {
        try {
            // Validar parámetros
            if (!$user_id || !$nuevo_estado) {
                return [
                    'success' => false,
                    'message' => 'user_id y estado son requeridos'
                ];
            }
            
            // Verificar que el usuario existe en horarios_usuarios
            $stmt = $this->db->prepare("SELECT user_id FROM horarios_usuarios WHERE user_id = ? AND activo = 1");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o inactivo'
                ];
            }
            
            // Determinar el estado anterior
            $stmt = $this->db->prepare("SELECT estado FROM estado_usuarios WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $estado_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $estado_anterior = $estado_actual ? $estado_actual['estado'] : 'inactivo';
            
            // Insertar o actualizar estado
            $stmt = $this->db->prepare("
                INSERT INTO estado_usuarios (user_id, estado, ultima_actualizacion) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    estado = VALUES(estado),
                    ultima_actualizacion = VALUES(ultima_actualizacion)
            ");
            
            $result = $stmt->execute([$user_id, $nuevo_estado]);
            
            if (!$result) {
                throw new Exception('Error al actualizar estado en la base de datos');
            }
            
            // Registrar en logs
            $this->registrarCambioEstado($user_id, $estado_anterior, $nuevo_estado, $supervisor_id);
            
            return [
                'success' => true,
                'message' => "Estado cambiado exitosamente de '{$estado_anterior}' a '{$nuevo_estado}'",
                'nuevo_estado' => $nuevo_estado,
                'estado_anterior' => $estado_anterior
            ];
            
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoUsuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error del sistema: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar cambio de estado en logs
     */
    private function registrarCambioEstado($user_id, $estado_anterior, $estado_nuevo, $supervisor_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs_cambio_estado 
                (user_id, estado_anterior, estado_nuevo, supervisor_id, fecha_cambio) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$user_id, $estado_anterior, $estado_nuevo, $supervisor_id]);
            
        } catch (Exception $e) {
            error_log("Error al registrar log de estado: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estado actual de usuarios
     */
    public function getEstadosUsuarios() {
        $stmt = $this->db->prepare("
            SELECT 
                eu.user_id,
                hu.nombre_completo,
                eu.estado,
                eu.ultima_actualizacion,
                hu.area
            FROM estado_usuarios eu
            INNER JOIN horarios_usuarios hu ON eu.user_id = hu.user_id
            WHERE hu.activo = 1
            ORDER BY hu.nombre_completo
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener métricas de balance para panel
     */
    public function getMetricasBalancePanel() {
        $stmt = $this->db->prepare("
            SELECT 
                hu.user_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                eu.ultima_actualizacion,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() THEN c.id END) as casos_hoy,
                COUNT(CASE WHEN c.estado != 'resuelto' THEN c.id END) as casos_activos
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id
            WHERE hu.activo = 1 AND hu.area = 'Depto Micro&amp;SOHO'
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
            ORDER BY casos_hoy ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todos los casos del sistema
     */
    public function getAllCasos($filtros = []) {
        return $this->casoModel->getAllCasos($filtros);
    }
}
?>