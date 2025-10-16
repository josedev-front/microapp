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
    // FUNCIONES AUXILIARES
    // -----------------------------------------
    function buscarVista($vista, $baseCore, $baseMicro) {
        $ruta = $baseCore . $vista . '.php';
        if (file_exists($ruta)) return $ruta;

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
    if (!$rutaVista) {
        $vista = '404';
        $rutaVista = $baseCore . '404.php';
    }

    // -----------------------------------------
    // CARGA DEL LAYOUT PRINCIPAL (UNA SOLA VEZ)
    // -----------------------------------------
    include $baseCore . 'layout.php';
