<?php
// microservices/trivia-play/views/welcome.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

// Verificar sesión básica (opcional)
$usuario_actual = null;
if (validarSesion()) {
    $usuario_actual = obtenerUsuarioActual();
}

require_once __DIR__ . '/../init.php';
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tata Trivia - ¡Diversión asegurada!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .trivia-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .trivia-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .btn-trivia {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        .btn-host {
            background: linear-gradient(45deg, #FF6B6B, #FF8E53);
            border: none;
        }
        .btn-join {
            background: linear-gradient(45deg, #4ECDC4, #44A08D);
            border: none;
        }
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        .avatar-option {
            cursor: pointer;
            border: 3px solid transparent;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .avatar-option.selected {
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="trivia-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="trivia-card p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-trophy fa-4x text-warning mb-3"></i>
                            <h1 class="display-4 fw-bold text-dark">Tata Trivia</h1>
                            <p class="lead text-muted">¡Crea y participa en trivias emocionantes!</p>
                        </div>

                        <div class="row g-4">
                            <!-- Anfitrión -->
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex flex-column">
                                        <i class="fas fa-crown fa-3x text-warning mb-3"></i>
                                        <h3 class="card-title">Ser Anfitrión</h3>
                                        <p class="card-text flex-grow-1">Crea tu propia trivia personalizada con preguntas, temas y modos de juego.</p>
                                        <a href="/microservices/tata-trivia/host/setup" class="btn btn-option btn-host">
                                            <i class="fas fa-plus me-2"></i>Crear Trivia
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Unirse -->
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex flex-column">
                                        <i class="fas fa-gamepad fa-3x text-success mb-3"></i>
                                        <h3 class="card-title">Unirse al Juego</h3>
                                        <p class="card-text flex-grow-1">Participa en trivias existentes usando código o escaneando QR.</p>
                                        <a href="/microservices/tata-trivia/player/join" class="btn btn-option btn-join">
                                            <i class="fas fa-play me-2"></i>Jugar Ahora
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial (solo para usuarios logueados) -->
                        <?php if ($usuario_actual): ?>
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="<?php echo BASE_URL; ?>?vista=trivia_player_history" class="btn btn-outline-primary">
                                        <i class="fas fa-history me-2"></i>Mis Partidas
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>?vista=trivia_host_history" class="btn btn-outline-warning">
                                        <i class="fas fa-crown me-2"></i>Mis Trivias Creadas
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Información adicional -->
                        <div class="row mt-5 text-start">
                            <div class="col-md-4">
                                <div class="d-flex">
                                    <i class="fas fa-mobile-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <h6>Totalmente Responsivo</h6>
                                        <small class="text-muted">Juega desde cualquier dispositivo</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex">
                                    <i class="fas fa-bolt fa-2x text-warning me-3"></i>
                                    <div>
                                        <h6>Tiempo Real</h6>
                                        <small class="text-muted">Resultados instantáneos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex">
                                    <i class="fas fa-users fa-2x text-success me-3"></i>
                                    <div>
                                        <h6>Multijugador</h6>
                                        <small class="text-muted">Hasta 8 equipos o individual</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>