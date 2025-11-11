<?php
// microservices/back-admision/api/test_api.php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => '✅ API funcionando correctamente',
    'session' => [
        'user_id' => $_SESSION['user_id'] ?? 'NO',
        'role' => $_SESSION['role'] ?? 'NO'
    ]
]);
exit;
?>