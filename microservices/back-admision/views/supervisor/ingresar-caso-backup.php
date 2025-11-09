<?php
// microservices/back-admision/views/supervisor/ingresar-caso-backup.php
require_once __DIR__ . '/../../init.php';

$tipo = $_GET['tipo'] ?? 'ejecutivo';
$user_id = $backAdmision->getUserId();

// Cargar controladores
require_once __DIR__ . '/../../controllers/SupervisorController.php';
require_once __DIR__ . '/../../controllers/TeamController.php';

$supervisorController = new SupervisorController();
$teamController = new TeamController();

// Obtener ejecutivos disponibles
$ejecutivos = $teamController->getEjecutivosDisponibles();

$titulo = $tipo === 'ejecutivo' ? 'Ingresar Caso como Ejecutivo' : 'Asignación Manual';
$descripcion = $tipo === 'ejecutivo' 
    ? 'El caso será asignado automáticamente al ejecutivo con menor carga'
    : 'Selecciona manualmente el ejecutivo al que asignar el caso';
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
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-success">
                        <li class="breadcrumb-item"><a href="/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/public/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active"><?php echo $titulo; ?></li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-<?php echo $tipo === 'ejecutivo' ? 'primary' : 'success'; ?> text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-<?php echo $tipo === 'ejecutivo' ? 'user-plus' : 'user-shield'; ?> me-2"></i>
                            <?php echo $titulo; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Instrucciones</h6>
                            <p class="mb-0"><?php echo $descripcion; ?></p>
                        </div>

                        <form id="formIngresarCaso" method="post" action="/?vista=admision-api-ingresar-caso-supervisor">
                            <input type="hidden" name="tipo_asignacion" value="<?php echo $tipo; ?>">
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="sr_hijo" class="form-label">
                                        <strong>Número de SR Hijo *</strong>
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="sr_hijo" 
                                           name="sr_hijo" 
                                           placeholder="Ej: SR123456789"
                                           required
                                           maxlength="50">
                                    <div class="form-text">
                                        Ingresa el número completo de la SR hijo con la que se trabajará.
                                    </div>
                                </div>

                                <?php if ($tipo === 'supervisor'): ?>
                                <div class="col-12 mb-3">
                                    <label for="analista_id" class="form-label">
                                        <strong>Seleccionar Ejecutivo *</strong>
                                    </label>
                                    <select class="form-select form-select-lg" id="analista_id" name="analista_id" required>
                                        <option value="">Selecciona un ejecutivo...</option>
                                        <?php foreach ($ejecutivos as $ejecutivo): ?>
                                        <option value="<?php echo $ejecutivo['user_id']; ?>" 
                                                data-estado="<?php echo $ejecutivo['estado']; ?>"
                                                data-casos="<?php echo $ejecutivo['casos_activos']; ?>">
                                            <?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?> 
                                            (<?php echo $ejecutivo['casos_activos']; ?> casos) 
                                            - <?php echo ucfirst($ejecutivo['estado']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <span id="info-ejecutivo" class="text-muted"></span>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="forzar_asignacion" name="forzar_asignacion">
                                        <label class="form-check-label" for="forzar_asignacion">
                                            <strong>Forzar reasignación si la SR ya está asignada</strong>
                                        </label>
                                        <div class="form-text">
                                            Si la SR ya está asignada a otro ejecutivo, se reasignará al ejecutivo seleccionado.
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/?vista=back-admision" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Volver
                                </a>
                                <button type="submit" class="btn btn-<?php echo $tipo === 'ejecutivo' ? 'primary' : 'success'; ?> btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo $tipo === 'ejecutivo' ? 'Ingresar Caso' : 'Asignar Manualmente'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información de Ejecutivos Disponibles -->
                <?php if ($tipo === 'ejecutivo'): ?>
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Ejecutivos Disponibles para Asignación Automática</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Ejecutivo</th>
                                        <th>Estado</th>
                                        <th>Casos Activos</th>
                                        <th>Disponibilidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ejecutivos as $ejecutivo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ejecutivo['nombre_completo']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $ejecutivo['estado'] == 'activo' ? 'success' : 
                                                ($ejecutivo['estado'] == 'colacion' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($ejecutivo['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $ejecutivo['casos_activos']; ?></td>
                                        <td>
                                            <?php if ($ejecutivo['estado'] == 'activo' && $ejecutivo['casos_activos'] < 10): ?>
                                                <span class="badge bg-success">Alta</span>
                                            <?php elseif ($ejecutivo['estado'] == 'activo' && $ejecutivo['casos_activos'] < 20): ?>
                                                <span class="badge bg-warning">Media</span>
                                            <?php elseif ($ejecutivo['estado'] == 'activo'): ?>
                                                <span class="badge bg-danger">Baja</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        // Mostrar información del ejecutivo seleccionado
        $('#analista_id').change(function() {
            const selectedOption = $(this).find('option:selected');
            const estado = selectedOption.data('estado');
            const casos = selectedOption.data('casos');
            
            let infoText = '';
            if (estado === 'activo') {
                infoText = `✅ Ejecutivo activo con ${casos} casos asignados - Disponibilidad: ${casos < 10 ? 'Alta' : casos < 20 ? 'Media' : 'Baja'}`;
            } else if (estado === 'colacion') {
                infoText = `⚠️ Ejecutivo en colación - No recomendado para asignación inmediata`;
            } else {
                infoText = `❌ Ejecutivo inactivo - No disponible para asignación`;
            }
            
            $('#info-ejecutivo').text(infoText);
        });

        // Validación del formulario
        $('#formIngresarCaso').submit(function(e) {
            const sr_hijo = $('#sr_hijo').val().trim();
            if (!sr_hijo) {
                e.preventDefault();
                alert('Por favor, ingresa el número de SR hijo');
                return false;
            }
            
            <?php if ($tipo === 'supervisor'): ?>
            const analista_id = $('#analista_id').val();
            if (!analista_id) {
                e.preventDefault();
                alert('Por favor, selecciona un ejecutivo');
                return false;
            }
            
            const estado = $('#analista_id option:selected').data('estado');
            if (estado !== 'activo') {
                if (!confirm('⚠️ El ejecutivo seleccionado no está activo. ¿Deseas continuar con la asignación?')) {
                    e.preventDefault();
                    return false;
                }
            }
            <?php endif; ?>
        });
    });
    </script>
