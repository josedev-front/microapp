<?php
// microservices/tata-trivia/index.php - Punto de entrada principal

// Debugging
error_log("=== TATA TRIVIA INDEX ACCESSED ===");
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

// Incluir el sistema de rutas
require_once __DIR__ . '/routes.php';

// Si llegamos aquí, significa que ninguna ruta hizo exit
echo "Tata Trivia - Sistema de rutas cargado pero no se ejecutó ninguna ruta específica";
?>