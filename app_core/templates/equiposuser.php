<?php
// app_core/templates/equiposuser.php

$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Funciones auxiliares
function obtenerNombreRol($rol) {
    $nombres = [
        'developer' => 'Developer',
        'superuser' => 'Superuser',
        'supervisor' => 'Supervisor',
        'backup' => 'Backup',
        'qa' => 'Agente QA',
        'ejecutivo' => 'Ejecutivo'
    ];
    return $nombres[$rol] ?? ucfirst($rol);
}

function obtenerClaseRol($rol) {
    $clases = [
        'developer' => 'bg-danger',
        'superuser' => 'bg-dark',
        'supervisor' => 'bg-warning',
        'backup' => 'bg-info',
        'qa' => 'bg-secondary',
        'ejecutivo' => 'bg-primary'
    ];
    return $clases[$rol] ?? 'bg-secondary';
}

function obtenerNombreManager($manager_id, $pdo) {
    if (!$manager_id) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM core_customuser WHERE id = ?");
        $stmt->execute([$manager_id]);
        $manager = $stmt->fetch();
        return $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : null;
    } catch (Exception $e) {
        return null;
    }
}

// Usar el controlador para obtener datos
$usuarios = UserController::obtenerUsuariosPorRol($usuario_actual);
$estadisticas = UserController::obtenerEstadisticas($usuario_actual, $usuarios);

// Determinar permisos
$puede_editar = in_array($usuario_actual['role'], ['developer', 'supervisor', 'superuser']);
$puede_crear = $puede_editar;
$acceso_completo = in_array($usuario_actual['role'], ['developer', 'superuser']);

// Mostrar mensajes flash
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Obtener conexión para la función obtenerNombreManager
$pdo = conexion();
?>

<div class="container" style="margin-top: 10%; margin-bottom: 10%;">
    <div class="row">
        <div class="col-12">
            
            <div class="d-flex justify-content-between mt-4">
                <a href="./?vista=home" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>      
            </div>
            
            <!-- Encabezado con botón de crear usuario condicional -->
            <?php if ($puede_crear): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-users"></i> Gestión de Equipo
                </h2>
                <a href="./?vista=crear_usuario" class="btn btn-success">
                    <i class="fas fa-plus"></i> Crear Usuario
                </a>
            </div>
            <?php else: ?>
            <h2 class="fw-bold mb-4 text-primary">
                <i class="fas fa-users"></i> Mi Equipo
            </h2>
            <?php endif; ?>
            
            <!-- Info del nivel de acceso -->
            <div class="alert alert-info">
                <strong>Nivel de acceso:</strong>
                <?php if ($acceso_completo): ?>
                    <span class="badge bg-success">Acceso Completo (<?php echo obtenerNombreRol($usuario_actual['role']); ?>)</span>
                <?php elseif ($puede_editar): ?>
                    <span class="badge bg-warning">Supervisor (Solo mi área: <?php echo $usuario_actual['work_area']; ?>)</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Consulta (Solo mi área: <?php echo $usuario_actual['work_area']; ?>)</span>
                <?php endif; ?>
            </div>

            <?php if (isset($flash_message)): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Tabla de usuarios -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre Completo</th>
                                    <th>Rol</th>
                                    <th>Jefe Directo</th>
                                    <th>Área</th>
                                    <?php if ($acceso_completo): ?>
                                    <th>Email</th>
                                    <th>ID Empleado</th>
                                    <th>Teléfono</th>
                                    <?php endif; ?>
                                    
                                    <!-- Columna de acciones condicional -->
                                    <?php if ($puede_editar): ?>
                                    <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="<?php echo $acceso_completo ? ($puede_editar ? 8 : 7) : ($puede_editar ? 5 : 4); ?>" 
                                        class="text-center text-muted py-4">
                                        No hay usuarios para mostrar
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <?php 
                                $nombre_manager = obtenerNombreManager($usuario['manager_id'], $pdo);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['first_name'] . ' ' . $usuario['last_name']); ?></strong>
                                        <?php if ($acceso_completo && ($usuario['middle_name'] || $usuario['second_last_name'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            $nombres_extra = [];
                                            if ($usuario['middle_name']) $nombres_extra[] = $usuario['middle_name'];
                                            if ($usuario['second_last_name']) $nombres_extra[] = $usuario['second_last_name'];
                                            echo htmlspecialchars(implode(' ', $nombres_extra));
                                            ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo obtenerClaseRol($usuario['role']); ?>">
                                            <?php echo obtenerNombreRol($usuario['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($nombre_manager): ?>
                                        <?php echo htmlspecialchars($nombre_manager); ?>
                                        <?php else: ?>
                                        <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($usuario['work_area'] ?: 'Sin área'); ?></span>
                                    </td>
                                    
                                    <?php if ($acceso_completo): ?>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['phone_number'] ?: '-'); ?></td>
                                    <?php endif; ?>
                                    
                                    <!-- Celda de acciones -->
                                    <?php if ($puede_editar): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="./?vista=editar_usuario&id=<?php echo $usuario['id']; ?>" class="btn btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($acceso_completo): ?>
                                            <!-- Botón para trigger del modal -->
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarModal<?php echo $usuario['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <!-- Modal de confirmación -->
                                            <div class="modal fade" id="eliminarModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Estás seguro de que deseas eliminar al usuario 
                                                            <strong><?php echo htmlspecialchars($usuario['first_name'] . ' ' . $usuario['last_name']); ?></strong>?
                                                            <br><br>
                                                            <small class="text-muted">Esta acción no se puede deshacer.</small>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="./?vista=procesar_eliminar_usuario" method="post" class="d-inline">
                                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5><?php echo $estadisticas['total_usuarios']; ?></h5>
                            <p>Total Usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5><?php echo $estadisticas['usuarios_mi_area']; ?></h5>
                            <p>En mi área</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5><?php echo $estadisticas['mis_subordinados']; ?></h5>
                            <p>Mis subordinados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5><?php echo obtenerNombreRol($usuario_actual['role']); ?></h5>
                            <p>Mi Rol</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>