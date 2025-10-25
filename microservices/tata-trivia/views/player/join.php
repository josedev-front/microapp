<?php
$user = $user ?? getTriviaMicroappsUser();
$join_code = $_GET['code'] ?? '';

// Si ya viene con código en la URL, redirigir directamente al formulario
if (!empty($join_code)) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { document.getElementById('joinCode').value = '" . htmlspecialchars($join_code) . "'; validateCode(); });</script>";
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .join-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .join-header {
            background: #343a40;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .join-body {
            padding: 2rem;
        }
        .avatar-option {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            object-fit: cover;
        }
        .avatar-option:hover {
            transform: scale(1.1);
        }
        .avatar-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .btn-join {
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        .game-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-card">
            <div class="join-header">
                <h1 class="h3 mb-0">
                    <i class="fas fa-gamepad me-2"></i>
                    Unirse a Trivia
                </h1>
                <p class="mb-0 mt-2">¡Ingresa el código y prepárate para jugar!</p>
            </div>
            
            <div class="join-body">
                <!-- Paso 1: Ingresar código -->
                <div class="step active" id="step1">
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-3x text-primary mb-3"></i>
                        <h4>Ingresa el Código</h4>
                        <p class="text-muted">El anfitrión te debe haber compartido un código de 6 letras</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="joinCode" class="form-label">Código de la Trivia</label>
                        <input type="text" class="form-control form-control-lg text-uppercase text-center" 
                               id="joinCode" placeholder="EJ: ABC123" maxlength="6"
                               style="font-size: 1.5rem; font-weight: bold; letter-spacing: 2px;"
                               oninput="this.value = this.value.toUpperCase(); validateCode()">
                        <div class="form-text">Ingresa el código en mayúsculas</div>
                    </div>
                    
                    <div id="gameInfo" class="game-info" style="display: none;">
                        <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Información de la Trivia</h6>
                        <div id="gameDetails"></div>
                    </div>
                    
                    <button class="btn btn-primary btn-join w-100" id="btnNext1" onclick="showStep(2)" disabled>
                        Continuar <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="/microservices/tata-trivia/" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>Volver al inicio
                        </a>
                    </div>
                </div>

                <!-- Paso 2: Información del jugador -->
                <div class="step" id="step2">
                    <div class="text-center mb-4">
                        <i class="fas fa-user fa-3x text-primary mb-3"></i>
                        <h4>Tu Información</h4>
                        <p class="text-muted">Completa tus datos para unirte al juego</p>
                    </div>
                    
                    <form id="playerForm">
                        <div class="mb-3">
                            <label for="playerName" class="form-label">Tu Nombre *</label>
                            <input type="text" class="form-control" id="playerName" 
                                   placeholder="Ej: Juan Pérez" required
                                   value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                        </div>
                        
                        <div class="mb-3" id="teamField" style="display: none;">
                            <label for="teamName" class="form-label">Nombre del Equipo</label>
                            <input type="text" class="form-control" id="teamName" 
                                   placeholder="Ej: Los Ganadores">
                            <div class="form-text">Opcional - Solo para modo por equipos</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Selecciona tu Avatar</label>
                            <div class="row text-center">
                                <?php
                                $avatars = ['default1', 'default2', 'default3', 'default4', 'default5', 'default6'];
                                for ($i = 1; $i <= 20; $i++):
                                    $avatarName = 'default' . (($i - 1) % 6 + 1);
                                ?>
                                <div class="col-3 col-sm-2 mb-2">
                                    <img src="/public/assets/img/default/<?php echo $avatarName; ?>.png" 
                                         class="avatar-option" 
                                         data-avatar="<?php echo $avatarName; ?>"
                                         onclick="selectAvatar(this)"
                                         alt="Avatar <?php echo $i; ?>">
                                </div>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="selectedAvatar" name="selectedAvatar" value="default1">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="showStep(1)">
                                <i class="fas fa-arrow-left me-1"></i>Atrás
                            </button>
                            <button type="submit" class="btn btn-success flex-fill" id="btnJoin">
                                <i class="fas fa-play me-1"></i>Unirse al Juego
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Paso 3: Esperando -->
                <div class="step" id="step3">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <h4 class="text-success">¡Unido Exitosamente!</h4>
                        <p class="text-muted">Esperando a que el anfitrión inicie el juego...</p>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong id="waitingMessage">Conectado a la trivia</strong>
                        </div>
                        
                        <div class="mt-4">
                            <button class="btn btn-outline-danger" onclick="leaveGame()">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir de la Partida
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        let gameInfo = null;
        let playerData = null;
        let checkInterval = null;

        // Navegación entre pasos
        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
        }

        // Validar código en tiempo real
        async function validateCode() {
            const code = document.getElementById('joinCode').value.trim().toUpperCase();
            const btnNext = document.getElementById('btnNext1');
            const gameInfoDiv = document.getElementById('gameInfo');
            const gameDetailsDiv = document.getElementById('gameDetails');
            
            if (code.length === 6) {
                try {
                    const response = await fetch('/microservices/tata-trivia/api/validate_code.php?code=' + code);
                    const result = await response.json();
                    
                    if (result.success) {
                        gameInfo = result.data;
                        btnNext.disabled = false;
                        gameInfoDiv.style.display = 'block';
                        
                        // Mostrar información del juego
                        gameDetailsDiv.innerHTML = `
                            <div class="row small">
                                <div class="col-6">
                                    <strong>Título:</strong><br>
                                    ${gameInfo.title}
                                </div>
                                <div class="col-6">
                                    <strong>Modalidad:</strong><br>
                                    ${gameInfo.game_mode === 'individual' ? 'Individual' : 'Por Equipos'}
                                </div>
                            </div>
                            <div class="row small mt-2">
                                <div class="col-6">
                                    <strong>Anfitrión:</strong><br>
                                    ${gameInfo.host_name || 'Anfitrión'}
                                </div>
                                <div class="col-6">
                                    <strong>Estado:</strong><br>
                                    <span class="badge bg-success">Disponible</span>
                                </div>
                            </div>
                        `;
                        
                        // Mostrar campo de equipo si es modo equipos
                        if (gameInfo.game_mode === 'teams') {
                            document.getElementById('teamField').style.display = 'block';
                        }
                    } else {
                        btnNext.disabled = true;
                        gameInfoDiv.style.display = 'block';
                        gameDetailsDiv.innerHTML = `
                            <div class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${result.error || 'Código inválido o partida no disponible'}
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    btnNext.disabled = true;
                    gameInfoDiv.style.display = 'block';
                    gameDetailsDiv.innerHTML = `
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al validar el código
                        </div>
                    `;
                }
            } else {
                btnNext.disabled = true;
                gameInfoDiv.style.display = 'none';
            }
        }

        // Seleccionar avatar
        function selectAvatar(element) {
            document.querySelectorAll('.avatar-option').forEach(avatar => {
                avatar.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('selectedAvatar').value = element.dataset.avatar;
        }

        // Enviar formulario de unión
        document.getElementById('playerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const joinCode = document.getElementById('joinCode').value.trim().toUpperCase();
            const playerName = document.getElementById('playerName').value.trim();
            const teamName = document.getElementById('teamName').value.trim();
            const avatar = document.getElementById('selectedAvatar').value;
            
            if (!playerName) {
                alert('Por favor ingresa tu nombre');
                return;
            }

            const btnJoin = document.getElementById('btnJoin');
            btnJoin.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uniendo...';
            btnJoin.disabled = true;

            try {
                const response = await fetch('/microservices/tata-trivia/api/join_game.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        join_code: joinCode,
                        player_name: playerName,
                        team_name: teamName || null,
                        avatar: avatar,
                        user_id: <?php echo $user['id'] ?? 'null'; ?>
                    })
                });

                const result = await response.json();

                if (result.success) {
                    playerData = result.data;
                    showStep(3);
                    startWaitingForGame();
                } else {
                    alert('Error: ' + result.error);
                    btnJoin.innerHTML = '<i class="fas fa-play me-1"></i>Unirse al Juego';
                    btnJoin.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al unirse al juego. Por favor intenta nuevamente.');
                btnJoin.innerHTML = '<i class="fas fa-play me-1"></i>Unirse al Juego';
                btnJoin.disabled = false;
            }
        });

        // Esperar a que comience el juego
        function startWaitingForGame() {
            let dots = 0;
            const messageElement = document.getElementById('waitingMessage');
            
            // Actualizar mensaje con puntos animados
            const dotsInterval = setInterval(() => {
                dots = (dots + 1) % 4;
                messageElement.textContent = 'Esperando que el anfitrión inicie el juego' + '.'.repeat(dots);
            }, 500);

            // Verificar estado del juego cada 3 segundos
            checkInterval = setInterval(async () => {
                try {
                    const response = await fetch('/microservices/tata-trivia/api/game_status.php?trivia_id=' + playerData.trivia_id);
                    const result = await response.json();
                    
                    if (result.success && result.data.status === 'active') {
                        clearInterval(checkInterval);
                        clearInterval(dotsInterval);
                        window.location.href = '/microservices/tata-trivia/player/game?player_id=' + playerData.player_id;
                    }
                } catch (error) {
                    console.error('Error checking game status:', error);
                }
            }, 3000);
        }

        function leaveGame() {
            if (checkInterval) {
                clearInterval(checkInterval);
            }
            window.location.href = '/microservices/tata-trivia/';
        }

        // Inicializar avatar por defecto
        document.addEventListener('DOMContentLoaded', function() {
            const firstAvatar = document.querySelector('.avatar-option');
            if (firstAvatar) {
                firstAvatar.click();
            }
        });
    </script>
</body>
</html>