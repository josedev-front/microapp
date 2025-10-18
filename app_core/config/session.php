<?php
// app_core/config/session.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Función para obtener el usuario actual
function obtenerUsuarioActual() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $pdo = conexion();
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, first_name, middle_name, last_name, second_last_name, 
                   employee_id, role, birth_date, gender, avatar, phone_number, work_area, 
                   manager_id, avatar_predefinido, is_active, last_login, date_joined
            FROM core_customuser 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error en obtenerUsuarioActual: " . $e->getMessage());
        return null;
    }
}

// Función para verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para limpiar cadena (seguridad)
function limpiarCadena($cadena) {
    if ($cadena === null) {
        return null;
    }
    return htmlspecialchars(trim($cadena), ENT_QUOTES, 'UTF-8');
}

// Función para redirigir si no está autenticado
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: ./?vista=login');
        exit;
    }
}

// Función para verificar permisos de rol
function tienePermiso($rolesPermitidos) {
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }
    
    if (is_array($rolesPermitidos)) {
        return in_array($usuario['role'], $rolesPermitidos);
    }
    
    return $usuario['role'] === $rolesPermitidos;
}