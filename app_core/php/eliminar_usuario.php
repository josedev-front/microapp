<?php
// app_core/templates/eliminar_usuario.php

// Verificar permisos
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual || !in_array($usuario_actual['role'], ['developer', 'superuser'])) {
    header('Location: ./?vista=equipo_user');
    exit;
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // No permitir auto-eliminación
    if ($usuario_id == $usuario_actual['id']) {
        $_SESSION['flash_message'] = "No puedes eliminarte a ti mismo";
        $_SESSION['flash_type'] = "danger";
        header('Location: ./?vista=equipo_user');
        exit;
    }
    
    try {
        $pdo = conexion();
        
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT username FROM core_customuser WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // En lugar de eliminar físicamente, desactivamos el usuario (soft delete)
            $stmt = $pdo->prepare("UPDATE core_customuser SET is_active = 0 WHERE id = ?");
            $stmt->execute([$usuario_id]);
            
            $_SESSION['flash_message'] = "Usuario " . $usuario['username'] . " desactivado correctamente";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Usuario no encontrado";
            $_SESSION['flash_type'] = "danger";
        }
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Error al eliminar el usuario: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header('Location: ./?vista=equipo_user');
    exit;
} else {
    // Si no es POST, redirigir
    header('Location: ./?vista=equipo_user');
    exit;
}