<?php
// Obtener datos del usuario actual
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Función para obtener la URL del avatar - CORREGIDA
function obtenerAvatarUrl($usuario) {
    // Verificar si tiene avatar personalizado y si el archivo existe
    if (!empty($usuario['avatar'])) {
        $avatar_relative_path = 'assets/avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'];
        $full_path = __DIR__ . '/../../public/' . $avatar_relative_path;
        
        // Verificar si el archivo realmente existe
        if (file_exists($full_path)) {
            // Usar la misma base URL que las otras imágenes
            return ASSETS_URL . 'avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'] . '?v=' . filemtime($full_path);
        }
    }
    
    // Si no tiene avatar personalizado o el archivo no existe, usar el predefinido
    $avatar_num = str_replace('default', '', $usuario['avatar_predefinido'] ?? 'default1');
    return ASSETS_URL . 'img/default/default' . $avatar_num . '.png';
}

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = conexion();
    
    // Procesar avatar personalizado
    if (isset($_POST['update_avatar'])) {
        $avatar_predefinido = limpiarCadena($_POST['avatar_predefinido'] ?? 'default1');
        
        // Manejar subida de avatar personalizado
        if (isset($_FILES['avatar_personalizado']) && $_FILES['avatar_personalizado']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['avatar_personalizado'];
            
            // Validaciones
            $tipo_permitido = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tamano_maximo = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($archivo['type'], $tipo_permitido)) {
                $mensaje = "Formato de archivo no permitido. Use JPG, PNG o GIF.";
                $tipo_mensaje = "danger";
            } elseif ($archivo['size'] > $tamano_maximo) {
                $mensaje = "El archivo es demasiado grande. Máximo 2MB.";
                $tipo_mensaje = "danger";
            } else {
                try {
                    // Crear directorio si no existe
                    $directorio_avatar = __DIR__ . '/../../public/assets/avatares_usuarios/user_' . $usuario_actual['id'];
                    if (!is_dir($directorio_avatar)) {
                        mkdir($directorio_avatar, 0755, true);
                    }
                    
                    // Limpiar archivos antiguos primero
                    $archivos_antiguos = glob($directorio_avatar . '/*');
                    foreach ($archivos_antiguos as $archivo_antiguo) {
                        if (is_file($archivo_antiguo)) {
                            unlink($archivo_antiguo);
                        }
                    }
                    
                    // Generar nombre único para el archivo
                    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                    $nombre_archivo = 'avatar.' . $extension;
                    $ruta_completa = $directorio_avatar . '/' . $nombre_archivo;
                    
                    // Mover archivo
                    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                        // Actualizar base de datos
                        $stmt = $pdo->prepare("UPDATE core_customuser SET avatar = ?, avatar_predefinido = NULL WHERE id = ?");
                        $stmt->execute([$nombre_archivo, $usuario_actual['id']]);
                        
                        $mensaje = "Avatar personalizado actualizado correctamente";
                        $tipo_mensaje = "success";
                        $usuario_actual = obtenerUsuarioActual();
                    } else {
                        $mensaje = "Error al subir el archivo";
                        $tipo_mensaje = "danger";
                    }
                    
                } catch (Exception $e) {
                    $mensaje = "Error al procesar el avatar: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
        }
        // Manejar eliminación de avatar personalizado
        elseif (isset($_POST['eliminar_avatar']) && $_POST['eliminar_avatar'] === 'on') {
            try {
                // Eliminar archivo físico si existe
                $directorio_avatar = __DIR__ . '/../../public/assets/avatares_usuarios/user_' . $usuario_actual['id'];
                if (is_dir($directorio_avatar)) {
                    $archivos = glob($directorio_avatar . '/*');
                    foreach ($archivos as $archivo) {
                        if (is_file($archivo)) {
                            unlink($archivo);
                        }
                    }
                    // Opcional: eliminar el directorio si está vacío
                    if (count(glob($directorio_avatar . '/*')) === 0) {
                        rmdir($directorio_avatar);
                    }
                }
                
                // Actualizar base de datos
                $stmt = $pdo->prepare("UPDATE core_customuser SET avatar = NULL, avatar_predefinido = ? WHERE id = ?");
                $stmt->execute([$avatar_predefinido, $usuario_actual['id']]);
                
                $mensaje = "Avatar personalizado eliminado correctamente";
                $tipo_mensaje = "success";
                $usuario_actual = obtenerUsuarioActual();
                
            } catch (Exception $e) {
                $mensaje = "Error al eliminar el avatar: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
        // Solo cambiar avatar predefinido
        else {
            try {
                $stmt = $pdo->prepare("UPDATE core_customuser SET avatar_predefinido = ? WHERE id = ?");
                $stmt->execute([$avatar_predefinido, $usuario_actual['id']]);
                
                $mensaje = "Avatar predefinido actualizado correctamente";
                $tipo_mensaje = "success";
                $usuario_actual = obtenerUsuarioActual();
                
            } catch (Exception $e) {
                $mensaje = "Error al actualizar el avatar: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
    
    // Actualizar perfil
    if (isset($_POST['update_profile'])) {
        $phone_number = limpiarCadena($_POST['phone_number'] ?? '');
        $birth_date = limpiarCadena($_POST['birth_date'] ?? '');
        $gender = limpiarCadena($_POST['gender'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE core_customuser SET phone_number = ?, birth_date = ?, gender = ? WHERE id = ?");
            $stmt->execute([$phone_number, $birth_date, $gender, $usuario_actual['id']]);
            
            $mensaje = "Perfil actualizado correctamente";
            $tipo_mensaje = "success";
            $usuario_actual = obtenerUsuarioActual(); // Recargar datos
            
        } catch (Exception $e) {
            $mensaje = "Error al actualizar el perfil: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
    
    // Cambiar contraseña
    if (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password1 = $_POST['new_password1'] ?? '';
        $new_password2 = $_POST['new_password2'] ?? '';
        
        if (empty($old_password) || empty($new_password1) || empty($new_password2)) {
            $mensaje = "Todos los campos de contraseña son requeridos";
            $tipo_mensaje = "danger";
        } elseif ($new_password1 !== $new_password2) {
            $mensaje = "Las nuevas contraseñas no coinciden";
            $tipo_mensaje = "danger";
        } elseif (strlen($new_password1) < 8) {
            $mensaje = "La nueva contraseña debe tener al menos 8 caracteres";
            $tipo_mensaje = "danger";
        } else {
            try {
                // Verificar contraseña actual
                $stmt = $pdo->prepare("SELECT password FROM core_customuser WHERE id = ?");
                $stmt->execute([$usuario_actual['id']]);
                $usuario_db = $stmt->fetch();
                
                if ($usuario_db && password_verify($old_password, $usuario_db['password'])) {
                    // Actualizar contraseña
                    $new_password_hash = password_hash($new_password1, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE core_customuser SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password_hash, $usuario_actual['id']]);
                    
                    $mensaje = "Contraseña actualizada correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "La contraseña actual es incorrecta";
                    $tipo_mensaje = "danger";
                }
            } catch (Exception $e) {
                $mensaje = "Error al cambiar la contraseña: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}
?>

<div class="container py-5" style="margin-top: 5%; margin-bottom: 5%;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Mensajes -->
                    <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Encabezado con avatar -->
                    <div class="d-flex align-items-center mb-4 flex-column flex-md-row text-center text-md-start">
                        <img src="<?php echo obtenerAvatarUrl($usuario_actual); ?>" 
                             alt="Avatar" class="rounded-circle me-md-3 mb-3 mb-md-0" 
                             style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #dee2e6;"
                             onerror="this.src='<?php echo ASSETS_URL; ?>img/default/default1.png'">
                        
                        <div class="ms-md-4">
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($usuario_actual['first_name'] . ' ' . $usuario_actual['last_name']); ?></h4>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $usuario_actual['role']))); ?></p>
                            <p class="text-muted small"><i class="fas fa-id-badge me-1"></i> ID: <?php echo htmlspecialchars($usuario_actual['employee_id']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Configuración de Avatar -->
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-4 p-3 bg-light rounded-3">
                            <h5 class="fw-bold mb-3">Configuración de Avatar</h5>
                            
                            <!-- Mostrar avatar actual -->
                            <div class="mb-3 text-center">
                                <label class="form-label fw-semibold d-block">Avatar actual</label>
                                <img src="<?php echo obtenerAvatarUrl($usuario_actual); ?>" 
                                     alt="Avatar" class="rounded-circle border" 
                                     style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #dee2e6 !important;"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>img/default/default1.png'">
                                <div class="mt-2 small text-muted">
                                    <?php 
                                    if (!empty($usuario_actual['avatar'])) {
                                        echo "Avatar personalizado";
                                    } else {
                                        echo "Avatar predefinido: " . str_replace('default', 'Avatar ', $usuario_actual['avatar_predefinido']);
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subir avatar personalizado</label>
                                <input type="file" class="form-control" name="avatar_personalizado" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">Formatos aceptados: JPG, PNG, GIF, WEBP. Tamaño máximo: 2MB.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Seleccionar avatar predefinido</label>
                                <div class="d-flex flex-wrap gap-3 mt-2 justify-content-center">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="avatar_predefinido" 
                                               id="avatar_<?php echo $i; ?>" value="default<?php echo $i; ?>"
                                               <?php 
                                               if (empty($usuario_actual['avatar'])) {
                                                   echo ($usuario_actual['avatar_predefinido'] == "default$i") ? 'checked' : '';
                                               }
                                               ?>>
                                        <label class="form-check-label d-flex flex-column align-items-center" for="avatar_<?php echo $i; ?>">
                                            <img src="<?php echo ASSETS_URL; ?>img/default/default<?php echo $i; ?>.png" 
                                                 alt="Avatar <?php echo $i; ?>" class="rounded-circle mb-1" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                            <span class="small">Avatar <?php echo $i; ?></span>
                                        </label>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($usuario_actual['avatar'])): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="eliminar_avatar" name="eliminar_avatar">
                                <label class="form-check-label text-danger" for="eliminar_avatar">
                                    <i class="fas fa-trash me-1"></i> Eliminar avatar personalizado y usar predefinido
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-center">
                                <button type="submit" name="update_avatar" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios de avatar
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Información personal editable -->
                    <h4 class="fw-bold mb-4 text-center text-primary">Información personal</h4>
                    <form method="post">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover">
                                <tbody>
                                    <tr>
                                        <th class="text-muted" width="30%">Nombre</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['first_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Segundo Nombre</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['middle_name'] ?: 'No especificado'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Apellido</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Segundo Apellido</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['second_last_name'] ?: 'No especificado'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Correo electrónico</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Usuario</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Número de teléfono</th>
                                        <td>
                                            <input type="text" class="form-control" name="phone_number" 
                                                   value="<?php echo htmlspecialchars($usuario_actual['phone_number'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Fecha de cumpleaños</th>
                                        <td>
                                            <input type="date" class="form-control" name="birth_date" 
                                                   value="<?php echo htmlspecialchars($usuario_actual['birth_date'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Género</th>
                                        <td>
                                            <select class="form-select" name="gender">
                                                <option value="">Seleccionar...</option>
                                                <option value="M" <?php echo ($usuario_actual['gender'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                                                <option value="F" <?php echo ($usuario_actual['gender'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                                                <option value="O" <?php echo ($usuario_actual['gender'] == 'O') ? 'selected' : ''; ?>>Otro</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Área de trabajo</th>
                                        <td><?php echo htmlspecialchars($usuario_actual['work_area'] ?: 'No especificado'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Jefe directo</th>
                                        <td>
                                            <?php 
                                            if ($usuario_actual['manager_id']) {
                                                echo "Manager ID: " . $usuario_actual['manager_id'];
                                            } else {
                                                echo "No especificado";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Último acceso</th>
                                        <td>
                                            <?php 
                                            if ($usuario_actual['last_login']) {
                                                echo date('d/m/Y H:i', strtotime($usuario_actual['last_login']));
                                            } else {
                                                echo 'Nunca';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios de perfil
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Cambio de contraseña -->
                    <div class="mt-5">
                        <h4 class="fw-bold text-center text-primary mb-4">Cambiar contraseña</h4>
                        <form method="POST">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="old_password" class="form-label fw-semibold">Contraseña actual</label>
                                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="new_password1" class="form-label fw-semibold">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="new_password1" name="new_password1" required>
                                    <div class="form-text">La contraseña debe contener al menos 8 caracteres.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="new_password2" class="form-label fw-semibold">Repetir nueva contraseña</label>
                                    <input type="password" class="form-control" id="new_password2" name="new_password2" required>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fa-solid fa-key me-1"></i> Actualizar contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="./?vista=home" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Inicio
                        </a>      
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>