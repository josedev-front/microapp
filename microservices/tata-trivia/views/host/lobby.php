<?php
// microservices/trivia-play/views/host/lobby.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '?vista=trivia_host');
    exit;
}

$trivia_id = intval($_GET['id']);
$usuario_actual = null;

if (validarSesion()) {
    $usuario_actual = obtenerUsuarioActual();
}

require_once __DIR__ . '/../../init.php';

try {
    $hostController = new TriviaPlay\Controllers\HostController();
    $triviaController = new TriviaPlay\Controllers\TriviaController();
    
    $trivia = $hostController->getTrivia($trivia_id, $usuario_actual['id'] ?? null);
    $players = $hostController->getLobbyPlayers($trivia_id);
    $questions = $hostController->getQuestions($trivia_id);
    
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '?vista=trivia_host');
    exit;
}

// Procesar inicio del juego
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'begin_game') {
        try {
            $hostController->beginGame($trivia_id, $usuario_actual['id']);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - <?php echo $trivia['title']; ?> - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .lobby-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .player-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .player-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        .qr-code {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: inline-block;
        }
        .join-code {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 5px;
            background: linear-gradient(45deg, #FF6B6B, #FF8E53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .team-badge {
            font-size: 0.7rem;
        }
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="lobby-container">
        <!-- Header -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>?vista=trivia">
                    <i class="fas fa-trophy me-2"></i>Tata Trivia
                </a>
                <div class="navbar-text">
                    <span class="badge bg-warning me-2"><?php echo count($players); ?> jugadores</span>
                    <span class="badge bg-light text-dark"><?php echo $trivia['title']; ?></span>
                </div>
            </div>
        </nav>

        <div class="container py-4">
            <div class="row">
                <!-- Información principal -->
                <div class="col-lg-8">
                    <div class="card shadow-lg mb-4">
                        <div class="card-body text-center py-5">
                            <h1 class="display-4 fw-bold text-dark mb-3">¡Lobby de la Trivia!</h1>
                            <p class="lead text-muted mb-4">
                                Los jugadores se están uniendo. Prepárate para comenzar.
                            </p>
                            
                            <!-- Código para unirse -->
                            <div class="mb-4">
                                <h5 class="text-muted mb-2">Código para unirse:</h5>
                                <div class="join-code"><?php echo $trivia['join_code']; ?></div>
                                <small class="text-muted">Comparte este código con los participantes</small>
                            </div>

                            <!-- QR Code (placeholder) -->
                            <div class="mb-4">
                                <div class="qr-code d-inline-block">
                                    <div id="qrcode" class="text-center">
                                        <div style="width: 200px; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                            <div class="text-center">
                                                <i class="fas fa-qrcode fa-5x text-muted mb-2"></i>
                                                <br>
                                                <small class="text-muted">Código: <?php echo $trivia['join_code']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón para comenzar -->
                            <div class="mt-4">
                                <button id="beginGameBtn" class="btn btn-success btn-lg pulse-animation" 
                                        <?php echo count($players) === 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-play me-2"></i>Comenzar Trivia
                                </button>
                                <p class="small text-muted mt-2">
                                    <?php echo count($players); ?> jugadores listos
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de jugadores -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Jugadores en el Lobby
                                <span class="badge bg-primary ms-2"><?php echo count($players); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="playersContainer">
                                <?php if (empty($players)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Esperando jugadores...</p>
                                        <small class="text-muted">Los jugadores aparecerán aquí cuando se unan</small>
                                    </div>
                                <?php else: ?>
                                    <div class="players-grid">
                                        <?php foreach ($players as $player): ?>
                                        <div class="player-card p-3 text-center">
                                            <img src="<?php echo $player['avatar'] ?: BASE_URL . 'microservices/trivia-play/assets/images/avatars/default.png'; ?>" 
                                                 alt="Avatar" class="player-avatar mb-2">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($player['player_name']); ?></h6>
                                            <?php if ($player['team_name']): ?>
                                                <span class="badge bg-primary team-badge"><?php echo $player['team_name']; ?></span>
                                            <?php endif; ?>
                                            <small class="text-muted d-block">
                                                Unido <?php echo time_elapsed_string($player['join_time']); ?>
                                            </small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral -->
                <div class="col-lg-4">
                    <!-- Información de la trivia -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Título:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($trivia['title']); ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>Modalidad:</strong>
                                <p class="mb-0">
                                    <?php echo $trivia['game_mode'] === 'teams' ? 'Competencia por Equipos' : 'Competencia Individual'; ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Preguntas:</strong>
                                <p class="mb-0"><?php echo count($questions); ?> preguntas configuradas</p>
                            </div>
                            <div>
                                <strong>Tiempo total estimado:</strong>
                                <p class="mb-0">
                                    <?php 
                                    $total_time = array_sum(array_column($questions, 'time_limit'));
                                    echo ceil($total_time / 60); 
                                    ?> minutos
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Acciones</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?php echo BASE_URL; ?>?vista=trivia_questions&id=<?php echo $trivia_id; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-2"></i>Editar Preguntas
                                </a>
                                <button class="btn btn-outline-warning btn-sm" id="copyCodeBtn">
                                    <i class="fas fa-copy me-2"></i>Copiar Código
                                </button>
                                <button class="btn btn-outline-info btn-sm" id="shareBtn">
                                    <i class="fas fa-share me-2"></i>Compartir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat del lobby -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Chat del Lobby</h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="lobbyChat" style="height: 200px; overflow-y: auto; padding: 1rem;">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-comment-slash fa-2x mb-2"></i>
                                    <p class="small mb-0">El chat estará disponible durante el juego</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmBeginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-play-circle me-2 text-success"></i>Comenzar Trivia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás listo para comenzar la trivia con <strong><?php echo count($players); ?> jugadores</strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Una vez que comiences, no se podrán unir más jugadores.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmBeginBtn">
                        <i class="fas fa-play me-2"></i>¡Comenzar!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const beginGameBtn = document.getElementById('beginGameBtn');
            const confirmBeginModal = new bootstrap.Modal(document.getElementById('confirmBeginModal'));
            const confirmBeginBtn = document.getElementById('confirmBeginBtn');
            const copyCodeBtn = document.getElementById('copyCodeBtn');
            const shareBtn = document.getElementById('shareBtn');
            const joinCode = '<?php echo $trivia['join_code']; ?>';

            // Actualizar jugadores cada 5 segundos
            let playersUpdateInterval = setInterval(updatePlayers, 5000);

            // Comenzar juego
            beginGameBtn.addEventListener('click', function() {
                confirmBeginModal.show();
            });

            confirmBeginBtn.addEventListener('click', function() {
                beginGame();
            });

            // Copiar código
            copyCodeBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(joinCode).then(function() {
                    const originalText = copyCodeBtn.innerHTML;
                    copyCodeBtn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
                    copyCodeBtn.classList.remove('btn-outline-warning');
                    copyCodeBtn.classList.add('btn-success');
                    
                    setTimeout(function() {
                        copyCodeBtn.innerHTML = originalText;
                        copyCodeBtn.classList.remove('btn-success');
                        copyCodeBtn.classList.add('btn-outline-warning');
                    }, 2000);
                });
            });

            // Compartir
            shareBtn.addEventListener('click', function() {
                const shareText = `¡Únete a mi trivia! Código: ${joinCode}`;
                const shareUrl = '<?php echo BASE_URL; ?>?vista=trivia_join';
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Tata Trivia',
                        text: shareText,
                        url: shareUrl
                    });
                } else {
                    navigator.clipboard.writeText(shareText + ' ' + shareUrl).then(function() {
                        alert('Enlace copiado al portapapeles');
                    });
                }
            });

            // Funciones
            function updatePlayers() {
                fetch('<?php echo BASE_URL; ?>microservices/trivia-play/api/get_lobby_players.php?trivia_id=<?php echo $trivia_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updatePlayersList(data.players);
                            
                            // Actualizar contador
                            const playerCount = data.players.length;
                            document.querySelector('#beginGameBtn + p').textContent = playerCount + ' jugadores listos';
                            
                            // Habilitar/deshabilitar botón
                            beginGameBtn.disabled = playerCount === 0;
                        }
                    })
                    .catch(error => console.error('Error actualizando jugadores:', error));
            }

            function updatePlayersList(players) {
                const container = document.getElementById('playersContainer');
                
                if (players.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Esperando jugadores...</p>
                            <small class="text-muted">Los jugadores aparecerán aquí cuando se unan</small>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="players-grid">';
                players.forEach(player => {
                    html += `
                        <div class="player-card p-3 text-center">
                            <img src="${player.avatar || '<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/avatars/default.png'}" 
                                 alt="Avatar" class="player-avatar mb-2">
                            <h6 class="mb-1">${escapeHtml(player.player_name)}</h6>
                            ${player.team_name ? `<span class="badge bg-primary team-badge">${escapeHtml(player.team_name)}</span>` : ''}
                            <small class="text-muted d-block">
                                Unido ${timeAgo(player.join_time)}
                            </small>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
            }

            function beginGame() {
                const formData = new FormData();
                formData.append('action', 'begin_game');

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirigir a la pantalla de juego
                        window.location.href = '<?php echo BASE_URL; ?>?vista=trivia_game&id=<?php echo $trivia_id; ?>';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error al comenzar el juego');
                    console.error('Error:', error);
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function timeAgo(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const seconds = Math.floor((now - date) / 1000);
                
                if (seconds < 60) return 'hace un momento';
                if (seconds < 3600) return `hace ${Math.floor(seconds / 60)} minutos`;
                if (seconds < 86400) return `hace ${Math.floor(seconds / 3600)} horas`;
                return `hace ${Math.floor(seconds / 86400)} días`;
            }

            // Limpiar intervalo cuando se abandone la página
            window.addEventListener('beforeunload', function() {
                clearInterval(playersUpdateInterval);
            });
        });
    </script>
</body>
</html>

<?php
// Función helper para mostrar tiempo transcurrido
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'hace ' . implode(', ', $string) : 'ahora mismo';
}
?>