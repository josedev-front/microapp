<?php
// microservices/middy/routes.php

function getMiddyRoutes() {
    return [
        'middy' => __DIR__ . '/views/chat.php',
        'middy_api' => __DIR__ . '/api/chat.php',
        'middy_admin' => __DIR__ . '/views/admin.php',
    ];
}

// Función para verificar permisos de acceso a Middy
function middy_check_permissions($user_role) {
    $allowed_roles = ['ejecutivo', 'supervisor', 'backup', 'developer', 'superuser', 'agente_qa'];
    return in_array($user_role, $allowed_roles);
}