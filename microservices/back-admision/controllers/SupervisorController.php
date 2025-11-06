<?php
require_once __DIR__ . '/../models/Caso.php';
require_once __DIR__ . '/../models/UserSync.php';
require_once __DIR__ . '/../lib/AssignmentManager.php';

class SupervisorController {
    private $casoModel;
    private $userSync;
    private $assignmentManager;
    
    public function __construct() {
        $this->casoModel = new Caso();
        $this->userSync = new UserSync();
        $this->assignmentManager = new AssignmentManager();
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
        // Verificar si la SR ya existe
        $caso_existente = $this->casoModel->getCasoPorSR($sr_hijo);
        
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
                    $_SESSION['user_id'],
                    'asignacion_manual',
                    [
                        'analista_asignado' => $analista_data['nombre_completo'],
                        'supervisor' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
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
        $analista_data = $this->userSync->getEjecutivoPorId($nuevo_analista_id);
        $caso_actual = $this->casoModel->getCasoPorSR($sr_hijo);
        
        if (!$analista_data) {
            return [
                'success' => false,
                'message' => 'Ejecutivo no encontrado'
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
                $_SESSION['user_id'],
                'reasignacion_manual',
                [
                    'analista_anterior' => $caso_actual['analista_nombre'],
                    'analista_nuevo' => $analista_data['nombre_completo'],
                    'supervisor' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
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
        
        $loadBalancer = new LoadBalancer();
        $area = $_SESSION['work_area'];
        
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
                    $_SESSION['user_id'],
                    'asignacion_equilibrada',
                    [
                        'analista_asignado' => $analista_data['nombre_completo'],
                        'supervisor' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
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
    
    /**
     * Obtener todos los casos del sistema
     */
    public function getAllCasos($filtros = []) {
        return $this->casoModel->getAllCasos($filtros);
    }
}
?>