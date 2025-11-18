<?php
error_log("🔍 DIAGNÓSTICO FECHA:");
error_log(" - date(): " . date('Y-m-d H:i:s'));
error_log(" - time(): " . time());
error_log(" - DEFAULT TIMEZONE: " . date_default_timezone_get());

// Verificar permisos de supervisor
$user_role = $backAdmision->getUserRole();
// microservices/back-admision/views/supervisor/gestionar-equipos.php
require_once __DIR__ . '/../../init.php';

// Verificar permisos de supervisor
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-ban me-2"></i>Acceso Denegado</h4>
            <p>No tienes permisos para gestionar equipos.</p>
            <p><strong>Tu rol actual:</strong> ' . htmlspecialchars($user_role) . '</p>
        </div>
    </div>';
    exit;
}

$user_id = $backAdmision->getUserId();
$js_user_id = $backAdmision->getUserId();
$js_user_role = $backAdmision->getUserRole();
$js_work_area = $backAdmision->getUserArea();

// Cargar controladores
require_once __DIR__ . '/../../controllers/TeamController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';
$teamController = new TeamController();
$reportController = new ReportController();

// Obtener datos
$ejecutivos = $teamController->getEjecutivosActivos();
$metricas = $teamController->getMetricasCasosHoy();
// Funciones helper para la vista
class TeamViewHelper {
    public static function getIniciales($nombre_completo) {
        $nombres = explode(' ', $nombre_completo);
        $iniciales = '';
        if (count($nombres) >= 2) {
            $iniciales = strtoupper(substr($nombres[0], 0, 1) . substr($nombres[1], 0, 1));
        } else {
            $iniciales = strtoupper(substr($nombres[0], 0, 2));
        }
        return $iniciales;
    }
    
    public static function calcularPorcentajeCarga($casos, $metricas) {
    // Si queremos calcular porcentaje basado en casos hoy
    $casos_hoy = array_column($metricas, 'casos_hoy');
    if (empty($casos_hoy)) return 0;
    
    $total_casos = array_sum($casos_hoy);
    return $total_casos > 0 ? round(($casos / $total_casos) * 100) : 0;
    }
    
    public static function evaluarBalance($metricas) {
        $casos_activos = array_column($metricas, 'casos_activos');
        if (count($casos_activos) === 0) return true;
        
        $max = max($casos_activos);
        $min = min($casos_activos);
        
        // Consideramos balanceado si la diferencia entre max y min es <= 5
        return ($max - $min) <= 5;
    }
}

// Crear instancia helper
$viewHelper = new TeamViewHelper();
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .progress-thin {
            height: 6px;
        }
        .casos-badge {
            font-size: 0.75em;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-activo { background-color: #28a745; }
        .status-colacion { background-color: #ffc107; }
        .status-inactivo { background-color: #dc3545; }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.025);
        }
        .badge-estado {
            font-size: 0.75em;
            padding: 0.25em 0.6em;
        }
    </style>

    <div class="container-fluid mt-4" style="margin-bottom: 30%;">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-warning">
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active">Gestionar Equipos</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-users-cog me-2"></i>Gestión de Equipos - Micro&SOHO</h4>
                        <p class="mb-0 mt-1 small opacity-75">Administración de ejecutivos y distribución de carga</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Pestañas -->
                        <ul class="nav nav-tabs mb-4" id="equiposTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="lista-usuarios-tab" data-bs-toggle="tab" data-bs-target="#lista-usuarios" type="button" role="tab">
                                    <i class="fas fa-list me-2"></i>Lista de Usuarios
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="panel-asignaciones-tab" data-bs-toggle="tab" data-bs-target="#panel-asignaciones" type="button" role="tab">
                                    <i class="fas fa-chart-pie me-2"></i>Panel de Asignaciones
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="equiposTabsContent">
                            <!-- Pestaña 1: Lista de Usuarios -->
                            <div class="tab-pane fade show active" id="lista-usuarios" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-primary">
                                            <tr>
                                                <th width="5%">Avatar</th>
                                                <th width="20%">Ejecutivo</th>
                                                <th width="10%">Estado</th>
                                                <th width="15%">Casos Activos</th>
                                                <th width="15%">Última Actualización</th>
                                                <th width="20%">Cambiar Estado</th>
                                                <th width="15%" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ejecutivos as $ejecutivo): 
                                                $iniciales = $viewHelper->getIniciales($ejecutivo['nombre_completo']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="user-avatar">
                                                        <?php echo $iniciales; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($ejecutivo['area']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="status-indicator status-<?php echo $ejecutivo['estado']; ?>"></span>
                                                    <span class="badge bg-<?php 
                                                        echo $ejecutivo['estado'] == 'activo' ? 'success' : 
                                                        ($ejecutivo['estado'] == 'colacion' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($ejecutivo['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $ejecutivo['casos_activos'] == 0 ? 'secondary' : 
                                                        ($ejecutivo['casos_activos'] < 3 ? 'info' : 
                                                        ($ejecutivo['casos_activos'] < 6 ? 'warning' : 'danger')); 
                                                    ?> fs-6">
                                                        <?php echo $ejecutivo['casos_activos']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo $ejecutivo['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($ejecutivo['ultima_actualizacion'])) : 'Nunca'; ?></small>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm cambiar-estado" 
                                                            data-user-id="<?php echo $ejecutivo['user_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?>">
                                                        <option value="activo" <?php echo $ejecutivo['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                                        <option value="colacion" <?php echo $ejecutivo['estado'] == 'colacion' ? 'selected' : ''; ?>>Colación</option>
                                                        <option value="inactivo" <?php echo $ejecutivo['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary gestionar-horarios" 
                                                                data-user-id="<?php echo $ejecutivo['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?>">
                                                            <i class="fas fa-clock me-1"></i>Horarios
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pestaña 2: Panel de Asignaciones -->
<div class="tab-pane fade" id="panel-asignaciones" role="tabpanel">
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Casos ingresados hoy:</strong> Esta sección muestra <strong>exclusivamente</strong> los casos que fueron creados hoy 
                <br>
                <small class="text-muted">
                    <strong>Fecha del sistema:</strong> <?php echo date('Y-m-d H:i:s'); ?> | 
                    <strong>Zona horaria:</strong> <?php echo date_default_timezone_get(); ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Distribución de Casos Ingresados Hoy</h5>
                    <p class="mb-0 mt-1 small text-muted">
                        Casos creados exclusivamente el <?php echo date('d/m/Y'); ?> 
                        <span class="badge bg-primary ms-2">Fecha actual</span>
                    </p>
                </div>
                <div class="card-body">
                    <?php 
                    $total_casos_hoy = array_sum(array_column($metricas, 'casos_hoy'));
                    $ejecutivos_con_casos_hoy = array_filter($metricas, function($m) { 
                        return ($m['casos_hoy'] ?? 0) > 0; 
                    });
                    
                    if ($total_casos_hoy == 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h5>No hay casos ingresados hoy</h5>
                        <p class="mb-0">No se han creado casos en la fecha <?php echo date('d/m/Y'); ?></p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($metricas as $metrica): 
                            $casos_hoy = $metrica['casos_hoy'] ?? 0;
                            $porcentaje = $total_casos_hoy > 0 ? round(($casos_hoy / $total_casos_hoy) * 100) : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <strong><?php echo htmlspecialchars($metrica['nombre_completo']); ?></strong>
                                    <span class="badge bg-<?php echo $metrica['estado'] == 'activo' ? 'success' : 'warning'; ?> ms-2">
                                        <?php echo ucfirst($metrica['estado']); ?>
                                    </span>
                                </span>
                                <span>
                                    <strong><?php echo $casos_hoy; ?></strong> casos hoy
                                    <?php if ($total_casos_hoy > 0): ?>
                                    (<?php echo $porcentaje; ?>%)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar bg-<?php 
                                    echo $casos_hoy == 0 ? 'secondary' : 
                                    ($casos_hoy < 3 ? 'info' : 
                                    ($casos_hoy < 6 ? 'warning' : 'danger')); 
                                ?>" 
                                role="progressbar" 
                                style="width: <?php echo $porcentaje; ?>%"
                                aria-valuenow="<?php echo $porcentaje; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                </div>
                            </div>
                            <?php if ($casos_hoy > 0): ?>
                            <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-play-circle text-primary me-1"></i><?php echo $metrica['en_curso_hoy'] ?? 0; ?> en curso
                                    <i class="fas fa-pause-circle text-warning ms-2 me-1"></i><?php echo $metrica['en_espera_hoy'] ?? 0; ?> en espera
                                    <i class="fas fa-check-circle text-success ms-2 me-1"></i><?php echo $metrica['resuelto_hoy'] ?? 0; ?> resueltos
                                    <i class="fas fa-times-circle text-danger ms-2 me-1"></i><?php echo $metrica['cancelado_hoy'] ?? 0; ?> cancelados
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Resumen del Día</h5>
                    <p class="mb-0 mt-1 small text-muted"><?php echo date('d/m/Y'); ?></p>
                </div>
                <div class="card-body">
                    <?php
                    $total_casos_hoy = array_sum(array_column($metricas, 'casos_hoy'));
                    $ejecutivos_activos = count(array_filter($metricas, function($m) { 
                        return $m['estado'] == 'activo'; 
                    }));
                    $ejecutivos_con_casos_hoy = count(array_filter($metricas, function($m) { 
                        return ($m['casos_hoy'] ?? 0) > 0; 
                    }));
                    $promedio_hoy = $ejecutivos_con_casos_hoy > 0 ? round($total_casos_hoy / $ejecutivos_con_casos_hoy, 1) : 0;
                    
                    // Calcular balance basado en casos hoy
                    $casos_hoy_array = array_column($metricas, 'casos_hoy');
                    $balance_hoy = true;
                    if (count($casos_hoy_array) > 1 && $total_casos_hoy > 0) {
                        $max = max($casos_hoy_array);
                        $min = min($casos_hoy_array);
                        $balance_hoy = ($max - $min) <= 3; // Máximo 3 casos de diferencia
                    }
                    ?>
                    <div class="mb-3">
                        <strong>Total Casos Hoy:</strong>
                        <span class="float-end badge bg-primary fs-6"><?php echo $total_casos_hoy; ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Ejecutivos Activos:</strong>
                        <span class="float-end badge bg-success"><?php echo $ejecutivos_activos; ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Con Casos Hoy:</strong>
                        <span class="float-end badge bg-info"><?php echo $ejecutivos_con_casos_hoy; ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Promedio Hoy:</strong>
                        <span class="float-end badge bg-warning"><?php echo $promedio_hoy; ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Balance del Día:</strong>
                        <span class="float-end badge bg-<?php echo $balance_hoy ? 'success' : 'warning'; ?>">
                            <?php echo $balance_hoy ? '✅ Balanceado' : '⚠️ Por Mejorar'; ?>
                        </span>
                    </div>
                    
                    <?php if ($total_casos_hoy > 0): ?>
                    <hr>
                    <!-- Distribución por estado hoy -->
                    <?php
                    $en_curso_hoy = array_sum(array_column($metricas, 'en_curso_hoy'));
                    $en_espera_hoy = array_sum(array_column($metricas, 'en_espera_hoy'));
                    $resuelto_hoy = array_sum(array_column($metricas, 'resuelto_hoy'));
                    $cancelado_hoy = array_sum(array_column($metricas, 'cancelado_hoy'));
                    ?>
                    <div class="mb-2">
                        <small><i class="fas fa-play-circle text-primary me-2"></i>En Curso:</small>
                        <span class="float-end badge bg-primary"><?php echo $en_curso_hoy; ?></span>
                    </div>
                    <div class="mb-2">
                        <small><i class="fas fa-pause-circle text-warning me-2"></i>En Espera:</small>
                        <span class="float-end badge bg-warning"><?php echo $en_espera_hoy; ?></span>
                    </div>
                    <div class="mb-2">
                        <small><i class="fas fa-check-circle text-success me-2"></i>Resueltos:</small>
                        <span class="float-end badge bg-success"><?php echo $resuelto_hoy; ?></span>
                    </div>
                    <div class="mb-2">
                        <small><i class="fas fa-times-circle text-danger me-2"></i>Cancelados:</small>
                        <span class="float-end badge bg-danger"><?php echo $cancelado_hoy; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($total_casos_hoy > 0): ?>
            <!-- Tarjeta de objetivos -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-bullseye me-2"></i>Objetivos del Día</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small>✅ Distribución equilibrada</small>
                        <div class="progress progress-thin mt-1">
                            <div class="progress-bar bg-<?php echo $balance_hoy ? 'success' : 'warning'; ?>" 
                                 style="width: <?php echo $balance_hoy ? '100' : '50'; ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small>✅ Máximo 5 casos por ejecutivo</small>
                        <div class="progress progress-thin mt-1">
                            <?php
                            $ejecutivos_sobrecargados = count(array_filter($metricas, function($m) { 
                                return ($m['casos_hoy'] ?? 0) > 5; 
                            }));
                            $porcentaje_sobrecarga = count($metricas) > 0 ? round((1 - ($ejecutivos_sobrecargados / count($metricas))) * 100) : 100;
                            ?>
                            <div class="progress-bar bg-<?php echo $ejecutivos_sobrecargados == 0 ? 'success' : 'warning'; ?>" 
                                 style="width: <?php echo $porcentaje_sobrecarga; ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <small>✅ Todos los activos reciben casos</small>
                        <div class="progress progress-thin mt-1">
                            <?php
                            $porcentaje_cobertura = $ejecutivos_activos > 0 ? round(($ejecutivos_con_casos_hoy / $ejecutivos_activos) * 100) : 0;
                            ?>
                            <div class="progress-bar bg-<?php echo $porcentaje_cobertura >= 80 ? 'success' : 'warning'; ?>" 
                                 style="width: <?php echo $porcentaje_cobertura; ?>%">
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- Modal Gestionar Horarios -->
    <div class="modal fade" id="modalHorarios" tabindex="-1" aria-labelledby="modalHorariosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalHorariosLabel">
                    <i class="fas fa-clock me-2"></i>Gestionar Horarios
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoHorarios">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando horarios...</span>
                        </div>
                        <p class="mt-2">Cargando horarios del ejecutivo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btn-guardar-horarios">
                    <i class="fas fa-save me-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
    </div>


    <script>
    // =============================================
    // CÓDIGO JAVASCRIPT PARA GESTIÓN DE EQUIPOS
    // =============================================

    // Función para obtener la URL correcta del API
    function getApiUrl() {
        return '/dashboard/vsm/microapp/microservices/back-admision/api/cambiar_estado_usuario.php';
    }

    // Función principal para cambiar estado
    function cambiarEstadoUsuario(userId, nuevoEstado, userName = '') {
        console.log('🔧 Cambiando estado:', { userId, nuevoEstado, userName });
        
        const datos = {
            user_id: parseInt(userId),
            estado: nuevoEstado,
            supervisor_id: <?php echo $js_user_id; ?>,
            user_role: '<?php echo $js_user_role; ?>',
            work_area: '<?php echo $js_work_area; ?>',
            timestamp: Date.now()
        };
        
        const url = getApiUrl();
        
        // Mostrar loading
        const select = document.querySelector(`select[data-user-id="${userId}"]`);
        const originalHTML = select ? select.innerHTML : '';
        
        if (select) {
            select.disabled = true;
            select.innerHTML = '<option value="">🔄 Procesando...</option>';
        }
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(datos)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarMensaje('success', data.message || `✅ Estado cambiado a ${nuevoEstado}`);
                
                // Actualizar interfaz inmediatamente
                actualizarEstadoEnInterfaz(userId, nuevoEstado, userName);
                
                // Recargar la página para actualizar datos
                setTimeout(() => {
                    console.log('🔄 Recargando para actualizar datos...');
                    window.location.reload();
                }, 1500);
                
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            mostrarMensaje('error', error.message);
            revertirSelectEstado(userId, originalHTML);
        })
        .finally(() => {
            setTimeout(() => {
                if (select) select.disabled = false;
            }, 3000);
        });
    }

    // Función para cargar horarios
    function cargarHorariosUsuario(userId, userName) {
        console.log('📅 Cargando horarios para:', { userId, userName });
        
        const modal = $('#modalHorarios');
        modal.find('#modalHorariosLabel').html(`<i class="fas fa-clock me-2"></i>Horarios - ${userName}`);
        
        // Mostrar loading
        $('#contenidoHorarios').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando horarios...</span>
                </div>
                <p class="mt-2">Cargando horarios de ${userName}...</p>
            </div>
        `);
        
        // URL del API para obtener horarios
        const url = '/dashboard/vsm/microapp/microservices/back-admision/api/get_horarios.php';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                action: 'get_horarios'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarFormularioHorarios(data.horarios, userId, userName);
            } else {
                $('#contenidoHorarios').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar horarios: ${data.message || 'Error desconocido'}
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('❌ Error cargando horarios:', error);
            $('#contenidoHorarios').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión: ${error.message}
                </div>
            `);
        });
    }

    // Función para mostrar formulario de horarios
    function mostrarFormularioHorarios(horarios, userId, userName) {
        const diasSemana = [
            { clave: 'lunes', nombre: 'Lunes' },
            { clave: 'martes', nombre: 'Martes' },
            { clave: 'miercoles', nombre: 'Miércoles' },
            { clave: 'jueves', nombre: 'Jueves' },
            { clave: 'viernes', nombre: 'Viernes' },
            { clave: 'sabado', nombre: 'Sábado' },
            { clave: 'domingo', nombre: 'Domingo' }
        ];
        
        let html = `
            <div class="mb-3">
                <p class="text-muted">Configura los horarios de trabajo para <strong>${userName}</strong></p>
            </div>
            
            <form id="formHorarios" data-user-id="${userId}">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Día</th>
                                <th width="15%">Activo</th>
                                <th width="20%">Hora Entrada</th>
                                <th width="20%">Hora Salida</th>
                                <th width="15%">Almuerzo Inicio</th>
                                <th width="15%">Almuerzo Fin</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        diasSemana.forEach(dia => {
            const horarioDia = horarios.find(h => h.dia_semana === dia.clave) || {};
            
            html += `
                <tr>
                    <td><strong>${dia.nombre}</strong></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input activo-dia" type="checkbox" 
                                   name="horarios[${dia.clave}][activo]" 
                                   ${horarioDia.activo ? 'checked' : ''}
                                   value="1">
                        </div>
                    </td>
                    <td>
                        <input type="time" class="form-control form-control-sm" 
                               name="horarios[${dia.clave}][hora_entrada]"
                               value="${horarioDia.hora_entrada || ''}">
                    </td>
                    <td>
                        <input type="time" class="form-control form-control-sm" 
                               name="horarios[${dia.clave}][hora_salida]"
                               value="${horarioDia.hora_salida || ''}">
                    </td>
                    <td>
                        <input type="time" class="form-control form-control-sm" 
                               name="horarios[${dia.clave}][hora_almuerzo_inicio]"
                               value="${horarioDia.hora_almuerzo_inicio || ''}">
                    </td>
                    <td>
                        <input type="time" class="form-control form-control-sm" 
                               name="horarios[${dia.clave}][hora_almuerzo_fin]"
                               value="${horarioDia.hora_almuerzo_fin || ''}">
                    </td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Los días marcados como inactivos no se considerarán para asignaciones automáticas.
                </div>
            </form>
        `;
        
        $('#contenidoHorarios').html(html);
    }

    // Función para guardar horarios
    function guardarHorarios() {
        const form = document.getElementById('formHorarios');
        if (!form) return;
        
        const userId = form.dataset.userId;
        const formData = new FormData(form);
        const horarios = {};
        
        // Convertir FormData a objeto
        for (let [key, value] of formData.entries()) {
            const match = key.match(/horarios\[(\w+)\]\[(\w+)\]/);
            if (match) {
                const dia = match[1];
                const campo = match[2];
                if (!horarios[dia]) horarios[dia] = {};
                horarios[dia][campo] = value;
            }
        }
        
        console.log('💾 Guardando horarios:', { userId, horarios });
        
        // URL del API para guardar horarios
        const url = '/dashboard/vsm/microapp/microservices/back-admision/api/guardar_horarios.php';
        
        // Mostrar loading en botón
        const btnGuardar = $('#btn-guardar-horarios');
        const originalText = btnGuardar.html();
        btnGuardar.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        btnGuardar.prop('disabled', true);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                horarios: horarios,
                action: 'guardar_horarios'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('success', data.message || '✅ Horarios guardados correctamente');
                $('#modalHorarios').modal('hide');
            } else {
                mostrarMensaje('error', data.message || '❌ Error al guardar horarios');
            }
        })
        .catch(error => {
            console.error('❌ Error guardando horarios:', error);
            mostrarMensaje('error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            btnGuardar.html(originalText);
            btnGuardar.prop('disabled', false);
        });
    }

    // =============================================
    // FUNCIONES AUXILIARES
    // =============================================

    function actualizarEstadoEnInterfaz(userId, nuevoEstado, userName = '') {
        const select = document.querySelector(`select[data-user-id="${userId}"]`);
        if (!select) return;
        
        const fila = select.closest('tr');
        if (!fila) return;
        
        const badgeEstado = fila.querySelector('td:nth-child(3) .badge');
        const statusIndicator = fila.querySelector('.status-indicator');
        
        // Actualizar badge de estado
        if (badgeEstado) {
            badgeEstado.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'bg-secondary', 'bg-info');
            
            const clasesEstado = {
                'activo': 'bg-success',
                'colacion': 'bg-warning', 
                'inactivo': 'bg-danger'
            };
            
            badgeEstado.classList.add(clasesEstado[nuevoEstado] || 'bg-secondary');
            badgeEstado.textContent = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
        }
        
        // Actualizar indicador visual
        if (statusIndicator) {
            statusIndicator.classList.remove('status-activo', 'status-colacion', 'status-inactivo');
            statusIndicator.classList.add('status-' + nuevoEstado);
        }
        
        console.log(`✅ Interfaz actualizada: ${userName} -> ${nuevoEstado}`);
    }

    function revertirSelectEstado(userId, originalHTML = null) {
        const select = document.querySelector(`select[data-user-id="${userId}"]`);
        if (select) {
            if (originalHTML) {
                select.innerHTML = originalHTML;
            } else {
                const previousValue = $(select).data('previous-value');
                if (previousValue) {
                    select.value = previousValue;
                }
            }
            select.disabled = false;
        }
    }

    function mostrarMensaje(tipo, mensaje) {
        const emojiMap = {
            'success': '✅', 
            'error': '❌', 
            'warning': '⚠️', 
            'info': 'ℹ️'
        };
        
        const colorMap = {
            'success': '#28a745',
            'error': '#dc3545', 
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        
        const emoji = emojiMap[tipo] || '💡';
        const color = colorMap[tipo] || '#6c757d';
        
        // Crear mensaje estilizado
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${color};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 400px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        messageDiv.innerHTML = `<strong>${emoji}</strong> ${mensaje}`;
        
        document.body.appendChild(messageDiv);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 5000);
    }

    // =============================================
    // INICIALIZACIÓN Y EVENTOS
    // =============================================

    $(document).ready(function() {
        console.log('🚀 Gestión de equipos inicializada');

        // Cambiar estado de ejecutivo
        $('.cambiar-estado').on('change', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name') || 'Usuario';
            const nuevoEstado = $(this).val();
            const previousValue = $(this).data('previous-value');
            
            if (!userId || userId === 0) {
                mostrarMensaje('error', 'ID de usuario no válido');
                $(this).val(previousValue);
                return;
            }
            
            if (nuevoEstado === previousValue) {
                console.log('Estado sin cambios, ignorando...');
                return;
            }
            
            if (confirm(`¿Estás seguro de cambiar el estado de "${userName}" a "${nuevoEstado}"?`)) {
                $(this).prop('disabled', true);
                cambiarEstadoUsuario(userId, nuevoEstado, userName);
            } else {
                $(this).val(previousValue);
            }
        });

        // Guardar valor anterior al enfocar
        $('.cambiar-estado').on('focus', function() {
            $(this).data('previous-value', $(this).val());
        });

        // Botón de gestionar horarios
        $('.gestionar-horarios').on('click', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            
            cargarHorariosUsuario(userId, userName);
            $('#modalHorarios').modal('show');
        });

        // Guardar horarios
        $('#btn-guardar-horarios').on('click', guardarHorarios);

        // Limpiar modal cuando se cierre
        $('#modalHorarios').on('hidden.bs.modal', function() {
            $('#contenidoHorarios').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando horarios...</span>
                    </div>
                    <p class="mt-2">Cargando horarios del ejecutivo...</p>
                </div>
            `);
        });
    });
    $('#btn-guardar-horarios').on('click', function() {
    const form = document.getElementById('formHorarios');
    if (form) {
        guardarHorariosUsuario($(form).data('user'));
    }
    });
    </script>
