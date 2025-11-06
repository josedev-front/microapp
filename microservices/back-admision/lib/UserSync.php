<?php
// lib/UserSync.php
class UserSync {
    private $dbMicroapps;
    private $dbBackAdmision;
    
    public function __construct() {
        $this->dbMicroapps = getDatabaseConnection(); // BD principal
        $this->dbBackAdmision = getBackAdmisionDB(); // BD independiente
    }
    
    public function sincronizarEjecutivos($area = null) {
        $sql = "
            SELECT id, first_name, last_name, work_area, role, is_active
            FROM core_customuser 
            WHERE role = 'ejecutivo' 
            AND is_active = 1
        ";
        
        if ($area) {
            $sql .= " AND work_area = ?";
            $stmt = $this->dbMicroapps->prepare($sql);
            $stmt->execute([$area]);
        } else {
            $stmt = $this->dbMicroapps->query($sql);
        }
        
        $ejecutivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sincronizados = 0;
        
        foreach ($ejecutivos as $ejecutivo) {
            $this->sincronizarEjecutivoIndividual($ejecutivo);
            $sincronizados++;
        }
        
        return $sincronizados;
    }
    
    private function sincronizarEjecutivoIndividual($ejecutivo) {
        // Sincronizar en estado_usuarios
        $stmt = $this->dbBackAdmision->prepare("
            INSERT INTO estado_usuarios (user_id, user_external_id, estado)
            VALUES (?, ?, 'activo')
            ON DUPLICATE KEY UPDATE 
                user_external_id = VALUES(user_external_id),
                ultima_actualizacion = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $ejecutivo['id'],
            $ejecutivo['id']
        ]);
        
        // Sincronizar en horarios_usuarios (datos básicos)
        $nombre_completo = $ejecutivo['first_name'] . ' ' . $ejecutivo['last_name'];
        
        $stmt = $this->dbBackAdmision->prepare("
            INSERT INTO horarios_usuarios (user_id, user_external_id, nombre_completo, area, dia_semana)
            VALUES (?, ?, ?, ?, 'lunes'), (?, ?, ?, ?, 'martes'), (?, ?, ?, ?, 'miercoles'),
                   (?, ?, ?, ?, 'jueves'), (?, ?, ?, ?, 'viernes'), (?, ?, ?, ?, 'sabado'), (?, ?, ?, ?, 'domingo')
            ON DUPLICATE KEY UPDATE 
                nombre_completo = VALUES(nombre_completo),
                area = VALUES(area),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $params = [];
        for ($i = 0; $i < 7; $i++) {
            array_push($params, 
                $ejecutivo['id'], 
                $ejecutivo['id'],
                $nombre_completo,
                $ejecutivo['work_area']
            );
        }
        
        $stmt->execute($params);
    }
}
?>