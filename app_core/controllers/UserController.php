<?php
// app_core/controllers/UserController.php

class UserController {
    
    public static function eliminarUsuario() {
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
    
            // No permitir auto-eliminación
            if ($usuario_id == $usuario_actual['id']) {
                $_SESSION['flash_message'] = "No puedes eliminarte a ti mismo";
                $_SESSION['flash_type'] = "danger";
                header('Location: ./?vista=equiposuser');
                exit;
            }
            
            try {
                $pdo = conexion();
                
                // Verificar si el usuario existe
                // Verificar si el usuario existe
            $stmt = $pdo->prepare("SELECT username FROM core_customuser WHERE id = ? AND is_active = 1");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Soft delete - desactivar usuario
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
    }
    
    header('Location: ./?vista=equiposuser');
    exit;
}
    
    public static function obtenerUsuariosPorRol($usuario_actual) {
    $pdo = conexion();
    $usuarios = [];
    
    try {
        if (in_array($usuario_actual['role'], ['developer', 'superuser'])) {
            // Acceso completo - ver TODOS los usuarios activos
            $stmt = $pdo->query("SELECT * FROM core_customuser WHERE is_active = 1 ORDER BY first_name, last_name");
            $usuarios = $stmt->fetchAll();
        } elseif ($usuario_actual['role'] === 'supervisor') {
            // Solo usuarios de su área
            $stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE work_area = ? AND is_active = 1 ORDER BY first_name, last_name");
            $stmt->execute([$usuario_actual['work_area']]);
            $usuarios = $stmt->fetchAll();
        } else {
            // Solo información básica de su área
            $stmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, second_last_name, role, work_area, manager_id FROM core_customuser WHERE work_area = ? AND is_active = 1 ORDER BY first_name, last_name");
            $stmt->execute([$usuario_actual['work_area']]);
            $usuarios = $stmt->fetchAll();
        }
        
    } catch (Exception $e) {
        error_log("Error en obtenerUsuariosPorRol: " . $e->getMessage());
        return [];
    }
    
    return $usuarios;
}
    
    public static function obtenerEstadisticas($usuario_actual, $usuarios) {
    $pdo = conexion();
    $estadisticas = [
        'total_usuarios' => count($usuarios),
        'usuarios_mi_area' => 0,
        'mis_subordinados' => 0
    ];
    
    try {
        if (in_array($usuario_actual['role'], ['developer', 'superuser'])) {
            // Para superuser/developer, usuarios en su área
            if ($usuario_actual['work_area']) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_customuser WHERE work_area = ? AND is_active = 1");
                $stmt->execute([$usuario_actual['work_area']]);
                $estadisticas['usuarios_mi_area'] = $stmt->fetchColumn();
            } else {
                $estadisticas['usuarios_mi_area'] = 0;
            }
            
            // Subordinados directos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_customuser WHERE manager_id = ? AND is_active = 1");
            $stmt->execute([$usuario_actual['id']]);
            $estadisticas['mis_subordinados'] = $stmt->fetchColumn();
            
        } elseif ($usuario_actual['role'] === 'supervisor') {
            $estadisticas['usuarios_mi_area'] = $estadisticas['total_usuarios'];
            
            // Subordinados directos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_customuser WHERE manager_id = ? AND is_active = 1");
            $stmt->execute([$usuario_actual['id']]);
            $estadisticas['mis_subordinados'] = $stmt->fetchColumn();
            
        } else {
            $estadisticas['usuarios_mi_area'] = $estadisticas['total_usuarios'];
        }
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticas: " . $e->getMessage());
    }
    
    return $estadisticas;
}

}