<?php
// microservices/back-admision/data/diagnostico_rutas.php
session_start();
require_once __DIR__ . '/../init.php';

echo "<pre>=== DIAGNÓSTICO DE RUTAS API ===\n\n";

// Probar diferentes rutas
$rutas_a_probar = [
    'procesar-caso',
    'gestionar-caso', 
    'admision-api-gestionar-caso',
    'cambiar-estado'
];

foreach ($rutas_a_probar as $ruta) {
    echo "🔍 Probando ruta: $ruta\n";
    
    // Simular request
    $_GET['vista'] = 'back-admision';
    $_GET['action'] = $ruta;
    
    // Incluir routes para ver qué pasa
    ob_start();
    try {
        include __DIR__ . '/../routes.php';
        $output = ob_get_clean();
        
        if (strpos($output, 'success') !== false || strpos($output, 'error') !== false) {
            echo "   ✅ FUNCIONA - Devuelve JSON\n";
        } else if (strpos($output, '<html') !== false) {
            echo "   ❌ FALLA - Devuelve HTML (carga vista)\n";
        } else if (empty($output)) {
            echo "   ❌ FALLA - No devuelve nada\n";
        } else {
            echo "   ⚠️  INCIERTO - Devuelve: " . substr($output, 0, 100) . "...\n";
        }
    } catch (Exception $e) {
        echo "   💥 ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== VERIFICANDO ARCHIVOS API ===\n";
$archivos_api = [
    'ingresar_caso.php',
    'gestionar_caso.php',
    'cambiar_estado.php'
];

foreach ($archivos_api as $archivo) {
    $ruta = __DIR__ . '/../api/' . $archivo;
    if (file_exists($ruta)) {
        echo "   ✅ $archivo - EXISTE\n";
    } else {
        echo "   ❌ $archivo - NO EXISTE\n";
    }
}

echo "</pre>";
?>