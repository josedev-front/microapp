<?php
// microservices/back-admision/views/supervisor/panel-asignaciones.php

// COPIAR EXACTAMENTE LA ESTRUCTURA DE ingresar-caso.php
require_once __DIR__ . '/../../init.php';

// Verificar que el usuario tenga permisos de supervisor (igual que ingresar-caso)
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-ban me-2"></i>Acceso Denegado</h4>
            <p>No tienes permisos para acceder al Panel de Asignaciones.</p>
            <p><strong>Tu rol actual:</strong> ' . htmlspecialchars($user_role) . '</p>
            <p><strong>Roles permitidos:</strong> ' . implode(', ', $roles_permitidos) . '</p>
            <div class="mt-3">
                <a href="/dashboard/vsm/microapp/public/?vista=back-admision" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Menú
                </a>
            </div>
        </div>
    </div>';
    exit;
}

// Cargar controladores (igual que en otras vistas)
require_once __DIR__ . '/../../controllers/TeamController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../controllers/AdmissionController.php';

$teamController = new TeamController();
$reportController = new ReportController();
$admissionController = new AdmissionController();

// PRIMERO: Obtener parámetros de filtro si existen - ESTO DEBE IR ANTES
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Validar fechas
if (!strtotime($fecha_desde)) $fecha_desde = date('Y-m-d', strtotime('-7 days'));
if (!strtotime($fecha_hasta)) $fecha_hasta = date('Y-m-d');

// AHORA SÍ: Obtener métricas de balance con los filtros aplicados
$metricas = $admissionController->getMetricasEjecutivosCorregidas($fecha_desde, $fecha_hasta);
$estadisticas_diarias = $reportController->getEstadisticasDiarias($fecha_desde, $fecha_hasta);
$distribucion_estados = $reportController->getDistribucionEstadosPorFecha($fecha_hasta);

// Si los controladores no soportan parámetros de fecha, usar estos métodos alternativos:
// $metricas = $teamController->getMetricasBalanceDiario();
// $estadisticas_diarias = $reportController->getEstadisticasDiarias();
// $distribucion_estados = $admissionController->getDistribucionEstadosHoy();

// Calcular métricas para gráficos
$labels = [];
$datos_casos_hoy = [];
$datos_casos_totales = [];
$colores = [];

foreach ($metricas as $metrica) {
    $labels[] = $metrica['nombre_completo'];
    
    // USAR EL CAMPO CORRECTO - 'total_casos' en lugar de 'casos_hoy'
    $casos_para_grafico = $metrica['total_casos'] ?? $metrica['casos_hoy'] ?? 0;
    $datos_casos_hoy[] = $casos_para_grafico;
    
       // Asignar colores según la carga de hoy
    if ($casos_para_grafico == 0) {
        $colores[] = '#28a745'; // Verde - Sin casos hoy
    } elseif ($casos_para_grafico < 3) {
        $colores[] = '#17a2b8'; // Azul - Carga baja
    } elseif ($casos_para_grafico < 6) {
        $colores[] = '#ffc107'; // Amarillo - Carga media
    } else {
        $colores[] = '#dc3545'; // Rojo - Carga alta
    }
}

// Datos por defecto para evitar errores
$estadisticas_diarias = array_merge([
    'total_casos_hoy' => 0,
    'ejecutivos_activos' => 0,
    'promedio_por_ejecutivo' => 0,
    'indice_balance' => 0,
    'desviacion_estandar' => 0,
    'coeficiente_variacion' => 0,
    'indice_gini' => 0
], $estadisticas_diarias);

$distribucion_estados = array_merge([
    'en_curso' => 0,
    'en_espera' => 0,
    'resuelto' => 0,
    'cancelado' => 0
], $distribucion_estados);

// Sanitizar datos para JavaScript (usar datos de backAdmision)
$js_user_id = $backAdmision->getUserId();
$js_user_role = $backAdmision->getUserRole();
$js_work_area = $backAdmision->getUserArea();
?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->

    <style>
        .breadcrumb {
            margin-top: 50px;
            border-radius: 5px;
        }
        .breadcrumb-item > a {
            color: white;
            text-decoration: none;
        }
        .stat-card {
            transition: transform 0.2s ease;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .progress-thin {
            height: 8px;
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
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-success">
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active">Panel de Asignaciones</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Panel de Asignaciones - Micro&SOHO</h4>
                        <p class="mb-0 mt-1 small opacity-75">Distribución diaria de casos - <?php echo date('d/m/Y'); ?></p>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
<div class="row mb-4">
    <div class="col-12 col-md-4">
        <label for="fecha_desde" class="form-label">Desde</label>
        <input type="date" class="form-control" id="fecha_desde" 
               value="<?php echo htmlspecialchars($fecha_desde); ?>">
    </div>
    <div class="col-12 col-md-4">
        <label for="fecha_hasta" class="form-label">Hasta</label>
        <input type="date" class="form-control" id="fecha_hasta" 
               value="<?php echo htmlspecialchars($fecha_hasta); ?>">
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" id="btn-aplicar-filtros">
            <i class="fas fa-filter me-2"></i>Aplicar
        </button>
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-secondary w-100" id="btn-limpiar-filtros">
            <i class="fas fa-times me-2"></i>Limpiar
        </button>
    </div>
</div>




                        <!-- Tarjetas de Resumen DIARIO -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                        <h3 class="text-primary"><?php echo $estadisticas_diarias['total_casos_hoy']; ?></h3>
                                        <p class="text-muted mb-0">Casos Hoy</p>
                                        <small class="text-muted">Ingresados el <?php echo date('d/m'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                        <h3 class="text-success"><?php echo $estadisticas_diarias['ejecutivos_activos']; ?></h3>
                                        <p class="text-muted mb-0">Ejecutivos Activos</p>
                                        <small class="text-muted">En turno actual</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                                        <h3 class="text-warning"><?php echo $estadisticas_diarias['promedio_por_ejecutivo']; ?></h3>
                                        <p class="text-muted mb-0">Promedio/Ejecutivo</p>
                                        <small class="text-muted">Casos hoy por persona</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-balance-scale fa-2x text-info mb-2"></i>
                                        <h3 class="text-info"><?php echo $estadisticas_diarias['indice_balance']; ?>%</h3>
                                        <p class="text-muted mb-0">Índice Balance</p>
                                        <small class="text-muted">Eficiencia distribución hoy</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráficos -->
                        <div class="row">
                            <!-- Gráfico de Distribución DIARIA -->
                            <div class="col-12 col-lg-8 mb-4">
    <div class="card h-100">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Distribución por Ejecutivo</h5>
            <p class="mb-0 mt-1 small text-muted">
                Estado actual
            </p>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="graficoDistribucion"></canvas>
            </div>
        </div>
    </div>
</div>
                        

                            <!-- Gráfico de Estados HOY -->
                           <!-- Gráfico de Estados -->
<div class="col-12 col-lg-4 mb-4">
    <div class="card h-100">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estados de Casos por periodo</h5>
            <p class="mb-0 mt-1 small text-muted">
                Período: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
            </p>
        </div>
        <div class="card-body">
            <?php
            // Obtener distribución de estados usando AdmissionController con filtros
            $distribucion_estados = $admissionController->getDistribucionEstadosPorRango($fecha_desde, $fecha_hasta);
            
            $total_casos = array_sum($distribucion_estados);
            ?>
            
            <div class="chart-container">
                <canvas id="graficoEstados" height="200"></canvas>
            </div>
            
            <div class="mt-3 text-center">
                <small class="text-muted">Total: <?php echo $total_casos; ?> casos en el período</small>
            </div>
        </div>
    </div>
</div>
                        </div>

                        <!-- Sección 2: Lista de Usuarios con Métricas por Estado -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Métricas por Ejecutivo - <?php echo date('d/m/Y'); ?></h5>
                                        <p class="mb-0 mt-1 small text-muted">Distribución de casos por estado</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th>Estado</th>
                                                        <th>Ejecutivo</th>
                                                        <th>Casos Hoy</th>
                                                        <th>En Curso</th>
                                                        <th>En Espera</th>
                                                        <th>Resueltos</th>
                                                        <th>Cancelados</th>
                                                        <th>Cambiar Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($metricas as $metrica): 
                                                        $user_id = $metrica['user_id'] ?? $metrica['id'] ?? 0;
                                                        $nombre = htmlspecialchars($metrica['nombre_completo'] ?? 'N/A');
                                                        $estado = $metrica['estado'] ?? 'inactivo';
                                                        $casos_hoy = $metrica['casos_hoy'] ?? 0;
                                                        $casos_curso = $metrica['en_curso'] ?? 0;
                                                        $casos_espera = $metrica['en_espera'] ?? 0;
                                                        $casos_resueltos = $metrica['resuelto'] ?? 0;
                                                        $casos_cancelados = $metrica['cancelado'] ?? 0;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <span class="status-indicator status-<?php echo $estado; ?>"></span>
                                                            <span class="badge bg-<?php 
                                                                echo $estado == 'activo' ? 'success' : 
                                                                ($estado == 'colacion' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($estado); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo $nombre; ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $casos_hoy == 0 ? 'secondary' : 
                                                                ($casos_hoy < 3 ? 'info' : 
                                                                ($casos_hoy < 6 ? 'warning' : 'danger')); 
                                                            ?> fs-6">
                                                                <?php echo $casos_hoy; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary badge-estado"><?php echo $casos_curso; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning badge-estado"><?php echo $casos_espera; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success badge-estado"><?php echo $casos_resueltos; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-danger badge-estado"><?php echo $casos_cancelados; ?></span>
                                                        </td>
                                                        <td>
                                                            <select class="form-select form-select-sm cambiar-estado" 
                                                                    data-user-id="<?php echo $user_id; ?>"
                                                                    data-user-name="<?php echo $nombre; ?>">
                                                                <option value="activo" <?php echo $estado == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                                                <option value="colacion" <?php echo $estado == 'colacion' ? 'selected' : ''; ?>>Colación</option>
                                                                <option value="inactivo" <?php echo $estado == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-primary gestionar-horarios" 
                                                                        data-user-id="<?php echo $user_id; ?>"
                                                                        data-user-name="<?php echo $nombre; ?>">
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
                                </div>
                            </div>
                        </div>

                        <!-- Métricas de Balance -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Métricas de Balance - Distribución Diaria</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['desviacion_estandar'] <= 2 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['desviacion_estandar'], 2); ?>
                                                </h3>
                                                <p class="text-muted mb-0">Desviación Estándar</p>
                                                <small class="text-muted">Casos hoy - Menor es mejor</small>
                                            </div>
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['coeficiente_variacion'] <= 40 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['coeficiente_variacion'], 1); ?>%
                                                </h3>
                                                <p class="text-muted mb-0">Coef. Variación</p>
                                                <small class="text-muted">≤ 40% bien balanceado</small>
                                            </div>
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['indice_gini'] <= 0.2 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['indice_gini'], 3); ?>
                                                </h3>
                                                <p class="text-muted mb-0">Índice Gini</p>
                                                <small class="text-muted">≤ 0.2 distribución ideal</small>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Objetivo:</strong> Distribuir los casos diarios de manera equilibrada entre todos los ejecutivos activos.
                                                    Meta ideal: ±2 casos de diferencia entre ejecutivos.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                          <!-- TABLA DE SRs ACTIVAS - NUEVA SECCIÓN -->
                        <div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>SRs Activas - Micro&SOHO</h5>
                <p class="mb-0 mt-1 small opacity-75">Casos en curso y en espera - Se actualizan automáticamente al cambiar estado</p>
            </div>
            <div class="card-body">
                <?php
                // Obtener SRs activas desde AdmissionController
                $srs_activas = $admissionController->getSRActivasMicroSOHO();
                ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>SR Hijo</th>
                                <th>Estado</th>
                                <th>Ejecutivo</th>
                                <th>Fecha Ingreso</th>
                                <th>Última Actualización</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($srs_activas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No hay SRs activas en este momento
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($srs_activas as $sr): ?>
                                <tr data-sr-hijo="<?php echo htmlspecialchars($sr['sr_hijo']); ?>">
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($sr['sr_hijo']); ?></strong>
                                        <?php if (!empty($sr['srp'])): ?>
                                            <br><small class="text-muted">SRP: <?php echo htmlspecialchars($sr['srp']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $sr['estado'] == 'en_curso' ? 'primary' : 
                                                 ($sr['estado'] == 'en_espera' ? 'warning' : 
                                                 ($sr['estado'] == 'resuelto' ? 'success' : 'danger')); 
                                        ?>">
                                            <?php 
                                            $estados = [
                                                'en_curso' => 'En Curso',
                                                'en_espera' => 'En Espera', 
                                                'resuelto' => 'Resuelto',
                                                'cancelado' => 'Cancelado'
                                            ];
                                            echo $estados[$sr['estado']] ?? $sr['estado'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="status-indicator status-<?php echo $sr['estado_ejecutivo'] ?? 'inactivo'; ?> me-2"></span>
                                            <?php echo htmlspecialchars($sr['analista_nombre'] ?? 'No asignado'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($sr['fecha_ingreso'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($sr['fecha_actualizacion'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                        <!-- Botón para gestionar SR - abre vista de gestión -->
                                            <button class="btn btn-outline-primary btn-gestionar-sr" 
                                                data-sr-hijo="<?php echo htmlspecialchars($sr['sr_hijo']); ?>"  
                                                data-bs-toggle="tooltip" title="Gestionar SR">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <!-- Botón para ver logs - abre vista de logs -->
                                            <button class="btn btn-outline-info btn-ver-log"
                                                data-sr-hijo="<?php echo htmlspecialchars($sr['sr_hijo']); ?>"
                                                data-bs-toggle="tooltip" title="Ver Historial">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Contadores -->
                <div class="row mt-3">
                    <div class="col-12 col-md-3 text-center">
                        <span class="badge bg-primary fs-6"><?php echo $distribucion_estados['en_curso']; ?> En Curso</span>
                    </div>
                    <div class="col-12 col-md-3 text-center">
                        <span class="badge bg-warning fs-6"><?php echo $distribucion_estados['en_espera']; ?> En Espera</span>
                    </div>
                    <div class="col-12 col-md-3 text-center">
                        <span class="badge bg-success fs-6"><?php echo $distribucion_estados['resuelto']; ?> Resueltos</span>
                    </div>
                    <div class="col-12 col-md-3 text-center">
                        <span class="badge bg-danger fs-6"><?php echo $distribucion_estados['cancelado']; ?> Cancelados</span>
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

<script>
// =============================================
// CÓDIGO JAVASCRIPT COMPLETO Y CORREGIDO
// =============================================

// Función para obtener la URL correcta del API
function getApiUrl() {
    return '/dashboard/vsm/microapp/microservices/back-admision/api/cambiar_estado_usuario.php';
}

// Función para aplicar filtros
function aplicarFiltros() {
    const fechaDesde = $('#fecha_desde').val();
    const fechaHasta = $('#fecha_hasta').val();
    
    if (!fechaDesde || !fechaHasta) {
        mostrarMensaje('warning', 'Por favor selecciona ambas fechas');
        return;
    }
    
    if (fechaDesde > fechaHasta) {
        mostrarMensaje('error', 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
        return;
    }
    
    // Mostrar loading
    mostrarMensaje('info', `Aplicando filtros desde ${fechaDesde} hasta ${fechaHasta}...`);
    
    // Obtener parámetros actuales de la URL
    const urlParams = new URLSearchParams(window.location.search);
    
    // Actualizar solo los parámetros de fecha
    urlParams.set('fecha_desde', fechaDesde);
    urlParams.set('fecha_hasta', fechaHasta);
    
    // Mantener los parámetros de vista y action si existen
    if (!urlParams.has('vista')) {
        urlParams.set('vista', 'back-admision');
    }
    if (!urlParams.has('action')) {
        urlParams.set('action', 'panel-asignaciones');
    }
    
    // Construir nueva URL
    const nuevaUrl = window.location.pathname + '?' + urlParams.toString();
    
    // Recargar la página con los filtros
    window.location.href = nuevaUrl;
}

// Función para limpiar filtros
function limpiarFiltros() {
    // Restablecer valores por defecto
    $('#fecha_desde').val('<?php echo date('Y-m-d', strtotime('-7 days')); ?>');
    $('#fecha_hasta').val('<?php echo date('Y-m-d'); ?>');
    
    mostrarMensaje('info', 'Filtros limpiados. Aplica para recargar.');
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
            
            // Recargar la página para actualizar gráficos y tabla de SRs
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

// Función para gestionar SR
function gestionarSR(srHijo) {
    console.log('📋 Gestionando SR:', srHijo);
    // Redirigir a la vista de gestión
    window.location.href = `/dashboard/vsm/microapp/public/?vista=back-admision&action=gestionar-solicitud&sr=${srHijo}`;
}

// Función para ver log de SR
function verLogSR(srHijo) {
    console.log('📊 Viendo log de SR:', srHijo);
    // Redirigir a la vista de logs
    window.location.href = `/dashboard/vsm/microapp/public/?vista=back-admision&action=ver-registros&sr=${srHijo}`;
}

// =============================================
// FUNCIONES AUXILIARES
// =============================================

function actualizarEstadoEnInterfaz(userId, nuevoEstado, userName = '') {
    const select = document.querySelector(`select[data-user-id="${userId}"]`);
    if (!select) return;
    
    const fila = select.closest('tr');
    if (!fila) return;
    
    const badgeEstado = fila.querySelector('td:first-child .badge');
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
    // Sistema de mensajes mejorado
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

// Función para inicializar gráficos
function inicializarGraficos() {
    // Gráfico de distribución
    const ctxDistribucion = document.getElementById('graficoDistribucion');
    if (ctxDistribucion) {
        new Chart(ctxDistribucion.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels ?? []); ?>,
                datasets: [{
                    label: 'Casos en el Período',
                    data: <?php echo json_encode($datos_casos_hoy ?? []); ?>,
                    backgroundColor: <?php echo json_encode($colores ?? ['#007bff']); ?>,
                    borderColor: '#343a40',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Casos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Ejecutivos'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return `Casos: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de estados
    const ctxEstados = document.getElementById('graficoEstados');
    if (ctxEstados) {
        new Chart(ctxEstados.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['En Curso', 'En Espera', 'Resueltos', 'Cancelados'],
                datasets: [{
                    data: [
                        <?php echo $distribucion_estados['en_curso']; ?>,
                        <?php echo $distribucion_estados['en_espera']; ?>,
                        <?php echo $distribucion_estados['resuelto']; ?>,
                        <?php echo $distribucion_estados['cancelado']; ?>
                    ],
                    backgroundColor: ['#007bff', '#ffc107', '#28a745', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}

// =============================================
// INICIALIZACIÓN Y EVENTOS
// =============================================

$(document).ready(function() {
    console.log('🚀 Panel de asignaciones inicializado');
    console.log('📊 Datos cargados:', {
        metricas: <?php echo count($metricas); ?> + ' ejecutivos',
        estadisticas: <?php echo json_encode($estadisticas_diarias); ?>,
        filtros: {
            desde: '<?php echo $fecha_desde; ?>',
            hasta: '<?php echo $fecha_hasta; ?>'
        }
    });

    // Inicializar gráficos
    inicializarGraficos();

    // Evento para aplicar filtros
    $('#btn-aplicar-filtros').on('click', aplicarFiltros);

    // Evento para limpiar filtros
    $('#btn-limpiar-filtros').on('click', limpiarFiltros);

    // Enter en campos de fecha aplica filtros
    $('#fecha_desde, #fecha_hasta').on('keypress', function(e) {
        if (e.which === 13) {
            aplicarFiltros();
        }
    });

    // Botón gestionar SR
    $('.btn-gestionar-sr').on('click', function() {
        const srHijo = $(this).data('sr-hijo');
        gestionarSR(srHijo);
    });
    
    // Botón ver log
    $('.btn-ver-log').on('click', function() {
        const srHijo = $(this).data('sr-hijo');
        verLogSR(srHijo);
    });

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
        
        mostrarMensaje('info', `Redirigiendo a gestión de horarios para ${userName}...`);
        window.location.href = `/dashboard/vsm/microapp/public/?vista=back-admision&action=gestionar-horarios&user_id=${userId}`;
    });

    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// =============================================
// FUNCIONES DE DIAGNÓSTICO Y DESARROLLO
// =============================================

// Diagnóstico completo del sistema
window.diagnosticoCompleto = function() {
    const currentUrl = window.location.href;
    const apiUrl = getApiUrl();
    const sessionInfo = {
        user_id: '<?php echo $_SESSION['user_id'] ?? 'none'; ?>',
        user_role: '<?php echo $_SESSION['user_role'] ?? 'none'; ?>',
        session_id: '<?php echo session_id(); ?>'
    };
    
    const diagnostico = `
🔍 DIAGNÓSTICO COMPLETO DEL SISTEMA

📍 URL ACTUAL:
${currentUrl}

🌐 ENDPOINT CONFIGURADO:
${apiUrl}

🔐 INFORMACIÓN DE SESIÓN:
${JSON.stringify(sessionInfo, null, 2)}

📊 DATOS CARGADOS:
- ${<?php echo count($metricas); ?>} ejecutivos en métricas
- ${<?php echo $estadisticas_diarias['total_casos_hoy'] ?? 0; ?>} casos hoy

🚀 ACCIONES:
1. probarEndpointActual() - Probar conexión API
2. verificarSesion() - Verificar estado de sesión
    `;
    
    console.log(diagnostico);
    alert(diagnostico);
};

// Probar el endpoint actual
window.probarEndpointActual = function() {
    const url = getApiUrl();
    console.log('🧪 Probando endpoint:', url);
    
    const testData = {
        test: true,
        user_id: 1,
        estado: 'activo',
        supervisor_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
        action: 'test_connection'
    };
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify(testData)
    })
    .then(response => {
        console.log('📨 Status:', response.status);
        console.log('🔗 URL final:', response.url);
        console.log('🔄 Redireccionado:', response.redirected);
        
        return response.text().then(text => {
            console.log('📄 Respuesta completa:', text);
            
            if (response.redirected) {
                alert(`❌ ENDPOINT REDIRIGE\n\nLa URL está redirigiendo a:\n${response.url}\n\nEsto indica problema de autenticación o ruta incorrecta.`);
            } else if (text.includes('<!DOCTYPE')) {
                alert(`❌ ENDPOINT DEVUELVE HTML\n\nEstá devolviendo HTML en lugar de JSON.\n\nPosible problema de ruta o sesión.`);
            } else {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert(`✅ ENDPOINT FUNCIONA\n\nStatus: ${response.status}\nMensaje: ${data.message || 'Conexión exitosa'}`);
                    } else {
                        alert(`⚠️ ENDPOINT RESPONDE CON ERROR\n\nStatus: ${response.status}\nError: ${data.message || 'Error desconocido'}`);
                    }
                } catch (e) {
                    alert(`❌ ERROR PARSEANDO RESPUESTA\n\nStatus: ${response.status}\nRespuesta: ${text.substring(0, 200)}`);
                }
            }
        });
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert(`❌ ERROR DE CONEXIÓN:\n${error.message}`);
    });
};

// Verificar estado de sesión
window.verificarSesion = function() {
    const sessionInfo = {
        user_id: '<?php echo $_SESSION['user_id'] ?? 'none'; ?>',
        user_role: '<?php echo $_SESSION['user_role'] ?? 'none'; ?>', 
        session_id: '<?php echo session_id(); ?>'
    };
    
    alert(`🔐 ESTADO DE SESIÓN:\n\n${JSON.stringify(sessionInfo, null, 2)}`);
};
</script>