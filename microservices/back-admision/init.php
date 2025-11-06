<?php
// microservices/back-admision/init.php
require_once __DIR__ . '/../../app_core/config/database.php';
require_once __DIR__ . '/../../app_core/config/helpers.php';
require_once __DIR__ . '/../../app_core/config/session.php';
require_once __DIR__ . '/config/database_back_admision.php';

class BackAdmision {
    private $db;
    private $session;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
        $this->session = new SessionManager();
    }
    
    public function init() {
        // Verificar sesión usando la sesión de la app madre
        session_start();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            header('Location: /login.php');
            exit;
        }
        
        return $this;
    }
    
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUserArea() {
        return $_SESSION['work_area'] ?? null;
    }
    
    public function getUserName() {
        return ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    }
    
    // Verificar si el usuario tiene acceso al Back de Admisión
    public function tieneAcceso() {
        $role = $this->getUserRole();
        $area = $this->getUserArea();
        
        // Roles que tienen acceso
        $roles_permitidos = ['ejecutivo', 'supervisor', 'backup', 'qa', 'superuser', 'developer'];
        
        // Áreas que tienen acceso
        $areas_permitidas = ['Depto Micro&SOHO'];
        
        return in_array($role, $roles_permitidos) && in_array($area, $areas_permitidas);
    }
}

// Inicializar y verificar acceso
$backAdmision = new BackAdmision();
$backAdmision->init();

if (!$backAdmision->tieneAcceso()) {
    header('Location: /?vista=home');
    exit;
}
?>