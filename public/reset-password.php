<?php
// public/reset-password.php

require_once __DIR__ . '/../app_core/config/database.php';
require_once __DIR__ . '/../app_core/config/helpers.php';
require_once __DIR__ . '/../app_core/controllers/UserController.php'; // Agregar esta línea

$mensaje = '';
$tipo_mensaje = '';
$token_valido = false;
$email = '';

// Verificar token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $pdo = conexion();
        
        // Verificar token válido y no expirado
        $stmt = $pdo->prepare("
            SELECT email, expires_at, used 
            FROM password_reset_tokens 
            WHERE token = ? AND used = 0 AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch();
        
        if ($token_data) {
            $token_valido = true;
            $email = $token_data['email'];
        } else {
            $mensaje = "El enlace de recuperación es inválido o ha expirado.";
            $tipo_mensaje = 'danger';
        }
        
    } catch (Exception $e) {
        error_log("Error verificando token: " . $e->getMessage());
        $mensaje = "Error al verificar el enlace.";
        $tipo_mensaje = 'danger';
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = 'danger';
    } else {
        try {
            $pdo = conexion();
            
            // Verificar token nuevamente
            $stmt = $pdo->prepare("
                SELECT email, expires_at, used 
                FROM password_reset_tokens 
                WHERE token = ? AND used = 0 AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $token_data = $stmt->fetch();
            
            if ($token_data) {
                // Usar el método del UserController para generar el hash
                $hashed_password = UserController::generatePasswordHash($password);
                
                // Actualizar contraseña del usuario
                $stmt = $pdo->prepare("UPDATE core_customuser SET password = ? WHERE email = ?");
                $result = $stmt->execute([$hashed_password, $token_data['email']]);
                
                if ($result) {
                    // Marcar token como usado
                    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                    $stmt->execute([$token]);
                    
                    $mensaje = "¡Contraseña actualizada correctamente! Ahora puedes iniciar sesión con tu nueva contraseña.";
                    $tipo_mensaje = 'success';
                    $token_valido = false;
                } else {
                    $mensaje = "Error al actualizar la contraseña.";
                    $tipo_mensaje = 'danger';
                }
                
            } else {
                $mensaje = "El enlace de recuperación es inválido o ha expirado.";
                $tipo_mensaje = 'danger';
            }
            
        } catch (Exception $e) {
            error_log("Error actualizando contraseña: " . $e->getMessage());
            $mensaje = "Error al actualizar la contraseña.";
            $tipo_mensaje = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - MicroApps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <img src="<?php echo ASSETS_URL; ?>img/microapps-simbol.png" alt="MicroApps" width="80" class="mb-3">
                        <h4 class="mb-0">Nueva Contraseña</h4>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($token_valido && empty($mensaje)): ?>
                        <p class="text-muted mb-4">
                            Hola, ingresa tu nueva contraseña para la cuenta <strong><?php echo htmlspecialchars($email); ?></strong>
                        </p>

                        <form method="post">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Ingresa nueva contraseña" required minlength="6">
                                </div>
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirma tu contraseña" required minlength="6">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                        <?php elseif (!$token_valido && empty($mensaje)): ?>
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-muted">Token no válido o expirado.</p>
                            <a href="forgot-password.php" class="btn btn-primary">
                                Solicitar nuevo enlace
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($tipo_mensaje === 'success'): ?>
                        <div class="text-center mt-3">
                            <a href="./?vista=login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i>Ir al Login
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center mt-4">
                            <a href="./?vista=login" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Login
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    password.focus();
                }
            });
        }
    });
    </script>
</body>
</html>