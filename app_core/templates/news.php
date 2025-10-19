<?php
// app_core/templates/news.php

// === MANEJO AJAX - VERSIÓN ULTRA SEGURA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_acuses') {
    
    // FORZAR limpieza de buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Iniciar sesión de manera AGGRESIVA
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // DEBUG: Log de sesión
    error_log("=== AJAX REQUEST ===");
    error_log("Session ID: " . session_id());
    error_log("Session Data: " . print_r($_SESSION, true));
    error_log("POST Data: " . print_r($_POST, true));
    
    // Cargar configuración MINIMA
    require_once __DIR__ . '/../../app_core/config/database.php';
    require_once __DIR__ . '/../../app_core/config/helpers.php';
    
    // Verificar autenticación usando la misma función que la app principal
    $usuario_actual = obtenerUsuarioActual();
    
    if (!$usuario_actual) {
        error_log("USUARIO NO AUTENTICADO EN AJAX");
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'No autenticado',
            'debug' => [
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]
        ]);
        exit;
    }
    
    error_log("Usuario autenticado: " . $usuario_actual['id']);
    
    // Cargar controlador y procesar
    require_once __DIR__ . '/../../app_core/controllers/ComunicadoController.php';
    
    $comunicado_id = intval($_POST['comunicado_id']);
    $comunicadoController = new ComunicadoController();
    $acuses = $comunicadoController->obtenerAcusesComunicado($comunicado_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'acuses' => $acuses,
        'debug' => [
            'comunicado_id' => $comunicado_id,
            'total_acuses' => count($acuses),
            'usuario_actual' => $usuario_actual['id']
        ]
    ]);
    exit;
}

// === CÓDIGO NORMAL DE LA PÁGINA ===
// Obtener datos del usuario actual
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Verificar permisos para crear comunicados
$puedeCrearComunicados = in_array($usuario_actual['role'], ['backup', 'agente_qa', 'supervisor', 'superuser', 'developer']);

// Cargar controladores
require_once __DIR__ . '/../controllers/ComunicadoController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$comunicadoController = new ComunicadoController();
$userController = new UserController();

// Obtener comunicados según el tipo
$comunicados_globales = $comunicadoController->obtenerComunicadosGlobalesPendientes($usuario_actual['id']);
$comunicados_area = $comunicadoController->obtenerComunicadosAreaPendientes($usuario_actual['id'], $usuario_actual['work_area']);
$comunicados_personales = $comunicadoController->obtenerComunicadosPersonalesPendientes($usuario_actual['id']);
$mis_comunicados = $puedeCrearComunicados ? $comunicadoController->obtenerMisComunicados($usuario_actual['id']) : [];
$historial_comunicados = $comunicadoController->obtenerHistorialComunicados($usuario_actual['id']);

// Obtener usuarios para comunicados personales (solo si puede crear)
$usuarios_para_comunicados = $puedeCrearComunicados ? $userController->obtenerUsuariosActivos() : [];

// Inicializar variables de mensajes
$mensaje = '';
$tipo_mensaje = '';

// PROCESAMIENTO DE FORMULARIOS REGULARES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Procesar eliminación de comunicado
    if (isset($_POST['eliminar_comunicado']) && $puedeCrearComunicados) {
        $comunicado_id = intval($_POST['comunicado_id']);
        $resultado = $comunicadoController->eliminarComunicado($comunicado_id, $usuario_actual['id']);
        
        if ($resultado['success']) {
            header('Location: ./?vista=news&eliminado=1');
            exit;
        } else {
            $mensaje = "Error: " . $resultado['message'];
            $tipo_mensaje = "danger";
        }
    }
    
    // Procesar acuse de recibo
    elseif (isset($_POST['dar_acuse'])) {
        $comunicado_id = intval($_POST['comunicado_id']);
        $resultado = $comunicadoController->registrarAcuseRecibo($comunicado_id, $usuario_actual['id']);
        
        if ($resultado['success']) {
            header('Location: ./?vista=news&acuse=1');
            exit;
        } else {
            $mensaje = "Error: " . $resultado['message'];
            $tipo_mensaje = "danger";
        }
    }
    
    // Procesar creación de comunicado
    elseif (isset($_POST['crear_comunicado']) && $puedeCrearComunicados) {
        $resultado = $comunicadoController->crearComunicado($_POST, $usuario_actual['id']);
        
        if ($resultado['success']) {
            header('Location: ./?vista=news&creado=1');
            exit;
        } else {
            $mensaje = "Error: " . $resultado['message'];
            $tipo_mensaje = "danger";
        }
    }
}

// MOSTRAR MENSAJES DE ÉXITO DESPUÉS DE REDIRECCIONES
if (isset($_GET['eliminado']) && $_GET['eliminado'] == 1) {
    $mensaje = "Comunicado eliminado correctamente";
    $tipo_mensaje = "success";
}
if (isset($_GET['acuse']) && $_GET['acuse'] == 1) {
    $mensaje = "Acuse de recibo registrado correctamente";
    $tipo_mensaje = "success";
}
if (isset($_GET['creado']) && $_GET['creado'] == 1) {
    $mensaje = "Comunicado creado correctamente";
    $tipo_mensaje = "success";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias & Comunicados</title>
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

<div class="container" style="margin-top: 10%; margin-bottom: 10%;">
  <div class="row justify-content-center">
    <div class="col-lg-12">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">

          <h2 class="fw-bold mb-4 text-primary"><i class="fas fa-newspaper me-2"></i>Noticias & Comunicados</h2>

          <!-- Mostrar mensajes -->
          <?php if ($mensaje): ?>
          <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <ul class="nav nav-tabs mb-4" id="comunicadosTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button" role="tab">
                <i class="fas fa-globe me-1"></i> Globales
                <?php if (count($comunicados_globales) > 0): ?>
                  <span class="badge bg-danger ms-1"><?php echo count($comunicados_globales); ?></span>
                <?php endif; ?>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="area-tab" data-bs-toggle="tab" data-bs-target="#area" type="button" role="tab">
                <i class="fas fa-building me-1"></i> Mi Área
                <?php if (count($comunicados_area) > 0): ?>
                  <span class="badge bg-danger ms-1"><?php echo count($comunicados_area); ?></span>
                <?php endif; ?>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                <i class="fas fa-user me-1"></i> Para mí
                <?php if (count($comunicados_personales) > 0): ?>
                  <span class="badge bg-danger ms-1"><?php echo count($comunicados_personales); ?></span>
                <?php endif; ?>
              </button>
            </li>
            
            <?php if ($puedeCrearComunicados): ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="mis-comunicados-tab" data-bs-toggle="tab" data-bs-target="#mis-comunicados" type="button" role="tab">
                <i class="fas fa-paper-plane me-1"></i> Mis Comunicados
              </button>
            </li>
            <?php endif; ?>
            
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab">
                <i class="fas fa-history me-1"></i> Historial
              </button>
            </li>
          </ul>

          <div class="tab-content" id="comunicadosTabsContent">

            <!-- TAB: Comunicados Globales -->
            <div class="tab-pane fade show active" id="global" role="tabpanel">
              <?php if (empty($comunicados_globales)): ?>
                <div class="alert alert-success text-center">
                  <i class="fas fa-check-circle fa-2x mb-3"></i><br>
                  No tienes comunicados globales pendientes de acuse 🎉
                </div>
              <?php else: ?>
                <?php foreach ($comunicados_globales as $comunicado): ?>
                  <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">
                        <i class="fas fa-globe me-2"></i><?php echo htmlspecialchars($comunicado['titulo']); ?>
                      </h5>
                      <span class="badge bg-light text-dark">
                        <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?>
                      </span>
                    </div>
                    <div class="card-body">
                      <?php if (!empty($comunicado['imagen'])): ?>
                        <div class="text-center mb-3">
                          <img src="<?php echo htmlspecialchars($comunicado['imagen']); ?>" class="img-fluid rounded" style="max-height: 300px;" alt="Imagen del comunicado">
                        </div>
                      <?php endif; ?>
                      
                      <div class="mb-3">
                        <?php echo nl2br(htmlspecialchars($comunicado['contenido'])); ?>
                      </div>
                      
                      <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                          <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($comunicado['creador_nombre']); ?>
                        </small>
                        
                        <?php if ($comunicado['requiere_acuse']): ?>
                          <form method="post" class="d-inline">
                            <input type="hidden" name="comunicado_id" value="<?php echo $comunicado['id']; ?>">
                            <button type="submit" name="dar_acuse" class="btn btn-success btn-sm">
                              <i class="fas fa-check-circle me-1"></i> Dar Acuse de Recibo
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="badge bg-secondary">No requiere acuse</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- TAB: Comunicados de Área -->
            <div class="tab-pane fade" id="area" role="tabpanel">
              <?php if (empty($comunicados_area)): ?>
                <div class="alert alert-warning text-center">
                  <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                  No hay comunicados para tu área actualmente.
                </div>
              <?php else: ?>
                <?php foreach ($comunicados_area as $comunicado): ?>
                  <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($comunicado['titulo']); ?>
                      </h5>
                      <span class="badge bg-dark">
                        <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?>
                      </span>
                    </div>
                    <div class="card-body">
                      <?php if (!empty($comunicado['imagen'])): ?>
                        <div class="text-center mb-3">
                          <img src="<?php echo htmlspecialchars($comunicado['imagen']); ?>" class="img-fluid rounded" style="max-height: 300px;" alt="Imagen del comunicado">
                        </div>
                      <?php endif; ?>
                      
                      <div class="mb-3">
                        <?php echo nl2br(htmlspecialchars($comunicado['contenido'])); ?>
                      </div>
                      
                      <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                          <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($comunicado['creador_nombre']); ?>
                        </small>
                        
                        <?php if ($comunicado['requiere_acuse']): ?>
                          <form method="post" class="d-inline">
                            <input type="hidden" name="comunicado_id" value="<?php echo $comunicado['id']; ?>">
                            <button type="submit" name="dar_acuse" class="btn btn-success btn-sm">
                              <i class="fas fa-check-circle me-1"></i> Dar Acuse de Recibo
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="badge bg-secondary">No requiere acuse</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- TAB: Comunicados Personales -->
            <div class="tab-pane fade" id="personal" role="tabpanel">
              <?php if (empty($comunicados_personales)): ?>
                <div class="alert alert-info text-center">
                  <i class="fas fa-user-check fa-2x mb-3"></i><br>
                  No tienes comunicados personales pendientes 🎉
                </div>
              <?php else: ?>
                <?php foreach ($comunicados_personales as $comunicado): ?>
                  <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($comunicado['titulo']); ?>
                      </h5>
                      <span class="badge bg-light text-dark">
                        <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?>
                      </span>
                    </div>
                    <div class="card-body">
                      <?php if (!empty($comunicado['imagen'])): ?>
                        <div class="text-center mb-3">
                          <img src="<?php echo htmlspecialchars($comunicado['imagen']); ?>" class="img-fluid rounded" style="max-height: 300px;" alt="Imagen del comunicado">
                        </div>
                      <?php endif; ?>
                      
                      <div class="mb-3">
                        <?php echo nl2br(htmlspecialchars($comunicado['contenido'])); ?>
                      </div>
                      
                      <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                          <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($comunicado['creador_nombre']); ?>
                        </small>
                        
                        <?php if ($comunicado['requiere_acuse']): ?>
                          <form method="post" class="d-inline">
                            <input type="hidden" name="comunicado_id" value="<?php echo $comunicado['id']; ?>">
                            <button type="submit" name="dar_acuse" class="btn btn-success btn-sm">
                              <i class="fas fa-check-circle me-1"></i> Dar Acuse de Recibo
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="badge bg-secondary">No requiere acuse</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- TAB: Mis Comunicados (solo para quienes pueden crear) -->
            <?php if ($puedeCrearComunicados): ?>
            <div class="tab-pane fade" id="mis-comunicados" role="tabpanel">
              <?php if (empty($mis_comunicados)): ?>
                <div class="alert alert-secondary text-center">
                  <i class="fas fa-paper-plane fa-2x mb-3"></i><br>
                  Aún no has creado ningún comunicado.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead class="table-dark">
                      <tr>
                        <th class="text-primary">Título</th>
                        <th class="text-primary">Tipo</th>
                        <th class="text-primary">Destinatario/Área</th>
                        <th class="text-primary">Fecha</th>
                        <th class="text-primary">Estado</th>
                        <th class="text-primary">Acuses</th>
                        <th class="text-primary">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($mis_comunicados as $comunicado): ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($comunicado['titulo']); ?></strong>
                          <?php if ($comunicado['requiere_acuse']): ?>
                            <i class="fas fa-check-double text-primary ms-1" title="Requiere acuse"></i>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php 
                          $badge_class = [
                            'global' => 'bg-primary',
                            'local' => 'bg-warning text-dark',
                            'personal' => 'bg-info'
                          ];
                          ?>
                          <span class="badge <?php echo $badge_class[$comunicado['tipo']] ?? 'bg-secondary'; ?>">
                            <?php echo ucfirst($comunicado['tipo']); ?>
                          </span>
                        </td>
                        <td>
                          <?php if ($comunicado['tipo'] === 'personal' && $comunicado['destinatario_nombre']): ?>
                            <?php echo htmlspecialchars($comunicado['destinatario_nombre']); ?>
                          <?php elseif ($comunicado['tipo'] === 'local' && $comunicado['area']): ?>
                            <?php echo htmlspecialchars($comunicado['area']); ?>
                          <?php else: ?>
                            Todos
                          <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?></td>
                        <td>
                          <span class="badge <?php echo $comunicado['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $comunicado['activo'] ? 'Activo' : 'Eliminado'; ?>
                          </span>
                        </td>
                        <td>
                          <?php if ($comunicado['requiere_acuse']): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="verAcuses(<?php echo $comunicado['id']; ?>)">
                              <i class="fas fa-list-check me-1"></i>
                              <?php echo $comunicado['total_acuses']; ?>/<?php echo $comunicado['total_destinatarios']; ?>
                            </button>
                          <?php else: ?>
                            <span class="text-muted">N/A</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <a href="./?vista=editar_comunicado&id=<?php echo $comunicado['id']; ?>" class="btn btn-warning">
                              <i class="fas fa-edit"></i>
                            </a>
                            <form method="post" class="d-inline">
                              <input type="hidden" name="comunicado_id" value="<?php echo $comunicado['id']; ?>">
                              <button type="submit" name="eliminar_comunicado" class="btn btn-danger" 
                                      onclick="return confirm('¿Estás seguro de eliminar este comunicado?')">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- TAB: Historial -->
            <div class="tab-pane fade" id="historial" role="tabpanel">
              <div class="mb-4">
                <form method="get" class="row g-3">
                  <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Buscar en comunicados..." 
                           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                  </div>
                  <div class="col-md-3">
                    <select name="tipo" class="form-select">
                      <option value="">Todos los tipos</option>
                      <option value="global" <?php echo ($_GET['tipo'] ?? '') === 'global' ? 'selected' : ''; ?>>Global</option>
                      <option value="local" <?php echo ($_GET['tipo'] ?? '') === 'local' ? 'selected' : ''; ?>>Local</option>
                      <option value="personal" <?php echo ($_GET['tipo'] ?? '') === 'personal' ? 'selected' : ''; ?>>Personal</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                      <i class="fas fa-search me-1"></i> Buscar
                    </button>
                  </div>
                  <div class="col-md-2">
                    <a href="./?vista=news" class="btn btn-secondary w-100">
                      <i class="fas fa-times me-1"></i> Limpiar
                    </a>
                  </div>
                </form>
              </div>

              <?php if (empty($historial_comunicados)): ?>
                <div class="alert alert-info text-center">
                  <i class="fas fa-inbox fa-2x mb-3"></i><br>
                  No hay comunicados en tu historial.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead class="table-dark">
                      <tr>
                        <th>Comunicado</th>
                        <th>Tipo</th>
                        <th>Emisor</th>
                        <th>Fecha Comunicado</th>
                        <th>Fecha Acuse</th>
                        <th>Días Visibilidad</th>
                        <th>Estado</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($historial_comunicados as $historial): ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($historial['titulo']); ?></strong>
                          <?php if ($historial['requiere_acuse'] && $historial['fecha_acuse']): ?>
                            <i class="fas fa-check-double text-success ms-1" title="Acuse dado"></i>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge <?php 
                            echo $historial['tipo'] === 'global' ? 'bg-primary' : 
                                 ($historial['tipo'] === 'local' ? 'bg-warning text-dark' : 'bg-info');
                          ?>">
                            <?php echo ucfirst($historial['tipo']); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($historial['creador_nombre']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($historial['created_at'])); ?></td>
                        <td>
                          <?php if ($historial['fecha_acuse']): ?>
                            <?php echo date('d/m/Y H:i', strtotime($historial['fecha_acuse'])); ?>
                          <?php else: ?>
                            <span class="text-muted">Pendiente</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php 
                          $dias_restantes = $historial['dias_visibilidad'] - floor((time() - strtotime($historial['created_at'])) / (60 * 60 * 24));
                          if ($dias_restantes > 0) {
                              echo "<span class='badge bg-success'>$dias_restantes días</span>";
                          } else {
                              echo "<span class='badge bg-secondary'>Expirado</span>";
                          }
                          ?>
                        </td>
                        <td>
                          <span class="badge <?php echo $historial['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $historial['activo'] ? 'Visible' : 'No visible'; ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </div>

          <!-- Formulario para crear comunicado (solo para roles permitidos) -->
          <?php if ($puedeCrearComunicados): ?>
          <hr class="my-5">
          <div class="row">
            <div class="col-lg-8 mx-auto">
              <h4 class="text-primary mb-4"><i class="fas fa-edit me-2"></i>Crear nuevo comunicado</h4>
              <form method="post" enctype="multipart/form-data" id="formComunicado">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="200">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="tipo" class="form-label">Tipo *</label>
                    <select class="form-select" id="tipo" name="tipo" required onchange="actualizarDestinatario()">
                      <option value="">Seleccionar tipo...</option>
                      <option value="global">🌐 Global (Todos los usuarios)</option>
                      <option value="local">🏢 Local (Mi área: <?php echo htmlspecialchars($usuario_actual['work_area']); ?>)</option>
                      <option value="personal">👤 Personal (Usuario específico)</option>
                    </select>
                  </div>
                </div>

                <div class="mb-3" id="destinatarioContainer" style="display: none;">
                  <label for="destinatario_id" class="form-label">Destinatario *</label>
                  <select class="form-select" id="destinatario_id" name="destinatario_id">
                    <option value="">Seleccionar usuario...</option>
                    <?php foreach ($usuarios_para_comunicados as $usuario): ?>
                      <option value="<?php echo $usuario['id']; ?>">
                        <?php echo htmlspecialchars($usuario['first_name'] . ' ' . $usuario['last_name'] . ' (' . $usuario['employee_id'] . ') - ' . $usuario['work_area']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="contenido" class="form-label">Contenido *</label>
                  <textarea class="form-control" id="contenido" name="contenido" rows="6" required 
                            placeholder="Escribe el contenido del comunicado aquí..."></textarea>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="imagen" class="form-label">Imagen (opcional)</label>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                    <div class="form-text">Formatos: JPG, PNG, GIF. Máx: 5MB</div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Opciones</label>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="requiere_acuse" name="requiere_acuse" value="1" checked>
                      <label class="form-check-label" for="requiere_acuse">
                        Requerir acuse de recibo
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                      <label class="form-check-label" for="activo">
                        Publicar inmediatamente
                      </label>
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="dias_visibilidad" class="form-label">Días de visibilidad</label>
                  <input type="number" class="form-control" id="dias_visibilidad" name="dias_visibilidad" value="7" min="1" max="30">
                  <div class="form-text">Número de días que estará visible el comunicado</div>
                </div>

                <div class="text-center">
                  <button type="submit" name="crear_comunicado" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i> Publicar Comunicado
                  </button>
                </div>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <div class="d-flex justify-content-end mt-4">
            <a href="./?vista=home" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver al Inicio
            </a>      
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para ver acuses -->
<div class="modal fade" id="modalAcuses" tabindex="-1" aria-labelledby="modalAcusesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAcusesLabel">
                    <i class="fas fa-list-check me-2"></i>Acuses de Recibo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="loadingAcuses" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando acuses...</p>
                </div>
                <div id="contenidoAcuses" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-primary">Usuario</th>
                                    <th class="text-primary">ID Empleado</th>
                                    <th class="text-primary">Área</th>
                                    <th class="text-primary">Rol</th>
                                    <th class="text-primary">Jefe Directo</th>
                                    <th class="text-primary">Fecha Acuse</th>
                                </tr>
                            </thead>
                            <tbody id="tablaAcuses">
                                <!-- Los acuses se cargarán aquí dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    <div id="sinAcuses" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay acuses de recibo registrados para este comunicado.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarDestinatario() {
  const tipo = document.getElementById('tipo').value;
  const container = document.getElementById('destinatarioContainer');
  const select = document.getElementById('destinatario_id');
  
  if (tipo === 'personal') {
    container.style.display = 'block';
    select.required = true;
  } else {
    container.style.display = 'none';
    select.required = false;
    select.value = '';
  }
}

function verAcuses(comunicadoId) {
    console.log('=== INICIANDO verAcuses ===');
    
    const modal = new bootstrap.Modal(document.getElementById('modalAcuses'));
    modal.show();
    
    document.getElementById('loadingAcuses').style.display = 'block';
    document.getElementById('contenidoAcuses').style.display = 'none';
    document.getElementById('sinAcuses').style.display = 'none';
    
    const formData = new FormData();
    formData.append('comunicado_id', comunicadoId);
    formData.append('user_id', <?php echo $usuario_actual['id']; ?>); // ← USER_ID MANUAL
    
    console.log('Enviando petición con user_id manual...');
    
    fetch('ajax/acuses.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos recibidos:', data);
        
        document.getElementById('loadingAcuses').style.display = 'none';
        
        if (data.success && data.acuses && data.acuses.length > 0) {
            const tabla = document.getElementById('tablaAcuses');
            tabla.innerHTML = '';
            
            data.acuses.forEach((acuse) => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td><strong>${acuse.usuario_nombre || 'N/A'}</strong></td>
                    <td>${acuse.employee_id || 'N/A'}</td>
                    <td><span class="badge bg-info">${acuse.work_area || 'N/A'}</span></td>
                    <td><span class="badge bg-secondary">${acuse.role || 'N/A'}</span></td>
                    <td>${acuse.jefe_nombre || 'No asignado'}</td>
                    <td><small>${acuse.fecha_acuse ? new Date(acuse.fecha_acuse).toLocaleString('es-ES') : 'N/A'}</small></td>
                `;
                tabla.appendChild(fila);
            });
            
            document.getElementById('contenidoAcuses').style.display = 'block';
        } else {
            document.getElementById('sinAcuses').style.display = 'block';
            if (data.message) {
                document.getElementById('sinAcuses').innerHTML += `<p class="text-warning">${data.message}</p>`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('loadingAcuses').style.display = 'none';
        document.getElementById('sinAcuses').style.display = 'block';
    });
}

// Inicializar el formulario
document.addEventListener('DOMContentLoaded', function() {
  actualizarDestinatario();
});
</script>

<style>
.nav-tabs .nav-link {
  font-weight: 500;
}
.nav-tabs .nav-link.active {
  font-weight: 600;
}
.card-header h5 {
  font-size: 1.1rem;
}
.badge {
  font-size: 0.75em;
}
.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
</style>
</body>
</html>