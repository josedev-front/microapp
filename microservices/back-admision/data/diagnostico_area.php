<?php
// microservices/back-admision/data/diagnostico_area.php
session_start();
require_once __DIR__ . '/../config/database_back_admision.php';

echo "<pre>";
echo "=== DIAGNÓSTICO DE ÁREA Y EJECUTIVOS ===\n\n";

// 1. Ver datos de sesión
echo "1. DATOS DE SESIÓN:\n";
echo "   User ID: " . ($_SESSION['id'] ?? $_SESSION['user_id'] ?? 'NO') . "\n";
echo "   Nombre: " . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '') . "\n";
echo "   Área: " . ($_SESSION['work_area'] ?? 'NO DEFINIDA') . "\n";
echo "   Rol: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "\n\n";

// 2. Verificar ejecutivos en la base de datos
$db = getBackAdmisionDB();

echo "2. EJECUTIVOS EN BASE DE DATOS:\n";

// Ver en estado_usuarios
$stmt = $db->query("SELECT * FROM estado_usuarios");
$ejecutivos_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($ejecutivos_estado)) {
    echo "   ❌ NO hay ejecutivos en estado_usuarios\n";
} else {
    echo "   ✅ Ejecutivos en estado_usuarios:\n";
    foreach ($ejecutivos_estado as $ejec) {
        echo "      - ID: {$ejec['user_id']}, Estado: {$ejec['estado']}\n";
    }
}

// Ver en horarios_usuarios
$stmt = $db->query("SELECT * FROM horarios_usuarios WHERE area = 'Depto Micro&SOHO'");
$ejecutivos_horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($ejecutivos_horarios)) {
    echo "   ❌ NO hay ejecutivos en horarios_usuarios para Micro&SOHO\n";
} else {
    echo "   ✅ Ejecutivos en horarios_usuarios (Micro&SOHO):\n";
    foreach ($ejecutivos_horarios as $ejec) {
        echo "      - ID: {$ejec['user_id']}, Nombre: {$ejec['nombre_completo']}\n";
    }
}

echo "\n3. RECOMENDACIÓN:\n";

if (($_SESSION['work_area'] ?? '') !== 'Depto Micro&SOHO') {
    echo "   ⚠️  Tu área es '{$_SESSION['work_area']}' pero el sistema necesita 'Depto Micro&SOHO'\n";
    echo "   💡 Solución: Forzar el área en el código o cambiar tu área en la base de datos\n";
}

if (empty($ejecutivos_estado) && empty($ejecutivos_horarios)) {
    echo "   ⚠️  No hay ejecutivos creados en la base de datos\n";
    echo "   💡 Solución: Ejecutar el script de creación de ejecutivos\n";
}

echo "</pre>";
?>