<?php
// microservices/back-admision/views/supervisor/panel-asignaciones.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();

// Cargar controladores
require_once __DIR__ . '/../../controllers/TeamController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

$teamController = new TeamController();
$reportController = new ReportController();

// Obtener datos para el panel
$metricas = $teamController->getMetricasBalance();
$estadisticas_diarias = $reportController->getEstadisticasDiarias();
$distribucion_estados = $reportController->getDistribucionEstados();

// Calcular métricas para gráficos
$labels = [];
$datos_casos = [];
$colores = [];

foreach ($metricas as $metrica) {
    $labels[] = $metrica['nombre_completo'];
    $datos_casos[] = $metrica['casos_activos'];
    
    // Asignar colores según la carga
    if ($metrica['casos_activos'] == 0) {
        $colores[] = '#28a745'; // Verde
    } elseif ($metrica['casos_activos'] < 5) {
        $colores[] = '#17a2b8'; // Azul
    } elseif ($metrica['casos_activos'] < 10) {
        $colores[] = '#ffc107'; // Amarillo
    } else {
        $colores[] = '#dc3545'; // Rojo
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Asignaciones - Back de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .progress-thin {
            height: 8px;
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
                        <li class="breadcrumb-item active">Panel de Asignaciones</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Panel de Asignaciones</h4>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" 
                                       value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="filtro_area" class="form-label">Área</label>
                                <select class="form-select" id="filtro_area">
                                    <option value="all">Todas las áreas</option>
                                    <option value="Depto Micro&SOHO" selected>Micro&SOHO</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="btn-aplicar-filtros">
                                    <i class="fas fa-filter me-2"></i>Aplicar Filtros
                                </button>
                            </div>
                        </div>

                        <!-- Tarjetas de Resumen -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-tasks fa-2x text-primary mb-2"></i>
                                        <h3 class="text-primary"><?php echo $estadisticas_diarias['total_casos']; ?></h3>
                                        <p class="text-muted mb-0">Total Casos</p>
                                        <small class="text-muted">Activos en el sistema</small>
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
                                        <small class="text-muted">Casos por persona</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="card stat-card border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-balance-scale fa-2x text-info mb-2"></i>
                                        <h3 class="text-info"><?php echo $estadisticas_diarias['indice_balance']; ?>%</h3>
                                        <p class="text-muted mb-0">Índice Balance</p>
                                        <small class="text-muted">Eficiencia de distribución</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráficos -->
                        <div class="row">
                            <!-- Gráfico de Distribución de Carga -->
                            <div class="col-12 col-lg-8 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Distribución de Carga por Ejecutivo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="graficoDistribucion"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Estados -->
                            <div class="col-12 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribución por Estado</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="graficoEstados"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla Detallada -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detalle por Ejecutivo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Ejecutivo</th>
                                                        <th>Estado</th>
                                                        <th>Casos Activos</th>
                                                        <th>Casos Resueltos</th>
                                                        <th>Casos En Espera</th>
                                                        <th>Eficiencia</th>
                                                        <th>Última Actividad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($metricas as $metrica): 
                                                        $eficiencia = $metrica['casos_activos'] > 0 ? 
                                                            min(100, round(($metrica['casos_resueltos'] / $metrica['casos_activos']) * 100)) : 100;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($metrica['nombre_completo']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($metrica['area']); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $metrica['estado'] == 'activo' ? 'success' : 
                                                                ($metrica['estado'] == 'colacion' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($metrica['estado']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $metrica['casos_activos'] == 0 ? 'success' : 
                                                                ($metrica['casos_activos'] < 5 ? 'info' : 
                                                                ($metrica['casos_activos'] < 10 ? 'warning' : 'danger')); 
                                                            ?>">
                                                                <?php echo $metrica['casos_activos']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo $metrica['casos_resueltos'] ?? 0; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning"><?php echo $metrica['casos_espera'] ?? 0; ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress progress-thin flex-grow-1 me-2">
                                                                    <div class="progress-bar bg-<?php 
                                                                        echo $eficiencia >= 80 ? 'success' : 
                                                                        ($eficiencia >= 60 ? 'warning' : 'danger'); 
                                                                    ?>" 
                                                                    role="progressbar" 
                                                                    style="width: <?php echo $eficiencia; ?>%"
                                                                    aria-valuenow="<?php echo $eficiencia; ?>" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted"><?php echo $eficiencia; ?>%</small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <small><?php echo $metrica['ultima_actualizacion'] ? 
                                                                date('d/m/Y H:i', strtotime($metrica['ultima_actualizacion'])) : 'Nunca'; ?>
                                                            </small>
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
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Métricas de Balance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['desviacion_estandar'] <= 3 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['desviacion_estandar'], 2); ?>
                                                </h3>
                                                <p class="text-muted mb-0">Desviación Estándar</p>
                                                <small class="text-muted">Menor es mejor</small>
                                            </div>
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['coeficiente_variacion'] <= 50 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['coeficiente_variacion'], 1); ?>%
                                                </h3>
                                                <p class="text-muted mb-0">Coef. Variación</p>
                                                <small class="text-muted">≤ 50% ideal</small>
                                            </div>
                                            <div class="col-12 col-md-4 text-center">
                                                <h3 class="text-<?php echo $estadisticas_diarias['indice_gini'] <= 0.3 ? 'success' : 'warning'; ?>">
                                                    <?php echo number_format($estadisticas_diarias['indice_gini'], 3); ?>
                                                </h3>
                                                <p class="text-muted mb-0">Índice Gini</p>
                                                <small class="text-muted">≤ 0.3 balanceado</small>
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

    <?php include __DIR__ . '/../../../../templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Gráfico de Distribución de Carga
        const ctxDistribucion = document.getElementById('graficoDistribucion').getContext('2d');
        const graficoDistribucion = new Chart(ctxDistribucion, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Casos Activos',
                    data: <?php echo json_encode($datos_casos); ?>,
                    backgroundColor: <?php echo json_encode($colores); ?>,
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
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Casos activos: ${context.parsed.y}`;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Estados
        const ctxEstados = document.getElementById('graficoEstados').getContext('2d');
        const graficoEstados = new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: ['En Curso', 'En Espera', 'Resueltos', 'Cancelados'],
                datasets: [{
                    data: [
                        <?php echo $distribucion_estados['en_curso']; ?>,
                        <?php echo $distribucion_estados['en_espera']; ?>,
                        <?php echo $distribucion_estados['resueltos']; ?>,
                        <?php echo $distribucion_estados['cancelados']; ?>
                    ],
                    backgroundColor: [
                        '#007bff',
                        '#ffc107', 
                        '#28a745',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Aplicar filtros
        $('#btn-aplicar-filtros').click(function() {
            const fecha_desde = $('#fecha_desde').val();
            const fecha_hasta = $('#fecha_hasta').val();
            const area = $('#filtro_area').val();
            
            // Aquí iría la lógica para actualizar los datos con los filtros
            alert('Filtros aplicados:\nDesde: ' + fecha_desde + '\nHasta: ' + fecha_hasta + '\nÁrea: ' + area);
            // En una implementación real, haríamos una petición AJAX para actualizar los datos
        });

        // Actualizar automáticamente cada 2 minutos
        setInterval(() => {
            console.log('Actualizando datos del panel...');
            // Aquí iría la lógica para actualizar los datos en tiempo real
        }, 120000);
    });
    </script>
</body>
</html>