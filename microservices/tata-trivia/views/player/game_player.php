<?php
// microservices/trivia-play/views/player/game_player.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

if (!isset($_GET['player_id']) || empty($_GET['player_id'])) {
    header('Location: ' . BASE_URL . '?vista=trivia_join');
    exit;
}

$player_id = intval($_GET['player_id']);

require_once __DIR__ . '/../../init.php';

try {
    $playerController = new TriviaPlay\Controllers\PlayerController();
    $gameData = $playerController->getPlayerGameData($player_id);
    
    if (!$gameData) {
        throw new Exception('Jugador no encontrado');
    }
    
    $current_question = $playerController->getCurrentQuestion($gameData['trivia_id']);
    
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '?vista=trivia_join');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jugando - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .player-game-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .question-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            color: #333;
            margin-bottom: 1rem;
        }
        .option-btn {
            border: 3px solid #dee2e6;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            text-align: left;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
        }
        .option-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .option-btn.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .option-btn.correct {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        .option-btn.incorrect {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        .timer-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            z-index: 1000;
        }
        .player-info {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 0.5rem 1rem;
            color: #333;
            font-weight: 500;
        }
        .pulse-warning {
            animation: pulse-warning 1s infinite;
        }
        @keyframes pulse-warning {
            0% { background: rgba(255, 193, 7, 0.8); }
            50% { background: rgba(255, 193, 7, 1); }
            100% { background: rgba(255, 193, 7, 0.8); }
        }
        .pulse-danger {
            animation: pulse-danger 1s infinite;
        }
        @keyframes pulse-danger {
            0% { background: rgba(220, 53, 69, 0.8); }
            50% { background: rgba(220, 53, 69, 1); }
            100% { background: rgba(220, 53, 69, 0.8); }
        }
    </style>
</head>
<body class="player-game-container">
    <!-- Timer flotante -->
    <div class="timer-container" id="timer">
        <?php echo $current_question['time_limit'] ?? 30; ?>
    </div>

    <div class="container py-4">
        <!-- Información del jugador -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="player-info">
                <img src="<?php echo $gameData['avatar']; ?>" 
                     alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%;" class="me-2">
                <?php echo htmlspecialchars($gameData['player_name']); ?>
                <span class="badge bg-primary ms-2"><?php echo $gameData['score']; ?> pts</span>
            </div>
            <div class="player-info">
                Pregunta <span id="currentQuestion">1</span>/<span id="totalQuestions"><?php echo $gameData['total_questions']; ?></span>
            </div>
        </div>

        <?php if ($current_question): ?>
        <!-- Tarjeta de pregunta -->
        <div class="question-card p-4">
            <!-- Encabezado de pregunta -->
            <div class="text-center mb-4">
                <small class="text-muted">PREGUNTA ACTUAL</small>
                <h3 class="fw-bold mt-1"><?php echo htmlspecialchars($current_question['question_text']); ?></h3>
            </div>

            <!-- Opciones de respuesta -->
            <div id="optionsContainer">
                <?php foreach ($current_question['options'] as $index => $option): ?>
                <button class="option-btn w-100" 
                        data-option-id="<?php echo $option['id']; ?>"
                        data-option-index="<?php echo $index; ?>">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                            <?php echo chr(65 + $index); ?>
                        </span>
                        <span><?php echo htmlspecialchars($option['text']); ?></span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Botón de enviar -->
            <div class="text-center mt-4">
                <button class="btn btn-primary btn-lg w-100" id="submitAnswer" disabled>
                    <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                </button>
            </div>
        </div>

        <!-- Feedback de respuesta -->
        <div class="alert alert-info d-none" id="feedbackAlert">
            <div class="text-center">
                <i class="fas fa-sync fa-spin me-2"></i>
                <span id="feedbackText">Esperando resultados...</span>
            </div>
        </div>

        <!-- Mini leaderboard -->
        <div class="question-card p-3 mt-3">
            <h6 class="text-center mb-3">
                <i class="fas fa-trophy me-2 text-warning"></i>Tabla de Posiciones
            </h6>
            <div id="miniLeaderboard">
                <!-- Se actualizará dinámicamente -->
            </div>
        </div>

        <?php else: ?>
        <!-- Esperando siguiente pregunta -->
        <div class="question-card p-5 text-center">
            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
            <h4 class="fw-bold">Esperando siguiente pregunta</h4>
            <p class="text-muted">El anfitrión está preparando la siguiente ronda</p>
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timerElement = document.getElementById('timer');
            const optionsContainer = document.getElementById('optionsContainer');
            const submitButton = document.getElementById('submitAnswer');
            const feedbackAlert = document.getElementById('feedbackAlert');
            const feedbackText = document.getElementById('feedbackText');
            const miniLeaderboard = document.getElementById('miniLeaderboard');
            
            let timeLeft = <?php echo $current_question['time_limit'] ?? 30; ?>;
            let selectedOption = null;
            let answerSubmitted = false;
            let timerInterval;
            let playerId = <?php echo $player_id; ?>;
            let questionId = <?php echo $current_question['id'] ?? 0; ?>;

            // Iniciar timer
            startTimer();

            // Actualizar leaderboard cada 3 segundos
            setInterval(updateMiniLeaderboard, 3000);

            // Selección de opciones
            optionsContainer.addEventListener('click', function(e) {
                if (answerSubmitted) return;
                
                const optionBtn = e.target.closest('.option-btn');
                if (optionBtn) {
                    // Deseleccionar anterior
                    optionsContainer.querySelectorAll('.option-btn').forEach(btn => {
                        btn.classList.remove('selected');
                    });
                    
                    // Seleccionar nueva opción
                    optionBtn.classList.add('selected');
                    selectedOption = optionBtn.dataset.optionId;
                    submitButton.disabled = false;
                }
            });

            // Enviar respuesta
            submitButton.addEventListener('click', function() {
                if (!selectedOption || answerSubmitted) return;
                
                submitAnswer();
            });

            function startTimer() {
                timerInterval = setInterval(function() {
                    timeLeft--;
                    timerElement.textContent = timeLeft;
                    
                    // Cambiar color del timer
                    if (timeLeft <= 10) {
                        timerElement.classList.remove('pulse-warning');
                        timerElement.classList.add('pulse-danger');
                    } else if (timeLeft <= 20) {
                        timerElement.classList.add('pulse-warning');
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        timerElement.textContent = '0';
                        if (!answerSubmitted) {
                            autoSubmit();
                        }
                    }
                }, 1000);
            }

            function submitAnswer() {
                answerSubmitted = true;
                submitButton.disabled = true;
                
                // Mostrar feedback
                feedbackAlert.classList.remove('d-none');
                feedbackText.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i>Enviando respuesta...';
                
                // Enviar respuesta al servidor
                fetch('<?php echo BASE_URL; ?>microservices/trivia-play/api/submit_answer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        player_id: playerId,
                        question_id: questionId,
                        option_id: selectedOption,
                        response_time: <?php echo $current_question['time_limit'] ?? 30; ?> - timeLeft
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult(data.correct, data.correct_option_id);
                    } else {
                        feedbackText.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error al enviar respuesta';
                        feedbackAlert.classList.remove('alert-info');
                        feedbackAlert.classList.add('alert-danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedbackText.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error de conexión';
                    feedbackAlert.classList.remove('alert-info');
                    feedbackAlert.classList.add('alert-danger');
                });
            }

            function showResult(isCorrect, correctOptionId) {
                // Marcar opciones correctas/incorrectas
                optionsContainer.querySelectorAll('.option-btn').forEach(btn => {
                    const optionId = btn.dataset.optionId;
                    
                    if (optionId == correctOptionId) {
                        btn.classList.add('correct');
                    } else if (optionId == selectedOption && !isCorrect) {
                        btn.classList.add('incorrect');
                    }
                    
                    btn.classList.remove('selected');
                });

                // Mostrar feedback
                if (isCorrect) {
                    feedbackText.innerHTML = '<i class="fas fa-check-circle me-2 text-success"></i>¡Correcto! +10 puntos';
                    feedbackAlert.classList.remove('alert-info');
                    feedbackAlert.classList.add('alert-success');
                } else {
                    feedbackText.innerHTML = '<i class="fas fa-times-circle me-2 text-danger"></i>Incorrecto';
                    feedbackAlert.classList.remove('alert-info');
                    feedbackAlert.classList.add('alert-danger');
                }

                // Preparar para siguiente pregunta
                setTimeout(() => {
                    feedbackText.innerHTML = '<i class="fas fa-clock me-2"></i>Esperando siguiente pregunta...';
                    feedbackAlert.classList.remove('alert-success', 'alert-danger');
                    feedbackAlert.classList.add('alert-info');
                }, 3000);
            }

            function autoSubmit() {
                if (!answerSubmitted && selectedOption) {
                    submitAnswer();
                } else if (!answerSubmitted) {
                    // Enviar respuesta vacía por tiempo
                    feedbackAlert.classList.remove('d-none');
                    feedbackText.innerHTML = '<i class="fas fa-clock me-2 text-warning"></i>Tiempo agotado';
                    feedbackAlert.classList.remove('alert-info');
                    feedbackAlert.classList.add('alert-warning');
                }
            }

            function updateMiniLeaderboard() {
                fetch(`<?php echo BASE_URL; ?>microservices/trivia-play/api/mini_leaderboard.php?trivia_id=<?php echo $gameData['trivia_id']; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderMiniLeaderboard(data.leaderboard);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function renderMiniLeaderboard(leaderboard) {
                let html = '';
                leaderboard.slice(0, 5).forEach((player, index) => {
                    const isCurrentPlayer = player.id == playerId;
                    html += `
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 ${isCurrentPlayer ? 'bg-light rounded' : ''}">
                            <div class="d-flex align-items-center">
                                <span class="badge ${getRankClass(index)} me-2">${index + 1}</span>
                                <small>${escapeHtml(player.player_name)}</small>
                            </div>
                            <span class="badge bg-dark">${player.score}</span>
                        </div>
                    `;
                });
                
                miniLeaderboard.innerHTML = html;
            }

            function getRankClass(rank) {
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