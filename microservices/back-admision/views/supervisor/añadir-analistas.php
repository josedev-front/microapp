<?php
// microservices/back-admision/views/supervisor/añadir-analistas.php
require_once __DIR__ . '/../../init.php';

// Verificar permisos
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-ban me-2"></i>Acceso Denegado</h4>
            <p>No tienes permisos para gestionar analistas.</p>
            <p><strong>Tu rol actual:</strong> ' . htmlspecialchars($user_role) . '</p>
        </div>
    </div>';
    exit;
}

// Cargar controladores
require_once __DIR__ . '/../../controllers/TeamController.php';
$teamController = new TeamController();

// Obtener usuarios de Micro&SOHO
$usuarios_micro_soho = $teamController->getUsuariosMicroSOHO();
$usuarios_back_admision = $teamController->getUsuariosBackAdmision();

// Variables para mensajes
$mensaje_exito = '';
$mensaje_error = '';

// Procesar acciones CON REDIRECCIÓN
if ($_POST['action'] ?? '' == 'agregar_analista') {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id) {
        $result = $teamController->agregarUsuarioBackAdmision($user_id);
        if ($result) {
            // REDIRIGIR para evitar reenvío del formulario
            header('Location: ?vista=back-admision&action=añadir-analistas&exito=agregado&user_id=' . $user_id);
            exit;
        } else {
            $mensaje_error = "Error al agregar el analista";
        }
    }
}

if ($_POST['action'] ?? '' == 'eliminar_analista') {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id) {
        $result = $teamController->eliminarUsuarioBackAdmision($user_id);
        if ($result) {
            // REDIRIGIR para evitar reenvío del formulario
            header('Location: ?vista=back-admision&action=añadir-analistas&exito=eliminado&user_id=' . $user_id);
            exit;
        } else {
            $mensaje_error = "Error al eliminar el analista";
        }
    }
}

// Mostrar mensajes de éxito desde GET
if ($_GET['exito'] ?? '' == 'agregado') {
    $mensaje_exito = "✅ Analista agregado correctamente al sistema Back Admisión";
}

if ($_GET['exito'] ?? '' == 'eliminado') {
    $mensaje_exito = "✅ Analista eliminado correctamente del sistema Back Admisión";
}
?>

<style>
.breadcrumb {
    margin-top: 50px;
    border-radius: 5px;
}
.breadcrumb-item > a {
    color: white;
    text-decoration: none;
}
.card-usuario {
    transition: all 0.3s ease;
    border-left: 4px solid #007bff;
}
.card-usuario:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.usuario-agregado {
    border-left-color: #28a745;
    opacity: 0.8;
}
.badge-area {
    font-size: 0.8em;
}
.avatar-usuario {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}
.btn-loading {
    position: relative;
    color: transparent !important;
}
.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-right-color: transparent;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-secondary">
                    <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision">Back de Admisión</a></li>
                    <li class="breadcrumb-item active text-white">Añadir Analistas</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Gestión de Analistas</h4>
                            <p class="mb-0 mt-1 small opacity-75">
                                Agrega y gestiona analistas del área Micro&SOHO en el sistema Back Admisión
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark fs-6">
                                <?php echo count($usuarios_back_admision); ?> analista(s) activo(s)
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Alertas -->
                    <?php if (isset($mensaje_exito)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $mensaje_exito; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($mensaje_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $mensaje_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Resumen -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1"><?php echo count($usuarios_micro_soho); ?></h3>
                                    <p class="mb-0 small">Usuarios Micro&SOHO</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1"><?php echo count($usuarios_back_admision); ?></h3>
                                    <p class="mb-0 small">En Back Admisión</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1"><?php echo count($usuarios_micro_soho) - count($usuarios_back_admision); ?></h3>
                                    <p class="mb-0 small">Disponibles para agregar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usuarios de Micro&SOHO Disponibles -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i>Usuarios Micro&SOHO Disponibles
                                    </h5>
                                    <p class="mb-0 mt-1 small opacity-75">
                                        Usuarios del área Micro&SOHO que pueden ser agregados al sistema Back Admisión
                                    </p>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($usuarios_micro_soho)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <h5>No hay usuarios en el área Micro&SOHO</h5>
                                            <p>Los usuarios deben tener el área "Depto Micro&SOHO" asignada</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($usuarios_micro_soho as $usuario): 
                                                $ya_agregado = in_array($usuario['id'], array_column($usuarios_back_admision, 'user_id'));
                                            ?>
                                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                                <div class="card card-usuario <?php echo $ya_agregado ? 'usuario-agregado' : ''; ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start">
                                                            <div class="flex-shrink-0">
                                                                <img src="<?php echo htmlspecialchars($usuario['avatar'] ?? '/dashboard/vsm/microapp/public/assets/img/default/user-default.png'); ?>" 
                                                                     class="avatar-usuario" 
                                                                     alt="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                                                     onerror="this.src='/dashboard/vsm/microapp/public/assets/img/default/user-default.png'">
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h6>
                                                                <p class="mb-1 small text-muted">
                                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($usuario['username']); ?>
                                                                </p>
                                                                <p class="mb-1 small text-muted">
                                                                    <i class="fas fa-id-card me-1"></i>ID: <?php echo $usuario['id']; ?>
                                                                </p>
                                                                <span class="badge badge-area bg-<?php echo $ya_agregado ? 'success' : 'primary'; ?>">
                                                                    <?php echo $ya_agregado ? 'En Back Admisión' : 'Micro&SOHO'; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-3">
                                                            <?php if (!$ya_agregado): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="agregar_analista">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                                                        <i class="fas fa-plus me-1"></i>Agregar a Back Admisión
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="eliminar_analista">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm w-100" 
                                                                            onclick="return confirm('¿Estás seguro de eliminar este analista del sistema Back Admisión?')">
                                                                        <i class="fas fa-trash me-1"></i>Eliminar de Back Admisión
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analistas en Back Admisión -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle me-2"></i>Analistas en Back Admisión
                                    </h5>
                                    <p class="mb-0 mt-1 small opacity-75">
                                        Usuarios que actualmente tienen acceso al sistema Back Admisión
                                    </p>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($usuarios_back_admision)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-user-slash fa-3x mb-3"></i>
                                            <h5>No hay analistas en Back Admisión</h5>
                                            <p>Agrega analistas desde la lista de usuarios disponibles</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Usuario</th>
                                                        <th>ID</th>
                                                        <th>Área</th>
                                                        <th>Estado</th>
                                                        <th>Fecha Registro</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($usuarios_back_admision as $analista): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo htmlspecialchars($analista['avatar'] ?? '/dashboard/vsm/microapp/public/assets/img/default/user-default.png'); ?>" 
                                                                     class="avatar-usuario me-3" 
                                                                     alt="<?php echo htmlspecialchars($analista['nombre_completo']); ?>"
                                                                     onerror="this.src='/dashboard/vsm/microapp/public/assets/img/default/user-default.png'">
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($analista['nombre_completo']); ?></strong>
                                                                    <br>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($analista['username']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $analista['user_id']; ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo htmlspecialchars($analista['work_area']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $analista['estado'] == 'activo' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($analista['estado'] ?? 'inactivo'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small><?php echo date('d/m/Y H:i', strtotime($analista['created_at'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="eliminar_analista">
                                                                <input type="hidden" name="user_id" value="<?php echo $analista['user_id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                                        onclick="return confirm('¿Estás seguro de eliminar este analista del sistema Back Admisión?')">
                                                                    <i class="fas fa-trash me-1"></i>Eliminar
                                                                </button>
                                                            </form>
                                                            <a href="/dashboard/vsm/microapp/public/?vista=back-admision&action=gestionar-horarios&user_id=<?php echo $analista['user_id']; ?>&user_name=<?php echo urlencode($analista['nombre_completo']); ?>" 
                                                               class="btn btn-primary btn-sm ms-1">
                                                                <i class="fas fa-clock me-1"></i>Horarios
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar envío de formularios con AJAX
    $('form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const originalText = button.html();
        
        // Mostrar loading
        button.html('<i class="fas fa-spinner fa-spin me-1"></i>Procesando...');
        button.prop('disabled', true);
        
        $.ajax({
            url: '',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                // Recargar la página para ver los cambios
                window.location.reload();
            },
            error: function() {
                alert('Error al procesar la solicitud');
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });

    // Buscador de usuarios
    $('#buscadorUsuarios').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('.card-usuario').each(function() {
            const userName = $(this).find('h6').text().toLowerCase();
            if (userName.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>