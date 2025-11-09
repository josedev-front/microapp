<?php
// microservices/back-admision/views/supervisor/ver-registros.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();

// Cargar controladores
require_once __DIR__ . '/../../controllers/ReportController.php';
$reportController = new ReportController();

// Obtener filtros
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days')),
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
    'sr_hijo' => $_GET['sr_hijo'] ?? '',
    'usuario_id' => $_GET['usuario_id'] ?? ''
];

// Obtener logs
$logs = $reportController->getLogsSistema($filtros);

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

function getTextoAccion($accion) {
    $textos = [
        'asignacion_automatica' => 'Asign. Auto',
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
            return 'Asignado automáticamente a ' . ($detalles['analista_asignado'] ?? 'N/A');
        case 'asignacion_manual':
            return 'Asignado manualmente por ' . ($detalles['supervisor'] ?? 'N/A') . ' a ' . ($detalles['analista_asignado'] ?? 'N/A');
        case 'reasignacion':
            return 'Reasignado de ' . ($detalles['analista_anterior'] ?? 'N/A') . ' a ' . ($detalles['analista_nuevo'] ?? 'N/A');
        case 'cambio_estado':
            return 'Estado cambiado a: ' . ($detalles['nuevo_estado'] ?? 'N/A');
        case 'modificacion_caso':
            return 'Campos modificados: ' . (isset($detalles) ? implode(', ', array_keys($detalles)) : 'N/A');
        default:
            return $log['detalles'] ?? 'Sin detalles';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Registros - Back de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-row:hover {
            background-color: #f8f9fa;
        }
        .badge-accion {
            font-size: 0.7em;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active">Ver Registros</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Registros del Sistema</h4>
                    </div>
                    <div class="card-body">
                        <!-- Pestañas -->
                        <ul class="nav nav-tabs mb-4" id="registrosTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="consultar-logs-tab" data-bs-toggle="tab" data-bs-target="#consultar-logs" type="button" role="tab">
                                    <i class="fas fa-search me-2"></i>Consultar Logs
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="descargar-registros-tab" data-bs-toggle="tab" data-bs-target="#descargar-registros" type="button" role="tab">
                                    <i class="fas fa-download me-2"></i>Descargar Registros
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="registrosTabsContent">
                            <!-- Pestaña 1: Consultar Logs -->
                            <div class="tab-pane fade show active" id="consultar-logs" role="tabpanel">
                                <!-- Filtros -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="get" action="">
                                            <input type="hidden" name="vista" value="admision-ver-registros">
                                            <div class="row">
                                                <div class="col-12 col-md-3">
                                                    <label for="fecha_desde" class="form-label">Desde</label>
                                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                                                           value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label for="fecha_hasta" class="form-label">Hasta</label>
                                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                                           value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label for="sr_hijo" class="form-label">SR Hijo</label>
                                                    <input type="text" class="form-control" id="sr_hijo" name="sr_hijo"
                                                           value="<?php echo htmlspecialchars($filtros['sr_hijo']); ?>"
                                                           placeholder="Buscar por SR...">
                                                </div>
                                                <div class="col-12 col-md-3 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="fas fa-search me-2"></i>Buscar
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Resultados -->
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Resultados (<?php echo count($logs); ?> registros)</h5>
                                        <button class="btn btn-sm btn-outline-secondary" id="btn-exportar-logs">
                                            <i class="fas fa-file-export me-1"></i>Exportar
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($logs)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No se encontraron registros</h5>
                                                <p class="text-muted">Intenta con otros criterios de búsqueda</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="15%">Fecha/Hora</th>
                                                            <th width="12%">SR Hijo</th>
                                                            <th width="15%">Acción</th>
                                                            <th width="20%">Usuario</th>
                                                            <th width="28%">Detalles</th>
                                                            <th width="10%">IP</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($logs as $log): ?>
                                                        <tr class="log-row">
                                                            <td>
                                                                <small><?php echo date('d/m/Y H:i', strtotime($log['fecha_accion'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <strong class="text-primary"><?php echo htmlspecialchars($log['sr_hijo']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-accion bg-<?php 
                                                                    echo getColorAccion($log['accion']); 
                                                                ?>">
                                                                    <?php echo getTextoAccion($log['accion']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($log['usuario_nombre'] ?? 'Sistema'); ?></small>
                                                                <br>
                                                                <small class="text-muted">ID: <?php echo $log['usuario_id'] ?? 'N/A'; ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo getDescripcionAccion($log); ?></small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small>
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

                            <!-- Pestaña 2: Descargar Registros -->
                            <div class="tab-pane fade" id="descargar-registros" role="tabpanel">
                                <div class="row">
                                    <div class="col-12 col-md-8 mx-auto">
                                        <div class="card">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Descargar Planilla Excel</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle me-2"></i>Información</h6>
                                                    <p class="mb-0">
                                                        Descarga una planilla Excel con todos los registros del sistema, 
                                                        incluyendo información completa de casos, asignaciones y movimientos.
                                                    </p>
                                                </div>

                                                <form method="post" action="/?vista=admision-api-descargar-excel">
                                                    <div class="row mb-3">
                                                        <div class="col-12 col-md-6">
                                                            <label for="excel_fecha_desde" class="form-label">Desde</label>
                                                            <input type="date" class="form-control" id="excel_fecha_desde" name="fecha_desde"
                                                                   value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <label for="excel_fecha_hasta" class="form-label">Hasta</label>
                                                            <input type="date" class="form-control" id="excel_fecha_hasta" name="fecha_hasta"
                                                                   value="<?php echo date('Y-m-d'); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                                                            <select class="form-select" id="tipo_reporte" name="tipo_reporte">
                                                                <option value="completo">Reporte Completo</option>
                                                                <option value="asignaciones">Solo Asignaciones</option>
                                                                <option value="casos_activos">Casos Activos</option>
                                                                <option value="metricas">Métricas y Estadísticas</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-success btn-lg">
                                                            <i class="fas fa-file-excel me-2"></i>Generar y Descargar Excel
                                                        </button>
                                                    </div>
                                                </form>

                                                <div class="mt-4">
                                                    <h6>Columnas incluidas en el reporte:</h6>
                                                    <div class="row">
                                                        <div class="col-12 col-md-6">
                                                            <ul class="small">
                                                                <li>SR Hijo</li>
                                                                <li>SRP</li>
                                                                <li>Estado</li>
                                                                <li>Fecha Ingreso</li>
                                                                <li>Ticket</li>
                                                                <li>Analista</li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <ul class="small">
                                                                <li>Motivo del Ticket</li>
                                                                <li>Tipo de Negocio</li>
                                                                <li>Observaciones</li>
                                                                <li>Biometría</li>
                                                                <li>Acreditación</li>
                                                                <li>Inicio de Actividades</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalles Log -->
    <div class="modal fade" id="modalDetallesLog" tabindex="-1" aria-labelledby="modalDetallesLogLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetallesLogLabel">
                        <i class="fas fa-info-circle me-2"></i>Detalles del Registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetallesLog">
                        <!-- El contenido se cargará via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../../templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Exportar logs a CSV
        $('#btn-exportar-logs').click(function() {
            const fecha_desde = $('#fecha_desde').val();
            const fecha_hasta = $('#fecha_hasta').val();
            const sr_hijo = $('#sr_hijo').val();
            
            const url = `/?vista=admision-api-exportar-logs&fecha_desde=${fecha_desde}&fecha_hasta=${fecha_hasta}&sr_hijo=${sr_hijo}`;
            window.open(url, '_blank');
        });

        // Ver detalles del log (podría implementarse con AJAX)
        $('.log-row').click(function() {
            // En una implementación real, cargaríamos los detalles via AJAX
            alert('Funcionalidad de detalles se implementará próximamente');
        });
    });
    </script>
</body>
</html>