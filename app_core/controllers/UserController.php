<?php
// app_core/controllers/UserController.php

class UserController {
    
    // Función para verificar contraseña (compatible con Django y PHP)
    public static function verifyPassword($password, $hashed_password) {
        // Si es hash de Django (pbkdf2_sha256)
        if (strpos($hashed_password, 'pbkdf2_sha256$') === 0) {
            return self::verifyDjangoPassword($password, $hashed_password);
        }
        
        // Si es hash de PHP (password_hash)
        return password_verify($password, $hashed_password);
    }
    
    // Función para verificar contraseña Django
    private static function verifyDjangoPassword($password, $hashed_password) {
        $parts = explode('$', $hashed_password);
        if (count($parts) !== 4) return false;
        
        list($algorithm, $iterations, $salt, $hash) = $parts;
        
        $new_hash = hash_pbkdf2("sha256", $password, $salt, $iterations, 0, true);
        $new_hash_base64 = base64_encode($new_hash);
        
        return hash_equals($hash, $new_hash_base64);
    }
    
    // Función para login
    public static function login($username, $password) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM core_customuser 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$username, $username]);
            $usuario = $stmt->fetch();
            
            if ($usuario && self::verifyPassword($password, $usuario['password'])) {
                // Iniciar sesión
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['role'] = $usuario['role'];
                $_SESSION['work_area'] = $usuario['work_area'];
                
                // Actualizar último login
                $stmt = $pdo->prepare("UPDATE core_customuser SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                return ['success' => true, 'user' => $usuario];
            }
            
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error del sistema'];
        }
    }
    
    // Función para generar hash compatible
    public static function generatePasswordHash($password) {
        // Usar password_hash estándar de PHP (más simple)
        return password_hash($password, PASSWORD_DEFAULT);
    }

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
                $stmt = $pdo->query("SELECT * FROM core_customuser WHERE is_active = 1 ORDER BY work_area, first_name, last_name");
                $usuarios = $stmt->fetchAll();
                
            } elseif ($usuario_actual['role'] === 'supervisor') {
                // Supervisores ven usuarios de su área y sus subordinados directos
                $stmt = $pdo->prepare("
                    SELECT * FROM core_customuser 
                    WHERE (work_area = ? OR manager_id = ?) 
                    AND is_active = 1 
                    ORDER BY first_name, last_name
                ");
                $stmt->execute([$usuario_actual['work_area'], $usuario_actual['id']]);
                $usuarios = $stmt->fetchAll();
                
            } else {
                // Ejecutivos, backups, QA ven usuarios de su área
                if (!empty($usuario_actual['work_area'])) {
                    $stmt = $pdo->prepare("
                        SELECT id, first_name, middle_name, last_name, second_last_name, 
                               role, work_area, manager_id, employee_id, email, phone_number
                        FROM core_customuser 
                        WHERE work_area = ? AND is_active = 1 
                        ORDER BY first_name, last_name
                    ");
                    $stmt->execute([$usuario_actual['work_area']]);
                    $usuarios = $stmt->fetchAll();
                } else {
                    // Si no tiene área asignada, mostrar información básica del usuario actual
                    $stmt = $pdo->prepare("
                        SELECT id, first_name, middle_name, last_name, second_last_name, 
                               role, work_area, manager_id, employee_id, email, phone_number
                        FROM core_customuser 
                        WHERE id = ? AND is_active = 1
                    ");
                    $stmt->execute([$usuario_actual['id']]);
                    $usuarios = $stmt->fetchAll();
                }
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
            'mis_subordinados' => 0,
            'mi_manager' => null
        ];
        
        try {
            if (in_array($usuario_actual['role'], ['developer', 'superuser'])) {
                // Para superuser/developer
                if ($usuario_actual['work_area']) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_customuser WHERE work_area = ? AND is_active = 1");
                    $stmt->execute([$usuario_actual['work_area']]);
                    $estadisticas['usuarios_mi_area'] = $stmt->fetchColumn();
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
                // Para ejecutivos, backups, QA
                $estadisticas['usuarios_mi_area'] = $estadisticas['total_usuarios'];
                
                // Obtener información del manager
                if ($usuario_actual['manager_id']) {
                    $stmt = $pdo->prepare("SELECT first_name, last_name FROM core_customuser WHERE id = ?");
                    $stmt->execute([$usuario_actual['manager_id']]);
                    $manager = $stmt->fetch();
                    if ($manager) {
                        $estadisticas['mi_manager'] = $manager['first_name'] . ' ' . $manager['last_name'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
        }
        
        return $estadisticas;
    }

    public static function obtenerUsuarioPorId($id) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.*,
                    m.first_name as manager_first_name,
                    m.last_name as manager_last_name
                FROM core_customuser u
                LEFT JOIN core_customuser m ON u.manager_id = m.id
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error en obtenerUsuarioPorId: " . $e->getMessage());
            return null;
        }
    }

    public static function obtenerAreas() {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->query("
                SELECT DISTINCT work_area 
                FROM core_customuser 
                WHERE work_area IS NOT NULL AND work_area != ''
                ORDER BY work_area
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerAreas: " . $e->getMessage());
            return [];
        }
    }

    public static function obtenerJefesPotenciales() {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, role, work_area
                FROM core_customuser 
                WHERE is_active = 1 
                AND role IN ('supervisor', 'superuser', 'developer')
                ORDER BY first_name, last_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerJefesPotenciales: " . $e->getMessage());
            return [];
        }
    }

    public static function actualizarUsuario($usuario_id, $datos) {
        // Verificar permisos
        $usuario_actual = obtenerUsuarioActual();
        if (!$usuario_actual || !in_array($usuario_actual['role'], ['superuser', 'developer', 'supervisor'])) {
            return ['success' => false, 'message' => 'No tienes permisos para editar usuarios'];
        }

        // Validaciones básicas
        if (empty($datos['first_name']) || empty($datos['last_name']) || empty($datos['employee_id']) || empty($datos['email'])) {
            return ['success' => false, 'message' => 'Los campos obligatorios no pueden estar vacíos'];
        }

        $pdo = conexion();
        
        try {
            // Preparar datos para la actualización
            $campos = [];
            $valores = [];

            // Campos básicos
            $campos[] = "first_name = ?";
            $valores[] = trim($datos['first_name']);

            $campos[] = "middle_name = ?";
            $valores[] = !empty($datos['middle_name']) ? trim($datos['middle_name']) : null;

            $campos[] = "last_name = ?";
            $valores[] = trim($datos['last_name']);

            $campos[] = "second_last_name = ?";
            $valores[] = !empty($datos['second_last_name']) ? trim($datos['second_last_name']) : null;

            $campos[] = "employee_id = ?";
            $valores[] = trim($datos['employee_id']);

            $campos[] = "email = ?";
            $valores[] = trim($datos['email']);

            $campos[] = "username = ?";
            $valores[] = trim($datos['username']);

            // Campos opcionales
            if (isset($datos['phone_number'])) {
                $campos[] = "phone_number = ?";
                $valores[] = !empty($datos['phone_number']) ? trim($datos['phone_number']) : null;
            }

            if (isset($datos['birth_date'])) {
                $campos[] = "birth_date = ?";
                $valores[] = !empty($datos['birth_date']) ? $datos['birth_date'] : null;
            }

            if (isset($datos['gender'])) {
                $campos[] = "gender = ?";
                $valores[] = $datos['gender'];
            }

            // Rol y área (solo para superuser y developer)
            if (in_array($usuario_actual['role'], ['superuser', 'developer'])) {
                if (isset($datos['role'])) {
                    $campos[] = "role = ?";
                    $valores[] = $datos['role'];
                }

                if (isset($datos['work_area'])) {
                    $campos[] = "work_area = ?";
                    $valores[] = $datos['work_area'];
                }
            } elseif ($usuario_actual['role'] === 'supervisor') {
                // Los supervisores solo pueden editar usuarios de su área
                $usuario_a_editar = self::obtenerUsuarioPorId($usuario_id);
                if ($usuario_a_editar && $usuario_a_editar['work_area'] !== $usuario_actual['work_area']) {
                    return ['success' => false, 'message' => 'Solo puedes editar usuarios de tu área'];
                }
            }

            // Jefe directo
            if (isset($datos['manager_id'])) {
                $campos[] = "manager_id = ?";
                $valores[] = !empty($datos['manager_id']) ? intval($datos['manager_id']) : null;
            }

            // Estado activo
            if (isset($datos['is_active'])) {
                $campos[] = "is_active = ?";
                $valores[] = $datos['is_active'] ? 1 : 0;
            }

            // Avatar predefinido
            if (isset($datos['avatar_predefinido'])) {
                $campos[] = "avatar_predefinido = ?";
                $valores[] = $datos['avatar_predefinido'];
            }

            // Agregar ID al final de los valores
            $valores[] = $usuario_id;

            // Construir y ejecutar la consulta
            $sql = "UPDATE core_customuser SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);

            return ['success' => true, 'message' => 'Usuario actualizado correctamente'];

        } catch (Exception $e) {
            error_log("Error en actualizarUsuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar el usuario: ' . $e->getMessage()];
        }
    }

    public static function obtenerRoles() {
        return [
            'ejecutivo',
            'supervisor', 
            'backup',
            'developer',
            'superuser',
            'agente_qa'
        ];
    }

    public static function obtenerUsuariosActivos() {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->query("
                SELECT id, first_name, last_name, employee_id, work_area, role
                FROM core_customuser 
                WHERE is_active = 1 
                ORDER BY first_name, last_name
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerUsuariosActivos: " . $e->getMessage());
            return [];
        }
    }
}