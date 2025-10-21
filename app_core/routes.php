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