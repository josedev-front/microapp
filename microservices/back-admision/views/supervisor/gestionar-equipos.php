<?php
// microservices/back-admision/views/supervisor/gestionar-equipos.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();

// Cargar controladores
require_once __DIR__ . '/../../controllers/TeamController.php';
$teamController = new TeamController();

// Obtener datos
$ejecutivos = $teamController->getEjecutivosActivos();
$metricas = $teamController->getMetricasBalance();

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
        $max_casos = max(array_column($metricas, 'casos_activos'));
        return $max_casos > 0 ? round(($casos / $max_casos) * 100) : 0;
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Equipos - Back de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
                        <li class="breadcrumb-item active">Gestionar Equipos</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-users-cog me-2"></i>Gestión de Equipos</h4>
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
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">Avatar</th>
                                                <th width="25%">Ejecutivo</th>
                                                <th width="15%">Estado</th>
                                                <th width="15%">Casos Activos</th>
                                                <th width="20%">Última Actualización</th>
                                                <th width="20%" class="text-center">Acciones</th>
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
                                                    <select class="form-select form-select-sm estado-usuario" 
                                                            data-user="<?php echo $ejecutivo['user_id']; ?>"
                                                            style="width: auto; display: inline-block;">
                                                        <option value="activo" <?php echo $ejecutivo['estado'] == 'activo' ? 'selected' : ''; ?>>
                                                            🟢 Activo
                                                        </option>
                                                        <option value="colacion" <?php echo $ejecutivo['estado'] == 'colacion' ? 'selected' : ''; ?>>
                                                            🟡 Colación
                                                        </option>
                                                        <option value="inactivo" <?php echo $ejecutivo['estado'] == 'inactivo' ? 'selected' : ''; ?>>
                                                            🔴 Inactivo
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $ejecutivo['casos_activos'] == 0 ? 'success' : 
                                                        ($ejecutivo['casos_activos'] < 5 ? 'info' : 
                                                        ($ejecutivo['casos_activos'] < 10 ? 'warning' : 'danger')); 
                                                    ?> casos-badge">
                                                        <?php echo $ejecutivo['casos_activos']; ?> casos
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo $ejecutivo['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($ejecutivo['ultima_actualizacion'])) : 'Nunca'; ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary gestionar-horarios" 
                                                            data-user="<?php echo $ejecutivo['user_id']; ?>"
                                                            data-nombre="<?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?>">
                                                        <i class="fas fa-clock me-1"></i>Horarios
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info ver-detalles" 
                                                            data-user="<?php echo $ejecutivo['user_id']; ?>">
                                                        <i class="fas fa-chart-bar me-1"></i>Métricas
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pestaña 2: Panel de Asignaciones -->
                            <div class="tab-pane fade" id="panel-asignaciones" role="tabpanel">
                                <div class="row">
                                    <div class="col-12 col-lg-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Distribución de Carga</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($metricas as $metrica): 
                                                    $porcentaje = $viewHelper->calcularPorcentajeCarga($metrica['casos_activos'], $metricas);
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
                                                            <?php echo $metrica['casos_activos']; ?> casos
                                                            (<?php echo $porcentaje; ?>%)
                                                        </span>
                                                    </div>
                                                    <div class="progress progress-thin">
                                                        <div class="progress-bar bg-<?php 
                                                            echo $metrica['casos_activos'] == 0 ? 'success' : 
                                                            ($metrica['casos_activos'] < 5 ? 'info' : 
                                                            ($metrica['casos_activos'] < 10 ? 'warning' : 'danger')); 
                                                        ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo $porcentaje; ?>%"
                                                        aria-valuenow="<?php echo $porcentaje; ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Resumen</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $total_casos = array_sum(array_column($metricas, 'casos_activos'));
                                                $ejecutivos_activos = count(array_filter($metricas, function($m) { 
                                                    return $m['estado'] == 'activo'; 
                                                }));
                                                $promedio = $ejecutivos_activos > 0 ? round($total_casos / $ejecutivos_activos, 1) : 0;
                                                $balance_equipo = $viewHelper->evaluarBalance($metricas);
                                                ?>
                                                <div class="mb-3">
                                                    <strong>Total de Casos Activos:</strong>
                                                    <span class="float-end badge bg-primary"><?php echo $total_casos; ?></span>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Ejecutivos Activos:</strong>
                                                    <span class="float-end badge bg-success"><?php echo $ejecutivos_activos; ?></span>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Promedio por Ejecutivo:</strong>
                                                    <span class="float-end badge bg-info"><?php echo $promedio; ?></span>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Balance del Equipo:</strong>
                                                    <span class="float-end badge bg-<?php echo $balance_equipo ? 'success' : 'warning'; ?>">
                                                        <?php echo $balance_equipo ? '✅ Balanceado' : '⚠️ Desbalanceado'; ?>
                                                    </span>
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
        // Cambiar estado de usuario
        $('.estado-usuario').change(function() {
            const user_id = $(this).data('user');
            const nuevo_estado = $(this).val();
            
            $.post('/?vista=admision-api-cambiar-estado-usuario', {
                user_id: user_id,
                estado: nuevo_estado
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    location.reload();
                }
            }, 'json');
        });

        // Cargar modal de horarios
        $('.gestionar-horarios').click(function() {
            const user_id = $(this).data('user');
            const nombre = $(this).data('nombre');
            
            $('#modalHorariosLabel').html('<i class="fas fa-clock me-2"></i>Horarios - ' + nombre);
            
            $.get('/?vista=admision-api-get-horarios&user_id=' + user_id, function(data) {
                $('#contenidoHorarios').html(data);
                $('#modalHorarios').modal('show');
            });
        });

        // Ver métricas de usuario
        $('.ver-detalles').click(function() {
            const user_id = $(this).data('user');
            alert('Métricas detalladas del usuario ' + user_id + ' se implementarán próximamente.');
        });
    });
    </script>
</body>
</html>