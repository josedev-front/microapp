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
            <div class="col-12 col-lg-10">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-success">
                        <li class="breadcrumb-item"><a href="/public/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/public/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item">Gestionar Solicitud</li>
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

                        <form method="post" action="/?vista=back-admision&action=gestionar-caso">
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
                                    <div class="mb-3"> <!-- obligatorio este campo -->
                                        <label for="srp" class="form-label">SRP</label>
                                        <input type="text" class="form-control" id="srp" name="srp"                   
                                               value="<?php echo htmlspecialchars($caso['srp'] ?? ''); ?>"
                                               placeholder="Ingrese SRP si aplica">
                                        <div class="form-text">Número de SR padre o relacionado</div>
                                    </div>

                                    <!-- Estado -->
                                    <div class="mb-3"> <!-- obligatorio este campo -->
                                        <label for="estado" class="form-label"><strong>Estado *</strong></label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="en_curso" <?php echo $caso['estado'] == 'en_curso' ? 'selected' : ''; ?>>En Curso</option>
                                            <option value="en_espera" <?php echo $caso['estado'] == 'en_espera' ? 'selected' : ''; ?>>En Espera</option>
                                            <option value="resuelto" <?php echo $caso['estado'] == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            <option value="cancelado" <?php echo $caso['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                    </div>

                                    <!-- Ticket -->
                                    <div class="mb-3"> <!-- opcional este campo -->
                                        <label for="tiket" class="form-label">Ticket</label>
                                        <input type="text" class="form-control" id="tiket" name="tiket"
                                               value="<?php echo htmlspecialchars($caso['tiket'] ?? ''); ?>"
                                               placeholder="Número de ticket relacionado">
                                    </div>

                                    <!-- Motivo del Ticket --> 
                                    <div class="mb-3"><!-- opcional este campo -->
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
                                    <div class="mb-3"> <!-- obligatorio este campo -->
                                        <label for="biometria" class="form-label">Biometría</label>
                                        <select class="form-select" id="biometria" name="biometria">
                                            <option value="">Seleccione...</option>
                                            <option value="si" <?php echo ($caso['biometria'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="no" <?php echo ($caso['biometria'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>

                                    <!-- Inicio de Actividades -->
                                    <div class="mb-3"> <!-- obligatorio este campo -->
                                        <label for="inicio_actividades" class="form-label">Inicio de Actividades</label>
                                        <select class="form-select" id="inicio_actividades" name="inicio_actividades">
                                            <option value="">Seleccione...</option>
                                            <option value="si" <?php echo ($caso['inicio_actividades'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="no" <?php echo ($caso['inicio_actividades'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>

                                    <!-- Acreditación -->
                                    <div class="mb-3"> <!-- obligatorio este campo -->
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
                            <div class="row"><!-- opcional este campo -->
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
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const estadoSelect = document.getElementById('estado');
    
    // ÚNICO event listener para submit
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log("=== INICIANDO ENVÍO FORMULARIO GESTIÓN ===");
        
        // 1. VALIDACIÓN ANTES DE ENVIAR
        const sr_hijo = document.getElementById('sr_hijo').value;
        if (!sr_hijo.trim()) {
            alert('El número de SR hijo es requerido');
            return false;
        }
        
        // Confirmar si se marca como resuelto
        if (estadoSelect.value === 'resuelto') {
            if (!confirm('¿Está seguro de marcar este caso como RESUELTO? El caso desaparecerá de su bandeja.')) {
                return false;
            }
        }
        
        // 2. MOSTRAR LOADING
        const submitBtns = this.querySelectorAll('button[type="submit"]');
        const originalTexts = Array.from(submitBtns).map(btn => btn.innerHTML);
        
        submitBtns.forEach(btn => {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            btn.disabled = true;
        });
        
        try {
            // 3. PREPARAR Y ENVIAR DATOS
            const formData = new FormData(this);
            const accion = document.querySelector('button[type="submit"]:focus')?.value || 'guardar';
            formData.set('accion', accion);
            
            // PROBAR DIFERENTES URLS - DEBUG
            const url = '/microservices/back-admision/api/gestionar_caso.php';

            console.log("📤 Enviando a URL:", url);
            console.log("📦 Datos:", Object.fromEntries(formData));
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            console.log("📥 Respuesta completa:", responseText.substring(0, 200) + "..."); // Solo primeros 200 chars
            
            // 4. DETECTAR SI LA RESPUESTA ES HTML (ERROR)
            if (responseText.trim().startsWith('<!DOCTYPE') || responseText.includes('<html')) {
                throw new Error('❌ El servidor devolvió HTML en lugar de JSON. La API no está funcionando.');
            }
            
            // 5. PROCESAR RESPUESTA JSON
            const data = JSON.parse(responseText);
            console.log("✅ Datos parseados:", data);
            
            if (data.success) {
                mostrarMensajeGestion(data.message, 'success');
                
                // Redirigir si hay URL (solo para guardar y cerrar)
                if (data.redirect && data.message.includes('Redirigiendo')) {
                    setTimeout(() => {
                        console.log("🔄 Redirigiendo a:", data.redirect);
                        window.location.href = data.redirect;
                    }, 2000);
                }
            } else {
                mostrarMensajeGestion(data.message, 'error');
            }
            
        } catch (error) {
            console.error('❌ Error en la solicitud:', error);
            
            if (error.message.includes('HTML en lugar de JSON')) {
                mostrarMensajeGestion(`
                    ❌ Error de configuración: 
                    La API no está respondiendo correctamente.
                    <br><br>
                    <strong>Posibles causas:</strong>
                    <br>• La ruta API no existe en routes.php
                    <br>• Hay un error en el archivo API
                    <br>• El sistema está cargando una vista HTML por defecto
                `, 'error');
            } else if (error instanceof SyntaxError) {
                mostrarMensajeGestion('❌ Error: El servidor devolvió una respuesta inválida (no JSON)', 'error');
            } else {
                mostrarMensajeGestion('❌ Error de conexión: ' + error.message, 'error');
            }
        } finally {
            // 6. RESTAURAR BOTONES
            submitBtns.forEach((btn, index) => {
                btn.innerHTML = originalTexts[index];
                btn.disabled = false;
            });
            
            console.log("=== FINALIZADO ENVÍO FORMULARIO ===");
        }
    });
    
    // Listener para cambio de estado
    estadoSelect.addEventListener('change', function() {
        if (this.value === 'resuelto') {
            console.log('Caso marcado como resuelto - se eliminará de la bandeja');
        }
    });
});

// FUNCIÓN PARA MOSTRAR MENSAJES EN GESTIÓN
function mostrarMensajeGestion(mensaje, tipo) {
    // Remover mensajes existentes
    const mensajesExistentes = document.querySelectorAll('.alert-dinamico-gestion');
    mensajesExistentes.forEach(msg => msg.remove());
    
    // Crear alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} alert-dismissible fade show alert-dinamico-gestion`;
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            <div>${mensaje}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insertar después del card-header
    const cardHeader = document.querySelector('.card-header');
    if (cardHeader && cardHeader.parentNode) {
        cardHeader.parentNode.insertBefore(alertDiv, cardHeader.nextSibling);
    }
}

// AGREGAR ESTILOS PARA ANIMACIONES
if (!document.querySelector('#estilos-gestion')) {
    const style = document.createElement('style');
    style.id = 'estilos-gestion';
    style.textContent = `
        .alert-dinamico-gestion {
            animation: fadeInGestion 0.5s ease-in;
        }
        @keyframes fadeInGestion {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
}
</script>
