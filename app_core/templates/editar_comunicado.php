<?php
// app_core/templates/editar_comunicado.php

// Verificar autenticación y permisos
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Verificar que el usuario puede editar comunicados
$puedeEditarComunicados = in_array($usuario_actual['role'], ['backup', 'agente_qa', 'supervisor', 'superuser', 'developer']);
if (!$puedeEditarComunicados) {
    header('Location: ./?vista=news&error=permisos');
    exit;
}

// Obtener ID del comunicado a editar
$comunicado_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($comunicado_id <= 0) {
    header('Location: ./?vista=news&error=id_invalido');
    exit;
}

// Cargar controladores
require_once __DIR__ . '/../controllers/ComunicadoController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$comunicadoController = new ComunicadoController();
$userController = new UserController();

// Obtener datos del comunicado
$comunicado = $comunicadoController->obtenerComunicadoPorId($comunicado_id);

// Verificar que el comunicado existe y pertenece al usuario
if (!$comunicado) {
    header('Location: ./?vista=news&error=comunicado_no_encontrado');
    exit;
}

if ($comunicado['created_by_id'] != $usuario_actual['id'] && $usuario_actual['role'] !== 'superuser') {
    header('Location: ./?vista=news&error=permisos_edicion');
    exit;
}

// Obtener usuarios para comunicados personales
$usuarios_para_comunicados = $userController->obtenerUsuariosActivos();

// Procesar actualización
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $comunicadoController->actualizarComunicado($comunicado_id, $_POST, $usuario_actual['id']);
    
    if ($resultado['success']) {
        header('Location: ./?vista=news&actualizado=1');
        exit;
    } else {
        $mensaje = $resultado['message'];
        $tipo_mensaje = 'danger';
    }
}

// Obtener estadísticas del comunicado
$estadisticas = $comunicadoController->obtenerEstadisticasComunicado($comunicado_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Comunicado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-dark text-light" style="background-image: url('http://localhost:3000/public/assets/img/fondo.png'); background-size: cover; background-position: center; background-attachment: fixed;">
    
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="?vista=home">
      <img src="http://localhost:3000/public/assets/img/microapps-simbol.png" alt="Micro apps" width="80" class="me-2">
      Micro apps
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="?vista=home">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="?vista=news">Noticias</a></li>
        <li class="nav-item"><a class="nav-link" href="?vista=myaccount">Mi perfil</a></li>
        <li class="nav-item">
          <a href="?vista=logout" class="btn btn-danger ms-2" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">Cerrar sesión</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Comunicado
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" id="formEditarComunicado">
                        <div class="alert alert-danger d-none" id="erroresFormulario">
                            <strong>Por favor, corrige los siguientes errores:</strong>
                            <ul class="mb-0" id="listaErrores"></ul>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" 
                                           value="<?php echo htmlspecialchars($comunicado['titulo']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="contenido" name="contenido" rows="5" required><?php echo htmlspecialchars($comunicado['contenido']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imagen" class="form-label">Imagen</label>
                                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                    <?php if (!empty($comunicado['imagen'])): ?>
                                    <div class="mt-2">
                                        <p class="mb-1"><strong>Imagen actual:</strong></p>
                                        <img src="<?php echo htmlspecialchars($comunicado['imagen']); ?>" 
                                             alt="Imagen actual" class="img-thumbnail" style="max-height: 150px;">
                                        <div class="form-check mt-2">
                                            <input type="checkbox" name="eliminar_imagen" id="eliminar_imagen" class="form-check-input">
                                            <label for="eliminar_imagen" class="form-check-label">Eliminar imagen actual</label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Configuración</h5>
                                
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tipo" name="tipo" required onchange="actualizarCamposDestino()">
                                        <option value="global" <?php echo $comunicado['tipo'] === 'global' ? 'selected' : ''; ?>>🌐 Global (Todos los usuarios)</option>
                                        <option value="local" <?php echo $comunicado['tipo'] === 'local' ? 'selected' : ''; ?>>🏢 Local (Mi área)</option>
                                        <option value="personal" <?php echo $comunicado['tipo'] === 'personal' ? 'selected' : ''; ?>>👤 Personal (Usuario específico)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="destinatarioContainer" style="display: <?php echo $comunicado['tipo'] === 'personal' ? 'block' : 'none'; ?>;">
                                    <label for="destinatario_id" class="form-label">Destinatario</label>
                                    <select class="form-select" id="destinatario_id" name="destinatario_id">
                                        <option value="">Seleccionar usuario...</option>
                                        <?php foreach ($usuarios_para_comunicados as $usuario): ?>
                                            <option value="<?php echo $usuario['id']; ?>" 
                                                <?php echo $comunicado['destinatario_id'] == $usuario['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($usuario['first_name'] . ' ' . $usuario['last_name'] . ' (' . $usuario['employee_id'] . ') - ' . $usuario['work_area']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="requiere_acuse" name="requiere_acuse" value="1"
                                            <?php echo $comunicado['requiere_acuse'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="requiere_acuse">Requiere acuse de recibo</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dias_visibilidad" class="form-label">Días de visibilidad</label>
                                    <input type="number" class="form-control" id="dias_visibilidad" name="dias_visibilidad" 
                                           value="<?php echo $comunicado['dias_visibilidad']; ?>" min="1" max="30">
                                    <div class="form-text">Número de días que el comunicado permanecerá visible</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                                            <?php echo $comunicado['activo'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">Comunicado activo</label>
                                    </div>
                                    <div class="form-text">Desactivar para archivar el comunicado</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="./?vista=news" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Comunicado</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> <?php echo htmlspecialchars($comunicado['creador_nombre'] ?? 'Usuario'); ?></p>
                            <p><strong>Fecha de creación:</strong> <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?></p>
                            <p><strong>Total de acuses:</strong> <?php echo $estadisticas['total_acuses'] ?? 0; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-<?php echo $comunicado['activo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $comunicado['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </p>
                            <p><strong>Días de visibilidad:</strong> <?php echo $comunicado['dias_visibilidad']; ?> días</p>
                            <p><strong>Requiere acuse:</strong> 
                                <span class="badge bg-<?php echo $comunicado['requiere_acuse'] ? 'info' : 'secondary'; ?>">
                                    <?php echo $comunicado['requiere_acuse'] ? 'Sí' : 'No'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarCamposDestino() {
    const tipo = document.getElementById('tipo').value;
    const container = document.getElementById('destinatarioContainer');
    
    if (tipo === 'personal') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
        document.getElementById('destinatario_id').value = '';
    }
}

// Validación del formulario
document.getElementById('formEditarComunicado').addEventListener('submit', function(e) {
    const errores = [];
    const titulo = document.getElementById('titulo').value.trim();
    const contenido = document.getElementById('contenido').value.trim();
    const tipo = document.getElementById('tipo').value;
    const destinatario = document.getElementById('destinatario_id').value;
    
    if (!titulo) {
        errores.push('El título es obligatorio');
    }
    
    if (!contenido) {
        errores.push('El contenido es obligatorio');
    }
    
    if (tipo === 'personal' && !destinatario) {
        errores.push('Debe seleccionar un destinatario para comunicados personales');
    }
    
    if (errores.length > 0) {
        e.preventDefault();
        const listaErrores = document.getElementById('listaErrores');
        const erroresFormulario = document.getElementById('erroresFormulario');
        
        listaErrores.innerHTML = '';
        errores.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            listaErrores.appendChild(li);
        });
        
        erroresFormulario.classList.remove('d-none');
        erroresFormulario.scrollIntoView({ behavior: 'smooth' });
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    actualizarCamposDestino();
});
</script>
</body>
</html>