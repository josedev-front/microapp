<?php
// microservices/back-admision/diagnostico-ejecutivos.php
require_once __DIR__ . '/config/database_back_admision.php';
require_once __DIR__ . '/controllers/TeamController.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== DIAGNÓSTICO EJECUTIVOS BACK ADMISIÓN ===\n\n";

$teamController = new TeamController();

// 1. Usuarios en Micro&SOHO
echo "1. USUARIOS MICRO&SOHO:\n";
$usuarios_micro = $teamController->getUsuariosMicroSOHO();
echo "Total: " . count($usuarios_micro) . "\n";
foreach ($usuarios_micro as $usuario) {
    echo "   - ID: " . $usuario['id'] . " | " . $usuario['nombre_completo'] . " | " . $usuario['work_area'] . "\n";
}

// 2. Usuarios en Back Admisión
echo "\n2. USUARIOS BACK ADMISIÓN:\n";
$usuarios_back = $teamController->getUsuariosBackAdmision();
echo "Total: " . count($usuarios_back) . "\n";
foreach ($usuarios_back as $usuario) {
    echo "   - ID: " . $usuario['user_id'] . " | " . $usuario['nombre_completo'] . " | Estado: " . ($usuario['estado'] ?? 'N/A') . "\n";
}

// 3. Ejecutivos disponibles
echo "\n3. EJECUTIVOS DISPONIBLES:\n";
$ejecutivos = $teamController->getEjecutivosDisponibles();
echo "Total: " . count($ejecutivos) . "\n";
foreach ($ejecutivos as $ejecutivo) {
    echo "   - ID: " . $ejecutivo['user_id'] . " | " . $ejecutivo['nombre_completo'] . " | Estado: " . $ejecutivo['estado'] . " | Casos: " . $ejecutivo['casos_activos'] . "\n";
}

// 4. Verificar horarios
echo "\n4. HORARIOS DE EJECUTIVOS:\n";
try {
    $db = getBackAdmisionDB();
    $stmt = $db->query("SELECT user_id, nombre_completo, COUNT(*) as dias FROM horarios_usuarios WHERE activo = 1 GROUP BY user_id, nombre_completo");
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total con horarios: " . count($horarios) . "\n";
    foreach ($horarios as $horario) {
        echo "   - ID: " . $horario['user_id'] . " | " . $horario['nombre_completo'] . " | Días: " . $horario['dias'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
?>