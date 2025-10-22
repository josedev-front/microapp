<?php
// microservices/trivia-play/views/player/join.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

$usuario_actual = null;
if (validarSesion()) {
    $usuario_actual = obtenerUsuarioActual();
}

require_once __DIR__ . '/../../init.php';

// Avatares predefinidos
$avatars = [
    'avatar1', 'avatar2', 'avatar3', 'avatar4', 'avatar5',
    'avatar6', 'avatar7', 'avatar8', 'avatar9', 'avatar10',
    'avatar11', 'avatar12', 'avatar13', 'avatar14', 'avatar15',
    'avatar16', 'avatar17', 'avatar18', 'avatar19', 'avatar20'
];

// Procesar unión a trivia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $triviaController = new TriviaPlay\Controllers\TriviaController();
        
        $playerData = [
            'user_id' => $usuario_actual['id'] ?? null,
            'player_name' => $_POST['player_name'],
            'team_name' => $_POST['team_name'] ?? null,
            'avatar' => $_POST['avatar']
        ];
        
        $result = $triviaController->joinTrivia($_POST['join_code'], $playerData);
        
        // Redirigir al lobby de espera del jugador
        header('Location: ' . BASE_URL . '?vista=trivia_waiting&player_id=' . $result['player_id']);
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Trivia - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .join-hero {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .avatar-option {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .avatar-option.selected {
            border-color: #4ECDC4;
            transform: scale(1.1);
        }
        .join-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .scan-section {
            background: rgba(0,0,0,0.05);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="join-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="join-card p-5">
                        <div class="row">
                            <!-- Formulario de unión -->
                            <div class="col-md-7">
                                <div class="mb-4 text-center">
                                    <i class="fas fa-gamepad fa-3x text-success mb-3"></i>
                                    <h1 class="h2 fw-bold text-dark">Unirse a Trivia</h1>
                                    <p class="text-muted">Ingresa el código o escanea el QR para unirte</p>
                                </div>

                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" id="joinForm">
                                    <!-- Código de unión -->
                                    <div class="mb-4">
                                        <label for="join_code" class="form-label fw-bold">Código de la Trivia</label>
                                        <input type="text" class="form-control form-control-lg text-uppercase text-center" 
                                               id="join_code" name="join_code" 
                                               placeholder="EJ: ABC123" maxlength="6" required
                                               style="font-size: 1.5rem; letter-spacing: 3px;">
                                        <div class="form-text">Ingresa el código de 6 letras proporcionado por el anfitrión</div>
                                    </div>

                                    <!-- Información del jugador -->
                                    <div class="mb-4">
                                        <label for="player_name" class="form-label fw-bold">Tu Nombre</label>
                                        <input type="text" class="form-control" 
                                               id="player_name" name="player_name" 
                                               placeholder="Ej: Juan Pérez" 
                                               value="<?php echo $usuario_actual['first_name'] . ' ' . $usuario_actual['last_name'] ?? ''; ?>"
                                               maxlength="50" required>
                                    </div>

                                    <!-- Selección de avatar -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Selecciona tu Avatar</label>
                                        <div class="d-flex flex-wrap gap-2" id="avatarSelection">
                                            <?php foreach ($avatars as $avatar): ?>
                                            <img src="<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/avatars/<?php echo $avatar; ?>.png" 
                                                 class="avatar-option" 
                                                 data-avatar="<?php echo $avatar; ?>"
                                                 alt="Avatar <?php echo $avatar; ?>">
                                            <?php endforeach; ?>
                                            <div class="avatar-option custom-avatar-upload" 
                                                 style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 2px dashed #dee2e6;">
                                                <i class="fas fa-camera text-muted"></i>
                                                <input type="file" id="customAvatarFile" accept="image/*" class="d-none">
                                            </div>
                                        </div>
                                        <input type="hidden" name="avatar" id="selectedAvatar" required>
                                    </div>

                                    <!-- Nombre de equipo (solo para modalidad equipos) -->
                                    <div class="mb-4" id="teamNameSection" style="display: none;">
                                        <label for="team_name" class="form-label fw-bold">Nombre del Equipo</label>
                                        <input type="text" class="form-control" 
                                               id="team_name" name="team_name" 
                                               placeholder="Ej: Los Campeones" maxlength="30">
                                        <div class="form-text">Opcional - el anfitrión puede asignarte un equipo</div>
                                    </div>

                                    <!-- Botón de unirse -->
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Unirse a la Trivia
                                        </button>
                                    </div>
                                </form>

                                <!-- Volver al inicio -->
                                <div class="text-center mt-3">
                                    <a href="<?php echo BASE_URL; ?>?vista=trivia" class="text-decoration-none">
                                        <i class="fas fa-arrow-left me-2"></i>Volver al inicio
                                    </a>
                                </div>
                            </div>

                            <!-- Sección de escaneo -->
                            <div class="col-md-5">
                                <div class="scan-section h-100">
                                    <i class="fas fa-qrcode fa-5x text-muted mb-3"></i>
                                    <h5 class="text-dark mb-3">Escanea el Código QR</h5>
                                    <p class="text-muted small mb-4">
                                        Si el anfitrión te proporcionó un código QR, escanéalo con tu cámara para unirte automáticamente.
                                    </p>
                                    
                                    <!-- Lector QR simulado -->
                                    <div class="border rounded p-4 bg-white mb-3">
                                        <div class="text-center">
                                            <i class="fas fa-camera fa-2x text-primary mb-2"></i>
                                            <p class="small text-muted mb-2">Apunta la cámara al código QR</p>
                                            <button class="btn btn-outline-primary btn-sm" id="simulateQrScan">
                                                Simular Escaneo QR
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Información adicional -->
                                    <div class="mt-4">
                                        <h6 class="text-dark mb-3">¿Primera vez jugando?</h6>
                                        <div class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <small class="text-muted">No necesitas cuenta para jugar</small>
                                        </div>
                                        <div class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <small class="text-muted">Respuestas en tiempo real</small>
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <small class="text-muted">Compatible con móviles</small>
                                        </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const joinForm = document.getElementById('joinForm');
            const avatarSelection = document.getElementById('avatarSelection');
            const selectedAvatarInput = document.getElementById('selectedAvatar');
            const teamNameSection = document.getElementById('teamNameSection');
            const joinCodeInput = document.getElementById('join_code');
            const simulateQrScan = document.getElementById('simulateQrScan');

            // Auto-seleccionar primer avatar
            const firstAvatar = avatarSelection.querySelector('.avatar-option:not(.custom-avatar-upload)');
            if (firstAvatar) {
                firstAvatar.click();
            }

            // Selección de avatar
            avatarSelection.querySelectorAll('.avatar-option').forEach(avatar => {
                avatar.addEventListener('click', function() {
                    avatarSelection.querySelectorAll('.avatar-option').forEach(a => a.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    if (this.classList.contains('custom-avatar-upload')) {
                        document.getElementById('customAvatarFile').click();
                    } else {
                        selectedAvatarInput.value = this.dataset.avatar;
                    }
                });
            });

            // Subir avatar personalizado
            document.getElementById('customAvatarFile').addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (!file.type.startsWith('image/')) {
                        alert('Por favor selecciona una imagen válida');
                        return;
                    }
                    
                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen no debe superar los 2MB');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const customAvatar = document.querySelector('.custom-avatar-upload');
                        customAvatar.style.backgroundImage = `url(${e.target.result})`;
                        customAvatar.innerHTML = '';
                        selectedAvatarInput.value = 'custom_' + Date.now();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Validar código (solo letras y números)
            joinCodeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            // Simular escaneo QR
            simulateQrScan.addEventListener('click', function() {
                const sampleCodes = ['TR1V14', 'G4M3FUN', 'QU1Z123', 'PLAYNOW'];
                const randomCode = sampleCodes[Math.floor(Math.random() * sampleCodes.length)];
                joinCodeInput.value = randomCode;
                
                // Mostrar mensaje
                const originalText = simulateQrScan.innerHTML;
                simulateQrScan.innerHTML = '<i class="fas fa-check me-2"></i>Escaneado!';
                simulateQrScan.classList.remove('btn-outline-primary');
                simulateQrScan.classList.add('btn-success');
                
                setTimeout(function() {
                    simulateQrScan.innerHTML = originalText;
                    simulateQrScan.classList.remove('btn-success');
                    simulateQrScan.classList.add('btn-outline-primary');
                }, 2000);
            });

            // Validar formulario
            joinForm.addEventListener('submit', function(e) {
                if (!selectedAvatarInput.value) {
                    e.preventDefault();
                    alert('Por favor selecciona un avatar');
                    return;
                }
                
                if (joinCodeInput.value.length !== 6) {
                    e.preventDefault();
                    alert('El código debe tener exactamente 6 caracteres');
                    return;
                }
            });

            // Detectar si es móvil para mejor UX
            if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                document.querySelector('.custom-avatar-upload').innerHTML = '<i class="fas fa-camera text-muted"></i>';
            }
        });
    </script>
</body>
</html>