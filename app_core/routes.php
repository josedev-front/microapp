<?php
error_log("=== ROUTES DEBUG ===");
error_log("Vista solicitada: " . ($_GET['vista'] ?? 'N/A'));
error_log("Routes disponibles: " . implode(', ', array_keys($routes)));
$routes = [
    // ... otras rutas existentes ...
    'myaccount' => 'templates/myaccount.php',
    'myconfig' => 'templates/myconfig.php',
    'editar_usuario' => 'templates/editar_usuario.php',
    'equiposuser' => 'templates/equiposuser.php',

    // MICROSERVICIOS - Middy
    'middy' => 'templates/middy.php',
    'middy_api' => '../microservices/middy/api/chat.php',
    'chat_simple' => '../microservices/middy/api/chat_simple.php',

    // APIs de administración de Middy
    'middy_admin_get_file' => '../microservices/middy/api/admin_get_file.php',
    'middy_admin_save_file' => '../microservices/middy/api/admin_save_file.php',
    'middy_admin_get_logs' => '../microservices/middy/api/admin_get_logs.php',
      
    // Trivia
    'trivia' => $base_path . '/microservices/trivia-play/views/welcome.php',
    'trivia_host' => $base_path . '/microservices/trivia-play/views/host/setup.php',
    'trivia_join' => $base_path . '/microservices/trivia-play/views/player/join.php',
    'trivia_player_history' => $base_path . '/microservices/trivia-play/views/history/player_history.php',
    'trivia_host_history' => $base_path . '/microservices/trivia-play/views/history/host_history.php',

    // MICROSERVICIOS - Back de Admisión
    'back-admision' => '../microservices/back-admision/routes.php',
    'admision-menu' => '../microservices/back-admision/views/ejecutivo/menu.php',
    'admision-ingresar-caso' => '../microservices/back-admision/views/ejecutivo/ingresar-caso.php',
    'admision-gestionar-solicitud' => '../microservices/back-admision/views/ejecutivo/gestionar-solicitud.php',
    'admision-supervisor' => '../microservices/back-admision/views/supervisor/menu.php',
    'admision-gestionar-equipos' => '../microservices/back-admision/views/supervisor/gestionar-equipos.php',
    'admision-ingresar-caso-backup' => '../microservices/back-admision/views/supervisor/ingresar-caso-backup.php',
    'admision-panel-asignaciones' => '../microservices/back-admision/views/supervisor/panel-asignaciones.php',
    'admision-ver-registros' => '../microservices/back-admision/views/supervisor/ver-registros.php',
    
    // APIs Back de Admisión
    'admision-api-ingresar-caso' => '../microservices/back-admision/api/ingresar_caso.php',
    'admision-api-gestionar-caso' => '../microservices/back-admision/api/gestionar_caso.php',
    'admision-api-cambiar-estado' => '../microservices/back-admision/api/cambiar_estado.php',
    'admision-api-get-casos' => '../microservices/back-admision/api/get_casos.php',
    'admision-api-ingresar-caso-supervisor' => '../microservices/back-admision/api/ingresar_caso_supervisor.php',
    'admision-api-cambiar-estado-usuario' => '../microservices/back-admision/api/cambiar_estado_usuario.php',
    'admision-api-get-horarios' => '../microservices/back-admision/api/get_horarios.php',
    'admision-api-guardar-horarios' => '../microservices/back-admision/api/guardar_horarios.php',
    'admision-api-descargar-excel' => '../microservices/back-admision/api/descargar_excel.php',
    'admision-api-exportar-logs' => '../microservices/back-admision/api/exportar_logs.php'
];

// Función para cargar la vista
function cargarVista($vista) {
    global $routes;
    
    if (isset($routes[$vista]) && file_exists($routes[$vista])) {
        return $routes[$vista];
    }
    
    // Si es una ruta de microservicio middy
    if (strpos($vista, 'middy') === 0) {
        $microservice_path = __DIR__ . '/../microservices/middy/routes.php';
        if (file_exists($microservice_path)) {
            require_once $microservice_path;
            $middy_routes = getMiddyRoutes();
            if (isset($middy_routes[$vista])) {
                return $middy_routes[$vista];
            }
        }
    }
    
    // Si es una ruta de microservicio back-admision
    if (strpos($vista, 'admision') === 0 || $vista === 'back-admision') {
        $microservice_path = __DIR__ . '/../microservices/back-admision/routes.php';
        if (file_exists($microservice_path)) {
            return $microservice_path;
        }
    }
    
    return 'templates/404.php';
}
?>