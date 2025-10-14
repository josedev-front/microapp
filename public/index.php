<?php
require_once __DIR__ . '/../app_core/inc/session_start.php';
require_once __DIR__ . '/../app_core/php/main.php'; // conexión DB, helpers, etc.

// -------------------------------
// CONFIGURACIÓN GENERAL
// -------------------------------
$vistas_sin_nav = ['login', '404', 'logout', 'confirmar_pago'];
$vista = $_GET['vista'] ?? 'home';

// Rutas base
$baseCore = __DIR__ . '/../app_core/templates/';
$baseMicro = __DIR__ . '/../microservices/';

// -------------------------------
// FUNCIONES AUXILIARES
// -------------------------------

/**
 * Busca la vista solicitada en el núcleo o en los microservicios.
 */
function buscarVista($vista, $baseCore, $baseMicro) {
    // Buscar en templates base
    $ruta = $baseCore . $vista . '.php';
    if (file_exists($ruta)) return $ruta;

    // Buscar en microservicios
    foreach (glob($baseMicro . '*/pages/' . $vista . '.php') as $rutaMicro) {
        return $rutaMicro;
    }

    return false;
}

// -------------------------------
// LÓGICA DE CONTROL PRINCIPAL
// -------------------------------

// Logout y vistas especiales directas
if ($vista === 'logout') {
    include $baseCore . 'logout.php';
    exit();
}

if ($vista === 'confirmar_pago') {
    include $baseCore . 'confirmar_pago.php';
    exit();
}

// Si el usuario ya está logueado e intenta ir a login
if ($vista === 'login' && validarSesion()) {
    header('Location: ./?vista=myaccount');
    exit();
}

// Buscar vista solicitada
$rutaVista = buscarVista($vista, $baseCore, $baseMicro);
if (!$rutaVista) {
    $vista = '404';
    $rutaVista = $baseCore . '404.php';
}

// -------------------------------
// CARGA DEL LAYOUT GLOBAL
// -------------------------------
// layout.php se encarga de incluir head, nav, footer y cargar $rutaVista
include __DIR__ . '/../app_core/templates/layout.php';
