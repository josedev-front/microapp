<?php
// microservices/back-admision/data/verificar_fix.php
require_once __DIR__ . '/../config/database_back_admision.php';
$db = getBackAdmisionDB();

echo "<pre>=== VERIFICANDO FIX ===\n";

// Verificar que LoadBalancer encuentre ejecutivos
require_once __DIR__ . '/../lib/LoadBalancer.php';
$loadBalancer = new LoadBalancer();

$ejecutivos = $loadBalancer->getEjecutivosDisponibles('Depto Micro&SOHO');

if (empty($ejecutivos)) {
    echo "❌ LoadBalancer aún NO encuentra ejecutivos\n";
} else {
    echo "✅ LoadBalancer ENCUENTRA " . count($ejecutivos) . " ejecutivos:\n";
    foreach ($ejecutivos as $ejec) {
        echo "   👤 {$ejec['nombre_completo']} - Casos: {$ejec['casos_activos']} - Estado: {$ejec['estado']}\n";
    }
}

echo "</pre>";
?>