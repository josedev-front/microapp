<?php
function getDatabaseConnection() {
    try {
        // Usar la misma configuración que tu aplicación principal
        require_once __DIR__ . '/../../app_core/config/database.php';
        return conexion(); // O el nombre de tu función de conexión principal
    } catch (Exception $e) {
        throw new Exception("Error conectando a BD principal: " . $e->getMessage());
    }
}
?>