<?php
// microservices/back-admision/views/supervisor/ver-registros.php
require_once __DIR__ . '/../../init.php';

// Verificar permisos de supervisor
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-ban me-2"></i>Acceso Denegado</h4>
            <p>No tienes permisos para acceder a los registros del sistema.</p>
            <p><strong>Tu rol actual:</strong> ' . htmlspecialchars($user_role) . '</p>
        </div>
    </div>';
    exit;
}

$user_id = $backAdmision->getUserId();

// Cargar controladores
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../controllers/AdmissionController.php';
$reportController = new ReportController();
$admissionController = new AdmissionController();

// Obtener filtros
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days')),
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
    'sr_hijo' => $_GET['sr_hijo'] ?? '',
    'usuario_id' => $_GET['usuario_id'] ?? '',
    'accion' => $_GET['accion'] ?? ''
];

// Obtener logs
$logs = $reportController->getLogsSistema($filtros);

// Obtener lista de SRs únicas para el filtro
$srs_unicas = $reportController->getSRsUnicas($filtros['fecha_desde'], $filtros['fecha_hasta']);

// Funciones helper para la vista
function getColorAccion($accion) {
    $colores = [
        'asignacion_automatica' => 'primary',
        'asignacion_manual' => 'success',
        'reasignacion' => 'warning',
        'cambio_estado' => 'info',
        'modificacion_caso' => 'secondary',
        'creacion_caso' => 'success',
        'cierre_caso' => 'dark',
        'reasignacion_automatica' => 'warning'
    ];
    
    return $colores[$accion] ?? 'secondary';
}

function getIconoAccion($accion) {
    $iconos = [
        'asignacion_automatica' => 'fas fa-robot',
        'asignacion_manual' => 'fas fa-user-check',
        'reasignacion' => 'fas fa-exchange-alt',
        'cambio_estado' => 'fas fa-sync',
        'modificacion_caso' => 'fas fa-edit',
        'creacion_caso' => 'fas fa-plus-circle',
        'cierre_caso' => 'fas fa-check-circle',
        'reasignacion_automatica' => 'fas fa-cogs'
    ];
    
    return $iconos[$accion] ?? 'fas fa-info-circle';
}

function getTextoAccion($accion) {
    $textos = [
        'asignacion_automatica' => 'Asign. Automática',
        'asignacion_manual' => 'Asign. Manual',
        'reasignacion' => 'Reasignación',
        'cambio_estado' => 'Cambio Estado',
        'modificacion_caso' => 'Modificación',
        'creacion_caso' => 'Creación',
        'cierre_caso' => 'Cierre',
        'reasignacion_automatica' => 'Reasign. Auto'
    ];
    
    return $textos[$accion] ?? $accion;
}

function getDescripcionAccion($log) {
    $detalles = json_decode($log['detalles'] ?? '{}', true);
    
    switch ($log['accion']) {
        case 'asignacion_automatica':
            return '<i class="fas fa-user-check text-success"></i> Asignado automáticamente a <strong>' . ($detalles['analista'] ?? 'N/A') . '</strong>';
        
        case 'asignacion_manual':
            return '<i class="fas fa-user-edit text-primary"></i> Asignado manualmente por <strong>' . ($detalles['supervisor'] ?? 'N/A') . '</strong> a <strong>' . ($detalles['analista'] ?? 'N/A') . '</strong>';
        
        case 'reasignacion':
        case 'reasignacion_automatica':
            return '<i class="fas fa-exchange-alt text-warning"></i> Reasignado de <strong>' . ($detalles['analista_anterior'] ?? 'N/A') . '</strong> a <strong>' . ($detalles['analista_nuevo'] ?? 'N/A') . '</strong>';
        
        case 'cambio_estado':
            return '<i class="fas fa-sync text-info"></i> Estado cambiado a: <strong>' . ($detalles['nuevo_estado'] ?? 'N/A') . '</strong>' . 
                   (isset($detalles['estado_anterior']) ? ' (antes: ' . $detalles['estado_anterior'] . ')' : '');
        
        case 'modificacion_caso':
            $campos = isset($detalles['campos_modificados']) ? implode(', ', $detalles['campos_modificados']) : 'N/A';
            return '<i class="fas fa-edit text-secondary"></i> Campos modificados: <strong>' . $campos . '</strong>';
        
        case 'creacion_caso':
            return '<i class="fas fa-plus-circle text-success"></i> Caso creado por <strong>' . ($detalles['analista'] ?? 'Sistema') . '</strong>';
        
        case 'cierre_caso':
            return '<i class="fas fa-check-circle text-dark"></i> Caso cerrado - ' . ($detalles['motivo'] ?? 'Finalizado');
        
        default:
            return '<i class="fas fa-info-circle"></i> ' . ($log['detalles'] ?? 'Sin detalles específicos');
    }
}

function getDetallesCompletos($log) {
    $detalles = json_decode($log['detalles'] ?? '{}', true);
    $html = '';
    
    if (!empty($detalles)) {
        foreach ($detalles as $clave => $valor) {
            if (is_array($valor)) {
                $valor = json_encode($valor, JSON_PRETTY_PRINT);
            }
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($clave) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($valor) . '</td>';
            $html .= '</tr>';
        }
    }
    
    return $html;
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
        .log-row:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .badge-accion {
            font-size: 0.75em;
        }
        .table-fixed {
            table-layout: fixed;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .filtro-avanzado {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .estado-activo {
            color: #28a745;
        }
        .estado-inactivo {
            color: #dc3545;
        }
    </style>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-primary">
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active text-white">Registros del Sistema</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><i class="fas fa-history me-2"></i>Registros del Sistema</h4>
                            <p class="mb-0 mt-1 small opacity-75">Auditoría completa de todos los movimientos y cambios</p>
                        </div>
                        <span class="badge bg-light text-dark fs-6"><?php echo count($logs); ?> registros</span>
                    </div>
                    
                    <div class="card-body">
                        <!-- Filtros Avanzados -->
                        <div class="filtro-avanzado">
                            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                            <form method="get" action="">
                                <input type="hidden" name="vista" value="back-admision">
                                <input type="hidden" name="action" value="ver-registros">
                                
                                <div class="row g-3">
                                    <div class="col-12 col-md-2">
                                        <label for="fecha_desde" class="form-label">Desde</label>
                                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                                               value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="fecha_hasta" class="form-label">Hasta</label>
                                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                               value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="sr_hijo" class="form-label">SR Hijo</label>
                                        <input type="text" class="form-control" id="sr_hijo" name="sr_hijo"
                                               value="<?php echo htmlspecialchars($filtros['sr_hijo']); ?>"
                                               placeholder="Ej: SR12345" list="lista-srs">
                                        <datalist id="lista-srs">
                                            <?php foreach ($srs_unicas as $sr): ?>
                                                <option value="<?php echo htmlspecialchars($sr); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="accion" class="form-label">Tipo de Acción</label>
                                        <select class="form-select" id="accion" name="accion">
                                            <option value="">Todas las acciones</option>
                                            <option value="creacion_caso" <?php echo $filtros['accion'] == 'creacion_caso' ? 'selected' : ''; ?>>Creación de Caso</option>
                                            <option value="asignacion_automatica" <?php echo $filtros['accion'] == 'asignacion_automatica' ? 'selected' : ''; ?>>Asignación Automática</option>
                                            <option value="asignacion_manual" <?php echo $filtros['accion'] == 'asignacion_manual' ? 'selected' : ''; ?>>Asignación Manual</option>
                                            <option value="reasignacion" <?php echo $filtros['accion'] == 'reasignacion' ? 'selected' : ''; ?>>Reasignación</option>
                                            <option value="cambio_estado" <?php echo $filtros['accion'] == 'cambio_estado' ? 'selected' : ''; ?>>Cambio de Estado</option>
                                            <option value="modificacion_caso" <?php echo $filtros['accion'] == 'modificacion_caso' ? 'selected' : ''; ?>>Modificación</option>
                                            <option value="cierre_caso" <?php echo $filtros['accion'] == 'cierre_caso' ? 'selected' : ''; ?>>Cierre</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label for="usuario_id" class="form-label">Usuario ID</label>
                                        <input type="number" class="form-control" id="usuario_id" name="usuario_id"
                                               value="<?php echo htmlspecialchars($filtros['usuario_id']); ?>"
                                               placeholder="ID de usuario">
                                    </div>
                                    <div class="col-12 col-md-2 d-flex align-items-end">
                                        <div class="d-grid gap-2 w-100">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Buscar
                                            </button>
                                            <a href="?vista=back-admision&action=ver-registros" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Estadísticas Rápidas -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Registros</small>
                                        <h4 class="mb-0 text-primary"><?php echo count($logs); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Período</small>
                                        <h6 class="mb-0 text-dark"><?php echo date('d/m/Y', strtotime($filtros['fecha_desde'])); ?> - <?php echo date('d/m/Y', strtotime($filtros['fecha_hasta'])); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">SR Filtrada</small>
                                        <h6 class="mb-0 text-dark"><?php echo $filtros['sr_hijo'] ?: 'Todas'; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Acción</small>
                                        <h6 class="mb-0 text-dark"><?php echo $filtros['accion'] ? getTextoAccion($filtros['accion']) : 'Todas'; ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resultados -->
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Historial de Movimientos</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-2" id="btn-exportar-csv">
                                        <i class="fas fa-file-csv me-1"></i>CSV
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" id="btn-exportar-excel">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card-body p-0">
                                <?php if (empty($logs)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No se encontraron registros</h5>
                                        <p class="text-muted">Intenta con otros criterios de búsqueda o amplía el rango de fechas</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover table-fixed mb-0">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="15%">Fecha/Hora</th>
                                                    <th width="12%">SR Hijo</th>
                                                    <th width="15%">Acción</th>
                                                    <th width="18%">Usuario</th>
                                                    <th width="30%">Detalles</th>
                                                    <th width="10%">IP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($logs as $log): ?>
                                                <tr class="log-row" data-log-id="<?php echo $log['id']; ?>" 
                                                    data-bs-toggle="modal" data-bs-target="#modalDetallesLog">
                                                    <td>
                                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($log['fecha_accion'])); ?></small>
                                                        <br>
                                                        <small class="text-dark"><?php echo date('H:i:s', strtotime($log['fecha_accion'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($log['sr_hijo']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-accion bg-<?php echo getColorAccion($log['accion']); ?>">
                                                            <i class="<?php echo getIconoAccion($log['accion']); ?> me-1"></i>
                                                            <?php echo getTextoAccion($log['accion']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user-circle me-2 text-muted"></i>
                                                            <div>
                                                                <small class="text-dark"><?php echo htmlspecialchars($log['usuario_nombre'] ?? 'Sistema'); ?></small>
                                                                <br>
                                                                <small class="text-muted">ID: <?php echo $log['usuario_id'] ?? 'N/A'; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate-2">
                                                            <?php echo getDescripcionAccion($log); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted font-monospace"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Paginación (si hay muchos resultados) -->
                                    <?php if (count($logs) >= 100): ?>
                                    <div class="card-footer bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Mostrando los últimos 100 registros</small>
                                            <a href="#" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i>Descargar todos
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalles Completos -->
    <div class="modal fade" id="modalDetallesLog" tabindex="-1" aria-labelledby="modalDetallesLogLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetallesLogLabel">
                        <i class="fas fa-info-circle me-2"></i>Detalles Completos del Registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetallesLog">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando detalles...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Cargar detalles del log via AJAX
        $('#modalDetallesLog').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var logId = button.closest('tr').data('log-id');
            var modal = $(this);
            
            $.ajax({
                url: '/dashboard/vsm/microapp/microservices/back-admision/api/get_log_details.php',
                type: 'GET',
                data: { log_id: logId },
                success: function(response) {
                    modal.find('#contenidoDetallesLog').html(response);
                },
                error: function() {
                    modal.find('#contenidoDetallesLog').html(
                        '<div class="alert alert-danger">Error al cargar los detalles del registro.</div>'
                    );
                }
            });
        });

        // Exportar a CSV
        $('#btn-exportar-csv').click(function() {
            const params = new URLSearchParams({
                fecha_desde: $('#fecha_desde').val(),
                fecha_hasta: $('#fecha_hasta').val(),
                sr_hijo: $('#sr_hijo').val(),
                usuario_id: $('#usuario_id').val(),
                accion: $('#accion').val(),
                formato: 'csv'
            });
            
            window.open('/dashboard/vsm/microapp/microservices/back-admision/api/exportar_logs.php?' + params.toString(), '_blank');
        });

        // Exportar a Excel
        $('#btn-exportar-excel').click(function() {
            const params = new URLSearchParams({
                fecha_desde: $('#fecha_desde').val(),
                fecha_hasta: $('#fecha_hasta').val(),
                sr_hijo: $('#sr_hijo').val(),
                usuario_id: $('#usuario_id').val(),
                accion: $('#accion').val(),
                formato: 'excel'
            });
            
            window.open('/dashboard/vsm/microapp/microservices/back-admision/api/exportar_logs.php?' + params.toString(), '_blank');
        });

        // Auto-seleccionar texto en campo SR al hacer clic
        $('#sr_hijo').click(function() {
            $(this).select();
        });
    });
    </script>
