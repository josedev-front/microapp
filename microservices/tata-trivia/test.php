<?php
echo "<h1>Tata Trivia - Test</h1>";
echo "<p>Si ves esto, el microservicio está funcionando.</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Test de base de datos
try {
    $db = new PDO("mysql:host=localhost;dbname=tata_trivia;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Conexión a BD exitosa</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error BD: " . $e->getMessage() . "</p>";
}
?>