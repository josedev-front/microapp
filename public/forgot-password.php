<?php
// public/forgot-password.php

require_once __DIR__ . '/../app_core/config/database.php';
require_once __DIR__ . '/../app_core/config/helpers.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        $pdo = conexion();
        
        // Verificar si el email existe
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM core_customuser WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Verificar si la tabla password_reset_tokens existe
            try {
                $pdo->query("SELECT 1 FROM password_reset_tokens LIMIT 1");
            } catch (Exception $e) {
                // La tabla no existe
                $mensaje = "Error del sistema: La tabla de tokens no está configurada. Contacta al administrador.";
                $tipo_mensaje = 'danger';
                error_log("Tabla password_reset_tokens no existe: " . $e->getMessage());
            }
            
            if (empty($mensaje)) {
                // Generar token único
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Guardar token en la base de datos
                $stmt = $pdo->prepare("
                    INSERT INTO password_reset_tokens (email, token, expires_at) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$email, $token, $expires_at]);
                
                // Enviar email con PHPMailer
                $reset_link = "http://localhost:3000/public/reset-password.php?token=" . $token;
                $nombre_completo = $usuario['first_name'] . ' ' . $usuario['last_name'];
                
                if (enviarEmailRecuperacion($email, $nombre_completo, $reset_link)) {
                    $mensaje = "Se ha enviado un enlace de recuperación a <strong>{$email}</strong>. Revisa tu bandeja de entrada y la carpeta de spam.";
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = "Error al enviar el email. Por favor, contacta al administrador del sistema.";
                    $tipo_mensaje = 'danger';
                }
            }
            
        } else {
            $mensaje = "No se encontró una cuenta activa con ese email.";
            $tipo_mensaje = 'danger';
        }
        
    } catch (Exception $e) {
        error_log("Error en recuperación: " . $e->getMessage());
        $mensaje = "Error al procesar la solicitud. Intenta nuevamente.";
        $tipo_mensaje = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - MicroApps</title>
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
        .alert ul {
            margin-bottom: 0;
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
                        <h4 class="mb-0">Recuperar Contraseña</h4>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <p class="text-muted mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Ingresa tu dirección de email registrada y te enviaremos un enlace seguro para restablecer tu contraseña.
                        </p>

                        <form method="post" id="recoveryForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="tu@email.com" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div class="form-text">
                                    Debe ser el mismo email que usas para iniciar sesión en MicroApps.
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Enlace de Recuperación
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Seguridad</h6>
                            <small class="text-muted">
                                • El enlace expira en 1 hora<br>
                                • Solo es válido para un uso<br>
                                • Tus datos están protegidos
                            </small>
                        </div>

                        <div class="text-center mt-4">
                            <a href="./?vista=login" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('recoveryForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            submitBtn.disabled = true;
        });
    });
    </script>
</body>
</html>