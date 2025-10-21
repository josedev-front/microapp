<?php
// microservices/middy/views/chat.php

// Calcular la ruta base correcta
$base_path = dirname(__DIR__, 3); // Sube 3 niveles desde microservices/middy/views/
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

// Verificar sesión de manera más robusta
if (!validarSesion()) {
    header('Location: ' . BASE_URL . '?vista=login');
    exit;
}

// Obtener usuario actual
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ' . BASE_URL . '?vista=login');
    exit;
}

// Verificar permisos para Middy
if (!middy_check_permissions($usuario_actual['role'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a Middy';
    header('Location: ' . BASE_URL . '?vista=home');
    exit;
}

// Cargar init de Middy
require_once __DIR__ . '/../init.php';

// Procesar subida de archivos (solo para roles específicos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documento']) && in_array($usuario_actual['role'], ['supervisor', 'developer', 'superuser'])) {
    $uploadResult = handleFileUpload($_FILES['documento']);
    if ($uploadResult['success']) {
        $mensaje_exito = "Archivo subido correctamente: " . $uploadResult['filename'];
    } else {
        $mensaje_error = "Error al subir archivo: " . $uploadResult['error'];
    }
}

function handleFileUpload($file) {
    $allowedTypes = ['text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error en la subida del archivo'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = MIDDY_UPLOADS_PATH . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Mover a docs después de validar
        $finalDestination = MIDDY_DATA_PATH . '/' . $file['name'];
        rename($destination, $finalDestination);
        return ['success' => true, 'filename' => $file['name']];
    }
    
    return ['success' => false, 'error' => 'No se pudo guardar el archivo'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Middy - Asistente Virtual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .chat-container {
            height: 70vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
        }
        .user-message {
            background-color: #007bff;
            color: white;
            margin-left: auto;
        }
        .bot-message {
            background-color: #e9ecef;
            color: #333;
        }
        .typing-indicator {
            display: none;
            padding: 10px 15px;
        }
        .document-list {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header de MicroApps -->
    

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-robot me-2"></i>Middy - Asistente Virtual</h4>
                        <small>Consulta información sobre gestiones de Entel</small>
                    </div>
                    <?php 
$admin_roles = ['supervisor', 'developer', 'backup', 'agente_qa', 'superuser'];
if (in_array($usuario_actual['role'], $admin_roles)): 
?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Panel de Administración</h5>
                <small>Gestión de documentos y logs del sistema</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-edit me-2"></i>Editor de Documentos</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="loadFileForEdit('informe.txt')">
                                <i class="fas fa-file-alt me-1"></i> Editar informe.txt
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="loadFileForEdit('datos.txt')">
                                <i class="fas fa-file-code me-1"></i> Editar datos.txt
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-history me-2"></i>Registros del Sistema</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info btn-sm" onclick="showDocumentLogs()">
                                <i class="fas fa-list-alt me-1"></i> Ver Logs de Modificaciones
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="showSystemStats()">
                                <i class="fas fa-chart-bar me-1"></i> Estadísticas del Sistema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar archivos -->
<div class="modal fade" id="editFileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFileModalTitle">Editar Archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Contenido del archivo:</label>
                    <textarea class="form-control" id="fileContentEditor" rows="15" style="font-family: monospace;"></textarea>
                </div>
                <div class="alert alert-info">
                    <small><i class="fas fa-info-circle me-1"></i> Los cambios se guardarán inmediatamente al presionar "Guardar".</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFileContent()">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar logs -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logs de Modificaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando logs...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
                    <div class="card-body">
                        <!-- Mensajes de éxito/error -->
                        <?php if (isset($mensaje_exito)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $mensaje_exito; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($mensaje_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $mensaje_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Panel de chat -->
                            <div class="col-md-8">
                                <div class="chat-container" id="chatContainer">
                                    <div class="message bot-message">
                                        <strong>Middy:</strong> Hola <?php echo $usuario_actual['first_name']; ?>, soy tu asistente virtual. ¿En qué puedo ayudarte con las gestiones de Entel?
                                    </div>
                                </div>
                                
                                <div class="typing-indicator" id="typingIndicator">
                                    <strong>Middy:</strong> <i class="fas fa-ellipsis-h"></i> escribiendo...
                                </div>
                                
                                <div class="input-group mt-3">
                                    <input type="text" class="form-control" id="questionInput" 
                                           placeholder="Escribe tu pregunta sobre gestiones de Entel..." 
                                           maxlength="1000">
                                    <button class="btn btn-primary" id="sendButton">
                                        <i class="fas fa-paper-plane"></i> Enviar
                                    </button>
                                </div>
                                <small class="text-muted">Ejemplo: "¿Cuáles son los procedimientos para gestionar incidencias de fibra óptica?"</small>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatContainer = document.getElementById('chatContainer');
        const questionInput = document.getElementById('questionInput');
        const sendButton = document.getElementById('sendButton');
        const typingIndicator = document.getElementById('typingIndicator');

        console.log("✅ JavaScript de Middy cargado");

        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
            messageDiv.innerHTML = `<strong>${isUser ? 'Tú' : 'Middy'}:</strong> ${message}`;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            typingIndicator.style.display = 'block';
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        async function sendMessage() {
    const question = questionInput.value.trim();
    if (!question) return;

    // Agregar mensaje del usuario
    addMessage(question, true);
    questionInput.value = '';
    sendButton.disabled = true;

    // Mostrar indicador de typing
    showTypingIndicator();

    try {
        // USAR ENDPOINT DIRECTO QUE SÍ FUNCIONA
        const apiUrl = 'http://localhost:3000/public/api/middy_chat.php';
        console.log("🌐 Llamando a endpoint directo:", apiUrl);
        console.log("📤 Pregunta:", question);

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ question: question })
        });

        console.log("📥 Respuesta HTTP:", response.status, response.statusText);

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log("📦 Datos recibidos:", data);

        hideTypingIndicator();

        if (data.error) {
            console.error("❌ Error en respuesta:", data.error);
            addMessage(`<span class="text-danger">Error: ${data.error}</span>`);
        } else {
            console.log("✅ Respuesta exitosa");
            addMessage(data.answer);
            
            // Mostrar información de fuentes si está disponible
            if (data.sources > 0) {
                const sourceInfo = document.createElement('div');
                sourceInfo.className = 'small text-muted mt-1';
                sourceInfo.innerHTML = `<i>📚 Información obtenida de ${data.sources} fuente(s)</i>`;
                chatContainer.appendChild(sourceInfo);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

    } catch (error) {
        hideTypingIndicator();
        console.error("💥 Error completo:", error);
        addMessage(`<span class="text-danger">Error de conexión: ${error.message}</span>`);
    }

    sendButton.disabled = false;
}
        // Event listeners
        sendButton.addEventListener('click', function() {
            console.log("🖱️ Botón enviar clickeado");
            sendMessage();
        });
        
        questionInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log("⌨️ Enter presionado");
                sendMessage();
            }
        });

        // Verificar que los elementos existen
        console.log("🔍 Elementos encontrados:", {
            chatContainer: !!chatContainer,
            questionInput: !!questionInput,
            sendButton: !!sendButton,
            typingIndicator: !!typingIndicator
        });

        console.log("🚀 Middy listo para usar");
    });
    let currentEditingFile = '';

// Funciones de administración
function loadFileForEdit(filename) {
    currentEditingFile = filename;
    
    // Mostrar loading
    document.getElementById('fileContentEditor').value = 'Cargando...';
    
    const modal = new bootstrap.Modal(document.getElementById('editFileModal'));
    document.getElementById('editFileModalTitle').textContent = `Editando: ${filename}`;
    
    // Cargar contenido del archivo
    fetch(`<?php echo BASE_URL; ?>microservices/middy/api/admin_get_file.php?file=${filename}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('fileContentEditor').value = data.content;
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el archivo');
        });
    
    modal.show();
}

function saveFileContent() {
    const content = document.getElementById('fileContentEditor').value;
    
    if (!currentEditingFile) {
        alert('No hay archivo seleccionado');
        return;
    }
    
    fetch(`<?php echo BASE_URL; ?>microservices/middy/api/admin_save_file.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            filename: currentEditingFile,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Archivo guardado correctamente');
            bootstrap.Modal.getInstance(document.getElementById('editFileModal')).hide();
            
            // Actualizar la interfaz si es necesario
            addMessage(`📝 Se actualizó el archivo ${currentEditingFile}`, false);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el archivo');
    });
}

function showDocumentLogs() {
    const modal = new bootstrap.Modal(document.getElementById('logsModal'));
    
    fetch(`<?php echo BASE_URL; ?>microservices/middy/api/admin_get_logs.php`)
        .then(response => response.json())
        .then(data => {
            const logsContent = document.getElementById('logsContent');
            
            if (data.success) {
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Estadísticas de Archivos</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th>Tamaño</th>
                                                <th>Líneas</th>
                                                <th>Última modificación</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;
                
                for (const [filename, stats] of Object.entries(data.stats)) {
                    html += `
                        <tr>
                            <td>${filename}</td>
                            <td>${(stats.size / 1024).toFixed(2)} KB</td>
                            <td>${stats.lines} líneas</td>
                            <td>${stats.last_modified || 'N/A'}</td>
                        </tr>
                    `;
                }
                
                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Resumen de Actividad</h6>
                                    <p>Total de registros: ${data.logs.length}</p>
                                    <p>Última modificación: ${data.logs[0] ? data.logs[0].created_at : 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6>Historial de Modificaciones</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Archivo</th>
                                    <th>Acción</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.logs.forEach(log => {
                    html += `
                        <tr>
                            <td>${new Date(log.created_at).toLocaleString()}</td>
                            <td>${log.first_name || 'Usuario'} ${log.last_name || ''}</td>
                            <td><span class="badge bg-secondary">${log.user_role}</span></td>
                            <td>${log.document_name}</td>
                            <td><span class="badge bg-info">${log.action}</span></td>
                            <td>${log.changes || '-'}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                logsContent.innerHTML = html;
            } else {
                logsContent.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('logsContent').innerHTML = '<div class="alert alert-danger">Error al cargar los logs</div>';
        });
    
    modal.show();
}

function showSystemStats() {
    // Puedes expandir esta función para mostrar más estadísticas
    showDocumentLogs();
}

// Verificar permisos de administración al cargar la página
console.log("🔐 Permisos de administración:", {
    userRole: '<?php echo $usuario_actual["role"] ?? ""; ?>',
    isAdmin: <?php echo in_array($usuario_actual["role"] ?? "", ['supervisor', 'developer', 'backup', 'agente_qa', 'superuser']) ? 'true' : 'false'; ?>
});
</script>
</body>
</html>