<?php
// microservices/trivia-play/views/host/game_host.php

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
    $questions = $hostController->getQuestions($trivia_id);
    $players = $hostController->getLobbyPlayers($trivia_id);
    
    if ($trivia['status'] !== 'active') {
        header('Location: ' . BASE_URL . '?vista=trivia_lobby&id=' . $trivia_id);
        exit;
    }
    
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '?vista=trivia_host');
    exit;
}

$current_question_index = isset($_GET['question']) ? intval($_GET['question']) : 0;
$current_question = $questions[$current_question_index] ?? null;
$total_questions = count($questions);
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En Vivo: <?php echo $trivia['title']; ?> - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .game-container {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: white;
        }
        .question-display {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .timer-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 5px solid #4ECDC4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto;
        }
        .progress-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1rem;
        }
        .player-response {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .player-response.correct {
            background: rgba(40, 167, 69, 0.3);
            border-left: 4px solid #28a745;
        }
        .player-response.incorrect {
            background: rgba(220, 53, 69, 0.3);
            border-left: 4px solid #dc3545;
        }
        .leaderboard-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .leaderboard-item.current {
            background: rgba(102, 126, 234, 0.3);
            border-left: 4px solid #667eea;
        }
        .option-stats {
            height: 30px;
            border-radius: 15px;
            margin-bottom: 0.5rem;
            overflow: hidden;
            position: relative;
        }
        .option-bar {
            height: 100%;
            border-radius: 15px;
            transition: width 0.5s ease;
            position: relative;
        }
        .option-text {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        .count-badge {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
        }
        .pulse {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="game-container">
    <!-- Header -->
    <nav class="navbar navbar-dark border-bottom border-secondary">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="fas fa-broadcast-tower fa-lg text-warning me-2"></i>
                <span class="navbar-brand mb-0 h1">EN VIVO</span>
                <span class="badge bg-danger ms-2 pulse">LIVE</span>
            </div>
            <div class="navbar-text">
                <span class="badge bg-warning me-2">Pregunta <?php echo $current_question_index + 1; ?>/<?php echo $total_questions; ?></span>
                <span class="badge bg-info"><?php echo count($players); ?> jugadores</span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <!-- Panel principal de pregunta -->
            <div class="col-lg-8">
                <?php if ($current_question): ?>
                <div class="question-display" 
                     style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/backgrounds/<?php echo $current_question['background_image']; ?>.jpg'); background-size: cover; background-position: center;">
                    
                    <!-- Timer -->
                    <div class="text-center mb-4">
                        <div class="timer-circle" id="timer">
                            <?php echo $current_question['time_limit']; ?>
                        </div>
                        <small class="text-muted mt-2 d-block">TIEMPO RESTANTE</small>
                    </div>

                    <!-- Pregunta -->
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold mb-4"><?php echo htmlspecialchars($current_question['question_text']); ?></h1>
                        
                        <!-- Estadísticas de opciones -->
                        <div class="row justify-content-center mt-4">
                            <?php foreach ($current_question['options'] as $index => $option): ?>
                            <div class="col-md-6 mb-3">
                                <div class="option-stats">
                                    <div class="option-bar bg-primary" 
                                         data-option="<?php echo $index; ?>"
                                         style="width: 0%; background: <?php echo $option['is_correct'] ? 'linear-gradient(45deg, #28a745, #20c997)' : 'linear-gradient(45deg, #6c757d, #adb5bd)'; ?>">
                                        <span class="option-text text-white">
                                            <?php echo htmlspecialchars($option['text']); ?>
                                        </span>
                                        <span class="count-badge text-white">0</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Controles del anfitrión -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-cogs me-2"></i>Controles de Juego</h6>
                                <div class="btn-group w-100">
                                    <?php if ($current_question_index > 0): ?>
                                    <a href="?vista=trivia_game&id=<?php echo $trivia_id; ?>&question=<?php echo $current_question_index - 1; ?>" 
                                       class="btn btn-outline-warning">
                                        <i class="fas fa-arrow-left me-2"></i>Anterior
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($current_question_index < $total_questions - 1): ?>
                                    <a href="?vista=trivia_game&id=<?php echo $trivia_id; ?>&question=<?php echo $current_question_index + 1; ?>" 
                                       class="btn btn-outline-success">
                                        Siguiente<i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="?vista=trivia_results&id=<?php echo $trivia_id; ?>" 
                                       class="btn btn-outline-success">
                                        Finalizar<i class="fas fa-flag-checkered ms-2"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-danger btn-lg" id="revealAnswers">
                                    <i class="fas fa-eye me-2"></i>Revelar Respuestas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h3>No hay más preguntas</h3>
                    <p class="text-muted">La trivia ha finalizado</p>
                    <a href="?vista=trivia_results&id=<?php echo $trivia_id; ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-chart-bar me-2"></i>Ver Resultados
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Respuestas en tiempo real -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Respuestas en Tiempo Real
                            <span class="badge bg-warning ms-2" id="responseCount">0</span>
                        </h6>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div id="realtimeResponses">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <p class="small mb-0">Esperando respuestas...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de posiciones -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary">
                        <h6 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Tabla de Posiciones
                        </h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div id="leaderboard">
                            <?php foreach (array_slice($players, 0, 10) as $index => $player): ?>
                            <div class="leaderboard-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                        <img src="<?php echo $player['avatar'] ?: BASE_URL . 'microservices/trivia-play/assets/images/avatars/default.png'; ?>" 
                                             alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%;" class="me-2">
                                        <span class="small"><?php echo htmlspecialchars($player['player_name']); ?></span>
                                    </div>
                                    <span class="badge bg-warning">0 pts</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Información rápida -->
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border rounded p-2 bg-secondary">
                                    <div class="h5 mb-0 text-warning" id="playersConnected"><?php echo count($players); ?></div>
                                    <small class="text-light">Conectados</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 bg-secondary">
                                    <div class="h5 mb-0 text-info" id="playersAnswered">0</div>
                                    <small class="text-light">Respondieron</small>
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
            const timerElement = document.getElementById('timer');
            const realtimeResponses = document.getElementById('realtimeResponses');
            const leaderboard = document.getElementById('leaderboard');
            const responseCount = document.getElementById('responseCount');
            const playersConnected = document.getElementById('playersConnected');
            const playersAnswered = document.getElementById('playersAnswered');
            const revealAnswersBtn = document.getElementById('revealAnswers');
            const optionBars = document.querySelectorAll('.option-bar');
            
            let timeLeft = <?php echo $current_question['time_limit'] ?? 30; ?>;
            let timerInterval;
            let questionId = <?php echo $current_question['id'] ?? 0; ?>;
            let triviaId = <?php echo $trivia_id; ?>;
            let playerResponses = new Map();

            // Iniciar timer
            startTimer();

            // Actualizar datos cada 2 segundos
            setInterval(updateGameData, 2000);

            // Revelar respuestas
            revealAnswersBtn.addEventListener('click', function() {
                revealCorrectAnswers();
            });

            function startTimer() {
                timerInterval = setInterval(function() {
                    timeLeft--;
                    timerElement.textContent = timeLeft;
                    
                    // Cambiar color según el tiempo
                    if (timeLeft <= 10) {
                        timerElement.style.borderColor = '#dc3545';
                        timerElement.style.color = '#dc3545';
                    } else if (timeLeft <= 20) {
                        timerElement.style.borderColor = '#ffc107';
                        timerElement.style.color = '#ffc107';
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        timerElement.textContent = '0';
                        // Auto-revelar respuestas cuando se acaba el tiempo
                        revealCorrectAnswers();
                    }
                }, 1000);
            }

            function updateGameData() {
                // Simular datos en tiempo real (en producción esto vendría de WebSockets)
                fetch(`<?php echo BASE_URL; ?>microservices/trivia-play/api/game_data.php?trivia_id=${triviaId}&question_id=${questionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateResponses(data.responses);
                            updateLeaderboard(data.leaderboard);
                            updateStats(data.stats);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateResponses(responses) {
                responseCount.textContent = responses.length;
                playersAnswered.textContent = responses.length;
                
                if (responses.length === 0) {
                    realtimeResponses.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <p class="small mb-0">Esperando respuestas...</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                responses.forEach(response => {
                    const isCorrect = response.is_correct;
                    const responseTime = response.response_time;
                    
                    html += `
                        <div class="player-response ${isCorrect ? 'correct' : 'incorrect'}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="${response.avatar}" alt="Avatar" 
                                         style="width: 25px; height: 25px; border-radius: 50%;" class="me-2">
                                    <span class="small">${escapeHtml(response.player_name)}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-${isCorrect ? 'success' : 'danger'} me-2">
                                        ${responseTime}s
                                    </span>
                                    <i class="fas fa-${isCorrect ? 'check' : 'times'}"></i>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                realtimeResponses.innerHTML = html;
                realtimeResponses.scrollTop = realtimeResponses.scrollHeight;
            }

            function updateLeaderboard(leaderboardData) {
                let html = '';
                leaderboardData.forEach((player, index) => {
                    html += `
                        <div class="leaderboard-item ${index < 3 ? 'current' : ''}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge ${getRankBadgeClass(index)} me-2">${index + 1}</span>
                                    <img src="${player.avatar}" alt="Avatar" 
                                         style="width: 30px; height: 30px; border-radius: 50%;" class="me-2">
                                    <span class="small">${escapeHtml(player.player_name)}</span>
                                </div>
                                <span class="badge bg-warning">${player.score} pts</span>
                            </div>
                        </div>
                    `;
                });
                
                leaderboard.innerHTML = html;
            }

            function updateStats(stats) {
                // Actualizar barras de opciones
                optionBars.forEach(bar => {
                    const optionIndex = bar.dataset.option;
                    const optionStats = stats.options[optionIndex] || { count: 0, percentage: 0 };
                    
                    bar.style.width = optionStats.percentage + '%';
                    bar.querySelector('.count-badge').textContent = optionStats.count;
                });
            }

            function revealCorrectAnswers() {
                // Marcar opciones correctas
                optionBars.forEach(bar => {
                    const isCorrect = bar.style.background.includes('28a745');
                    if (isCorrect) {
                        bar.classList.add('pulse');
                    }
                });
                
                // Deshabilitar botón
                revealAnswersBtn.disabled = true;
                revealAnswersBtn.innerHTML = '<i class="fas fa-check me-2"></i>Respuestas Reveladas';
                
                // Parar timer
                clearInterval(timerInterval);
            }

            function getRankBadgeClass(rank) {
                switch(rank) {
                    case 0: return 'bg-warning';
                    case 1: return 'bg-secondary';
                    case 2: return 'bg-danger';
                    default: return 'bg-primary';
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Limpiar intervalos al salir
            window.addEventListener('beforeunload', function() {
                clearInterval(timerInterval);
            });
        });
    </script>
</body>
</html>