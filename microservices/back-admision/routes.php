<?php
// microservices/back-admision/routes.php
require_once __DIR__ . '/init.php';

$action = $_GET['action'] ?? 'menu';
$vista = $_GET['vista'] ?? '';

// Routing interno del microservicio
switch ($vista) {
    case 'back-admision':
        if ($backAdmision->getUserRole() === 'ejecutivo') {
            require_once __DIR__ . '/views/ejecutivo/menu.php';
        } else {
            require_once __DIR__ . '/views/supervisor/menu.php';
        }
        break;
        
    case 'admision-ingresar-caso':
        require_once __DIR__ . '/views/ejecutivo/ingresar-caso.php';
        break;
        
    case 'admision-gestionar-solicitud':
        require_once __DIR__ . '/views/ejecutivo/gestionar-solicitud.php';
        break;
        
    case 'admision-supervisor':
        require_once __DIR__ . '/views/supervisor/menu.php';
        break;
        
    default:
        // Redirigir al menú según el rol
        if ($backAdmision->getUserRole() === 'ejecutivo') {
            require_once __DIR__ . '/views/ejecutivo/menu.php';
        } else {
            require_once __DIR__ . '/views/supervisor/menu.php';
        }
}
?>