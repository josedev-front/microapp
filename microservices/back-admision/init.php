<?php
// microservices/back-admision/init.php

// Usar los nombres CORRECTOS de las variables de sesión
$user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$work_area = $_SESSION['work_area'] ?? null;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado - CON LOS NOMBRES CORRECTOS
if (!$user_id || !$user_role) {
    header('Location: /public/?vista=login');
    exit;
}

// Cargar configuración de base de datos
require_once __DIR__ . '/config/database_back_admision.php';

class BackAdmision {
    private $db;
    private $user_id;
    private $user_role;
    private $work_area;
    
    public function __construct($user_id, $user_role, $work_area) {
        $this->db = getBackAdmisionDB();
        $this->user_id = $user_id;
        $this->user_role = $user_role;
        $this->work_area = $work_area;
    }
    
    public function init() {
        // Verificar acceso al microservicio
        if (!$this->tieneAcceso()) {
            $_SESSION['notificacion'] = [
                'tipo' => 'error',
                'mensaje' => 'No tiene acceso al Back de Admisión. Su área: ' . $this->getUserArea()
            ];
            header('Location: /public/?vista=myaccount');
            exit;
        }
        
        return $this;
    }
    
    public function tieneAcceso() {
        // Roles que tienen acceso
        $roles_permitidos = ['ejecutivo', 'supervisor', 'backup', 'qa', 'superuser', 'developer'];
        $areas_permitidas = ['Depto Micro&SOHO', 'Depto Corporaciones', 'General'];
        
        // Si es superuser, permitir acceso sin importar el área
        if ($this->user_role === 'superuser') {
            return true;
        }
        
        return in_array($this->user_role, $roles_permitidos) && in_array($this->work_area, $areas_permitidas);
    }
    
    public function getUserRole() {
        return $this->user_role;
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function getUserArea() {
        return $this->work_area;
    }
    
    public function getUserName() {
        return ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    }
}

// Inicializar con los datos CORRECTOS
try {
    $backAdmision = new BackAdmision($user_id, $user_role, $work_area);
    $backAdmision->init();
} catch (Exception $e) {
    error_log("Error inicializando Back de Admisión: " . $e->getMessage());
    $_SESSION['notificacion'] = [
        'tipo' => 'error',
        'mensaje' => 'Error al cargar el módulo de Admisión: ' . $e->getMessage()
    ];
    header('Location: /public/?vista=home');
    exit;
}
?>