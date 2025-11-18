<?php
// microservices/back-admision/views/supervisor/menu.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();
$user_nombre = $backAdmision->getUserName();
$user_role = $backAdmision->getUserRole();

// Cargar controladores necesarios
require_once __DIR__ . '/../../controllers/SupervisorController.php';
require_once __DIR__ . '/../../controllers/TeamController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

$supervisorController = new SupervisorController();
$teamController = new TeamController();
$reportController = new ReportController();

// Obtener estadísticas para el dashboard
$estadisticas = $supervisorController->getEstadisticasDashboard();
$ejecutivos_activos = $teamController->getEjecutivosActivos();
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
    </style>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <div aria-label="breadcrumb bg-success">
                    <ol class="breadcrumb bg-success">
                        <li class="breadcrumb-item"><a href="/public/index.php?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item">Back de Admisión</li>
                    </ol>
                </div>

                <!-- Alertas -->
                <?php if (isset($_SESSION['notificacion'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['notificacion']['tipo']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['notificacion']['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['notificacion']); ?>
                <?php endif; ?>

                <!-- Header del Dashboard -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Panel de Supervisor</h4>
                                <small class="opacity-75">Bienvenido, <?php echo htmlspecialchars($user_nombre); ?> (<?php echo htmlspecialchars($user_role); ?>)</small>
                            </div>
                            <div class="text-end">
                                <small class="opacity-75">Área: <?php echo htmlspecialchars($backAdmision->getUserArea()); ?></small>
                                <br>
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo count($ejecutivos_activos); ?> ejecutivo(s) activo(s)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de Estadísticas -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Casos Activos</h6>
                                        <h3 class="text-primary"><?php echo $estadisticas['casos_activos']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tasks fa-2x text-primary opacity-50"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Total de casos en curso</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Casos Resueltos</h6>
                                        <h3 class="text-success"><?php echo $estadisticas['casos_resueltos']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Hoy</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="card stat-card border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Promedio por Ejecutivo</h6>
                                        <h3 class="text-warning"><?php echo $estadisticas['promedio_por_ejecutivo']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-bar fa-2x text-warning opacity-50"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Casos por ejecutivo</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="card stat-card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Ejecutivos Activos</h6>
                                        <h3 class="text-info"><?php echo $estadisticas['ejecutivos_activos']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-check fa-2x text-info opacity-50"></i>
                                    </div>
                                </div>
                                <small class="text-muted">En turno actual</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menú de Opciones de Supervisor -->
                <!-- Menú de Opciones de Supervisor -->
<div class="row">
    <!-- Ingresar Caso como Ejecutivo -->
    <!-- Menú de Opciones de Supervisor -->
<div class="row">
    <!-- Ingresar Caso como Ejecutivo -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-file-signature fa-3x text-primary"></i><!-- fa-user-plus -->
                </div>
                <h5 class="card-title">Ingresar SR</h5>
                <p class="card-text text-muted">
                    Ingresa un caso que será asignado automáticamente al ejecutivo con menor carga
                </p>
                <a href="./?vista=back-admision&action=ingresar-caso&tipo=ejecutivo" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Ingresar Caso
                </a>
            </div>
        </div>
    </div>

    <!-- Ingresar Caso como Backup/Supervisor -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-clipboard-list fa-3x text-success"></i>
                </div>
                <h5 class="card-title">Asignación Manual</h5>
                <p class="card-text text-muted">
                    Asigna manualmente casos a ejecutivos específicos con opción de reasignación
                </p>
                <a href="./?vista=back-admision&action=ingresar-caso&tipo=supervisor" class="btn btn-success">
                    <i class="fas fa-hand-pointer me-2"></i>Asignar Manualmente
                </a>
            </div>
        </div>
    </div>

    <!-- Gestionar Equipos -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-users-cog fa-3x text-warning"></i>
                </div>
                <h5 class="card-title">Gestionar Equipos</h5>
                <p class="card-text text-muted">
                    Administra horarios, estados y configuración del equipo de ejecutivos
                </p>
                <a href="./?vista=back-admision&action=gestionar-equipos" class="btn btn-warning">
                    <i class="fas fa-cog me-2"></i>Gestionar Equipos
                </a>
            </div>
        </div>
    </div>

    <!-- Ver Registros -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-file-alt fa-3x text-info"></i>
                </div>
                <h5 class="card-title">Ver Registros</h5>
                <p class="card-text text-muted">
                    Consulta logs del sistema y descarga planillas Excel con todos los registros
                </p>
                <a href="./?vista=back-admision&action=ver-registros" class="btn btn-info">
                    <i class="fas fa-download me-2"></i>Ver Registros
                </a>
            </div>
        </div>
    </div>

    <!-- Panel de Asignaciones -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-chart-pie fa-3x text-danger"></i>
                </div>
                <h5 class="card-title">Panel de Asignaciones</h5>
                <p class="card-text text-muted">
                    Visualiza el balance de carga entre ejecutivos y métricas de asignación
                </p>
                <a href="./?vista=back-admision&action=panel-asignaciones" class="btn btn-danger">
                    <i class="fas fa-chart-bar me-2"></i>Ver Panel
                </a>
            </div>
        </div>
    </div>


    <!-- Reasignación Rápida -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm card-hover">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-sync-alt fa-3x text-secondary"></i>
                </div>
                <h5 class="card-title">Reasignación Rápida</h5>
                <p class="card-text text-muted">
                    Reasigna casos entre ejecutivos de manera manual y controlada
                </p>
                <a href="./?vista=admision-reasignacion-rapida" class="btn btn-secondary">
                    <i class="fas fa-exchange-alt me-2"></i>Reasignar Casos
                </a>
            </div>
        </div>
    </div>
</div>

                <!-- Lista Rápida de Ejecutivos Activos -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>Ejecutivos Activos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($ejecutivos_activos as $ejecutivo): ?>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="user-status status-<?php echo $ejecutivo['estado']; ?>"></span>
                                                    <div class="ms-2">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo $ejecutivo['casos_activos']; ?> caso(s) |
                                                            <span class="text-<?php 
                                                                echo $ejecutivo['estado'] == 'activo' ? 'success' : 
                                                                ($ejecutivo['estado'] == 'colacion' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($ejecutivo['estado']); ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  