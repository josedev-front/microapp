<?php
// microservices/tata-trivia/routes.php - VERSIÓN COMPLETA Y CORREGIDA

// Debugging
error_log("=== TATA TRIVIA ACCESSED ===");
error_log("Full URL: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

// Evitar session_start() duplicado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debugging
error_log("=== TATA TRIVIA ROUTES START ===");
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

// Incluir inicialización (sin conflictos)
require_once __DIR__ . '/init.php';

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/microservices/tata-trivia';

// Extraer la ruta relativa
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

error_log("Cleaned Path: " . $path);

// Si es la raíz, cargar welcome
if (empty($path) || $path === 'index.php' || $path === '') {
    error_log("Loading welcome page");
    loadTriviaView('welcome');
    exit;
}

// Dividir en segmentos
$segments = $path ? explode('/', $path) : [];
error_log("Segments: " . json_encode($segments));

// Routing
try {
    if (empty($segments[0])) {
        loadTriviaView('welcome');
        exit;
    }

    switch($segments[0]) {
        case 'host':
            if (!class_exists('HostController')) {
                error_log("HostController no existe");
                http_response_code(500);
                echo "Error: Controlador no disponible";
                exit;
            }
            
            $controller = new HostController();
            if (empty($segments[1]) || $segments[1] === 'setup') {
                $controller->setup();
            } elseif ($segments[1] === 'questions') {
                $controller->questions();
            } elseif ($segments[1] === 'lobby') {
                $controller->lobby();
            } elseif ($segments[1] === 'game') {
                $controller->gameHost();
            } elseif ($segments[1] === 'history') {
                loadTriviaView('history/host_history');
            } else {
                http_response_code(404);
                loadTriviaView('404');
            }
            break;

        case 'player':
            if (empty($segments[1]) || $segments[1] === 'join') {
                loadTriviaView('player/join');
            } elseif ($segments[1] === 'game') {
                loadTriviaView('player/game');
            } elseif ($segments[1] === 'game_player') {
                loadTriviaView('player/game_player');
            } elseif ($segments[1] === 'history') {
                loadTriviaView('history/player_history');
            } else {
                http_response_code(404);
                loadTriviaView('404');
            }
            break;

        case 'api':
            if (!empty($segments[1])) {
                $api_file = TATA_TRIVIA_API . '/' . $segments[1] . '.php';
                if (file_exists($api_file)) {
                    require_once $api_file;
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'API endpoint no encontrado: ' . $segments[1]]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Endpoint API no especificado']);
            }
            break;

        case 'results':
            loadTriviaView('results');
            break;

        case 'welcome':
            loadTriviaView('welcome');
            break;

        case 'test.php':
        case 'debug.php':
            // Permitir acceso a archivos de prueba
            if (file_exists(__DIR__ . '/' . $segments[0])) {
                require_once __DIR__ . '/' . $segments[0];
            } else {
                http_response_code(404);
                echo "Archivo de prueba no encontrado";
            }
            break;

        default:
            http_response_code(404);
            loadTriviaView('404');
    }
} catch (Exception $e) {
    error_log("Error en Tata Trivia: " . $e->getMessage());
    http_response_code(500);
    
    // Mostrar error amigable
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error - Tata Trivia</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> Error del Sistema</h4>
                <p>Ha ocurrido un error interno. Por favor, intenta nuevamente.</p>
                <small>Detalle técnico: ' . htmlspecialchars($e->getMessage()) . '</small>
                <div class="mt-3">
                    <a href="/microservices/tata-trivia/" class="btn btn-primary">Volver al Inicio</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

exit;
?>