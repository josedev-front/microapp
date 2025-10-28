<?php
// microservices/tata-trivia/debug_controller.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug - Controlador TriviaController</h1>";

// Test 1: Incluir init
echo "<h2>1. Incluyendo init.php</h2>";
try {
    require_once 'init.php';
    echo "✅ init.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Verificar si existe el archivo del controlador
echo "<h2>2. Verificando archivo TriviaController.php</h2>";
$controllerFile = __DIR__ . '/controllers/TriviaController.php';
if (file_exists($controllerFile)) {
    echo "✅ Archivo existe: " . $controllerFile . "<br>";
    
    // Test 3: Verificar sintaxis del archivo
    $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile));
    echo "✅ Sintaxis: " . $syntaxCheck . "<br>";
} else {
    echo "❌ Archivo NO existe: " . $controllerFile . "<br>";
}

// Test 4: Verificar si la clase existe
echo "<h2>3. Verificando clase TriviaController</h2>";
if (class_exists('TriviaController')) {
    echo "✅ Clase TriviaController existe<br>";
    
    // Test 5: Instanciar el controlador
    try {
        $controller = new TriviaController();
        echo "✅ Controlador instanciado correctamente<br>";
        
        // Test 6: Probar método generateJoinCode
        try {
            $code = $controller->generateJoinCode();
            echo "✅ generateJoinCode() funciona: " . $code . "<br>";
        } catch (Exception $e) {
            echo "❌ generateJoinCode() falló: " . $e->getMessage() . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error instanciando controlador: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Clase TriviaController NO existe<br>";
}

// Test 7: Verificar base de datos
echo "<h2>4. Verificando base de datos</h2>";
try {
    $db = getTriviaDatabaseConnection();
    if ($db) {
        echo "✅ Conexión a BD exitosa<br>";
        
        // Test de consulta simple
        $stmt = $db->query("SELECT COUNT(*) as count FROM trivias");
        $result = $stmt->fetch();
        echo "✅ Consulta BD funciona: " . $result['count'] . " trivias en BD<br>";
    } else {
        echo "❌ Conexión a BD falló<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Debug completado</h2>";