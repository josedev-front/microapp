<?php
// microservices/back-admision/views/ejecutivo/menu.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();
$user_nombre = $backAdmision->getUserName();
$user_role = $backAdmision->getUserRole();

// Cargar controlador y modelos
require_once __DIR__ . '/../../controllers/AdmissionController.php';
$admissionController = new AdmissionController();
$casos = $admissionController->getBandejaEjecutivo($user_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back de Admisión - Micro&SOHO</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .estado-badge {
            font-size: 0.75em;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.025);
        }
    </style>
</head>
<body>
    <!-- Header de la App Madre -->
    <?php include __DIR__ . '/../../../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item active">Back de Admisión</li>
                    </ol>
                </nav>

                <!-- Alertas -->
                <?php if (isset($_SESSION['notificacion'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['notificacion']['tipo']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['notificacion']['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['notificacion']); ?>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-tasks me-2"></i>Back de Admisión</h4>
                                <small class="opacity-75">Bienvenido, <?php echo htmlspecialchars($user_nombre); ?> (<?php echo htmlspecialchars($user_role); ?>)</small>
                            </div>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-layer-group me-1"></i>
                                <?php echo count($casos); ?> caso(s) en bandeja
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Sección 1: Ingresar Caso -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card card-hover border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">
                                            <i class="fas fa-plus-circle me-2"></i>Ingresar Nuevo Caso
                                        </h5>
                                        <p class="card-text text-muted mb-3">
                                            Ingresa un número de SR para asignarlo al sistema de manera equilibrada
                                        </p>
                                        <a href="/?vista=admision-ingresar-caso" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus me-2"></i>Ingresar Caso
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Bandeja de Casos -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-inbox me-2"></i>Bandeja de Casos
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($casos)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No hay casos en tu bandeja</h5>
                                                <p class="text-muted">Los casos asignados aparecerán aquí</p>
                                                <a href="/?vista=admision-ingresar-caso" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Ingresar Primer Caso
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-striped">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="15%">SR Hijo</th>
                                                            <th width="20%">Estado</th>
                                                            <th width="20%">Fecha Ingreso</th>
                                                            <th width="20%">Última Actualización</th>
                                                            <th width="25%" class="text-center">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($casos as $caso): ?>
                                                        <tr>
                                                            <td>
                                                                <strong class="text-primary"><?php echo htmlspecialchars($caso['sr_hijo']); ?></strong>
                                                                <?php if ($caso['srp']): ?>
                                                                    <br><small class="text-muted">SRP: <?php echo htmlspecialchars($caso['srp']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm estado-caso" 
                                                                        data-sr="<?php echo htmlspecialchars($caso['sr_hijo']); ?>"
                                                                        style="width: auto; display: inline-block;">
                                                                    <option value="en_curso" <?php echo $caso['estado'] == 'en_curso' ? 'selected' : ''; ?>>
                                                                        🟡 En Curso
                                                                    </option>
                                                                    <option value="en_espera" <?php echo $caso['estado'] == 'en_espera' ? 'selected' : ''; ?>>
                                                                        🟠 En Espera
                                                                    </option>
                                                                    <option value="resuelto" <?php echo $caso['estado'] == 'resuelto' ? 'selected' : ''; ?>>
                                                                        ✅ Resuelto
                                                                    </option>
                                                                    <option value="cancelado" <?php echo $caso['estado'] == 'cancelado' ? 'selected' : ''; ?>>
                                                                        ❌ Cancelado
                                                                    </option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <small><?php echo date('d/m/Y H:i', strtotime($caso['fecha_ingreso'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo date('d/m/Y H:i', strtotime($caso['fecha_actualizacion'])); ?></small>
                                                            </td>
                                                            <td class="text-center">
                                                                <button class="btn btn-sm btn-outline-primary gestionar-caso" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#modalGestionar"
                                                                        data-sr="<?php echo htmlspecialchars($caso['sr_hijo']); ?>">
                                                                    <i class="fas fa-edit me-1"></i>Gestionar
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-info ver-detalles" 
                                                                        data-sr="<?php echo htmlspecialchars($caso['sr_hijo']); ?>">
                                                                    <i class="fas fa-eye me-1"></i>Ver
                                                                </button>
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

    <!-- Modal Gestionar Caso -->
    <div class="modal fade" id="modalGestionar" tabindex="-1" aria-labelledby="modalGestionarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalGestionarLabel">
                        <i class="fas fa-edit me-2"></i>Gestionar Caso
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formGestionarCaso">
                        <div id="contenidoModal">
                            <!-- El contenido se cargará via AJAX -->
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando información del caso...</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer de la App Madre -->
    <?php include __DIR__ . '/../../../../templates/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Cambiar estado del caso
        $('.estado-caso').change(function() {
            const sr_hijo = $(this).data('sr');
            const nuevo_estado = $(this).val();
            
            $.post('/?vista=admision-api-cambiar-estado', {
                sr_hijo: sr_hijo,
                estado: nuevo_estado
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        });

        // Cargar modal de gestión
        $('.gestionar-caso').click(function() {
            const sr_hijo = $(this).data('sr');
            
            $.get('/?vista=admision-api-get-casos&sr=' + sr_hijo, function(data) {
                $('#contenidoModal').html(data);
            });
        });

        // Ver detalles del caso
        $('.ver-detalles').click(function() {
            const sr_hijo = $(this).data('sr');
            alert('Detalles del caso: ' + sr_hijo + '\nEsta funcionalidad se implementará próximamente.');
        });
    });
    </script>
</body>
</html>