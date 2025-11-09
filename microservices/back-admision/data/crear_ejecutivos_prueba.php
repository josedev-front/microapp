<?php
// microservices/back-admision/data/crear_ejecutivos_simple.php

require_once __DIR__ . '/../config/database_back_admision.php';
$db = getBackAdmisionDB();

// Insertar ejecutivos directamente en estado_usuarios (mínimo requerido)
$ejecutivos = [
    [1000, 'Ana García López', 'activo'],
    [1001, 'Carlos Rodríguez Silva', 'activo'], 
    [1002, 'María Fernández Castro', 'activo']
];

foreach ($ejecutivos as $ejecutivo) {
    $stmt = $db->prepare("
        INSERT IGNORE INTO estado_usuarios 
        (user_id, user_external_id, estado, ultima_actualizacion)
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$ejecutivo[0], $ejecutivo[0], $ejecutivo[2]]);
    echo "✅ Ejecutivo: {$ejecutivo[1]} - ID: {$ejecutivo[0]}\n";
}

echo "🎯 Ejecutivos de prueba creados. Ahora prueba ingresar un caso.\n";
?>