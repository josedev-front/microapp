<?php
// microservices/back-admision/data/solucion_completa.php
require_once __DIR__ . '/../config/database_back_admision.php';
$db = getBackAdmisionDB();

echo "<pre>=== SOLUCIÓN COMPLETA - CREANDO HORARIOS ===\n";

// 1. AGREGAR HORARIOS para los ejecutivos que ya existen
$ejecutivos = [
    [1000, 1000, 'Ana García López'],
    [1001, 1001, 'Carlos Rodríguez Silva'], 
    [1002, 1002, 'María Fernández Castro'],
    [84, 84, 'Admin Corporaciones'] // ← TÚ como ejecutivo también
];

$dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

foreach ($ejecutivos as $ejec) {
    foreach ($dias_semana as $dia) {
        $stmt = $db->prepare("
            INSERT IGNORE INTO horarios_usuarios 
            (user_id, user_external_id, nombre_completo, area, dia_semana, hora_entrada, hora_salida, hora_almuerzo_inicio, hora_almuerzo_fin, activo)
            VALUES (?, ?, ?, 'Depto Micro&SOHO', ?, '09:00:00', '18:00:00', '13:00:00', '14:00:00', 1)
        ");
        
        $stmt->execute([
            $ejec[0], $ejec[1], $ejec[2], $dia
        ]);
    }
    echo "✅ Horarios creados para: {$ejec[2]}\n";
}

// 2. VERIFICAR lo que se creó
echo "\n=== VERIFICACIÓN ===\n";

$stmt = $db->query("
    SELECT DISTINCT user_id, nombre_completo, area 
    FROM horarios_usuarios 
    WHERE area = 'Depto Micro&SOHO'
");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Ejecutivos con horarios en Micro&SOHO:\n";
foreach ($result as $row) {
    echo "  👤 ID: {$row['user_id']} - {$row['nombre_completo']} - Área: {$row['area']}\n";
}

echo "\n🎯 LISTO! Ahora el balanceo debería funcionar.\n";
echo "📌 Se crearon horarios para 4 ejecutivos (incluyéndote).\n";
echo "🔄 Actualiza y prueba ingresar un caso.\n";
echo "</pre>";