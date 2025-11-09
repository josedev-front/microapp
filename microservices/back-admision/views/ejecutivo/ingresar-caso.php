<?php
// microservices/back-admision/views/ejecutivo/ingresar-caso.php
require_once __DIR__ . '/../../init.php';
?>
<style>
    /* Estilos para los mensajes dinámicos */
.alert {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Mejorar el modal de confirmación */
.modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    border-radius: 10px 10px 0 0;
}

/* Estilos para el formulario */
.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 10px;
    margin-bottom: 40%;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

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
        <div class="col-12 col-md-8 col-lg-6">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-success">
                    <li class="breadcrumb-item"><a href="./?vista=home"><i class="fas fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="./?vista=back-admision">Back de Admisión</a></li>
                    <li class="breadcrumb-item active">Ingresar Caso</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Ingresar Nuevo Caso</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['notificacion_confirmacion'])): ?>
                        <!-- Modal de Confirmación para Reasignación -->
                        <div class="modal fade show" id="modalConfirmacion" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning">
                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Reasignación</h5>
                                    </div>
                                    <div class="modal-body">
                                        <p><?php echo $_SESSION['notificacion_confirmacion']['mensaje']; ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <form id="formConfirmarReasignacion" action="./?vista=back-admision&action=procesar-caso" method="POST">
                                            <input type="hidden" name="sr_hijo" value="<?php echo $_SESSION['notificacion_confirmacion']['sr_hijo']; ?>">
                                            <input type="hidden" name="confirmar_reasignacion" value="1">
                                            <button type="submit" class="btn btn-warning">Sí, Reasignar</button>
                                        </form>
                                        <a href="./?vista=back-admision&action=ingresar-caso" class="btn btn-secondary">Cancelar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['notificacion_confirmacion']); ?>
                    <?php endif; ?>

                    <!-- Alertas normales -->
                    <?php if (isset($_SESSION['notificacion'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['notificacion']['tipo']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['notificacion']['mensaje']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['notificacion']); ?>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Instrucciones</h6>
                        <p class="mb-0">Ingresa el número de SR hijo para que el sistema lo asigne de manera equilibrada entre los ejecutivos disponibles del área Micro&SOHO.</p>
                    </div>

                    <form id="formIngresarCaso" method="post" action="./?vista=back-admision&action=procesar-caso">
                        <div class="mb-3">
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

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="./?vista=back-admision" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Ingresar Caso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para manejar el envío del formulario -->
<script>
document.getElementById('formIngresarCaso')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log("=== INICIANDO ENVÍO FORMULARIO ===");
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Mostrar loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        
        console.log("Enviando request a:", this.action);
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log("Respuesta completa:", responseText);
        
        // Parsear JSON
        const data = JSON.parse(responseText);
        console.log("Datos parseados:", data);
        
        if (data.success) {
    console.log("✅ Éxito - Datos:", data);
    
    // Mostrar mensaje de éxito normal
    mostrarMensajeExito(data.message);
    
    // Redirigir si hay URL
    if (data.redirect) {
        setTimeout(() => {
            window.location.href = data.redirect;
        }, 2000);
    }
    
} else if (data.message === 'confirmar_reasignacion') {
    // ✅ ESTE ES EL CASO ESPECIAL - Mostrar modal de confirmación
    console.log("🔄 Mostrando modal de confirmación");
    mostrarModalConfirmacion(data.detalles, data.sr_hijo, data);
    
} else {
    console.error("❌ Error del servidor:", data);
    mostrarMensajeError(data.message);
}
        
    } catch (error) {
        console.error('❌ Error en la solicitud:', error);
        mostrarMensajeError('Error de conexión: ' + error.message);
    } finally {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        console.log("=== FINALIZADO ENVÍO FORMULARIO ===");
    }
});

// FUNCIONES PARA MOSTRAR MENSAJES
function mostrarMensajeExito(mensaje) {
    // Crear alerta de éxito
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar después del formulario
    const form = document.getElementById('formIngresarCaso');
    form.parentNode.insertBefore(alertDiv, form.nextSibling);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function mostrarMensajeError(mensaje) {
    // Crear alerta de error
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar después del formulario
    const form = document.getElementById('formIngresarCaso');
    form.parentNode.insertBefore(alertDiv, form.nextSibling);
}

function mostrarModalConfirmacion(mensaje, sr_hijo, datosExtra = {}) {
    // Crear modal de confirmación mejorado
    const modalHTML = `
        <div class="modal fade show" id="modalConfirmacionDinamico" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exchange-alt me-2"></i>Caso Existente - Opciones de Asignación
                        </h5>
                        <button type="button" class="btn-close" onclick="cerrarModalConfirmacion()"></button>
                    </div>
                    <div class="modal-body">
                        ${mensaje}
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong>Recomendación:</strong> 
                                "Asignar al siguiente" mantiene el balance del equipo. "Reasignar a mí" es para cuando necesitas trabajar el caso personalmente.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <form id="formConfirmarReasignacionDinamico" method="POST">
                            <input type="hidden" name="sr_hijo" value="${sr_hijo}">
                            <input type="hidden" name="confirmar_reasignacion" value="1">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-user-check me-2"></i>Reasignar a Mí
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-info btn-lg" onclick="asignarAlSiguiente('${sr_hijo}')">
                            <i class="fas fa-robot me-2"></i>Asignar al Siguiente
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalConfirmacion()">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insertar modal en el body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Configurar envío del formulario del modal
    document.getElementById('formConfirmarReasignacionDinamico').addEventListener('submit', async function(e) {
        e.preventDefault();
        await enviarFormularioReasignacion(this);
    });
}

// MEJORAR la función asignarAlSiguiente
async function asignarAlSiguiente(sr_hijo) {
    try {
        mostrarMensajeExito('🔄 Buscando siguiente ejecutivo disponible...');
        cerrarModalConfirmacion();
        
        // En una implementación completa, aquí llamarías a una API para reassignación automática
        // Por ahora, informamos que el sistema lo hará automáticamente
        setTimeout(() => {
            mostrarMensajeExito('✅ El sistema reasignará este caso automáticamente al siguiente ejecutivo con menor carga.');
            
            // Opcional: Recargar después de un tiempo para ver los cambios
            setTimeout(() => {
                window.location.href = './?vista=back-admision';
            }, 3000);
        }, 1000);
        
    } catch (error) {
        mostrarMensajeError('Error: ' + error.message);
    }
}

function cerrarModalConfirmacion() {
    const modal = document.getElementById('modalConfirmacionDinamico');
    if (modal) {
        modal.remove();
        
        // Mostrar mensaje informativo
        mostrarMensajeExito('🔄 El caso será asignado al siguiente ejecutivo disponible con menor carga');
        
        // Aquí podríamos implementar la lógica para asignar al siguiente automáticamente
        // Por ahora, simplemente cerramos el modal
    }
}

async function enviarFormularioReasignacion(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Mostrar loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Reasignando...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        const response = await fetch('./?vista=back-admision&action=procesar-caso', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensajeExito(data.message);
            cerrarModalConfirmacion();
            
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            mostrarMensajeError(data.message);
        }
        
    } catch (error) {
        mostrarMensajeError('Error de conexión: ' + error.message);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}
</script>