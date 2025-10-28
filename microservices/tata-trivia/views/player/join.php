<?php
// microservices/tata-trivia/views/player/join.php - VERSIÓN CORREGIDA

$user = getTriviaMicroappsUser();
$error = null;
$success = false;

// Si ya tiene player_id en sesión, redirigir al juego
if (isset($_SESSION['player_id']) && isset($_SESSION['trivia_id'])) {
    header('Location: /microservices/tata-trivia/player/game?trivia_id=' . $_SESSION['trivia_id']);
    exit;
}

// Procesar unión al juego
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $join_code = strtoupper(trim($_POST['join_code'] ?? ''));
        $player_name = trim($_POST['player_name'] ?? '');
        $avatar = $_POST['avatar'] ?? 'default1';
        $team_name = trim($_POST['team_name'] ?? '');
        
        if (empty($join_code)) {
            throw new Exception('El código de unión es requerido');
        }
        
        if (empty($player_name)) {
            throw new Exception('El nombre del jugador es requerido');
        }
        
        if (strlen($join_code) !== 6) {
            throw new Exception('El código debe tener 6 caracteres');
        }
        
        // Validar código directamente con la base de datos
        $db = getTriviaDatabaseConnection();
        if (!$db) {
            throw new Exception('Error de conexión a la base de datos');
        }
        
        $stmt = $db->prepare("
            SELECT id, title, status, game_mode, theme 
            FROM trivias 
            WHERE join_code = ? AND status IN ('setup', 'waiting', 'active')
        ");
        $stmt->execute([$join_code]);
        $trivia = $stmt->fetch();
        
        if (!$trivia) {
            throw new Exception('Código inválido o juego no disponible');
        }
        
        $trivia_id = $trivia['id'];
        
        // Verificar si el jugador ya existe en esta trivia
        $stmt = $db->prepare("
            SELECT id FROM players 
            WHERE trivia_id = ? AND player_name = ?
        ");
        $stmt->execute([$trivia_id, $player_name]);
        $existing_player = $stmt->fetch();
        
        if ($existing_player) {
            // Jugador ya existe, usar ese ID
            $player_id = $existing_player['id'];
            
            // Actualizar datos del jugador (sin work_area si no existe)
            try {
                $stmt = $db->prepare("
                    UPDATE players 
                    SET avatar = ?, team_name = ?, user_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$avatar, $team_name, $user['id'] ?? null, $player_id]);
            } catch (PDOException $e) {
                // Si falla por work_area, intentar sin work_area
                $stmt = $db->prepare("
                    UPDATE players 
                    SET avatar = ?, team_name = ?, user_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$avatar, $team_name, $user['id'] ?? null, $player_id]);
            }
            
        } else {
            // Crear nuevo jugador (sin work_area si no existe)
            try {
                $stmt = $db->prepare("
                    INSERT INTO players 
                    (trivia_id, user_id, player_name, team_name, avatar, work_area, score, join_time)
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([
                    $trivia_id, 
                    $user['id'] ?? null, 
                    $player_name, 
                    $team_name, 
                    $avatar, 
                    $user['work_area'] ?? ''
                ]);
            } catch (PDOException $e) {
                // Si falla por work_area, intentar sin work_area
                $stmt = $db->prepare("
                    INSERT INTO players 
                    (trivia_id, user_id, player_name, team_name, avatar, score, join_time)
                    VALUES (?, ?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([
                    $trivia_id, 
                    $user['id'] ?? null, 
                    $player_name, 
                    $team_name, 
                    $avatar
                ]);
            }
            
            $player_id = $db->lastInsertId();
        }
        
        // Guardar en sesión
        $_SESSION['player_id'] = $player_id;
        $_SESSION['trivia_id'] = $trivia_id;
        $_SESSION['player_name'] = $player_name;
        $_SESSION['player_avatar'] = $avatar;
        
        // Redirigir al juego
        header('Location: /microservices/tata-trivia/player/game?trivia_id=' . $trivia_id);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error en join.php: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Trivia - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .join-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .join-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .avatar-option {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
            object-fit: cover;
        }
        .avatar-option:hover {
            border-color: #007bff;
            transform: scale(1.1);
        }
        .avatar-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
        }
        .code-input {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5rem;
            text-transform: uppercase;
        }
        .loading-spinner {
            display: none;
        }
    </style>
</head>
<body class="join-container" style="background-image: url('<?php echo get_theme_image('TRIVIA.png'); ?>'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="join-card">
                    <div class="card-body p-4">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h1 class="text-primary">
                                <i class="fas fa-gamepad me-2"></i>Unirse a Trivia
                            </h1>
                            <p class="text-muted">Ingresa el código para unirte al juego</p>
                        </div>

                        <!-- Mensajes de error -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" id="joinForm">
                            <!-- Código de Unión -->
                            <div class="mb-4">
                                <label for="join_code" class="form-label fw-bold">
                                    <i class="fas fa-qrcode me-2"></i>Código de Unión
                                </label>
                                <input type="text" class="form-control form-control-lg code-input" 
                                       id="join_code" name="join_code" 
                                       placeholder="ABCDEF" maxlength="6"
                                       required style="text-transform: uppercase;">
                                <div class="form-text">Ingresa el código de 6 letras proporcionado por el anfitrión</div>
                            </div>

                            <!-- Nombre del Jugador -->
                            <div class="mb-4">
                                <label for="player_name" class="form-label fw-bold">
                                    <i class="fas fa-user me-2"></i>Tu Nombre
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="player_name" name="player_name" 
                                       placeholder="Ej: Juan Pérez"
                                       required value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                            </div>

                            <!-- Avatar -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user-circle me-2"></i>Selecciona tu Avatar
                                </label>
                                <div class="row text-center">
                                    <?php
                                    $avatars = ['default1', 'default2', 'default3', 'default4', 'default5', 'default6'];
                                    foreach ($avatars as $avatar): 
                                    ?>
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="/public/assets/img/default/<?php echo $avatar; ?>.png" 
                                             class="avatar-option <?php echo $avatar === 'default1' ? 'selected' : ''; ?>"
                                             data-avatar="<?php echo $avatar; ?>"
                                             onclick="selectAvatar('<?php echo $avatar; ?>')">
                                        <input type="radio" name="avatar" value="<?php echo $avatar; ?>" 
                                               <?php echo $avatar === 'default1' ? 'checked' : ''; ?> 
                                               style="display: none;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Nombre del Equipo (opcional) -->
                            <div class="mb-4">
                                <label for="team_name" class="form-label fw-bold">
                                    <i class="fas fa-users me-2"></i>Nombre del Equipo (Opcional)
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="team_name" name="team_name" 
                                       placeholder="Ej: Los Campeones">
                                <div class="form-text">Solo si es competencia por equipos</div>
                            </div>

                            <!-- Información del Usuario -->
                            <?php if ($user && $user['id']): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Conectado como:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                <?php if (!empty($user['work_area'])): ?>
                                    <br><small>Área: <?php echo htmlspecialchars($user['work_area']); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Botones -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-play me-2"></i>Unirse al Juego
                                </button>
                                <div class="text-center loading-spinner" id="loadingSpinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2">Validando código...</p>
                                </div>
                                <a href="/microservices/tata-trivia/" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al Inicio
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Selección de avatar
        function selectAvatar(avatar) {
            // Remover selección anterior
            document.querySelectorAll('.avatar-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Seleccionar nuevo avatar
            document.querySelector(`[data-avatar="${avatar}"]`).classList.add('selected');
            
            // Marcar el radio button
            document.querySelector(`input[value="${avatar}"]`).checked = true;
        }
        
        // Validación del código (solo letras y números, 6 caracteres)
        document.getElementById('join_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
        });
        
        // Validación del formulario
        document.getElementById('joinForm').addEventListener('submit', function(e) {
            const joinCode = document.getElementById('join_code').value.trim();
            const playerName = document.getElementById('player_name').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            if (joinCode.length !== 6) {
                e.preventDefault();
                alert('El código debe tener exactamente 6 caracteres');
                document.getElementById('join_code').focus();
                return;
            }
            
            if (!playerName) {
                e.preventDefault();
                alert('Por favor ingresa tu nombre');
                document.getElementById('player_name').focus();
                return;
            }
            
            // Mostrar loading
            submitBtn.style.display = 'none';
            loadingSpinner.style.display = 'block';
        });
        
        // Auto-focus en el código
        document.getElementById('join_code').focus();
    </script>
</body>
</html>