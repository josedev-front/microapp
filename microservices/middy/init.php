<?php
// microservices/middy/init.php

// Cargar autoload de Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback: cargar manualmente si no hay Composer
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use Dotenv\Dotenv;

// Cargar variables de entorno
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configuración de Middy
define('MIDDY_BASE_PATH', __DIR__);
define('MIDDY_DATA_PATH', __DIR__ . '/data/docs');
define('MIDDY_UPLOADS_PATH', __DIR__ . '/data/uploads');

// Crear directorios si no existen
if (!file_exists(MIDDY_DATA_PATH)) {
    mkdir(MIDDY_DATA_PATH, 0755, true);
}
if (!file_exists(MIDDY_UPLOADS_PATH)) {
    mkdir(MIDDY_UPLOADS_PATH, 0755, true);
}

// Función de seguridad
function middy_sanitize_input($data) {
    if (is_array($data)) {
        return array_map('middy_sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}



// Ahora cargamos las clases después de definir todo
require_once __DIR__ . '/lib/Helpers.php';
require_once __DIR__ . '/lib/OllamaClient.php';
require_once __DIR__ . '/lib/FileIndex.php';
require_once __DIR__ . '/controllers/ChatController.php';