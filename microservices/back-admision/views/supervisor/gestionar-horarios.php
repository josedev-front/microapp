<?php
// microservices/back-admision/views/supervisor/gestionar-horarios.php
require_once __DIR__ . '/../../init.php';

// Verificar permisos de supervisor
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-ban me-2"></i>Acceso Denegado</h4>
            <p>No tienes permisos para gestionar horarios.</p>
            <p><strong>Tu rol actual:</strong> ' . htmlspecialchars($user_role) . '</p>
        </div>
    </div>';
    exit;
}

// Obtener parámetros
$user_id = $_GET['user_id'] ?? 0;
$user_name = $_GET['user_name'] ?? '';

// Cargar controladores
require_once __DIR__ . '/../../controllers/TeamController.php';
require_once __DIR__ . '/../../controllers/AdmissionController.php';

$teamController = new TeamController();
$admissionController = new AdmissionController();

// Obtener información del usuario
$usuario_info = $teamController->getUsuarioById($user_id);
if (!$usuario_info) {
    echo '
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Usuario No Encontrado</h4>
            <p>El usuario especificado no existe o no tiene permisos.</p>
            <a href="/dashboard/vsm/microapp/public/?vista=back-admision&action=panel-asignaciones" class="btn btn-primary mt-3">
                <i class="fas fa-arrow-left me-2"></i>Volver al Panel
            </a>
        </div>
    </div>';
    exit;
}

// Obtener horarios actuales del usuario
$horarios_actuales = $teamController->getHorariosUsuario($user_id);

// Si no hay horarios, crear estructura vacía
$dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
$horarios_por_defecto = [];
foreach ($dias_semana as $dia) {
    $horarios_por_defecto[$dia] = [
        'hora_entrada' => '09:00',
        'hora_salida' => '18:00',
        'hora_almuerzo_inicio' => '13:00',
        'hora_almuerzo_fin' => '14:00',
        'activo' => $dia != 'sabado' && $dia != 'domingo'
    ];
}

// Combinar con horarios existentes
foreach ($horarios_actuales as $horario) {
    if (isset($horarios_por_defecto[$horario['dia_semana']])) {
        $horarios_por_defecto[$horario['dia_semana']] = array_merge($horarios_por_defecto[$horario['dia_semana']], $horario);
    }
}

$horarios = $horarios_por_defecto;
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
.card-horario {
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
}
.card-horario:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.dia-inactivo {
    opacity: 0.6;
    background-color: #f8f9fa;
}
.hora-input {
    max-width: 120px;
}
.btn-dia-toggle {
    cursor: pointer;
}
.badge-dia {
    font-size: 0.9em;
    padding: 0.5em 0.8em;
}
.export-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}
.template-preview {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
}
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-info">
                    <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision">Back de Admisión</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/vsm/microapp/public/?vista=back-admision&action=panel-asignaciones">Panel de Asignaciones</a></li>
                    <li class="breadcrumb-item active text-white">Gestión de Horarios</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Gestión de Horarios</h4>
                            <p class="mb-0 mt-1 small opacity-75">
                                Configuración de horarios para: <strong><?php echo htmlspecialchars($usuario_info['nombre_completo']); ?></strong>
                                (ID: <?php echo $user_id; ?> - Área: <?php echo htmlspecialchars($usuario_info['work_area']); ?>)
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark fs-6"><?php echo date('d/m/Y'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Alertas de configuración automática -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-robot me-2"></i>Configuración Automática</h5>
                        <p class="mb-2">El sistema automáticamente:</p>
                        <ul class="mb-0">
                            <li>Cambia a estado <span class="badge bg-warning">Colación</span> en la hora de almuerzo configurada</li>
                            <li>Vuelve a <span class="badge bg-success">Activo</span> al terminar el almuerzo</li>
                            <li>Cambia a <span class="badge bg-danger">Inactivo</span> al finalizar la jornada</li>
                            <li>No permite ingreso de casos 10 min antes de colación y fin de turno</li>
                            <li>Reasigna casos automáticamente en colación y fin de turno</li>
                        </ul>
                    </div>

                    <!-- Formulario de Horarios -->
                    <form id="formHorarios" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($usuario_info['nombre_completo']); ?>">
                        
                        <div class="row mb-4">
                            <?php foreach ($horarios as $dia => $config): 
                                $activo = $config['activo'] ?? true;
                                $es_fin_semana = in_array($dia, ['sabado', 'domingo']);
                            ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="card card-horario <?php echo !$activo ? 'dia-inactivo' : ''; ?>">
                                    <div class="card-header bg-<?php echo $activo ? 'primary' : 'secondary'; ?> text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-<?php echo $es_fin_semana ? 'umbrella-beach' : 'briefcase'; ?> me-2"></i>
                                            <?php echo ucfirst($dia); ?>
                                        </h6>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input btn-dia-toggle" type="checkbox" 
                                                   id="activo_<?php echo $dia; ?>" 
                                                   name="horarios[<?php echo $dia; ?>][activo]" 
                                                   value="1" 
                                                   <?php echo $activo ? 'checked' : ''; ?>
                                                   data-dia="<?php echo $dia; ?>">
                                            <label class="form-check-label text-white" for="activo_<?php echo $dia; ?>">
                                                <?php echo $activo ? 'Activo' : 'Inactivo'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Horario Entrada/Salida -->
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <label class="form-label small text-muted">Entrada</label>
                                                <input type="time" class="form-control form-control-sm hora-input" 
                                                       name="horarios[<?php echo $dia; ?>][hora_entrada]" 
                                                       value="<?php echo htmlspecialchars($config['hora_entrada']); ?>"
                                                       <?php echo !$activo ? 'disabled' : ''; ?>>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small text-muted">Salida</label>
                                                <input type="time" class="form-control form-control-sm hora-input" 
                                                       name="horarios[<?php echo $dia; ?>][hora_salida]" 
                                                       value="<?php echo htmlspecialchars($config['hora_salida']); ?>"
                                                       <?php echo !$activo ? 'disabled' : ''; ?>>
                                            </div>
                                        </div>
                                        
                                        <!-- Horario Almuerzo -->
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label small text-muted">Inicio Colación</label>
                                                <input type="time" class="form-control form-control-sm hora-input" 
                                                       name="horarios[<?php echo $dia; ?>][hora_almuerzo_inicio]" 
                                                       value="<?php echo htmlspecialchars($config['hora_almuerzo_inicio']); ?>"
                                                       <?php echo !$activo ? 'disabled' : ''; ?>>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small text-muted">Fin Colación</label>
                                                <input type="time" class="form-control form-control-sm hora-input" 
                                                       name="horarios[<?php echo $dia; ?>][hora_almuerzo_fin]" 
                                                       value="<?php echo htmlspecialchars($config['hora_almuerzo_fin']); ?>"
                                                       <?php echo !$activo ? 'disabled' : ''; ?>>
                                            </div>
                                        </div>
                                        
                                        <!-- Duración calculada -->
                                        <?php if ($activo): 
                                            $entrada = strtotime($config['hora_entrada']);
                                            $salida = strtotime($config['hora_salida']);
                                            $almuerzo_inicio = strtotime($config['hora_almuerzo_inicio']);
                                            $almuerzo_fin = strtotime($config['hora_almuerzo_fin']);
                                            
                                            $horas_trabajo = ($almuerzo_inicio - $entrada + $salida - $almuerzo_fin) / 3600;
                                        ?>
                                        <div class="mt-2 text-center">
                                            <small class="text-muted">
                                                <i class="fas fa-business-time me-1"></i>
                                                Jornada: <strong><?php echo number_format($horas_trabajo, 1); ?>h</strong>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save me-2"></i>Guardar Horarios
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary ms-2" id="btnResetHorarios">
                                            <i class="fas fa-undo me-2"></i>Restablecer
                                        </button>
                                    </div>
                                    <a href="/dashboard/vsm/microapp/public/?vista=back-admision&action=panel-asignaciones" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Volver al Panel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Sección de Exportación/Importación -->
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="card export-section text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-file-excel me-2"></i>Importar/Exportar Horarios</h5>
                                    <p class="card-text">Puedes descargar una plantilla Excel, completarla y luego importarla para configurar los horarios rápidamente.</p>
                                    
                                    <div class="row mt-4">
                                        <div class="col-12 col-md-6">
                                            <div class="template-preview text-dark mb-3">
                                                <h6><i class="fas fa-download me-2"></i>Descargar Plantilla</h6>
                                                <p class="small">Descarga la plantilla Excel con el formato requerido para importar horarios.</p>
                                                <button type="button" class="btn btn-success w-100" id="btnDescargarPlantilla">
                                                    <i class="fas fa-file-excel me-2"></i>Descargar Plantilla Excel
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="template-preview text-dark">
                                                <h6><i class="fas fa-upload me-2"></i>Importar Horarios</h6>
                                                <p class="small">Selecciona un archivo Excel con los horarios para importarlos automáticamente.</p>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="fileImportar" accept=".xlsx,.xls">
                                                    <button class="btn btn-warning" type="button" id="btnImportar">
                                                        <i class="fas fa-upload me-2"></i>Importar
                                                    </button>
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

<!-- Modal de confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Confirmación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="mensajeConfirmacion">Los horarios se han guardado correctamente.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle día activo/inactivo
    $('.btn-dia-toggle').change(function() {
        const dia = $(this).data('dia');
        const activo = $(this).is(':checked');
        const card = $(this).closest('.card');
        const inputs = card.find('input[type="time"]');
        
        if (activo) {
            card.removeClass('dia-inactivo');
            card.find('.card-header').removeClass('bg-secondary').addClass('bg-primary');
            inputs.prop('disabled', false);
        } else {
            card.addClass('dia-inactivo');
            card.find('.card-header').removeClass('bg-primary').addClass('bg-secondary');
            inputs.prop('disabled', true);
        }
    });

    // Validación de horarios
    function validarHorarios() {
        let valido = true;
        const mensajes = [];
        
        $('.card-horario').each(function() {
            const activo = $(this).find('.btn-dia-toggle').is(':checked');
            if (!activo) return;
            
            const dia = $(this).find('.btn-dia-toggle').data('dia');
            const entrada = $(this).find('input[name*="[hora_entrada]"]').val();
            const salida = $(this).find('input[name*="[hora_salida]"]').val();
            const almuerzoInicio = $(this).find('input[name*="[hora_almuerzo_inicio]"]').val();
            const almuerzoFin = $(this).find('input[name*="[hora_almuerzo_fin]"]').val();
            
            if (!entrada || !salida) {
                mensajes.push(`El ${dia} debe tener horario de entrada y salida`);
                valido = false;
                return;
            }
            
            if (entrada >= salida) {
                mensajes.push(`En ${dia}, la hora de entrada debe ser anterior a la salida`);
                valido = false;
            }
            
            if (almuerzoInicio && almuerzoFin) {
                if (almuerzoInicio >= almuerzoFin) {
                    mensajes.push(`En ${dia}, el inicio de colación debe ser anterior al fin`);
                    valido = false;
                }
                
                if (almuerzoInicio <= entrada || almuerzoFin >= salida) {
                    mensajes.push(`En ${dia}, el horario de colación debe estar dentro de la jornada`);
                    valido = false;
                }
            }
        });
        
        if (!valido) {
            alert('Errores en la configuración:\n\n' + mensajes.join('\n'));
        }
        
        return valido;
    }

    // Envío del formulario
    $('#formHorarios').on('submit', function(e) {
        e.preventDefault();
        
        if (!validarHorarios()) {
            return;
        }
        
        const formData = new FormData(this);
        
        // Mostrar loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        submitBtn.prop('disabled', true);
        
        fetch('/dashboard/vsm/microapp/microservices/back-admision/api/guardar_horarios.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#mensajeConfirmacion').text(data.message || 'Horarios guardados correctamente');
                $('#modalConfirmacion').modal('show');
            } else {
                throw new Error(data.message || 'Error al guardar horarios');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
    });

    // Restablecer horarios
    $('#btnResetHorarios').click(function() {
        if (confirm('¿Estás seguro de que quieres restablecer todos los horarios a los valores por defecto?')) {
            location.reload();
        }
    });

    // Descargar plantilla Excel
    // Importar desde Excel - ACTUALIZADO
$('#btnImportar').click(function() {
    const fileInput = $('#fileImportar')[0];
    if (!fileInput.files.length) {
        alert('Por favor selecciona un archivo Excel');
        return;
    }
    
    const formData = new FormData();
    formData.append('archivo', fileInput.files[0]);
    formData.append('user_id', <?php echo $user_id; ?>);
    
    // Mostrar loading
    const importBtn = $(this);
    const originalText = importBtn.html();
    importBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Importando...');
    importBtn.prop('disabled', true);
    
    // Ruta actualizada
    fetch('/dashboard/vsm/microapp/public/?vista=back-admision&action=importar-horarios-excel', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Horarios importados correctamente');
            location.reload();
        } else {
            throw new Error(data.message || 'Error al importar horarios');
        }
    })
    .catch(error => {
        alert('Error al importar: ' + error.message);
    })
    .finally(() => {
        importBtn.html(originalText);
        importBtn.prop('disabled', false);
        fileInput.value = '';
    });
});

// Envío del formulario - ACTUALIZADO
$('#formHorarios').on('submit', function(e) {
    e.preventDefault();
    
    if (!validarHorarios()) {
        return;
    }
    
    const formData = new FormData(this);
    
    // Mostrar loading
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
    submitBtn.prop('disabled', true);
    
    // Ruta actualizada
    fetch('/dashboard/vsm/microapp/public/?vista=back-admision&action=guardar-horarios', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#mensajeConfirmacion').text(data.message || 'Horarios guardados correctamente');
            $('#modalConfirmacion').modal('show');
        } else {
            throw new Error(data.message || 'Error al guardar horarios');
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.html(originalText);
        submitBtn.prop('disabled', false);
    });
});

    // Validación en tiempo real
    $('input[type="time"]').on('change', function() {
        const row = $(this).closest('.card-body');
        const entrada = row.find('input[name*="[hora_entrada]"]').val();
        const salida = row.find('input[name*="[hora_salida]"]').val();
        const almuerzoInicio = row.find('input[name*="[hora_almuerzo_inicio]"]').val();
        const almuerzoFin = row.find('input[name*="[hora_almuerzo_fin]"]').val();
        
        if (entrada && salida && entrada >= salida) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>