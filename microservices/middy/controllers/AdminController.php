<?php
namespace Middy\Controllers;

require_once __DIR__ . '/../init.php';

use Middy\Lib\Helpers;

class AdminController {
    private $db;
    private $docsPath;

    public function __construct() {
        $this->docsPath = __DIR__ . '/../data/docs';
        $this->connectDB();
        $this->createTablesIfNotExists();
    }

    private function connectDB() {
        try {
            $this->db = new \PDO(
                "mysql:host=localhost;dbname=microapps;charset=utf8mb4",
                "root",
                "",
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $e) {
            Helpers::log("Error de conexión a BD", ['error' => $e->getMessage()]);
            throw new \Exception("Error de conexión a la base de datos");
        }
    }

    private function createTablesIfNotExists() {
        // Crear tabla de logs si no existe
        $sql = "CREATE TABLE IF NOT EXISTS middy_document_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_name VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            user_id INT,
            user_role VARCHAR(50) NOT NULL,
            changes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            Helpers::log("Error creando tabla de logs", ['error' => $e->getMessage()]);
        }
    }

    // Verificar permisos de administración
    public function checkAdminPermissions($userRole) {
        $allowedRoles = ['supervisor', 'developer', 'backup', 'agente_qa', 'superuser'];
        return in_array($userRole, $allowedRoles);
    }

    // Obtener contenido de archivo
    public function getFileContent($filename) {
        $allowedFiles = ['informe.txt', 'datos.txt'];
        
        if (!in_array($filename, $allowedFiles)) {
            throw new \Exception("Archivo no permitido");
        }

        $filePath = $this->docsPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \Exception("Error al leer el archivo");
        }

        return $content;
    }

    // Guardar contenido de archivo
    public function saveFileContent($filename, $content, $userId, $userRole) {
        $allowedFiles = ['informe.txt', 'datos.txt'];
        
        if (!in_array($filename, $allowedFiles)) {
            throw new \Exception("Archivo no permitido");
        }

        if (!$this->checkAdminPermissions($userRole)) {
            throw new \Exception("No tienes permisos para editar archivos");
        }

        $filePath = $this->docsPath . '/' . $filename;
        
        // Crear directorio si no existe
        if (!is_dir($this->docsPath)) {
            mkdir($this->docsPath, 0755, true);
        }
        
        // Guardar contenido
        if (file_put_contents($filePath, $content) === false) {
            throw new \Exception("Error al guardar el archivo");
        }

        // Registrar en log
        $this->logDocumentChange($filename, 'EDICION', $userId, $userRole, 'Archivo modificado');

        return true;
    }

    // Obtener logs de modificaciones
    public function getDocumentLogs($limit = 50) {
        try {
            // Verificar si la tabla existe
            $tableExists = $this->db->query("SHOW TABLES LIKE 'middy_document_logs'")->rowCount() > 0;
            
            if (!$tableExists) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT ml.*, cu.first_name, cu.last_name, cu.role 
                FROM middy_document_logs ml 
                LEFT JOIN core_customuser cu ON ml.user_id = cu.id 
                ORDER BY ml.created_at DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Helpers::log("Error obteniendo logs", ['error' => $e->getMessage()]);
            return [];
        }
    }

    // Registrar cambio en log
    private function logDocumentChange($documentName, $action, $userId, $userRole, $changes = '') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO middy_document_logs (document_name, action, user_id, user_role, changes) 
                VALUES (:document_name, :action, :user_id, :user_role, :changes)
            ");
            
            $stmt->execute([
                ':document_name' => $documentName,
                ':action' => $action,
                ':user_id' => $userId,
                ':user_role' => $userRole,
                ':changes' => $changes
            ]);

            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            Helpers::log("Error registrando log", ['error' => $e->getMessage()]);
            return false;
        }
    }

    // Obtener estadísticas de documentos
    public function getDocumentStats() {
        $stats = [];
        $files = ['informe.txt', 'datos.txt'];
        
        foreach ($files as $file) {
            $filePath = $this->docsPath . '/' . $file;
            $exists = file_exists($filePath);
            
            if ($exists) {
                $lines = count(file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
            } else {
                $lines = 0;
            }
            
            $stats[$file] = [
                'exists' => $exists,
                'size' => $exists ? filesize($filePath) : 0,
                'lines' => $lines,
                'last_modified' => $exists ? date('Y-m-d H:i:s', filemtime($filePath)) : null
            ];
        }
        
        return $stats;
    }
}