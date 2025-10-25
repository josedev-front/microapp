$routes = [
    // ... otras rutas ...
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
      
        // En app_core/routes.php agregar:
        'trivia' => $base_path . '/microservices/trivia-play/views/welcome.php',
        'trivia_host' => $base_path . '/microservices/trivia-play/views/host/setup.php',
        'trivia_join' => $base_path . '/microservices/trivia-play/views/player/join.php',
        'trivia_player_history' => $base_path . '/microservices/trivia-play/views/history/player_history.php',
        'trivia_host_history' => $base_path . '/microservices/trivia-play/views/history/host_history.php',


    
];
// Función para cargar la vista
function cargarVista($vista) {
    global $routes;
    
    if (isset($routes[$vista]) && file_exists($routes[$vista])) {
        return $routes[$vista];
    }
    
    // Si es una ruta de microservicio
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
    
    return 'templates/404.php';
}