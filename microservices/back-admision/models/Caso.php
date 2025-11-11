<?php
require_once __DIR__ . '/../config/database_back_admision.php';

class Caso {
    private $db;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
    }
    
    public function getCasoPorSR($sr_hijo) {
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM casos c 
            WHERE c.sr_hijo = ?
        ");
        $stmt->execute([$sr_hijo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crearCaso($data) {
        $stmt = $this->db->prepare("
            INSERT INTO casos 
            (sr_hijo, srp, estado, tiket, motivo_tiket, analista_id, analista_nombre, area_ejecutivo, tipo_negocio, observaciones, biometria, inicio_actividades, acreditacion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['sr_hijo'],
            $data['srp'] ?? null,
            $data['estado'] ?? 'en_curso',
            $data['tiket'] ?? null,
            $data['motivo_tiket'] ?? null,
            $data['analista_id'],
            $data['analista_nombre'],
            $data['area_ejecutivo'],
            $data['tipo_negocio'] ?? 'Solicitud de revisión por backoffice',
            $data['observaciones'] ?? null,
            $data['biometria'] ?? null,
            $data['inicio_actividades'] ?? null,
            $data['acreditacion'] ?? null
        ]);
    }
    /**
 * Reasignar caso a otro analista
 */
public function reasignarCaso($sr_hijo, $nuevo_analista_id, $nuevo_analista_nombre) {
    try {
        $stmt = $this->db->prepare("
            UPDATE casos 
            SET 
                analista_id = ?,
                analista_nombre = ?,
                fecha_actualizacion = NOW()
            WHERE sr_hijo = ?
        ");
        
        return $stmt->execute([
            $nuevo_analista_id,
            $nuevo_analista_nombre,
            $sr_hijo
        ]);
        
    } catch (Exception $e) {
        error_log("ERROR en reasignarCaso: " . $e->getMessage());
        return false;
    }
}
    // Sincronización con usuarios de la BD principal
    public function sincronizarEjecutivo($user_data) {
        $stmt = $this->db->prepare("
            INSERT INTO estado_usuarios (user_id, user_external_id, estado)
            VALUES (?, ?, 'activo')
            ON DUPLICATE KEY UPDATE 
                user_external_id = VALUES(user_external_id),
                ultima_actualizacion = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $user_data['id_local'] ?? $user_data['id'],
            $user_data['id']
        ]);
    }
}
?>