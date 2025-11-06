<?php
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
        $this->casoModel = new Caso();
        $this->loadBalancer = new LoadBalancer();
        $this->assignmentManager = new AssignmentManager();
        $this->userSync = new UserSync();
    }
    
    /**
     * Vista menú para ejecutivos
     */
    public function menuEjecutivo() {
        $user_id = $_SESSION['user_id'];
        $user_nombre = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        
        // Obtener casos del ejecutivo
        $casos = $this->casoModel->getCasosPorEjecutivo($user_id);
        
        include __DIR__ . '/../views/ejecutivo/menu.php';
    }
    
    /**
     * Procesar ingreso de caso
     */
    public function ingresarCaso($sr_hijo) {
        $user_id = $_SESSION['user_id'];
        $user_nombre = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        $user_area = $_SESSION['work_area'];
        
        // Validar formato SR
        if (!$this->validarFormatoSR($sr_hijo)) {
            $_SESSION['notificacion'] = [
                'tipo' => 'error',
                'mensaje' => 'Formato de SR inválido'
            ];
            return 'ingresar-caso';
        }
        
        // Verificar si la SR ya existe
        $casoExistente = $this->casoModel->getCasoPorSR($sr_hijo);
        
        if ($casoExistente) {
            if ($casoExistente['analista_id'] == $user_id) {
                // Caso ya asignado al mismo ejecutivo
                $_SESSION['notificacion'] = [
                    'tipo' => 'info',
                    'mensaje' => 'Este caso ya se encontraba asignado a tu cuenta'
                ];
                $_SESSION['sr_a_gestionar'] = $sr_hijo;
                return 'gestionar-solicitud';
            } else {
                // Caso asignado a otro ejecutivo
                $_SESSION['notificacion_confirmacion'] = [
                    'tipo' => 'warning',
                    'mensaje' => "Este caso ya está asignado a {$casoExistente['analista_nombre']}. ¿Desea reasignarlo a su cuenta?",
                    'sr_hijo' => $sr_hijo,
                    'accion' => 'confirmar_reasignacion'
                ];
                return 'confirmar-reasignacion';
            }
        } else {
            // Asignar caso nuevo
            $analista_id = $this->loadBalancer->asignarCasoEquilibrado($sr_hijo, $user_area);
            
            if ($analista_id) {
                // Obtener datos del analista
                $analista_data = $this->userSync->getEjecutivosDisponibles($user_area);
                $analista_nombre = '';
                foreach ($analista_data as $ejecutivo) {
                    if ($ejecutivo['user_id'] == $analista_id) {
                        $analista_nombre = $ejecutivo['nombre_completo'];
                        break;
                    }
                }
                
                $caso_data = [
                    'sr_hijo' => $sr_hijo,
                    'analista_id' => $analista_id,
                    'analista_nombre' => $analista_nombre,
                    'area_ejecutivo' => $user_area,
                    'estado' => 'en_curso',
                    'tipo_negocio' => 'Solicitud de revisión por backoffice'
                ];
                
                if ($this->casoModel->crearCaso($caso_data)) {
                    // Log de asignación
                    $this->assignmentManager->logAsignacion(
                        $sr_hijo, 
                        $user_id, 
                        'asignacion_automatica',
                        ['analista_asignado' => $analista_nombre]
                    );
                    
                    $_SESSION['notificacion'] = [
                        'tipo' => 'success',
                        'mensaje' => "Caso asignado correctamente a $analista_nombre"
                    ];
                    $_SESSION['sr_a_gestionar'] = $sr_hijo;
                    return 'gestionar-solicitud';
                }
            }
            
            $_SESSION['notificacion'] = [
                'tipo' => 'error',
                'mensaje' => 'No hay ejecutivos disponibles para asignar este caso'
            ];
            return 'ingresar-caso';
        }
    }
    
    /**
     * Confirmar reasignación de caso
     */
    public function confirmarReasignacion($sr_hijo, $confirmar) {
        if ($confirmar) {
            $user_id = $_SESSION['user_id'];
            $user_nombre = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $user_area = $_SESSION['work_area'];
            
            // Reasignar caso
            if ($this->casoModel->reasignarCaso($sr_hijo, $user_id, $user_nombre)) {
                // Log de reasignación
                $this->assignmentManager->logAsignacion(
                    $sr_hijo, 
                    $user_id, 
                    'reasignacion_manual',
                    ['nuevo_analista' => $user_nombre]
                );
                
                $_SESSION['notificacion'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Caso reasignado correctamente a tu cuenta'
                ];
                $_SESSION['sr_a_gestionar'] = $sr_hijo;
                return 'gestionar-solicitud';
            }
        }
        
        $_SESSION['notificacion'] = [
            'tipo' => 'info',
            'mensaje' => 'Reasignación cancelada'
        ];
        return 'menu';
    }
    
    /**
     * Gestionar solicitud existente
     */
    public function gestionarSolicitud($data) {
        $user_id = $_SESSION['user_id'];
        
        // Verificar permisos
        $caso = $this->casoModel->getCasoPorSR($data['sr_hijo']);
        if (!$caso) {
            $_SESSION['notificacion'] = [
                'tipo' => 'error',
                'mensaje' => 'Caso no encontrado'
            ];
            return false;
        }
        
        // Verificar si el usuario tiene permisos (es el analista o supervisor)
        if ($caso['analista_id'] != $user_id && !$this->tienePermisosSupervisor()) {
            $_SESSION['notificacion'] = [
                'tipo' => 'error',
                'mensaje' => 'No tiene permisos para gestionar este caso'
            ];
            return false;
        }
        
        $actualizado = $this->casoModel->actualizarCaso($data['sr_hijo'], $data);
        
        if ($actualizado) {
            // Log de modificación
            $this->assignmentManager->logModificacion(
                $data['sr_hijo'], 
                $user_id, 
                'modificacion_caso', 
                $data
            );
            
            // Si se marca como resuelto
            if ($data['estado'] == 'resuelto') {
                $_SESSION['notificacion'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Caso marcado como resuelto y removido de la bandeja'
                ];
            } else {
                $_SESSION['notificacion'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Caso actualizado correctamente'
                ];
            }
            
            return true;
        }
        
        $_SESSION['notificacion'] = [
            'tipo' => 'error',
            'mensaje' => 'Error al actualizar el caso'
        ];
        return false;
    }
    
    /**
     * Obtener bandeja de casos para ejecutivo
     */
    public function getBandejaEjecutivo($user_id) {
        return $this->casoModel->getCasosActivosPorEjecutivo($user_id);
    }
    
    /**
     * Validar formato de SR
     */
    private function validarFormatoSR($sr_hijo) {
        // Implementar validación específica del formato de SR
        return !empty($sr_hijo) && strlen($sr_hijo) >= 5;
    }
    
    /**
     * Verificar permisos de supervisor
     */
    private function tienePermisosSupervisor() {
        $roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];
        return in_array($_SESSION['user_role'], $roles_permitidos);
    }
}
?>