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

// Variables para mensajes - INICIALIZAR CORRECTAMENTE
// Variables para mensajes - INICIALIZAR CORRECTAMENTE
$mensaje_exito = '';
$mensaje_error = '';

// DEBUG: Ver qué está llegando
error_log("🔍 DEBUG añadir-analistas - POST: " . json_encode($_POST));
error_log("🔍 DEBUG añadir-analistas - GET: " . json_encode($_GET));

// Procesar acciones CON MEJOR MANEJO DE ERRORES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    error_log("📝 Procesando acción: {$action} para user_id: {$user_id}");
    
    if ($action === 'agregar_analista' && $user_id > 0) {
        $result = $teamController->agregarUsuarioBackAdmision($user_id);
        if ($result) {
            // REDIRIGIR para evitar reenvío del formulario
            header('Location: ?vista=back-admision&action=añadir-analistas&exito=agregado&user_id=' . $user_id);
            exit;
        } else {
            $mensaje_error = "❌ Error al agregar el analista al sistema";
        }
    } 
    elseif ($action === 'eliminar_analista' && $user_id > 0) {
        $result = $teamController->eliminarUsuarioBackAdmision($user_id);
        if ($result) {
            // REDIRIGIR para evitar reenvío del formulario
            header('Location: ?vista=back-admision&action=añadir-analistas&exito=eliminado&user_id=' . $user_id);
            exit;
        } else {
            $mensaje_error = "❌ Error al eliminar el analista del sistema";
        }
    }
    else {
        $mensaje_error = "❌ Acción no válida o ID de usuario incorrecto";
    }
}

// Mostrar mensajes de éxito desde GET - LÓGICA CORREGIDA
if (empty($mensaje_error)) {
    $exito_tipo = $_GET['exito'] ?? '';
    $user_id_get = $_GET['user_id'] ?? 0;
    
    if ($exito_tipo === 'agregado' && $user_id_get > 0) {
        $mensaje_exito = "✅ Analista agregado correctamente al sistema Back Admisión (ID: {$user_id_get})";
    }
    elseif ($exito_tipo === 'eliminado' && $user_id_get > 0) {
        $mensaje_exito = "✅ Analista eliminado correctamente del sistema Back Admisión (ID: {$user_id_get})";
    }
    // Si hay éxito pero no user_id, mostrar mensaje genérico
    elseif ($exito_tipo === 'agregado') {
        $mensaje_exito = "✅ Analista agregado correctamente al sistema Back Admisión";
    }
    elseif ($exito_tipo === 'eliminado') {
        $mensaje_exito = "✅ Analista eliminado correctamente del sistema Back Admisión";
    }
}
// Mostrar mensajes de éxito desde GET - SOLO SI NO HAY ERROR
if (empty($mensaje_error)) {
    if ($_GET['exito'] ?? '' == 'agregado') {
        $user_id = $_GET['user_id'] ?? 0;
        $mensaje_exito = "✅ Analista agregado correctamente al sistema Back Admisión (ID: {$user_id})"; // indiferentemente de que sea añadido o eliminado dice ✅ Analista eliminado correctamente del sistema Back Admisión (ID: {$user_id})"; y no logro comprender porque la pagina siempre esta como cargando pero ya funciona la agregacion o eliminacion
    }
    
    if ($_GET['exito'] ?? '' == 'eliminado') {
        $user_id = $_GET['user_id'] ?? 0;
        $mensaje_exito = "✅ Analista eliminado correctamente del sistema Back Admisión (ID: {$user_id})";
    }
}

// DEBUG: Ver resultados
error_log("📊 Usuarios Micro&SOHO: " . count($usuarios_micro_soho));
error_log("📊 Usuarios Back Admisión: " . count($usuarios_back_admision));
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
    margin-bottom: 1rem;
}
.card-usuario:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.usuario-agregado {
    border-left-color: #28a745;
    background-color: #f8fff8;
}
.badge-area {
    font-size: 0.8em;
}
.avatar-usuario {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #dee2e6;
}
.btn-action {
    transition: all 0.3s ease;
}
.btn-action:hover {
    transform: scale(1.05);
}
.alert {
    border-left: 4px solid transparent;
}
.alert-success {
    border-left-color: #28a745;
}
.alert-danger {
    border-left-color: #dc3545;
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
                                <i class="fas fa-users me-1"></i><?php echo count($usuarios_back_admision); ?> analista(s) activo(s)
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Alertas MEJORADAS -->
                    <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
                        <i class="fas fa-check-circle me-2 fs-5"></i>
                        <div class="flex-grow-1"><?php echo $mensaje_exito; ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <div class="flex-grow-1"><?php echo $mensaje_error; ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Resumen -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-4 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo count($usuarios_micro_soho); ?></h3>
                                    <p class="mb-0 small">Usuarios Micro&SOHO</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo count($usuarios_back_admision); ?></h3>
                                    <p class="mb-0 small">En Back Admisión</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo max(0, count($usuarios_micro_soho) - count($usuarios_back_admision)); ?></h3>
                                    <p class="mb-0 small">Disponibles para agregar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usuarios de Micro&SOHO Disponibles -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
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
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-users fa-4x mb-3 opacity-50"></i>
                                            <h5>No hay usuarios en el área Micro&SOHO</h5>
                                            <p class="mb-0">Los usuarios deben tener el área "Depto Micro&SOHO" asignada</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row" id="lista-usuarios">
                                            <?php foreach ($usuarios_micro_soho as $usuario): 
                                                $ya_agregado = in_array($usuario['id'], array_column($usuarios_back_admision, 'user_id'));
                                            ?>
                                            <div class="col-12 col-md-6 col-lg-4 mb-3 usuario-item">
                                                <div class="card card-usuario h-100 <?php echo $ya_agregado ? 'usuario-agregado' : ''; ?>">
                                                    <div class="card-body d-flex flex-column">
                                                        <div class="d-flex align-items-start mb-3">
                                                            <div class="flex-shrink-0">
                                                                <img src="<?php echo htmlspecialchars($usuario['avatar'] ?? '/dashboard/vsm/microapp/public/assets/img/default/user-default.png'); ?>" 
                                                                     class="avatar-usuario" 
                                                                     alt="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                                                     onerror="this.src='/dashboard/vsm/microapp/public/assets/img/default/user-default.png'">
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h6>
                                                                <p class="mb-1 small text-muted">
                                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($usuario['username']); ?>
                                                                </p>
                                                                <p class="mb-1 small text-muted">
                                                                    <i class="fas fa-id-card me-1"></i>ID: <?php echo $usuario['id']; ?>
                                                                </p>
                                                                <span class="badge badge-area bg-<?php echo $ya_agregado ? 'success' : 'primary'; ?>">
                                                                    <i class="fas fa-<?php echo $ya_agregado ? 'check' : 'user'; ?> me-1"></i>
                                                                    <?php echo $ya_agregado ? 'En Back Admisión' : 'Micro&SOHO'; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-auto">
                                                            <?php if (!$ya_agregado): ?>
                                                                <form method="post" class="d-inline w-100">
                                                                    <input type="hidden" name="action" value="agregar_analista">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <button type="submit" class="btn btn-success btn-sm w-100 btn-action">
                                                                        <i class="fas fa-plus me-1"></i>Agregar a Back Admisión
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="post" class="d-inline w-100">
                                                                    <input type="hidden" name="action" value="eliminar_analista">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm w-100 btn-action" 
                                                                            onclick="return confirm('¿Estás seguro de eliminar a <?php echo htmlspecialchars($usuario['nombre_completo']); ?> del sistema Back Admisión?\n\nEsta acción eliminará sus horarios y estado.')">
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
                            <div class="card border-0 shadow-sm">
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
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-user-slash fa-4x mb-3 opacity-50"></i>
                                            <h5>No hay analistas en Back Admisión</h5>
                                            <p class="mb-0">Agrega analistas desde la lista de usuarios disponibles</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th><i class="fas fa-user me-1"></i> Usuario</th>
                                                        <th><i class="fas fa-id-card me-1"></i> ID</th>
                                                        <th><i class="fas fa-building me-1"></i> Área</th>
                                                        <th><i class="fas fa-circle me-1"></i> Estado</th>
                                                        <th><i class="fas fa-calendar me-1"></i> Fecha Registro</th>
                                                        <th><i class="fas fa-cogs me-1"></i> Acciones</th>
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
                                                        <td class="fw-bold"><?php echo $analista['user_id']; ?></td>
                                                        <td>
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($analista['work_area']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($analista['estado'] ?? 'inactivo') == 'activo' ? 'success' : 'secondary'; ?>">
                                                                <i class="fas fa-<?php echo ($analista['estado'] ?? 'inactivo') == 'activo' ? 'play' : 'pause'; ?> me-1"></i>
                                                                <?php echo ucfirst($analista['estado'] ?? 'inactivo'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                <?php echo date('d/m/Y H:i', strtotime($analista['created_at'] ?? 'now')); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="eliminar_analista">
                                                                    <input type="hidden" name="user_id" value="<?php echo $analista['user_id']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-action" 
                                                                            onclick="return confirm('¿Estás seguro de eliminar a <?php echo htmlspecialchars($analista['nombre_completo']); ?> del sistema Back Admisión?\n\nEsta acción eliminará sus horarios y estado.')">
                                                                        <i class="fas fa-trash me-1"></i>Eliminar
                                                                    </button>
                                                                </form>
                                                                <a href="/dashboard/vsm/microapp/public/?vista=back-admision&action=gestionar-horarios&user_id=<?php echo $analista['user_id']; ?>&user_name=<?php echo urlencode($analista['nombre_completo']); ?>" 
                                                                   class="btn btn-primary btn-action ms-1">
                                                                    <i class="fas fa-clock me-1"></i>Horarios
                                                                </a>
                                                            </div>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Página de gestión de analistas cargada correctamente');
    
    // SOLUCIÓN: Remover completamente el manejo de formularios del JavaScript
    // Los formularios se enviarán de forma tradicional sin intervención JS
    
    // Solo mantener funcionalidades que no interfieran con el envío de formularios
    
    // Buscador de usuarios (si existe)
    const buscador = document.getElementById('buscadorUsuarios');
    if (buscador) {
        buscador.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const usuarios = document.querySelectorAll('.usuario-item');
            
            usuarios.forEach(usuario => {
                const nombre = usuario.querySelector('h6').textContent.toLowerCase();
                if (nombre.includes(searchText)) {
                    usuario.style.display = 'block';
                } else {
                    usuario.style.display = 'none';
                }
            });
        });
    }
    
    // Auto-ocultar alertas después de 8 segundos (solo si existen)
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    // Si falla Bootstrap, ocultar manualmente
                    alert.style.display = 'none';
                }
            });
        }, 8000);
    }
    
    console.log('✅ JavaScript inicializado sin conflictos');
});

// Función separada para manejar confirmaciones (no interfiere con envío)
function confirmarEliminacion(nombre) {
    return confirm(`¿Estás seguro de eliminar a ${nombre} del sistema Back Admisión?\n\nEsta acción eliminará sus horarios y estado.`);
}
</script>