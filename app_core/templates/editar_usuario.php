<?php
// app_core/templates/editar_usuario.php

// Verificar permisos
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual || !in_array($usuario_actual['role'], ['superuser', 'developer', 'supervisor'])) {
    header('Location: ./?vista=equiposuser');
    exit;
}

// Obtener el ID del usuario a editar
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header('Location: ./?vista=equiposuser');
    exit;
}

// Cargar datos del usuario
$usuario = UserController::obtenerUsuarioPorId($user_id);

if (!$usuario) {
    $_SESSION['flash_message'] = "Usuario no encontrado";
    $_SESSION['flash_type'] = "danger";
    header('Location: ./?vista=equiposuser');
    exit;
}

// Verificar que el supervisor solo edite usuarios de su área
if ($usuario_actual['role'] === 'supervisor' && $usuario['work_area'] !== $usuario_actual['work_area']) {
    $_SESSION['flash_message'] = "No tienes permisos para editar usuarios de otras áreas";
    $_SESSION['flash_type'] = "danger";
    header('Location: ./?vista=equiposuser');
    exit;
}

// Cargar datos para los selects
$areas = UserController::obtenerAreas();
$jefes = UserController::obtenerJefesPotenciales();
$roles = UserController::obtenerRoles();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_usuario'])) {
    $resultado = UserController::actualizarUsuario($user_id, $_POST);
    
    if ($resultado['success']) {
        $_SESSION['flash_message'] = $resultado['message'];
        $_SESSION['flash_type'] = "success";
        header('Location: ./?vista=equiposuser');
        exit;
    } else {
        $error_message = $resultado['message'];
    }
}
?>

<div class="container" style="margin-top: 5%; margin-bottom: 5%;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-edit"></i> Editar Usuario: <?php echo htmlspecialchars($usuario['first_name'] . ' ' . $usuario['last_name']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="actualizar_usuario" value="1">
                        
                        <div class="row">
                            <!-- Información Personal -->
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Primer Nombre*</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($usuario['first_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="middle_name" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($usuario['middle_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Primer Apellido*</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($usuario['last_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="second_last_name" class="form-label">Segundo Apellido</label>
                                <input type="text" class="form-control" id="second_last_name" name="second_last_name"
                                       value="<?php echo htmlspecialchars($usuario['second_last_name'] ?? ''); ?>">
                            </div>

                            <!-- Información Laboral -->
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">ID de Empleado*</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                       value="<?php echo htmlspecialchars($usuario['employee_id']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email*</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username*</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
                            </div>

                            <!-- Rol y Área (solo para superuser y developer) -->
                            <?php if (in_array($usuario_actual['role'], ['superuser', 'developer'])): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Rol*</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Seleccionar Rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo $rol; ?>" 
                                                <?php echo ($usuario['role'] === $rol) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(str_replace('_', ' ', $rol)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="work_area" class="form-label">Área de Trabajo*</label>
                                    <select class="form-select" id="work_area" name="work_area" required>
                                        <option value="">Seleccionar Área</option>
                                        <?php foreach ($areas as $area): ?>
                                            <option value="<?php echo htmlspecialchars($area['work_area']); ?>" 
                                                <?php echo ($usuario['work_area'] === $area['work_area']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($area['work_area']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <!-- Para supervisores, mostrar información de rol y área como texto -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rol</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $usuario['role'])); ?>" readonly>
                                    <input type="hidden" name="role" value="<?php echo $usuario['role']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Área de Trabajo</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['work_area']); ?>" readonly>
                                    <input type="hidden" name="work_area" value="<?php echo htmlspecialchars($usuario['work_area']); ?>">
                                    <small class="form-text text-muted">Los supervisores no pueden cambiar el área de trabajo.</small>
                                </div>
                            <?php endif; ?>

                            <!-- Información Adicional -->
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number"
                                       value="<?php echo htmlspecialchars($usuario['phone_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="birth_date" class="form-label">Fecha de Cumpleaños</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date"
                                       value="<?php echo htmlspecialchars($usuario['birth_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Género</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="M" <?php echo ($usuario['gender'] === 'M') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo ($usuario['gender'] === 'F') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="O" <?php echo ($usuario['gender'] === 'O') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="manager_id" class="form-label">Jefe Directo</label>
                                <select class="form-select" id="manager_id" name="manager_id">
                                    <option value="">Seleccionar Jefe</option>
                                    <?php foreach ($jefes as $jefe): ?>
                                        <option value="<?php echo $jefe['id']; ?>" 
                                            <?php echo ($usuario['manager_id'] == $jefe['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($jefe['first_name'] . ' ' . $jefe['last_name'] . ' (' . $jefe['role'] . ' - ' . $jefe['work_area'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Avatar predefinido -->
                            <div class="col-md-6 mb-3">
                                <label for="avatar_predefinido" class="form-label">Avatar Predefinido</label>
                                <select class="form-select" id="avatar_predefinido" name="avatar_predefinido">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="default<?php echo $i; ?>" 
                                            <?php echo ($usuario['avatar_predefinido'] === 'default' . $i) ? 'selected' : ''; ?>>
                                            Avatar <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Estado activo -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo ($usuario['is_active']) ? 'checked' : ''; ?> value="1">
                                    <label class="form-check-label" for="is_active">Usuario Activo</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="./?vista=equiposuser" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al equipo
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información adicional del usuario -->
            <div class="alert alert-info mt-3">
                <small>
                    <strong>Información de usuario:</strong><br>
                    • Username: <?php echo htmlspecialchars($usuario['username']); ?><br>
                    • Rol actual: <?php echo ucfirst(str_replace('_', ' ', $usuario['role'])); ?><br>
                    • Área actual: <?php echo htmlspecialchars($usuario['work_area'] ?? 'N/A'); ?><br>
                    • Email: <?php echo htmlspecialchars($usuario['email']); ?><br>
                    • Último login: <?php echo $usuario['last_login'] ? date('d/m/Y H:i', strtotime($usuario['last_login'])) : 'Nunca'; ?><br>
                    • Editor: <?php echo htmlspecialchars($usuario_actual['first_name'] . ' ' . $usuario_actual['last_name'] . ' (' . $usuario_actual['role'] . ')'); ?>
                </small>
            </div>
        </div>
    </div>
</div>