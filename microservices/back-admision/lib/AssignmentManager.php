<?php
class AssignmentManager {
    private $db;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Log de asignaciones
     */
    public function logAsignacion($sr_hijo, $supervisor_id, $tipo_asignacion, $metadata = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs_asignaciones 
                (sr_hijo, supervisor_id, tipo_asignacion, metadata, fecha_creacion)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $sr_hijo,
                $supervisor_id,
                $tipo_asignacion,
                json_encode($metadata)
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error en logAsignacion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reasignación automática de casos
     */
    public function reasignarCasos() {
        // Método básico temporal
        return [
            'success' => true,
            'message' => 'Función de reasignación en desarrollo',
            'casos_reaasignados' => 0
        ];
    }
    
    /**
     * Obtener historial de asignaciones
     */
    public function getHistorialAsignaciones($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT la.*, u.first_name, u.last_name 
                FROM logs_asignaciones la
                LEFT JOIN core_customuser u ON la.supervisor_id = u.id
                ORDER BY la.fecha_creacion DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [];
        }
    }
}
?>