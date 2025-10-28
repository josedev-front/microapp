<?php
// microservices/tata-trivia/views/player/game_player.php

require_once __DIR__ . '/../../init.php';

$trivia_id = $_GET['trivia_id'] ?? null;
$player_id = $_GET['player_id'] ?? null;

// DEBUG
error_log("=== GAME_PLAYER.PHP ===");
error_log("GET - trivia_id: $trivia_id, player_id: $player_id");

// INTENTAR OBTENER player_id DE DIFERENTES FUENTES
if (!$player_id) {
    // 1. Intentar de la sesión específica de la trivia
    $session_key = 'player_id_' . $trivia_id;
    if (isset($_SESSION[$session_key])) {
        $player_id = $_SESSION[$session_key];
        error_log("Player ID obtenido de sesión específica: $player_id");
    }
    // 2. Intentar de la sesión general
    elseif (isset($_SESSION['current_player_id'])) {
        $player_id = $_SESSION['current_player_id'];
        error_log("Player ID obtenido de sesión general: $player_id");
    }
    // 3. Intentar de current_trivia_id
    elseif (isset($_SESSION['current_trivia_id']) && $_SESSION['current_trivia_id'] == $trivia_id) {
        $player_id = $_SESSION['player_id_' . $trivia_id] ?? null;
        error_log("Player ID obtenido de current_trivia_id: $player_id");
    }
}

// Si aún no hay player_id, mostrar página de error con opciones
if (!$trivia_id || !$player_id) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Tata Trivia</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-4"></i>
                            <h3 class="text-danger mb-4">No se pudo cargar el juego</h3>
                            
                            <div class="alert alert-info text-start mb-4">
                                <strong>Información del error:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Trivia ID:</strong> <?= htmlspecialchars($trivia_id ?? 'No proporcionado') ?></li>
                                    <li><strong>Player ID:</strong> <?= htmlspecialchars($player_id ?? 'No proporcionado') ?></li>
                                    <li><strong>Sesión activa:</strong> <?= session_id() ?></li>
                                </ul>
                            </div>
                            
                            <p class="text-muted mb-4">
                                Esto puede pasar si:
                                <br>• Recargaste la página después de unirse
                                <br>• Los parámetros se perdieron en la URL
                                <br>• La sesión expiró
                            </p>
                            
                            <div class="mt-4">
                                <a href="/microservices/tata-trivia/player/join" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-door-open me-1"></i>Volver a Unirse
                                </a>
                                <a href="/microservices/tata-trivia/" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-home me-1"></i>Ir al Inicio
                                </a>
                            </div>
                            
                            <div class="mt-4">
                                <small class="text-muted">
                                    Si el problema persiste, contacta al anfitrión del juego.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// CONTINUAR CON LA LÓGICA NORMAL SI TENEMOS LOS PARÁMETROS
try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    $player = $triviaController->getPlayerById($player_id);
    
    error_log("Datos obtenidos - Trivia: " . ($trivia ? 'SÍ' : 'NO') . ", Player: " . ($player ? 'SÍ' : 'NO'));
    
    // Si no se encuentran datos, mostrar error
    if (!$trivia || !$player) {
        error_log("ERROR: Trivia o Player no encontrados en BD");
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - Tata Trivia</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="alert alert-warning">
                    <h4><i class="fas fa-user-slash"></i> Jugador o Trivia no encontrados</h4>
                    <p>La información del juego no existe en el sistema.</p>
                    <div class="mt-3">
                        <a href="/microservices/tata-trivia/player/join" class="btn btn-primary me-2">Unirse a otra trivia</a>
                        <a href="/microservices/tata-trivia/" class="btn btn-outline-secondary">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    // DATOS ENCONTRADOS - CONTINUAR NORMALMENTE
    $currentQuestionIndex = $triviaController->getCurrentQuestionIndex($trivia_id);
    $backgroundImage = $triviaController->getBackgroundImagePath($trivia_id);
    
    $currentQuestion = null;
    $questionBackground = $backgroundImage;

    if ($currentQuestionIndex >= 0) {
        $questions = $triviaController->getTriviaQuestions($trivia_id);
        if (isset($questions[$currentQuestionIndex])) {
            $currentQuestion = $questions[$currentQuestionIndex];
            if (!empty($currentQuestion['background_image'])) {
                $questionBackground = $currentQuestion['background_image'];
            } else {
                $questionBackground = $triviaController->getRandomQuestionBackground();
            }
        }
    }
    
    error_log("Juego cargado - Status: " . $trivia['status'] . ", QuestionIndex: $currentQuestionIndex");
    
} catch (Exception $e) {
    error_log("EXCEPCIÓN en game_player: " . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Tata Trivia</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4><i class="fas fa-bug"></i> Error del sistema</h4>
                <p>Ocurrió un error al cargar el juego.</p>
                <a href="/microservices/tata-trivia/" class="btn btn-primary">Volver al inicio</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si llegamos aquí, todo está bien - mostrar el juego
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jugando - <?= htmlspecialchars($trivia['title']) ?> - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url('<?= $questionBackground ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .game-container {
            background: rgba(255, 255, 255, 0.95);
            min-height: 100vh;
            padding: 20px 0;
        }
        .question-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .option-btn {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: left;
        }
        .option-btn:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .option-btn.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .option-btn.correct {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        .option-btn.incorrect {
            border-color: #dc3545;
            background: #dc3545;
            color: white;
        }
        .timer-container {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .player-info {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .waiting-message {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    
                    <!-- Información del jugador -->
                    <div class="player-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="player-avatar me-3" 
                                     style="width: 50px; height: 50px; border-radius: 50%; background-image: url('/microservices/tata-trivia/assets/images/avatars/<?= $player['avatar'] ?? 'default1' ?>.png'); background-size: cover;">
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($player['player_name'] ?? 'Jugador') ?></h5>
                                    <small class="text-muted">Puntuación: <span id="playerScore"><?= $player['score'] ?? 0 ?></span></small>
                                    <br><small class="text-info">ID: <?= $player_id ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if ($trivia['status'] === 'active' && $currentQuestion): ?>
                                <div class="timer-container" id="timer">
                                    <?= $currentQuestion['time_limit'] ?? 30 ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del juego -->
                    <div id="gameContent">
                        <?php if ($trivia['status'] === 'setup' || $trivia['status'] === 'waiting'): ?>
                            <!-- Esperando a que comience el juego -->
                            <div class="waiting-message">
                                <i class="fas fa-hourglass-half fa-3x text-primary mb-3"></i>
                                <h3>Esperando a que el anfitrión inicie el juego</h3>
                                <p class="text-muted mb-4">Mantente atento, la trivia comenzará pronto...</p>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Estado actual: <?= strtoupper($trivia['status']) ?></small><br>
                                    <small class="text-muted">Trivia: <?= htmlspecialchars($trivia['title']) ?></small>
                                </div>
                            </div>
                            
                        <?php elseif ($trivia['status'] === 'active' && $currentQuestion): ?>
                            <!-- Pregunta activa -->
                            <div class="question-card">
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <h2 class="text-primary">Pregunta <?= $currentQuestionIndex + 1 ?></h2>
                                    </div>
                                    
                                    <div class="question-text mb-4">
                                        <h4 class="text-center"><?= htmlspecialchars($currentQuestion['question_text']) ?></h4>
                                    </div>
                                    
                                    <div class="options-container">
                                        <?php 
                                        // Obtener opciones de la pregunta
                                        $options = [];
                                        try {
                                            $db = getTriviaDatabaseConnection();
                                            $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY id");
                                            $stmt->execute([$currentQuestion['id']]);
                                            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            error_log("Error getting options: " . $e->getMessage());
                                        }
                                        
                                        foreach ($options as $index => $option): 
                                        ?>
                                        <div class="option-btn" data-option-id="<?= $option['id'] ?>" 
                                             onclick="selectOption(this, <?= $option['id'] ?>)">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-3"><?= chr(65 + $index) ?></span>
                                                <span><?= htmlspecialchars($option['option_text']) ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                        <?php elseif ($trivia['status'] === 'finished'): ?>
                            <!-- Juego terminado -->
                            <div class="waiting-message">
                                <i class="fas fa-flag-checkered fa-3x text-success mb-3"></i>
                                <h3>¡Trivia Terminada!</h3>
                                <p class="text-muted mb-4">Gracias por participar</p>
                                <a href="/microservices/tata-trivia/player/results?trivia_id=<?= $trivia_id ?>&player_id=<?= $player_id ?>" 
                                   class="btn btn-primary btn-lg">
                                    Ver Resultados
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <!-- Estado desconocido o sin pregunta activa -->
                            <div class="waiting-message">
                                <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                                <h3>Preparando siguiente pregunta...</h3>
                                <p class="text-muted mb-4">Por favor espera</p>
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const triviaId = '<?= $trivia_id ?>';
        const playerId = '<?= $player_id ?>';
        let timerInterval;
        let timeLeft = <?= $currentQuestion['time_limit'] ?? 30 ?>;

        // Timer countdown - solo si hay pregunta activa
        function startTimer() {
            const timerElement = document.getElementById('timer');
            if (!timerElement) return;
            
            timeLeft = <?= $currentQuestion['time_limit'] ?? 30 ?>;
            timerElement.textContent = timeLeft;
            
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    // El tiempo se agotó
                    if (document.querySelector('.option-btn.selected')) {
                        submitAnswer();
                    } else {
                        // Si no seleccionó ninguna opción, marcar como no respondida
                        document.querySelectorAll('.option-btn').forEach(btn => {
                            btn.style.pointerEvents = 'none';
                        });
                    }
                }
            }, 1000);
        }

        function selectOption(optionElement, optionId) {
            // Deseleccionar todas las opciones
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Seleccionar la opción clickeada
            optionElement.classList.add('selected');
            
            // Enviar respuesta automáticamente después de seleccionar
            setTimeout(submitAnswer, 500);
        }

        function submitAnswer() {
            const selectedOption = document.querySelector('.option-btn.selected');
            if (!selectedOption) return;
            
            const optionId = selectedOption.dataset.optionId;
            const responseTime = <?= $currentQuestion['time_limit'] ?? 30 ?> - timeLeft;
            
            // Deshabilitar más clicks
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.style.pointerEvents = 'none';
            });
            
            // Detener el timer
            clearInterval(timerInterval);
            
            // Enviar respuesta al servidor
            fetch('/microservices/tata-trivia/api/submit_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    player_id: playerId,
                    question_id: <?= $currentQuestion['id'] ?? 0 ?>,
                    option_id: optionId,
                    response_time: responseTime
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar si la respuesta fue correcta o incorrecta
                    if (data.is_correct) {
                        selectedOption.classList.add('correct');
                        // Actualizar puntuación
                        if (document.getElementById('playerScore')) {
                            document.getElementById('playerScore').textContent = data.new_score;
                        }
                    } else {
                        selectedOption.classList.add('incorrect');
                        // Mostrar la opción correcta
                        document.querySelectorAll('.option-btn').forEach(btn => {
                            if (btn.dataset.optionId == data.correct_option_id) {
                                btn.classList.add('correct');
                            }
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Verificar estado del juego periódicamente - SOLO si no hay pregunta activa
        function checkGameStatus() {
            // No verificar si estamos en medio de una pregunta
            if (document.querySelector('.option-btn') && !document.querySelector('.option-btn').style.pointerEvents) {
                return;
            }
            
            fetch(`/microservices/tata-trivia/api/game_status.php?trivia_id=${triviaId}&player_id=${playerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status_changed || data.question_changed) {
                        console.log('Estado cambiado, recargando...');
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($trivia['status'] === 'active' && $currentQuestion): ?>
            startTimer();
            <?php else: ?>
            // Verificar estado cada 3 segundos solo si estamos esperando
            setInterval(checkGameStatus, 3000);
            <?php endif; ?>
        });
    </script>
</body>
</html>