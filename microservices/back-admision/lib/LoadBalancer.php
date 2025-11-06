<?php
require_once __DIR__ . '/../models/UserSync.php';

class LoadBalancer {
    private $db;
    private $userSync;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
        $this->userSync = new UserSync();
    }
    
    /**
     * Asignar caso de manera equilibrada
     */
    public function asignarCasoEquilibrado($sr_hijo, $area) {
        // Obtener ejecutivos disponibles del área
        $ejecutivos = $this->userSync->getEjecutivosDisponibles($area);
        
        if (empty($ejecutivos)) {
            error_log("❌ No hay ejecutivos disponibles para el área: $area");
            return null;
        }
        
        // Filtrar ejecutivos que no estén próximos a salir (15 minutos)
        $ejecutivos_filtrados = $this->filtrarEjecutivosProximosSalir($ejecutivos);
        
        if (empty($ejecutivos_filtrados)) {
            error_log("⚠️ Todos los ejecutivos están próximos a salir, usando lista completa");
            $ejecutivos_filtrados = $ejecutivos;
        }
        
        // Ordenar por menor cantidad de casos
        usort($ejecutivos_filtrados, function($a, $b) {
            return $a['casos_activos'] - $b['casos_activos'];
        });
        
        // Seleccionar el ejecutivo con menos casos
        $ejecutivo_seleccionado = $ejecutivos_filtrados[0];
        
        error_log("✅ Caso $sr_hijo asignado a: {$ejecutivo_seleccionado['nombre_completo']} con {$ejecutivo_seleccionado['casos_activos']} casos");
        
        return $ejecutivo_seleccionado['user_id'];
    }
    
    /**
     * Filtrar ejecutivos que no estén próximos a salir de turno
     */
    private function filtrarEjecutivosProximosSalir($ejecutivos) {
        $filtrados = [];
        $minutos_limite = 15;
        
        foreach ($ejecutivos as $ejecutivo) {
            $hora_salida = $this->obtenerHoraSalidaEjecutivo($ejecutivo['user_id']);
            
            if ($hora_salida) {
                $tiempo_restante = $this->calcularMinutosHastaHora($hora_salida);
                
                if ($tiempo_restante > $minutos_limite) {
                    $filtrados[] = $ejecutivo;
                } else {
                    error_log("⏰ Ejecutivo {$ejecutivo['nombre_completo']} sale en $tiempo_restante minutos - excluido");
                }
            } else {
                // Si no tiene horario definido, incluirlo
                $filtrados[] = $ejecutivo;
            }
        }
        
        return $filtrados;
    }
    
    /**
     * Obtener hora de salida del ejecutivo para el día actual
     */
    private function obtenerHoraSalidaEjecutivo($user_id) {
        $dia_semana = strtolower(date('l')); // lunes, martes, etc.
        
        $stmt = $this->db->prepare("
            SELECT hora_salida 
            FROM horarios_usuarios 
            WHERE user_id = ? AND dia_semana = ? AND activo = 1
        ");
        
        $stmt->execute([$user_id, $dia_semana]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['hora_salida'] ?? null;
    }
    
    /**
     * Calcular minutos hasta una hora específica
     */
    private function calcularMinutosHastaHora($hora_salida) {
        $hora_actual = new DateTime();
        $hora_salida_obj = DateTime::createFromFormat('H:i:s', $hora_salida);
        
        if ($hora_salida_obj < $hora_actual) {
            // Si la hora de salida ya pasó, considerar que no está próximo a salir
            return 999;
        }
        
        $diferencia = $hora_actual->diff($hora_salida_obj);
        return ($diferencia->h * 60) + $diferencia->i;
    }
    
    /**
     * Reasignar casos automáticamente (para cron)
     */
    public function reasignarCasosInactivos() {
        $minutos_inactividad = $this->obtenerConfiguracion('tiempo_reasignacion_inactivos', 15);
        
        $stmt = $this->db->prepare("
            SELECT c.sr_hijo, c.analista_id, c.area_ejecutivo, c.analista_nombre
            FROM casos c
            JOIN estado_usuarios eu ON c.analista_id = eu.user_id
            WHERE c.estado = 'en_curso'
            AND eu.estado IN ('colacion', 'inactivo')
            AND TIMESTAMPDIFF(MINUTE, eu.ultima_actualizacion, NOW()) > ?
        ");
        
        $stmt->execute([$minutos_inactividad]);
        $casos_a_reasignar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reasignados = 0;
        foreach ($casos_a_reasignar as $caso) {
            $nuevo_analista = $this->asignarCasoEquilibrado($caso['sr_hijo'], $caso['area_ejecutivo']);
            
            if ($nuevo_analista && $nuevo_analista != $caso['analista_id']) {
                if ($this->reasignarCaso($caso['sr_hijo'], $nuevo_analista)) {
                    $reasignados++;
                    
                    // Log de reasignación automática
                    $assignmentManager = new AssignmentManager();
                    $assignmentManager->logAsignacion(
                        $caso['sr_hijo'], 
                        null, // Sistema automático
                        'reasignacion_automatica',
                        [
                            'analista_anterior' => $caso['analista_nombre'],
                            'analista_nuevo' => $this->obtenerNombreEjecutivo($nuevo_analista),
                            'motivo' => 'ejecutivo_inactivo'
                        ]
                    );
                }
            }
        }
        
        error_log("🔄 Reasignación automática: $reasignados casos reasignados");
        return $reasignados;
    }
    
    private function reasignarCaso($sr_hijo, $nuevo_analista_id) {
        $nombre_analista = $this->obtenerNombreEjecutivo($nuevo_analista_id);
        
        $stmt = $this->db->prepare("
            UPDATE casos 
            SET analista_id = ?, analista_nombre = ?, fecha_actualizacion = NOW() 
            WHERE sr_hijo = ?
        ");
        
        return $stmt->execute([$nuevo_analista_id, $nombre_analista, $sr_hijo]);
    }
    
    private function obtenerNombreEjecutivo($user_id) {
        $stmt = $this->db->prepare("
            SELECT nombre_completo FROM horarios_usuarios 
            WHERE user_id = ? LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['nombre_completo'] ?? 'Desconocido';
    }
    
    private function obtenerConfiguracion($clave, $default) {
        $stmt = $this->db->prepare("
            SELECT valor FROM configuracion_sistema WHERE clave = ?
        ");
        $stmt->execute([$clave]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $default;
    }
}
?>