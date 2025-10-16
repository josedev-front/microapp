<?php
// Obtener datos del usuario actual
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = conexion();
    
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
    
    // Cambiar avatar predefinido
    if (isset($_POST['update_avatar'])) {
        $avatar_predefinido = limpiarCadena($_POST['avatar_predefinido'] ?? 'default1');
        
        try {
            $stmt = $pdo->prepare("UPDATE core_customuser SET avatar_predefinido = ? WHERE id = ?");
            $stmt->execute([$avatar_predefinido, $usuario_actual['id']]);
            
            $mensaje = "Avatar actualizado correctamente";
            $tipo_mensaje = "success";
            $usuario_actual = obtenerUsuarioActual();
            
        } catch (Exception $e) {
            $mensaje = "Error al actualizar el avatar: " . $e->getMessage();
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
            // Verificar contraseña actual (aquí necesitarías implementar la verificación real)
            $mensaje = "Funcionalidad de cambio de contraseña en desarrollo";
            $tipo_mensaje = "warning";
        }
    }
}

// Función para obtener la URL del avatar
function obtenerAvatarUrl($usuario) {
    if (!empty($usuario['avatar'])) {
        // Si tiene avatar personalizado
        return ASSETS_URL . 'avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'];
    } else {
        // Avatar predefinido
        $avatar_num = str_replace('default', '', $usuario['avatar_predefinido']);
        return ASSETS_URL . 'img/default/default' . $avatar_num . '.png';
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
                             style="width: 100px; height: 100px; object-fit: cover;">
                        
                        <div class="ms-md-4">
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($usuario_actual['first_name'] . ' ' . $usuario_actual['last_name']); ?></h4>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars(ucfirst($usuario_actual['role'])); ?></p>
                            <p class="text-muted small"><i class="fas fa-id-badge me-1"></i> ID: <?php echo htmlspecialchars($usuario_actual['employee_id']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Configuración de Avatar -->
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-4 p-3 bg-light rounded-3">
                            <h5 class="fw-bold mb-3">Configuración de Avatar</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subir avatar personalizado</label>
                                <input type="file" class="form-control" name="avatar_personalizado" accept="image/*">
                                <div class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Seleccionar avatar predefinido</label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="avatar_predefinido" 
                                               id="avatar_<?php echo $i; ?>" value="default<?php echo $i; ?>"
                                               <?php echo ($usuario_actual['avatar_predefinido'] == "default$i") ? 'checked' : ''; ?>>
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
                                <label class="form-check-label" for="eliminar_avatar">Eliminar avatar personalizado y usar predefinido</label>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" name="update_avatar" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios de avatar
                            </button>
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
                                                // Aquí podrías obtener el nombre del manager
                                                echo "Manager ID: " . $usuario_actual['manager_id'];
                                            } else {
                                                echo "No especificado";
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
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fa-solid fa-key me-1"></i> Actualizar contraseña
                            </button>
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