<?php
// microservices/tata-trivia/views/host/lobby.php - VERSIÓN CORREGIDA

require_once __DIR__ . '/../../init.php';

$trivia_id = $_GET['trivia_id'] ?? null;
$join_code = $_GET['join_code'] ?? '';

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}

// Obtener información de la trivia
$triviaController = new TriviaController();
$trivia = $triviaController->getTriviaById($trivia_id);
$players = $triviaController->getLobbyPlayers($trivia_id);

// DEBUG: Verificar jugadores
error_log("Lobby - Trivia ID: $trivia_id, Jugadores encontrados: " . count($players));

// Obtener imagen de fondo de la trivia
$backgroundImage = $triviaController->getBackgroundImagePath($trivia_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url('<?= $backgroundImage ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .lobby-container {
            background: rgba(255, 255, 255, 0.95);
            min-height: 100vh;
            padding: 20px 0;
        }
        .player-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .player-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .player-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            border: 2px solid #dee2e6;
        }
        .qr-code {
            max-width: 200px;
            border: 2px solid #007bff;
            border-radius: 10px;
        }
        .join-info {
            background: rgba(0, 123, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #007bff;
        }
        .copy-link {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .copy-link:hover {
            background-color: #f8f9fa;
        }
        .empty-players {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="lobby-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="text-primary">
                            <i class="fas fa-door-open me-2"></i>Sala de Espera
                        </h1>
                        <p class="lead">Esperando a que los jugadores se unan...</p>
                    </div>

                    <!-- Información de la trivia -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($trivia['title']) ?></h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Modalidad:</strong> 
                                            <?= $trivia['game_mode'] === 'teams' ? 'Por Equipos' : 'Individual' ?>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Ganadores:</strong> <?= $trivia['max_winners'] ?>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Jugadores:</strong> 
                                            <span id="playerCount"><?= count($players) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="join-info text-center">
                                <h4 class="text-primary mb-3"><?= $join_code ?></h4>
                                <p class="mb-2"><strong>Código para unirse</strong></p>
                                <img src="/microservices/tata-trivia/api/generate_qr.php?code=<?= $join_code ?>" 
                                     class="qr-code img-fluid mb-3" alt="QR Code">
                                
                                <!-- Link para compartir -->
                                <div class="mb-3">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="gameLink" 
                                               value="<?= "http://$_SERVER[HTTP_HOST]/microservices/tata-trivia/player/join?code=$join_code" ?>" 
                                               readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyGameLink()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Comparte este link</small>
                                </div>
                                
                                <!-- Información de debug -->
                                <div class="mt-3 p-2 bg-light rounded">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Trivia ID: <?= $trivia_id ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de jugadores -->
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Jugadores Conectados
                            </h5>
                            <span class="badge bg-light text-dark fs-6">
                                <span id="livePlayerCount"><?= count($players) ?></span> jugadores
                            </span>
                        </div>
                        <div class="card-body">
                            <div id="playersList">
                                <?php if (empty($players)): ?>
                                    <div class="empty-players">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Aún no hay jugadores conectados</h5>
                                        <p class="text-muted">Los jugadores aparecerán aquí cuando se unan con el código</p>
                                        <div class="spinner-border text-primary mt-3" role="status">
                                            <span class="visually-hidden">Esperando jugadores...</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($players as $player): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="player-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="player-avatar me-3"
                                                         style="background-image: url('/microservices/tata-trivia/assets/images/avatars/<?= $player['avatar'] ?>.png')"
                                                         onerror="this.style.backgroundImage='url(/microservices/tata-trivia/assets/images/themes/default.jpg)'">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($player['player_name']) ?></h6>
                                                        <small class="text-muted d-block">
                                                            <?= !empty($player['work_area']) ? htmlspecialchars($player['work_area']) : 'Participante' ?>
                                                        </small>
                                                        <?php if (!empty($player['team_name'])): ?>
                                                        <small class="text-info">
                                                            <i class="fas fa-users me-1"></i><?= htmlspecialchars($player['team_name']) ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-success">
                                                            <i class="fas fa-circle fa-xs me-1"></i>Conectado
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="text-center mt-4">
                        <button class="btn btn-success btn-lg me-3" onclick="startGame()" id="startBtn">
                            <i class="fas fa-play me-2"></i>Iniciar Trivia
                        </button>
                        <a href="/microservices/tata-trivia/host/questions?trivia_id=<?= $trivia_id ?>&join_code=<?= $join_code ?>" 
                           class="btn btn-outline-primary btn-lg me-3">
                            <i class="fas fa-edit me-2"></i>Editar Preguntas
                        </a>
                        <a href="/microservices/tata-trivia/host/setup" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-plus me-2"></i>Nueva Trivia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const triviaId = '<?= $trivia_id ?>';
        let playerUpdateInterval;
        
        // Función para copiar el link del juego
        function copyGameLink() {
            const gameLinkInput = document.getElementById('gameLink');
            gameLinkInput.select();
            gameLinkInput.setSelectionRange(0, 99999);
            
            try {
                navigator.clipboard.writeText(gameLinkInput.value).then(() => {
                    const button = event.target.closest('button');
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-secondary');
                    }, 2000);
                });
            } catch (err) {
                document.execCommand('copy');
                alert('Link copiado al portapapeles');
            }
        }
        
        // Actualizar lista de jugadores
        function updatePlayers() {
            console.log('Actualizando lista de jugadores...');
            
            fetch(`/microservices/tata-trivia/api/get_lobby_players.php?trivia_id=${triviaId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    
                    if (data.success) {
                        const playersList = document.getElementById('playersList');
                        const playerCount = document.getElementById('playerCount');
                        const livePlayerCount = document.getElementById('livePlayerCount');
                        const startBtn = document.getElementById('startBtn');
                        
                        // Actualizar contadores
                        playerCount.textContent = data.players.length;
                        livePlayerCount.textContent = data.players.length;
                        
                        // Habilitar/deshabilitar botón de inicio
                        startBtn.disabled = data.players.length === 0;
                        
                        if (data.players.length === 0) {
                            playersList.innerHTML = `
                                <div class="empty-players">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aún no hay jugadores conectados</h5>
                                    <p class="text-muted">Los jugadores aparecerán aquí cuando se unan con el código</p>
                                    <div class="spinner-border text-primary mt-3" role="status">
                                        <span class="visually-hidden">Esperando jugadores...</span>
                                    </div>
                                </div>
                            `;
                        } else {
                            playersList.innerHTML = `
                                <div class="row">
                                    ${data.players.map(player => `
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="player-card">
                                                <div class="d-flex align-items-center">
                                                    <div class="player-avatar me-3"
                                                         style="background-image: url('/microservices/tata-trivia/assets/images/avatars/${player.avatar || 'default1'}.png')"
                                                         onerror="this.style.backgroundImage='url(/microservices/tata-trivia/assets/images/themes/default.jpg)'">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">${player.player_name}</h6>
                                                        <small class="text-muted d-block">
                                                            ${player.work_area || 'Participante'}
                                                        </small>
                                                        ${player.team_name ? `<small class="text-info"><i class="fas fa-users me-1"></i>${player.team_name}</small>` : ''}
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-success">
                                                            <i class="fas fa-circle fa-xs me-1"></i>Conectado
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                        }
                    } else {
                        console.error('Error en la respuesta:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar jugadores:', error);
                });
        }
        
        function startGame() {
            const playerCount = document.getElementById('playerCount').textContent;
            
            if (parseInt(playerCount) === 0) {
                alert('No hay jugadores conectados. Espera a que al menos un jugador se una.');
                return;
            }
            
            if (confirm(`¿Estás seguro de que quieres iniciar la trivia con ${playerCount} jugador(es)?`)) {
                // Mostrar loading
                const startBtn = document.getElementById('startBtn');
                const originalText = startBtn.innerHTML;
                startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...';
                startBtn.disabled = true;
                
                fetch('/microservices/tata-trivia/api/start_game.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        trivia_id: triviaId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/microservices/tata-trivia/host/game_host?trivia_id=${triviaId}`;
                    } else {
                        alert('Error: ' + data.error);
                        // Restaurar botón
                        startBtn.innerHTML = originalText;
                        startBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al iniciar el juego');
                    // Restaurar botón
                    startBtn.innerHTML = originalText;
                    startBtn.disabled = false;
                });
            }
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar jugadores inmediatamente
            updatePlayers();
            
            // Actualizar jugadores cada 2 segundos
            playerUpdateInterval = setInterval(updatePlayers, 2000);
            
            // Detener actualización cuando se cierre la página
            window.addEventListener('beforeunload', function() {
                clearInterval(playerUpdateInterval);
            });
        });
    </script>
</body>
</html>