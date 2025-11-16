
<?php
require_once __DIR__ . '/../models/UserSync.php';

class TeamController {
    private $userSync;
    private $db;
    
    public function __construct() {
        $this->userSync = new UserSync();
        $this->db = getBackAdmisionDB();
    }
     /**
     * Obtener métricas de balance DIARIO (casos ingresados hoy)
     */
    public function getMetricasBalanceDiario() {
        $stmt = $this->db->prepare("
            SELECT 
                hu.user_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                eu.ultima_actualizacion,
                -- Casos ingresados hoy
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() THEN c.id END) as casos_hoy,
                -- Casos activos totales
                COUNT(CASE WHEN c.estado != 'resuelto' THEN c.id END) as casos_activos,
                -- Distribución por estado (hoy)
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'en_curso' THEN c.id END) as en_curso,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'en_espera' THEN c.id END) as en_espera,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'resuelto' THEN c.id END) as resuelto,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'cancelado' THEN c.id END) as cancelado
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id
            WHERE hu.activo = 1 AND hu.area = 'Depto Micro&SOHO'
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
            ORDER BY casos_hoy ASC, hu.nombre_completo
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtener ejecutivos activos con información de casos
     */
    public function getEjecutivosActivos() {
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
    }
    
    /**
     * Obtener horarios de un ejecutivo
     */
    public function getHorariosEjecutivo($user_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM horarios_usuarios 
            WHERE user_id = ? 
            ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
?>