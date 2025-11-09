<?php
require_once __DIR__ . '/../app_core/inc/session_start.php';
require_once __DIR__ . '/../app_core/php/main.php';
require_once __DIR__ . '/../app_core/controllers/UserController.php';

// -----------------------------------------
// VERIFICAR SESIÓN
// -----------------------------------------
if (!validarSesion()) {
    redireccionar('login.php');
    exit;
}

// -----------------------------------------
// CONFIGURACIÓN GENERAL
// -----------------------------------------
$vistas_sin_nav = ['login', '404', 'logout'];
$vista = isset($_GET['vista']) ? limpiarCadena($_GET['vista']) : 'home';

$baseMicro = __DIR__ . '/../microservices/';
$baseCore  = __DIR__ . '/../app_core/templates/';

// -----------------------------------------
// FUNCIÓN BUSCAR VISTA MEJORADA
// -----------------------------------------
function buscarVista($vista, $baseCore, $baseMicro) {
    // 1. Primero buscar en templates del core
    $rutaCore = $baseCore . $vista . '.php';
    if (file_exists($rutaCore)) {
        return $rutaCore;
    }
    
    // 2. Buscar en microservicios específicos
    $microservicios = [
        'back-admision' => 'back-admision/routes.php',
        'middy' => 'middy/routes.php'
    ];
    
    if (isset($microservicios[$vista])) {
        $rutaMicro = $baseMicro . $microservicios[$vista];
        if (file_exists($rutaMicro)) {
            return $rutaMicro;
        }
    }
    
    // 3. Buscar en páginas de microservicios genéricos
    foreach (glob($baseMicro . '*/pages/' . $vista . '.php') as $rutaMicro) {
        return $rutaMicro;
    }

    return false;
}

// -----------------------------------------
// LÓGICA DE RUTAS ESPECIALES
// -----------------------------------------
if ($vista === 'logout') {
    include $baseCore . 'logout.php';
    exit;
}

if ($vista === 'procesar_eliminar_usuario') {
    UserController::eliminarUsuario();
    exit;
}

// Si el usuario ya está logueado y trata de ir al login
if ($vista === 'login' && validarSesion()) {
    header('Location: ./?vista=myaccount');
    exit;
}

// -----------------------------------------
// BUSCAR VISTA CORRESPONDIENTE
// -----------------------------------------
$rutaVista = buscarVista($vista, $baseCore, $baseMicro);

// DEBUG TEMPORAL
if ($vista === 'back-admision') {
    error_log("Vista: back-admision");
    error_log("Ruta encontrada: " . ($rutaVista ?: 'NO ENCONTRADA'));
    if ($rutaVista) {
        error_log("Archivo existe: " . (file_exists($rutaVista) ? 'SÍ' : 'NO'));
    }
}

if (!$rutaVista) {
    $vista = '404';
    $rutaVista = $baseCore . '404.php';
}

// -----------------------------------------
// CARGA DEL LAYOUT PRINCIPAL (UNA SOLA VEZ)
// -----------------------------------------
include $baseCore . 'layout.php';