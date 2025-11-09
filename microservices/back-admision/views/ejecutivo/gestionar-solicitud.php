<?php
// microservices/back-admision/views/ejecutivo/gestionar-solicitud.php
require_once __DIR__ . '/../../init.php';

$user_id = $backAdmision->getUserId();
$user_nombre = $backAdmision->getUserName();

// Obtener SR a gestionar
$sr_hijo = $_GET['sr'] ?? ($_SESSION['sr_a_gestionar'] ?? '');

if (empty($sr_hijo)) {
    header('Location: /?vista=back-admision');
    exit;
}

// Cargar controlador y obtener datos del caso
require_once __DIR__ . '/../../controllers/AdmissionController.php';
$admissionController = new AdmissionController();
$caso = $admissionController->getCasoPorSR($sr_hijo);

if (!$caso) {
    $_SESSION['notificacion'] = [
        'tipo' => 'error',
        'mensaje' => 'Caso no encontrado'
    ];
    header('Location: /?vista=back-admision');
    exit;
}

// Verificar permisos
if ($caso['analista_id'] != $user_id && !$admissionController->tienePermisosSupervisor()) {
    $_SESSION['notificacion'] = [
        'tipo' => 'error', 
        'mensaje' => 'No tiene permisos para gestionar este caso'
    ];
    header('Location: /?vista=back-admision');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Solicitud - Back de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active">Gestionar Solicitud</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Gestionar Solicitud - <?php echo htmlspecialchars($sr_hijo); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['notificacion'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['notificacion']['tipo']; ?> alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['notificacion']['mensaje']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['notificacion']); ?>
                        <?php endif; ?>

                        <form method="post" action="/?vista=admision-api-gestionar-caso">
                            <input type="hidden" name="sr_hijo" value="<?php echo htmlspecialchars($sr_hijo); ?>">
                            
                            <div class="row">
                                <!-- Columna Izquierda -->
                                <div class="col-12 col-md-6">
                                    <!-- SR Hijo -->
                                    <div class="mb-3">
                                        <label for="sr_hijo" class="form-label"><strong>SR Hijo *</strong></label>
                                        <input type="text" class="form-control" id="sr_hijo" 
                                               value="<?php echo htmlspecialchars($caso['sr_hijo']); ?>" 
                                               readonly disabled>
                                        <div class="form-text">Número de SR principal (no editable)</div>
                                    </div>

                                    <!-- SRP -->
                                    <div class="mb-3">
                                        <label for="srp" class="form-label">SRP</label>
                                        <input type="text" class="form-control" id="srp" name="srp"
                                               value="<?php echo htmlspecialchars($caso['srp'] ?? ''); ?>"
                                               placeholder="Ingrese SRP si aplica">
                                        <div class="form-text">Número de SR padre o relacionado</div>
                                    </div>

                                    <!-- Estado -->
                                    <div class="mb-3">
                                        <label for="estado" class="form-label"><strong>Estado *</strong></label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="en_curso" <?php echo $caso['estado'] == 'en_curso' ? 'selected' : ''; ?>>En Curso</option>
                                            <option value="en_espera" <?php echo $caso['estado'] == 'en_espera' ? 'selected' : ''; ?>>En Espera</option>
                                            <option value="resuelto" <?php echo $caso['estado'] == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            <option value="cancelado" <?php echo $caso['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                    </div>

                                    <!-- Ticket -->
                                    <div class="mb-3">
                                        <label for="tiket" class="form-label">Ticket</label>
                                        <input type="text" class="form-control" id="tiket" name="tiket"
                                               value="<?php echo htmlspecialchars($caso['tiket'] ?? ''); ?>"
                                               placeholder="Número de ticket relacionado">
                                    </div>

                                    <!-- Motivo del Ticket -->
                                    <div class="mb-3">
                                        <label for="motivo_tiket" class="form-label">Motivo del Ticket</label>
                                        <textarea class="form-control" id="motivo_tiket" name="motivo_tiket" 
                                                  rows="3" placeholder="Descripción del motivo del ticket"><?php echo htmlspecialchars($caso['motivo_tiket'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Columna Derecha -->
                                <div class="col-12 col-md-6">
                                    <!-- Analista -->
                                    <div class="mb-3">
                                        <label for="analista" class="form-label"><strong>Analista</strong></label>
                                        <input type="text" class="form-control" id="analista" 
                                               value="<?php echo htmlspecialchars($caso['analista_nombre']); ?>" 
                                               readonly disabled>
                                        <div class="form-text">Ejecutivo asignado al caso</div>
                                    </div>

                                    <!-- Tipo de Negocio -->
                                    <div class="mb-3">
                                        <label for="tipo_negocio" class="form-label">Tipo de Negocio</label>
                                        <input type="text" class="form-control" id="tipo_negocio" 
                                               value="Solicitud de revisión por backoffice" 
                                               readonly disabled>
                                        <input type="hidden" name="tipo_negocio" value="Solicitud de revisión por backoffice">
                                    </div>

                                    <!-- Biometría -->
                                    <div class="mb-3">
                                        <label for="biometria" class="form-label">Biometría</label>
                                        <select class="form-select" id="biometria" name="biometria">
                                            <option value="">Seleccione...</option>
                                            <option value="si" <?php echo ($caso['biometria'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="no" <?php echo ($caso['biometria'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>

                                    <!-- Inicio de Actividades -->
                                    <div class="mb-3">
                                        <label for="inicio_actividades" class="form-label">Inicio de Actividades</label>
                                        <select class="form-select" id="inicio_actividades" name="inicio_actividades">
                                            <option value="">Seleccione...</option>
                                            <option value="si" <?php echo ($caso['inicio_actividades'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="no" <?php echo ($caso['inicio_actividades'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>

                                    <!-- Acreditación -->
                                    <div class="mb-3">
                                        <label for="acreditacion" class="form-label">Acreditación</label>
                                        <select class="form-select" id="acreditacion" name="acreditacion">
                                            <option value="">Seleccione...</option>
                                            <option value="cte" <?php echo ($caso['acreditacion'] ?? '') == 'cte' ? 'selected' : ''; ?>>CTE</option>
                                            <option value="factura_operador_donante" <?php echo ($caso['acreditacion'] ?? '') == 'factura_operador_donante' ? 'selected' : ''; ?>>Factura del operador donante</option>
                                            <option value="formulario_29" <?php echo ($caso['acreditacion'] ?? '') == 'formulario_29' ? 'selected' : ''; ?>>Formulario 29</option>
                                            <option value="formulario_22" <?php echo ($caso['acreditacion'] ?? '') == 'formulario_22' ? 'selected' : ''; ?>>Formulario 22</option>
                                            <option value="cartola_bancaria" <?php echo ($caso['acreditacion'] ?? '') == 'cartola_bancaria' ? 'selected' : ''; ?>>Cartola bancaria</option>
                                            <option value="cta_cte" <?php echo ($caso['acreditacion'] ?? '') == 'cta_cte' ? 'selected' : ''; ?>>Cuenta Corriente</option>
                                            <option value="reporte_legal_plutto" <?php echo ($caso['acreditacion'] ?? '') == 'reporte_legal_plutto' ? 'selected' : ''; ?>>Reporte legal de Plutto</option>
                                            <option value="no_aplica" <?php echo ($caso['acreditacion'] ?? '') == 'no_aplica' ? 'selected' : ''; ?>>No aplica</option>
                                            <option value="cliente_antiguo" <?php echo ($caso['acreditacion'] ?? '') == 'cliente_antiguo' ? 'selected' : ''; ?>>Cliente antiguo</option>
                                            <option value="cliente_agil" <?php echo ($caso['acreditacion'] ?? '') == 'cliente_agil' ? 'selected' : ''; ?>>Cliente ágil</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" 
                                                  rows="4" placeholder="Ingrese observaciones relevantes sobre el caso"><?php echo htmlspecialchars($caso['observaciones'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Caso -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-info-circle me-2"></i>Información del Caso</h6>
                                            <div class="row">
                                                <div class="col-12 col-md-4">
                                                    <small><strong>Fecha Ingreso:</strong> <?php echo date('d/m/Y H:i', strtotime($caso['fecha_ingreso'])); ?></small>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <small><strong>Última Actualización:</strong> <?php echo date('d/m/Y H:i', strtotime($caso['fecha_actualizacion'])); ?></small>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <small><strong>Área:</strong> <?php echo htmlspecialchars($caso['area_ejecutivo']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="row mt-4">
                                <div class="col-12 d-flex justify-content-between">
                                    <a href="/?vista=back-admision" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Volver a la Bandeja
                                    </a>
                                    <div>
                                        <button type="submit" name="accion" value="guardar" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                        <button type="submit" name="accion" value="guardar_cerrar" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Guardar y Cerrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../../templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const estadoSelect = document.getElementById('estado');
        
        // Validación antes de enviar
        form.addEventListener('submit', function(e) {
            const sr_hijo = document.getElementById('sr_hijo').value;
            if (!sr_hijo.trim()) {
                e.preventDefault();
                alert('El número de SR hijo es requerido');
                return false;
            }
            
            // Si se marca como resuelto, confirmar
            if (estadoSelect.value === 'resuelto') {
                if (!confirm('¿Está seguro de marcar este caso como RESUELTO? El caso desaparecerá de su bandeja.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Mostrar/ocultar campos según estado
        estadoSelect.addEventListener('change', function() {
            if (this.value === 'resuelto') {
                // Podríamos mostrar campos adicionales para cierre
                console.log('Caso marcado como resuelto');
            }
        });
    });
    </script>
</body>
</html>