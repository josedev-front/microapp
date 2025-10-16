<?php
require_once __DIR__ . '/../app_core/php/main.php';

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
        $stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE username = ?");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if ($usuario && verificarPasswordDjango($clave, $usuario['password'])) {
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['first_name'] = $usuario['first_name'];
            $_SESSION['work_area'] = $usuario['work_area'];
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
</head>
<body class="bg-dark text-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="text-center mb-4">Iniciar sesión</h4>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= $flash['tipo'] ?>"><?= $flash['mensaje'] ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <input type="password" name="clave" id="clave" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
