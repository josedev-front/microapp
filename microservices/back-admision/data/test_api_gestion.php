<?php
// microservices/back-admision/data/test_api_gestion.php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => '✅ API de prueba funcionando',
    'timestamp' => date('Y-m-d H:i:s'),
    'session' => isset($_SESSION['user_id']) ? 'activa' : 'inactiva'
]);
?>