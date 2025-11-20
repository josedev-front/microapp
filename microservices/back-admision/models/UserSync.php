<?php
class UserSync {
    private $dbMicroapps;
    private $dbBackAdmision;
    
    public function __construct() {
       
      if (function_exists('conexion')) {
            $this->dbMicroapps = conexion();
        } 
        // Opción B: Crear conexión manualmente
        else {
            $this->dbMicroapps = $this->createMainDBConnection();
        }
        
        // Conexión a BD independiente
        $this->dbBackAdmision = getBackAdmisionDB();
    }

    // Agrega este método en la clase UserSync:
public function getEjecutivoPorId($ejecutivo_id) {
    if (!$this->dbBackAdmision) {
        return null;
    }
    
    try {
        $stmt = $this->dbBackAdmision->prepare("
            SELECT user_id, nombre_completo, area 
            FROM horarios_usuarios 
            WHERE user_id = ? AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([$ejecutivo_id]);
        $ejecutivo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ejecutivo) {
            return $ejecutivo;
        }
        
        // Si no existe en horarios_usuarios, buscar en BD principal
        if ($this->dbMicroapps) {
            $stmt = $this->dbMicroapps->prepare("
                SELECT id, first_name, last_name, work_area 
                FROM core_customuser 
                WHERE id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$ejecutivo_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                return [
                    'user_id' => $usuario['id'],
                    'nombre_completo' => $usuario['first_name'] . ' ' . $usuario['last_name'],
                    'area' => $usuario['work_area']
                ];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error obteniendo ejecutivo por ID: " . $e->getMessage());
        return null;
    }
}
    private function createMainDBConnection() {
        try {
            $host = 'localhost';
            $dbname = 'microapps'; // Tu BD principal
            $username = 'root';    // Tus credenciales
            $password = '';        // Tus credenciales
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Error conectando a BD principal: " . $e->getMessage());
        }
    }
     /**
     * Sincroniza todos los ejecutivos de un área específica
     */
    public function sincronizarEjecutivosPorArea($area = 'Depto Micro&amp;SOHO') {
        try {
            $sql = "
                SELECT id, first_name, last_name, work_area, role, is_active, employee_id
                FROM core_customuser 
                WHERE role = 'ejecutivo' 
                AND is_active = 1
                AND work_area = ?
            ";
            
            $stmt = $this->dbMicroapps->prepare($sql);
            $stmt->execute([$area]);
            $ejecutivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sincronizados = 0;
            foreach ($ejecutivos as $ejecutivo) {
                if ($this->sincronizarEjecutivoIndividual($ejecutivo)) {
                    $sincronizados++;
                }
            }
            
            // Log de sincronización
            $this->logSincronizacion($sincronizados, $area);
            
            return $sincronizados;
            
        } catch (Exception $e) {
            error_log("Error en sincronización: " . $e->getMessage());
            return false;
        }
    }
    

    /**
     * Sincroniza un ejecutivo individual
     */
    private function sincronizarEjecutivoIndividual($ejecutivo) {
        try {
            $nombre_completo = trim($ejecutivo['first_name'] . ' ' . $ejecutivo['last_name']);
            
            // 1. Sincronizar en estado_usuarios
            $stmt = $this->dbBackAdmision->prepare("
                INSERT INTO estado_usuarios (user_id, user_external_id, estado, ultima_actualizacion)
                VALUES (?, ?, 'activo', NOW())
                ON DUPLICATE KEY UPDATE 
                    user_external_id = VALUES(user_external_id),
                    ultima_actualizacion = NOW()
            ");
            
            $stmt->execute([
                $ejecutivo['id'],
                $ejecutivo['id']
            ]);
            
            // 2. Sincronizar horarios base para todos los días
            $dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            
            foreach ($dias_semana as $dia) {
                $stmt = $this->dbBackAdmision->prepare("
                    INSERT INTO horarios_usuarios 
                    (user_id, user_external_id, nombre_completo, area, dia_semana, activo)
                    VALUES (?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        nombre_completo = VALUES(nombre_completo),
                        area = VALUES(area),
                        activo = VALUES(activo),
                        updated_at = NOW()
                ");
                
                $stmt->execute([
                    $ejecutivo['id'],
                    $ejecutivo['id'],
                    $nombre_completo,
                    $ejecutivo['work_area'],
                    $dia
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error sincronizando ejecutivo {$ejecutivo['id']}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene ejecutivos disponibles desde BD independiente
     */
    public function getEjecutivosDisponibles($area = 'Depto Micro&amp;SOHO') {
        $stmt = $this->dbBackAdmision->prepare("
            SELECT 
                hu.user_id,
                hu.user_external_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                eu.ultima_actualizacion,
                COUNT(c.id) as casos_activos
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id AND c.estado != 'resuelto'
            WHERE hu.area = ?
            AND hu.activo = 1
            AND (eu.estado IS NULL OR eu.estado = 'activo')
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado
            ORDER BY casos_activos ASC
        ");
        
        $stmt->execute([$area]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function logSincronizacion($cantidad, $area) {
        error_log("✅ Sincronizados $cantidad ejecutivos del área: $area");
    }
}
?>