<?php
require_once __DIR__ . '/../app_core/php/main.php';
require_once __DIR__ . '/../app_core/controllers/UserController.php'; // Agregar esta línea

// Si ya está logueado, redirigir
if (validarSesion()) {
    redireccionar('index.php?vista=home');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = limpiarCadena($_POST['username'] ?? '');
    $clave = limpiarCadena($_POST['clave'] ?? '');

    if ($username && $clave) {
        $pdo = conexion();
        $stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if ($usuario && UserController::verifyPassword($clave, $usuario['password'])) {
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['first_name'] = $usuario['first_name'];
            $_SESSION['work_area'] = $usuario['work_area'];
            $_SESSION['role'] = $usuario['role']; // Agregar el rol también
            setFlash('success', 'Bienvenido, ' . $usuario['first_name']);
            redireccionar('index.php?vista=home');
        } else {
            setFlash('danger', 'Usuario o contraseña incorrectos.');
        }
    } else {
        setFlash('warning', 'Por favor ingresa tus credenciales.');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - MicroApps</title>
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
                        <h4 class="mb-0">Iniciar Sesión</h4>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($flash): ?>
                            <div class="alert alert-<?= $flash['tipo'] ?> alert-dismissible fade show" role="alert">
                                <?= $flash['mensaje'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="username" id="username" class="form-control" placeholder="Tu nombre de usuario" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="clave" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" name="clave" id="clave" class="form-control" placeholder="Tu contraseña" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <a href="forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Información</h6>
                            <small class="text-muted">
                                • Usa tu nombre de usuario o email<br>
                                • El sistema acepta contraseñas antiguas y nuevas<br>
                                • Contacta al administrador si tienes problemas
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>