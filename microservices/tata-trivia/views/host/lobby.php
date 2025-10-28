<?php
// microservices/tata-trivia/views/host/lobby.php - VERSIÓN CON BACKGROUND

$user = $user ?? getTriviaMicroappsUser();
$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}

// Obtener información de la trivia
try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    $join_code = $trivia['join_code'] ?? $trivia_id;
    $theme = $trivia['theme'] ?? 'default';
    
    // Obtener el background path usando el método del controlador
    $backgroundPath = $triviaController->getTriviaBackgroundPath($trivia_id);
    
    // URL para unirse
    $join_url = "http://" . $_SERVER['HTTP_HOST'] . "/microservices/tata-trivia/player/join?code=" . $join_code;
    $qr_url = "/microservices/tata-trivia/api/generate_qr.php?code=" . $join_code . "&size=200";
    
} catch (Exception $e) {
    $join_code = $trivia_id;
    $theme = 'default';
    $backgroundPath = '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
    $join_url = "http://" . $_SERVER['HTTP_HOST'] . "/microservices/tata-trivia/player/join?code=" . $trivia_id;
    $qr_url = "/microservices/tata-trivia/api/generate_qr.php?code=" . $trivia_id . "&size=200";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .lobby-container {
            background-image: url('<?php echo $backgroundPath; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            padding: 20px 0;
        }
        /* Fallback si la imagen no carga */
        .lobby-container.no-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        .lobby-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: inline-block;
        }
        .join-code {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .player-card {
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
        }
        .player-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-1px);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .theme-badge {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="lobby-container <?php echo (!file_exists($_SERVER['DOCUMENT_ROOT'] . $backgroundPath)) ? 'no-bg' : ''; ?>" 
         id="lobbyContainer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <!-- Header -->
                    <div class="lobby-card mb-4">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><i class="fas fa-users me-2"></i>Sala de Espera</h3>
                                <small>Tema: <?php echo ucfirst(str_replace('_', ' ', $theme)); ?></small>
                            </div>
                            <span class="badge bg-warning pulse">
                                <i class="fas fa-clock me-1"></i>Esperando jugadores...
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Código y QR -->
                                <div class="col-md-6 text-center">
                                    <h4 class="text-success mb-3">
                                        <i class="fas fa-gamepad me-2"></i>¡Trivia Creada!
                                    </h4>
                                    
                                    <div class="mb-4">
                                        <p class="text-muted mb-2">Comparte este código:</p>
                                        <div class="alert alert-info mb-3">
                                            <h1 class="join-code display-4 mb-0"><?php echo htmlspecialchars($join_code); ?></h1>
                                        </div>
                                        
                                        <div class="d-flex justify-content-center gap-2 mb-3">
                                            <button class="btn btn-outline-primary copy-btn" data-text="<?php echo htmlspecialchars($join_code); ?>">
                                                <i class="fas fa-copy me-1"></i>Copiar Código
                                            </button>
                                            <button class="btn btn-outline-secondary copy-btn" data-text="<?php echo htmlspecialchars($join_url); ?>">
                                                <i class="fas fa-link me-1"></i>Copiar Enlace
                                            </button>
                                        </div>
                                    </div>

                                    <!-- QR Code -->
                                    <div class="mb-4">
                                        <p class="text-muted mb-2">O escanea el QR:</p>
                                        <div class="qr-container">
                                            <img src="<?php echo $qr_url; ?>" 
                                                 alt="QR Code para unirse a la trivia" 
                                                 class="img-fluid"
                                                 style="max-width: 200px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Jugadores Conectados -->
                                <div class="col-md-6">
                                    <h5 class="text-center mb-3">
                                        <i class="fas fa-users me-2"></i>Jugadores Conectados
                                        <span class="badge bg-primary" id="playerCount">0</span>
                                    </h5>
                                    
                                    <div id="playersList" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-user-clock fa-2x mb-2"></i>
                                            <p>Esperando que se unan jugadores...</p>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Los jugadores aparecerán aquí automáticamente
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="lobby-card">
                        <div class="card-body text-center">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <button class="btn btn-outline-primary w-100" onclick="refreshQR()">
                                        <i class="fas fa-sync-alt me-2"></i>Actualizar QR
                                    </button>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <button class="btn btn-success w-100" onclick="startGame()" id="startBtn">
                                        <i class="fas fa-play me-2"></i>Comenzar Juego
                                    </button>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <a href="/microservices/tata-trivia/" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-home me-2"></i>Salir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="lobby-card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instrucciones</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="fas fa-share-alt text-primary me-2"></i>Comparte el código o QR</li>
                                        <li class="mb-2"><i class="fas fa-users text-success me-2"></i>Espera a que se unan los jugadores</li>
                                        <li class="mb-2"><i class="fas fa-play text-warning me-2"></i>Inicia el juego cuando estén todos</li>
                                        <li><i class="fas fa-trophy text-danger me-2"></i>¡Diviértanse!</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="lobby-card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Para Jugadores</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">Pueden unirse de dos formas:</p>
                                    <ol class="small">
                                        <li class="mb-1">Escaneando el código QR con su celular</li>
                                        <li class="mb-1">Ingresando el código en <strong>Tata Trivia → Unirse</strong></li>
                                    </ol>
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-palette me-1"></i>
                                            Tema actual: <strong><?php echo ucfirst(str_replace('_', ' ', $theme)); ?></strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let playerInterval;
        const triviaId = '<?php echo $trivia_id; ?>';
        const joinCode = '<?php echo $join_code; ?>';

        // Copiar texto al portapapeles
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                copyToClipboard(text);
                
                // Feedback visual
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-1"></i>Copiado!';
                this.classList.add('btn-success');
                this.classList.remove('btn-outline-primary', 'btn-outline-secondary');
                
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.remove('btn-success');
                    this.classList.add(this.getAttribute('data-text').length > 10 ? 'btn-outline-secondary' : 'btn-outline-primary');
                }, 2000);
            });
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                console.log('Texto copiado: ' + text);
            }).catch(err => {
                console.error('Error al copiar: ', err);
                // Fallback para navegadores antiguos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            });
        }

        function refreshQR() {
            const qrImg = document.querySelector('.qr-container img');
            if (qrImg) {
                qrImg.src = qrImg.src.split('?')[0] + '?code=' + joinCode + '&size=200&t=' + new Date().getTime();
            }
        }

        // Actualizar lista de jugadores
        async function updatePlayers() {
            try {
                const response = await fetch('/microservices/tata-trivia/api/get_lobby_players.php?trivia_id=' + triviaId);
                const result = await response.json();
                
                if (result.success) {
                    displayPlayers(result.data.players || []);
                }
            } catch (error) {
                console.error('Error actualizando jugadores:', error);
            }
        }

        function displayPlayers(players) {
            const container = document.getElementById('playersList');
            const countElement = document.getElementById('playerCount');
            
            countElement.textContent = players.length;
            
            if (players.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-clock fa-2x mb-2"></i>
                        <p>Esperando que se unan jugadores...</p>
                    </div>
                `;
            } else {
                container.innerHTML = players.map(player => `
                    <div class="card player-card mb-2">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        ${player.player_name}
                                    </h6>
                                    ${player.team_name ? `<small class="text-muted">Equipo: ${player.team_name}</small>` : ''}
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
            
            // Habilitar/deshabilitar botón de comenzar
            const startBtn = document.getElementById('startBtn');
            startBtn.disabled = players.length === 0;
            
            // Actualizar badge del header si hay jugadores
            const waitingBadge = document.querySelector('.pulse');
            if (players.length > 0) {
                waitingBadge.innerHTML = `<i class="fas fa-users me-1"></i>${players.length} jugador(es) conectado(s)`;
            }
        }

        async function startGame() {
    const startBtn = document.getElementById('startBtn');
    const originalText = startBtn.innerHTML;
    
    startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...';
    startBtn.disabled = true;

    try {
        const response = await fetch('/microservices/tata-trivia/api/start_game.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                trivia_id: triviaId
            })
        });

        const result = await response.json();

        if (result.success) {
            // Redirigir al juego
            const redirectUrl = result.redirect_url || '/microservices/tata-trivia/host/game?trivia_id=' + triviaId;
            window.location.href = redirectUrl;
        } else {
            // Manejar error específico de "no hay preguntas"
            if (result.error && result.error.includes('no tiene preguntas')) {
                alert('Error: ' + result.error + '\n\nSerás redirigido para agregar preguntas.');
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else {
                    window.location.href = '/microservices/tata-trivia/host/questions?trivia_id=' + triviaId;
                }
            } else {
                alert('Error: ' + result.error);
                startBtn.innerHTML = originalText;
                startBtn.disabled = false;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al iniciar el juego. Por favor intenta nuevamente.');
        startBtn.innerHTML = originalText;
        startBtn.disabled = false;
    }
}

        // Verificar si el background se cargó correctamente
        function checkBackgroundLoaded() {
            const lobbyContainer = document.getElementById('lobbyContainer');
            const bgImage = new Image();
            bgImage.src = '<?php echo $backgroundPath; ?>';
            
            bgImage.onload = function() {
                console.log('Background image loaded successfully');
            };
            
            bgImage.onerror = function() {
                console.log('Background image failed to load, using fallback');
                lobbyContainer.classList.add('no-bg');
            };
        }

        // Iniciar actualización automática de jugadores
        document.addEventListener('DOMContentLoaded', function() {
            updatePlayers(); // Actualizar inmediatamente
            playerInterval = setInterval(updatePlayers, 3000); // Actualizar cada 3 segundos
            checkBackgroundLoaded(); // Verificar carga del background
        });

        // Limpiar intervalo al salir
        window.addEventListener('beforeunload', function() {
            if (playerInterval) {
                clearInterval(playerInterval);
            }
        });

        // Efectos visuales adicionales
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar animación suave a los cards
            const cards = document.querySelectorAll('.lobby-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>