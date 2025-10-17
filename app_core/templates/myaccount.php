<?php
// app_core/templates/myaccount.php

// Obtener datos del usuario actual
$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ./?vista=login');
    exit;
}

// Función para obtener la URL del avatar
function obtenerAvatarUrl($usuario) {
    // Verificar si tiene avatar personalizado y si el archivo existe
    if (!empty($usuario['avatar'])) {
        $avatar_relative_path = 'assets/avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'];
        $full_path = __DIR__ . '/../../public/' . $avatar_relative_path;
        
        // Verificar si el archivo realmente existe
        if (file_exists($full_path)) {
            return ASSETS_URL . 'avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'] . '?v=' . filemtime($full_path);
        }
    }
    
    // Si no tiene avatar personalizado o el archivo no existe, usar el predefinido
    $avatar_num = str_replace('default', '', $usuario['avatar_predefinido'] ?? 'default1');
    return ASSETS_URL . 'img/default/default' . $avatar_num . '.png';
}

// Función para obtener las iniciales del avatar
function obtenerInicialesAvatar($usuario) {
    $iniciales = '';
    if (!empty($usuario['first_name'])) {
        $iniciales .= strtoupper(substr($usuario['first_name'], 0, 1));
    }
    if (!empty($usuario['last_name'])) {
        $iniciales .= strtoupper(substr($usuario['last_name'], 0, 1));
    }
    return $iniciales ?: 'US';
}

// Función para verificar si tiene imagen de perfil
function tieneImagenPerfil($usuario) {
    if (!empty($usuario['avatar'])) {
        $avatar_relative_path = 'assets/avatares_usuarios/user_' . $usuario['id'] . '/' . $usuario['avatar'];
        $full_path = __DIR__ . '/../../public/' . $avatar_relative_path;
        return file_exists($full_path);
    }
    return false;
}

// Función para formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha) || $fecha === '0000-00-00') {
        return 'No especificada';
    }
    return date('d/m/Y', strtotime($fecha));
}

// Función para obtener nombre del jefe
function obtenerNombreJefe($manager_id) {
    if (!$manager_id) {
        return 'No especificado';
    }
    
    $pdo = conexion();
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM core_customuser WHERE id = ?");
        $stmt->execute([$manager_id]);
        $jefe = $stmt->fetch();
        
        if ($jefe) {
            return htmlspecialchars($jefe['first_name'] . ' ' . $jefe['last_name']);
        }
    } catch (Exception $e) {
        error_log("Error al obtener jefe: " . $e->getMessage());
    }
    
    return "ID: " . $manager_id;
}

// Función para formatear género
function formatearGenero($genero) {
    switch ($genero) {
        case 'M': return 'Masculino';
        case 'F': return 'Femenino';
        case 'O': return 'Otro';
        default: return 'No especificado';
    }
}

// Función para formatear rol
function formatearRol($rol) {
    return ucfirst(str_replace('_', ' ', $rol));
}
?>

<div class="container py-5" style="margin-top: 10%; margin-bottom: 10%;">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">

          <!-- Encabezado con avatar -->
          <div class="d-flex align-items-center mb-4 flex-column flex-md-row text-center text-md-start">
            <?php if (tieneImagenPerfil($usuario_actual)): ?>
              <!-- Mostrar imagen de perfil -->
              <img src="<?php echo obtenerAvatarUrl($usuario_actual); ?>" 
                   alt="Avatar de <?php echo htmlspecialchars($usuario_actual['first_name'] . ' ' . $usuario_actual['last_name']); ?>" 
                   class="rounded-circle me-md-3 mb-3 mb-md-0"
                   style="width: 90px; height: 90px; object-fit: cover; border: 3px solid #007bff;">
            <?php else: ?>
              <!-- Mostrar iniciales -->
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-md-3 mb-3 mb-md-0"
                   style="width: 90px; height: 90px; font-size: 32px; font-weight: bold;">
                <?php echo obtenerInicialesAvatar($usuario_actual); ?>
              </div>
            <?php endif; ?>
            
            <div class="ms-md-3">
              <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($usuario_actual['first_name'] . ' ' . $usuario_actual['last_name']); ?></h4>
              <p class="text-muted mb-1"><?php echo formatearRol($usuario_actual['role']); ?></p>
              <p class="text-muted small"><i class="fas fa-id-badge me-1"></i> ID: <?php echo htmlspecialchars($usuario_actual['employee_id']); ?></p>
              <?php if (tieneImagenPerfil($usuario_actual)): ?>
                <p class="text-success small mb-0">
                  <i class="fas fa-check-circle me-1"></i>Imagen de perfil personalizada
                </p>
              <?php else: ?>
                <p class="text-muted small mb-0">
                  <i class="fas fa-user-circle me-1"></i>Avatar predefinido
                </p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Información personal -->
          <h5 class="fw-bold mb-3 text-primary">Información personal</h5>
          <div class="table-responsive">
            <table class="table table-borderless table-hover">
              <tbody>
                <tr>
                  <th class="text-muted" width="35%">Nombre</th>
                  <td><?php echo htmlspecialchars($usuario_actual['first_name']); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Segundo Nombre</th>
                  <td><?php echo htmlspecialchars($usuario_actual['middle_name'] ?: 'No especificado'); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Apellido</th>
                  <td><?php echo htmlspecialchars($usuario_actual['last_name']); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Segundo Apellido</th>
                  <td><?php echo htmlspecialchars($usuario_actual['second_last_name'] ?: 'No especificado'); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Correo electrónico</th>
                  <td>
                    <a href="mailto:<?php echo htmlspecialchars($usuario_actual['email']); ?>" class="text-decoration-none">
                      <?php echo htmlspecialchars($usuario_actual['email']); ?>
                    </a>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Usuario</th>
                  <td>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($usuario_actual['username']); ?></span>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Número de teléfono</th>
                  <td>
                    <?php if (!empty($usuario_actual['phone_number'])): ?>
                      <a href="tel:<?php echo htmlspecialchars($usuario_actual['phone_number']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($usuario_actual['phone_number']); ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">No especificado</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Fecha de cumpleaños</th>
                  <td><?php echo formatearFecha($usuario_actual['birth_date']); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Género</th>
                  <td><?php echo formatearGenero($usuario_actual['gender']); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Área de trabajo</th>
                  <td>
                    <?php if (!empty($usuario_actual['work_area'])): ?>
                      <span class="badge bg-info text-dark"><?php echo htmlspecialchars($usuario_actual['work_area']); ?></span>
                    <?php else: ?>
                      <span class="text-muted">No especificada</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Jefe directo</th>
                  <td><?php echo obtenerNombreJefe($usuario_actual['manager_id']); ?></td>
                </tr>
                <tr>
                  <th class="text-muted">Estado</th>
                  <td>
                    <?php if ($usuario_actual['is_active']): ?>
                      <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                    <?php else: ?>
                      <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Último acceso</th>
                  <td>
                    <?php if ($usuario_actual['last_login']): ?>
                      <?php echo date('d/m/Y H:i', strtotime($usuario_actual['last_login'])); ?>
                    <?php else: ?>
                      <span class="text-muted">Nunca</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Fecha de registro</th>
                  <td>
                    <?php if ($usuario_actual['date_joined']): ?>
                      <?php echo date('d/m/Y H:i', strtotime($usuario_actual['date_joined'])); ?>
                    <?php else: ?>
                      <span class="text-muted">No disponible</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="text-muted">Tipo de avatar</th>
                  <td>
                    <?php if (tieneImagenPerfil($usuario_actual)): ?>
                      <span class="badge bg-success">
                        <i class="fas fa-image me-1"></i>Personalizado
                      </span>
                    <?php else: ?>
                      <span class="badge bg-primary">
                        <i class="fas fa-user me-1"></i>Predefinido
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Acciones rápidas -->
          <div class="mt-4 pt-3 border-top">
            <h5 class="fw-bold mb-3 text-primary">Acciones rápidas</h5>
            <div class="d-flex flex-wrap gap-2">
              <a href="./?vista=myconfig" class="btn btn-outline-primary rounded-pill">
                <i class="fas fa-cog me-2"></i>Configuración de cuenta
              </a>
              <a href="./?vista=equiposuser" class="btn btn-outline-secondary rounded-pill">
                <i class="fas fa-users me-2"></i>Ver equipo
              </a>
              <a href="./?vista=home" class="btn btn-outline-info rounded-pill">
                <i class="fas fa-home me-2"></i>Volver al inicio
              </a>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<style>
.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
.table td {
  background-color: #fff;
}
.badge {
  font-size: 0.8em;
}
.rounded-circle {
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
</style>