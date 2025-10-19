<?php
// app_core/config/helpers.php

// Definir constantes de rutas
define('BASE_URL', 'http://localhost:3000/');
define('ASSETS_URL', BASE_URL . 'public/assets/');
define('APP_ROOT', dirname(__DIR__));

// Función para generar URLs
function url($vista = '') {
    return BASE_URL . '?vista=' . $vista;
}

// Función para incluir assets
function asset($path) {
    return ASSETS_URL . $path;
}

// Función para debug
function debug($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

// Función para log de errores personalizado
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - " . json_encode($context);
    }
    error_log($logMessage);
}

// Función para sanitizar datos de formularios
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para formatear fecha
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

function obtenerUsuarioActual() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        // Aquí tu lógica para obtener el usuario de la base de datos
        $pdo = conexion();
        $stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    return null;
}