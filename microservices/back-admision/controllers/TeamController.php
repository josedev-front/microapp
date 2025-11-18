<?php
require_once __DIR__ . '/../models/UserSync.php';

class TeamController {
    private $userSync;
    private $db;
    
    public function __construct() {
        $this->userSync = new UserSync();
        require_once __DIR__ . '/../config/database_back_admision.php';
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Obtener métricas de balance DIARIO - CORREGIDO
     */
    public function getMetricasBalanceDiario($fecha_desde = null, $fecha_hasta = null) {
        try {
            // Si no se proporcionan fechas, usar hoy
            if (!$fecha_desde) $fecha_desde = date('Y-m-d');
            if (!$fecha_hasta) $fecha_hasta = date('Y-m-d');
            
            $query = "
                SELECT 
                    hu.user_id,
                    hu.nombre_completo,
                    hu.area,
                    eu.estado,
                    eu.ultima_actualizacion,
                    -- Casos ingresados en el período
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? THEN c.id END) as casos_periodo,
                    -- Casos activos totales
                    COUNT(CASE WHEN c.estado != 'resuelto' THEN c.id END) as casos_activos,
                    -- Distribución por estado (período)
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'en_curso' THEN c.id END) as en_curso,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'en_espera' THEN c.id END) as en_espera,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'resuelto' THEN c.id END) as resuelto,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'cancelado' THEN c.id END) as cancelado
                FROM horarios_usuarios hu
                LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
                LEFT JOIN casos c ON hu.user_id = c.analista_id
                WHERE hu.activo = 1 AND hu.area = 'Depto Micro&SOHO'
                GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
                ORDER BY casos_periodo ASC, hu.nombre_completo
            ";
            
            $stmt = $this->db->prepare($query);
            
            // Pasar los parámetros correctamente - solo necesitamos 2 parámetros repetidos
            $params = [
                $fecha_desde, $fecha_hasta,    // Para casos_periodo
                $fecha_desde, $fecha_hasta,    // Para en_curso  
                $fecha_desde, $fecha_hasta,    // Para en_espera
                $fecha_desde, $fecha_hasta,    // Para resuelto
                $fecha_desde, $fecha_hasta     // Para cancelado
            ];
            
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Asegurar que todos los campos existan y mantener compatibilidad
            foreach ($resultados as &$fila) {
                $fila = array_merge([
                    'casos_hoy' => 0,
                    'casos_activos' => 0,
                    'en_curso' => 0,
                    'en_espera' => 0,
                    'resuelto' => 0,
                    'cancelado' => 0
                ], $fila);
                
                // Mantener compatibilidad con el nombre original
                $fila['casos_hoy'] = $fila['casos_periodo'] ?? 0;
            }
            
            error_log("✅ TeamController: Métricas obtenidas para {$fecha_desde} a {$fecha_hasta}: " . count($resultados) . " ejecutivos");
            return $resultados;
            
        } catch (Exception $e) {
            error_log("❌ Error en TeamController::getMetricasBalanceDiario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener ejecutivos activos con información de casos
     */
    public function getEjecutivosActivos() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    hu.user_id,
                    hu.nombre_completo,
                    hu.area,
                    eu.estado,
                    eu.ultima_actualizacion,
                    COUNT(c.id) as casos_activos
                FROM horarios_usuarios hu
                LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
                LEFT JOIN casos c ON hu.user_id = c.analista_id AND c.estado != 'resuelto'
                WHERE hu.activo = 1
                GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
                ORDER BY hu.nombre_completo
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getEjecutivosActivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener ejecutivos disponibles para asignación
     */
    public function getEjecutivosDisponibles() {
        return $this->userSync->getEjecutivosDisponibles();
    }
    
    /**
     * Cambiar estado de un ejecutivo
     */
    public function cambiarEstadoEjecutivo($user_id, $nuevo_estado) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO estado_usuarios (user_id, estado, ultima_actualizacion, ip_ultima_actualizacion) 
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                    estado = VALUES(estado),
                    ultima_actualizacion = NOW(),
                    ip_ultima_actualizacion = VALUES(ip_ultima_actualizacion)
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            return $stmt->execute([$user_id, $nuevo_estado, $ip]);
            
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoEjecutivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener horarios de un ejecutivo
     */
    public function getHorariosEjecutivo($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM horarios_usuarios 
                WHERE user_id = ? 
                ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getHorariosEjecutivo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar horarios de un ejecutivo
     */
    public function actualizarHorarios($user_id, $horarios) {
        $this->db->beginTransaction();
        
        try {
            foreach ($horarios as $dia => $horario) {
                $stmt = $this->db->prepare("
                    UPDATE horarios_usuarios 
                    SET hora_entrada = ?, hora_salida = ?, hora_almuerzo_inicio = ?, hora_almuerzo_fin = ?, 
                        activo = ?, updated_at = NOW()
                    WHERE user_id = ? AND dia_semana = ?
                ");
                
                $stmt->execute([
                    $horario['hora_entrada'] ?: null,
                    $horario['hora_salida'] ?: null,
                    $horario['hora_almuerzo_inicio'] ?: null,
                    $horario['hora_almuerzo_fin'] ?: null,
                    $horario['activo'] ? 1 : 0,
                    $user_id,
                    $dia
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando horarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener métricas de balance de carga
     */
    public function getMetricasBalance() {
        return $this->getMetricasBalanceDiario();
    }
}