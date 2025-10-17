<?php
// app_core/templates/crear_usuario.php

$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Verificar permisos - solo developers y superusers pueden crear usuarios
if (!in_array($usuario_actual['role'], ['developer', 'superuser'])) {
    header('Location: ./?vista=equiposuser');
    exit;
}

// Procesar el formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = conexion();
    
    try {
        // Recoger y limpiar datos
        $username = limpiarCadena($_POST['username'] ?? '');
        $first_name = limpiarCadena($_POST['first_name'] ?? '');
        $middle_name = limpiarCadena($_POST['middle_name'] ?? '');
        $last_name = limpiarCadena($_POST['last_name'] ?? '');
        $second_last_name = limpiarCadena($_POST['second_last_name'] ?? '');
        $email = limpiarCadena($_POST['email'] ?? '');
        $employee_id = limpiarCadena($_POST['employee_id'] ?? '');
        $role = limpiarCadena($_POST['role'] ?? '');
        $work_area = limpiarCadena($_POST['work_area'] ?? '');
        $birth_date = limpiarCadena($_POST['birth_date'] ?? '');
        $gender = limpiarCadena($_POST['gender'] ?? '');
        $phone_number = limpiarCadena($_POST['phone_number'] ?? '');
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_superuser = isset($_POST['is_superuser']) ? 1 : 0;
        $password = $_POST['password1'] ?? '';
        $password_confirm = $_POST['password2'] ?? '';

        // Validaciones básicas
        if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || 
            empty($employee_id) || empty($role) || empty($work_area) || empty($password)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }

        if ($password !== $password_confirm) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (strlen($password) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        // Verificar si el username ya existe
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("El nombre de usuario ya existe");
        }

        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado");
        }

        // Verificar si el ID de empleado ya existe
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        if ($stmt->fetch()) {
            throw new Exception("El ID de empleado ya existe");
        }

        // Generar hash de contraseña Django
        $salt = base64_encode(random_bytes(12));
        $iterations = 1000000;
        $hash = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
        $password_hash = 'pbkdf2_sha256$' . $iterations . '$' . $salt . '$' . base64_encode($hash);

        // Insertar nuevo usuario
        $stmt = $pdo->prepare("
            INSERT INTO core_customuser (
                password, is_superuser, username, is_staff, is_active, date_joined,
                email, first_name, middle_name, last_name, second_last_name, employee_id,
                role, birth_date, gender, avatar, phone_number, work_area, manager_id, avatar_predefinido
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, 'default1')
        ");

        $stmt->execute([
            $password_hash, $is_superuser, $username, $is_superuser, $is_active,
            $email, $first_name, $middle_name, $last_name, $second_last_name, $employee_id,
            $role, $birth_date, $gender, $phone_number, $work_area, $manager_id
        ]);

        $mensaje = "Usuario creado exitosamente";
        $tipo_mensaje = "success";

        // Limpiar el formulario después de éxito
        $_POST = [];

    } catch (Exception $e) {
        $mensaje = "Error al crear usuario: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener lista de supervisores para el campo "Jefe Directo"
$pdo = conexion();
$supervisores = [];
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM core_customuser WHERE role IN ('supervisor', 'superuser', 'developer') AND is_active = 1 ORDER BY first_name, last_name");
    $supervisores = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error al cargar supervisores: " . $e->getMessage());
}
?>

<div class="container" style="margin-top: 10%; margin-bottom: 10%;">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post" id="userForm">
                        <div class="row">
                            <!-- Información básica -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                    <div class="form-text">Nombre de usuario para iniciar sesión.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="middle_name" class="form-label">Segundo Nombre</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                           value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="second_last_name" class="form-label">Segundo Apellido</label>
                                    <input type="text" class="form-control" id="second_last_name" name="second_last_name" 
                                           value="<?php echo htmlspecialchars($_POST['second_last_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="birth_date" class="form-label">Fecha de Cumpleaños</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Género</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Seleccionar...</option>
                                        <option value="M" <?php echo ($_POST['gender'] ?? '') == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="F" <?php echo ($_POST['gender'] ?? '') == 'F' ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="O" <?php echo ($_POST['gender'] ?? '') == 'O' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Información laboral y contacto -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Información Laboral y Contacto</h5>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <div class="form-text">El email será utilizado para iniciar sesión.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">ID de Empleado <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                           value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Seleccionar rol...</option>
                                        <option value="ejecutivo" <?php echo ($_POST['role'] ?? '') == 'ejecutivo' ? 'selected' : ''; ?>>Ejecutivo</option>
                                        <option value="supervisor" <?php echo ($_POST['role'] ?? '') == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                        <option value="backup" <?php echo ($_POST['role'] ?? '') == 'backup' ? 'selected' : ''; ?>>Backup</option>
                                        <option value="developer" <?php echo ($_POST['role'] ?? '') == 'developer' ? 'selected' : ''; ?>>Developer</option>
                                        <option value="superuser" <?php echo ($_POST['role'] ?? '') == 'superuser' ? 'selected' : ''; ?>>Superuser</option>
                                        <option value="qa" <?php echo ($_POST['role'] ?? '') == 'qa' ? 'selected' : ''; ?>>Agente QA</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="work_area" class="form-label">Área de Trabajo <span class="text-danger">*</span></label>
                                    <select class="form-select" id="work_area" name="work_area" required>
                                        <option value="">Seleccionar área...</option>
                                        <option value="Depto Micro&SOHO" <?php echo ($_POST['work_area'] ?? '') == 'Depto Micro&SOHO' ? 'selected' : ''; ?>>Depto Micro&SOHO</option>
                                        <option value="Depto Corporaciones" <?php echo ($_POST['work_area'] ?? '') == 'Depto Corporaciones' ? 'selected' : ''; ?>>Depto Corporaciones</option>
                                        <option value="Depto B2B Empresas" <?php echo ($_POST['work_area'] ?? '') == 'Depto B2B Empresas' ? 'selected' : ''; ?>>Depto B2B Empresas</option>
                                        <option value="Depto Ventas WEB" <?php echo ($_POST['work_area'] ?? '') == 'Depto Ventas WEB' ? 'selected' : ''; ?>>Depto Ventas WEB</option>
                                        <option value="Back Portabilidad" <?php echo ($_POST['work_area'] ?? '') == 'Back Portabilidad' ? 'selected' : ''; ?>>Back Portabilidad</option>
                                        <option value="Depto Post-Venta Micro&SOHO" <?php echo ($_POST['work_area'] ?? '') == 'Depto Post-Venta Micro&SOHO' ? 'selected' : ''; ?>>Depto Post-Venta Micro&SOHO</option>
                                        <option value="Depto Post-Venta Corporaciones" <?php echo ($_POST['work_area'] ?? '') == 'Depto Post-Venta Corporaciones' ? 'selected' : ''; ?>>Depto Post-Venta Corporaciones</option>
                                        <option value="Depto BPO-LEGADO" <?php echo ($_POST['work_area'] ?? '') == 'Depto BPO-LEGADO' ? 'selected' : ''; ?>>Depto BPO-LEGADO</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="manager_id" class="form-label">Jefe Directo</label>
                                    <select class="form-select" id="manager_id" name="manager_id">
                                        <option value="">Seleccionar jefe...</option>
                                        <?php foreach ($supervisores as $supervisor): ?>
                                        <option value="<?php echo $supervisor['id']; ?>" 
                                                <?php echo ($_POST['manager_id'] ?? '') == $supervisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                           value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Seguridad</h5>
                                
                                <div class="mb-3">
                                    <label for="password1" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password1" name="password1" required>
                                    <div class="form-text">
                                        <ul class="small">
                                            <li>La contraseña no puede ser similar a su información personal.</li>
                                            <li>Debe contener al menos 8 caracteres.</li>
                                            <li>No puede ser una contraseña comúnmente utilizada.</li>
                                            <li>No puede ser entirely numérica.</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password2" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password2" name="password2" required>
                                    <div class="form-text">Introduzca la misma contraseña para verificación.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Permisos</h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo !isset($_POST['is_active']) || $_POST['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Usuario Activo</label>
                                    </div>
                                    <div class="form-text">Desmarque esta opción para desactivar el usuario.</div>
                                </div>
                                
                                <?php if (in_array($usuario_actual['role'], ['developer', 'superuser'])): ?>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_superuser" name="is_superuser"
                                               <?php echo ($_POST['is_superuser'] ?? '') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_superuser">Es Superusuario</label>
                                    </div>
                                    <div class="form-text">Los superusuarios tienen todos los permisos.</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="./?vista=equiposuser" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>