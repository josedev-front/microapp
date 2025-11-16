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
.container-fluid {
    margin-bottom: 7%;
}
</style>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-success">
                    <li class="breadcrumb-item"><a href="./?vista=home"><i class="fas fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="./?vista=back-admision">Back de Admisión</a></li>
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

                    <!-- CORREGIDO: URL del formulario -->
                    <form id="formIngresarCaso" method="post" action="./?vista=back-admision&action=ingresar-caso-supervisor">
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
                            <a href="./?vista=back-admision" class="btn btn-secondary me-md-2">
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
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formIngresarCaso');
    
    // Mostrar información del ejecutivo seleccionado
    const analistaSelect = document.getElementById('analista_id');
    const infoEjecutivo = document.getElementById('info-ejecutivo');
    
    if (analistaSelect && infoEjecutivo) {
        analistaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const estado = selectedOption.dataset.estado;
            const casos = selectedOption.dataset.casos;
            
            let infoText = '';
            if (estado === 'activo') {
                infoText = `✅ Ejecutivo activo con ${casos} casos asignados - Disponibilidad: ${casos < 10 ? 'Alta' : casos < 20 ? 'Media' : 'Baja'}`;
            } else if (estado === 'colacion') {
                infoText = `⚠️ Ejecutivo en colación - No recomendado para asignación inmediata`;
            } else {
                infoText = `❌ Ejecutivo inactivo - No disponible para asignación`;
            }
            
            infoEjecutivo.textContent = infoText;
        });
    }

    // Manejo del envío del formulario con AJAX
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log("=== INICIANDO ASIGNACIÓN SUPERVISOR ===");
        
        // 1. VALIDACIÓN
        const sr_hijo = document.getElementById('sr_hijo').value.trim();
        if (!sr_hijo) {
            alert('Por favor, ingresa el número de SR hijo');
            return false;
        }
        
        <?php if ($tipo === 'supervisor'): ?>
        const analista_id = document.getElementById('analista_id').value;
        if (!analista_id) {
            alert('Por favor, selecciona un ejecutivo');
            return false;
        }
        
        const estado = document.getElementById('analista_id').options[document.getElementById('analista_id').selectedIndex].dataset.estado;
        if (estado !== 'activo') {
            if (!confirm('⚠️ El ejecutivo seleccionado no está activo. ¿Deseas continuar con la asignación?')) {
                return false;
            }
        }
        <?php endif; ?>
        
        // 2. MOSTRAR LOADING
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        submitBtn.disabled = true;
        
        try {
            // 3. PREPARAR Y ENVIAR DATOS
            const formData = new FormData(this);
            
            console.log("📤 Enviando datos:", Object.fromEntries(formData));
            
            // PROBAR DIFERENTES URLS
            const urlsToTry = [
                './?vista=back-admision&action=ingresar-caso-supervisor',
                '/?vista=back-admision&action=ingresar-caso-supervisor',
                'index.php?vista=back-admision&action=ingresar-caso-supervisor'
            ];
            
            let response, responseText, data;
            let success = false;
            let workingUrl = '';
            
            for (let url of urlsToTry) {
                console.log("🔍 Probando URL:", url);
                
                try {
                    response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'include'
                    });
                    
                    responseText = await response.text();
                    console.log("📥 Respuesta completa:", responseText);
                    
                    // Verificar si es HTML (página de error)
                    if (responseText.trim().startsWith('<!DOCTYPE') || 
                        responseText.includes('<html') || 
                        responseText.includes('404 Not Found') ||
                        responseText.includes('Not Found')) {
                        console.log("❌ URL devuelve HTML/404, probando siguiente...");
                        continue;
                    }
                    
                    // Limpiar respuesta de posibles warnings PHP
                    let cleanResponse = responseText.trim();
                    
                    // Si hay warnings PHP, extraer solo el JSON
                    if (cleanResponse.includes('{') && cleanResponse.includes('}')) {
                        const jsonStart = cleanResponse.indexOf('{');
                        const jsonEnd = cleanResponse.lastIndexOf('}') + 1;
                        cleanResponse = cleanResponse.substring(jsonStart, jsonEnd);
                    }
                    
                    // Verificar que sea JSON válido
                    if (!cleanResponse.startsWith('{') || !cleanResponse.endsWith('}')) {
                        console.log("❌ Respuesta no es JSON válido, probando siguiente...");
                        continue;
                    }
                    
                    // Intentar parsear JSON
                    data = JSON.parse(cleanResponse);
                    success = true;
                    workingUrl = url;
                    console.log("✅ URL funciona:", url);
                    console.log("📊 Datos recibidos:", data);
                    break;
                    
                } catch (error) {
                    console.log("❌ Error con URL", url, ":", error.message);
                    console.log("📄 Respuesta cruda:", responseText.substring(0, 200));
                    continue;
                }
            }
            
            if (!success) {
                throw new Error(`
                    ❌ No se pudo encontrar una API funcionando correctamente.
                    
                    <strong>URLs probadas:</strong>
                    <br>• ./?vista=back-admision&action=ingresar-caso-supervisor
                    <br>• /?vista=back-admision&action=ingresar-caso-supervisor  
                    <br>• index.php?vista=back-admision&action=ingresar-caso-supervisor
                    
                    <br><br><strong>Posibles problemas:</strong>
                    <br>• La API está devolviendo HTML en lugar de JSON
                    <br>• Hay errores PHP en la API que rompen el JSON
                    <br>• La ruta no está configurada correctamente
                    
                    <br><br><strong>Solución:</strong>
                    <br>1. Verificar el archivo api/ingresar_caso_supervisor.php
                    <br>2. Revisar los logs de error del servidor
                    <br>3. Verificar que no haya warnings PHP
                `);
            }
            
            // 4. PROCESAR RESPUESTA
            console.log("🎉 ÉXITO - Procesando respuesta de:", workingUrl);
            
            if (data.success) {
                mostrarMensaje(data.message, 'success');
                
                // Redirigir si hay URL
                if (data.redirect) {
                    setTimeout(() => {
                        console.log("🔄 Redirigiendo a:", data.redirect);
                        window.location.href = data.redirect;
                    }, 2000);
                }
            } else {
                // Mostrar mensaje de error específico
                if (data.message.includes('ya está asignada') && data.caso_existente) {
                    // Preguntar si quiere forzar reasignación
                    const confirmar = confirm(`${data.message}\n\n¿Desea forzar la reasignación?`);
                    if (confirmar) {
                        // Marcar forzar reasignación y enviar nuevamente
                        document.getElementById('forzar_asignacion').checked = true;
                        form.dispatchEvent(new Event('submit'));
                    }
                } else {
                    mostrarMensaje(data.message, 'error');
                }
            }
            
        } catch (error) {
            console.error('❌ Error en la solicitud:', error);
            
            // Mostrar error específico según el tipo
            if (error.message.includes('No se pudo encontrar una API funcionando')) {
                mostrarMensaje(error.message, 'error');
            } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                mostrarMensaje('❌ Error de conexión: No se pudo conectar con el servidor', 'error');
            } else {
                mostrarMensaje('❌ Error inesperado: ' + error.message, 'error');
            }
        } finally {
            // 5. RESTAURAR BOTÓN
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.log("=== FINALIZADO ENVÍO FORMULARIO ===");
        }
    });
});

function mostrarMensaje(mensaje, tipo) {
    // Remover mensajes existentes
    const mensajesExistentes = document.querySelectorAll('.alert-dinamico-asignacion');
    mensajesExistentes.forEach(msg => msg.remove());
    
    // Crear alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} alert-dismissible fade show alert-dinamico-asignacion mt-3`;
    
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
    
    // Auto-eliminar después de 10 segundos para errores
    if (tipo === 'error') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 10000);
    }
}

// Agregar estilos para animaciones
if (!document.querySelector('#estilos-asignacion')) {
    const style = document.createElement('style');
    style.id = 'estilos-asignacion';
    style.textContent = `
        .alert-dinamico-asignacion {
            animation: fadeInAsignacion 0.5s ease-in;
        }
        @keyframes fadeInAsignacion {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
    `;
    document.head.appendChild(style);
}
</script>