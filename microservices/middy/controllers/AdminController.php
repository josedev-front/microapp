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

        return file_get_contents($filePath);
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
    }

    // Registrar cambio en log
    private function logDocumentChange($documentName, $action, $userId, $userRole, $changes = '') {
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
    }

    // Obtener estadísticas de documentos
    public function getDocumentStats() {
        $stats = [];
        $files = ['informe.txt', 'datos.txt'];
        
        foreach ($files as $file) {
            $filePath = $this->docsPath . '/' . $file;
            $stats[$file] = [
                'exists' => file_exists($filePath),
                'size' => file_exists($filePath) ? filesize($filePath) : 0,
                'lines' => file_exists($filePath) ? count(file($filePath)) : 0,
                'last_modified' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null
            ];
        }
        
        return $stats;
    }
}