<?php
// microservices/tata-trivia/views/player/game_player.php

$trivia_id = $_GET['trivia_id'] ?? '';
$player_id = $_SESSION['player_id'] ?? '';

if (empty($trivia_id) || empty($player_id)) {
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}

// Obtener información del jugador
try {
    $triviaController = new TriviaController();
    $player = $triviaController->getPlayerById($player_id);
    $trivia = $triviaController->getTriviaById($trivia_id);
    
    if (!$player || !$trivia) {
        throw new Exception('Datos no encontrados');
    }
} catch (Exception $e) {
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jugando - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .game-container { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            color: white; 
        }
        .question-display { 
            background: rgba(255,255,255,0.95); 
            color: #333; 
            border-radius: 15px; 
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .option-btn { 
            background: rgba(255,255,255,0.9); 
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .option-btn:hover { 
            background: rgba(255,255,255,1); 
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .option-btn.selected { 
            background: #007bff; 
            color: white; 
            border-color: #0056b3;
        }
        .option-btn.correct { 
            background: #28a745; 
            color: white; 
            border-color: #1e7e34;
        }
        .option-btn.incorrect { 
            background: #dc3545; 
            color: white; 
            border-color: #c82333;
        }
        .option-btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .timer-bar { 
            height: 10px; 
            background: #28a745; 
            transition: width 1s linear;
            border-radius: 5px;
        }
        .player-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="game-container">
    <div class="container py-4">
        <!-- Información del Jugador -->
        <div class="player-info">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        <?php echo htmlspecialchars($player['player_name']); ?>
                    </h4>
                    <small class="text-light"><?php echo htmlspecialchars($trivia['title']); ?></small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="h5 mb-0">
                        <i class="fas fa-star text-warning me-1"></i>
                        <span id="playerScore">0</span> puntos
                    </div>
                </div>
            </div>
        </div>

        <!-- Pantalla de Espera -->
        <div id="waitingScreen" class="text-center py-5">
            <div class="spinner-border text-warning mb-4" style="width: 4rem; height: 4rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h2 class="text-warning">Esperando pregunta...</h2>
            <p class="lead">El anfitrión prepara la siguiente ronda</p>
            <div class="mt-4">
                <div class="badge bg-info fs-6">
                    <i class="fas fa-users me-1"></i> Conectado
                </div>
            </div>
        </div>

        <!-- Pantalla de Pregunta -->
        <div id="questionScreen" style="display: none;">
            <div class="card bg-dark border-light shadow-lg">
                <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                    <h4 id="questionHeader" class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Pregunta
                    </h4>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2" id="questionCounter">1/10</span>
                        <div class="bg-danger text-white rounded-pill px-3 py-1">
                            <i class="fas fa-clock me-1"></i><span id="timerDisplay">30</span>s
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Barra de tiempo -->
                    <div class="mb-4">
                        <div class="progress" style="height: 12px; border-radius: 6px;">
                            <div id="timerBar" class="timer-bar" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <!-- Pregunta -->
                    <div class="question-display mb-4">
                        <h2 id="questionText" class="text-center mb-0 fw-bold"></h2>
                    </div>
                    
                    <!-- Opciones -->
                    <div class="row" id="optionsContainer">
                        <!-- Opciones se generan aquí -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Pantalla de Resultados -->
        <div id="resultsScreen" style="display: none;">
            <div class="card bg-dark border-light shadow-lg">
                <div class="card-body text-center py-5">
                    <div id="resultIcon" class="mb-3" style="font-size: 4rem;"></div>
                    <h2 id="resultTitle" class="mb-3"></h2>
                    <p id="resultMessage" class="lead mb-4"></p>
                    <div id="correctAnswer" class="mt-3"></div>
                    <div class="mt-4">
                        <div class="badge bg-secondary fs-6">
                            <i class="fas fa-clock me-1"></i>
                            Tiempo: <span id="responseTime">0</span>s
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pantalla de Juego Terminado -->
        <div id="gameOverScreen" style="display: none;">
            <div class="text-center py-5">
                <i class="fas fa-flag-checkered fa-5x text-warning mb-4"></i>
                <h2 class="text-warning">¡Juego Terminado!</h2>
                <p class="lead">Gracias por participar en <?php echo htmlspecialchars($trivia['title']); ?></p>
                <div class="mt-4">
                    <button class="btn btn-primary btn-lg me-3" onclick="window.location.href='/microservices/tata-trivia/results?trivia_id=<?php echo $trivia_id; ?>'">
                        <i class="fas fa-trophy me-2"></i>Ver Resultados Finales
                    </button>
                    <button class="btn btn-outline-light btn-lg" onclick="window.location.href='/microservices/tata-trivia/'">
                        <i class="fas fa-home me-2"></i>Volver al Inicio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const triviaId = '<?php echo $trivia_id; ?>';
        const playerId = '<?php echo $player_id; ?>';
        const playerName = '<?php echo addslashes($player['player_name']); ?>';
        let currentQuestion = null;
        let selectedOption = null;
        let questionStartTime = null;
        let timerInterval = null;
        let playerScore = 0;

        class PlayerGame {
            constructor() {
                this.checkGameState();
                // Consultar estado cada 2 segundos
                setInterval(() => this.checkGameState(), 2000);
                
                console.log('🎮 Jugador inicializado:', { playerId, playerName, triviaId });
            }

            async checkGameState() {
                try {
                    const response = await fetch('/microservices/tata-trivia/api/player_communication.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'get_game_state',
                            trivia_id: triviaId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.handleGameState(data);
                    } else {
                        console.error('Error en game state:', data.error);
                    }
                } catch (error) {
                    console.error('Error checking game state:', error);
                }
            }

            handleGameState(state) {
                console.log('🎮 Estado del juego:', state);
                
                if (state.trivia_status === 'finished') {
                    this.showGameOver();
                    return;
                }

                if (state.current_question && !this.isSameQuestion(state.current_question)) {
                    this.showQuestion(state.current_question);
                } else if (!state.current_question && document.getElementById('questionScreen').style.display !== 'none') {
                    this.showWaiting();
                }
            }

            isSameQuestion(question) {
                return currentQuestion && currentQuestion.id === question.id;
            }

            showWaiting() {
                document.getElementById('waitingScreen').style.display = 'block';
                document.getElementById('questionScreen').style.display = 'none';
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('gameOverScreen').style.display = 'none';
            }

            showQuestion(question) {
                console.log('📝 Mostrando pregunta:', question.question_text);
                
                currentQuestion = question;
                questionStartTime = Date.now();
                selectedOption = null;

                // Ocultar otras pantallas, mostrar pregunta
                document.getElementById('waitingScreen').style.display = 'none';
                document.getElementById('questionScreen').style.display = 'block';
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('gameOverScreen').style.display = 'none';

                // Mostrar pregunta
                document.getElementById('questionText').textContent = question.question_text;

                // Mostrar opciones
                const optionsContainer = document.getElementById('optionsContainer');
                optionsContainer.innerHTML = '';
                
                const letters = ['A', 'B', 'C', 'D'];
                question.options.forEach((option, index) => {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'col-md-6 mb-3';
                    optionDiv.innerHTML = `
                        <button class="option-btn btn w-100 p-3 text-start" 
                                onclick="playerGame.selectOption(${index}, ${option.id})"
                                data-option-id="${option.id}">
                            <span class="fw-bold me-2">${letters[index]}.</span>
                            ${option.text}
                        </button>
                    `;
                    optionsContainer.appendChild(optionDiv);
                });

                // Iniciar temporizador
                this.startTimer(question.time_limit || 30);
            }

            startTimer(seconds) {
                let timeLeft = seconds;
                const timerDisplay = document.getElementById('timerDisplay');
                const timerBar = document.getElementById('timerBar');
                
                timerDisplay.textContent = timeLeft;
                timerBar.style.width = '100%';
                timerBar.className = 'timer-bar bg-success';

                clearInterval(timerInterval);
                timerInterval = setInterval(() => {
                    timeLeft--;
                    timerDisplay.textContent = timeLeft;
                    const percentage = (timeLeft / seconds) * 100;
                    timerBar.style.width = percentage + '%';

                    // Cambiar color según el tiempo
                    if (timeLeft <= 10) {
                        timerBar.className = 'timer-bar bg-danger';
                    } else if (timeLeft <= 20) {
                        timerBar.className = 'timer-bar bg-warning';
                    }

                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        this.submitAnswer();
                    }
                }, 1000);
            }

            selectOption(optionIndex, optionId) {
                if (selectedOption !== null) {
                    console.log('⚠️ Ya se seleccionó una opción');
                    return; // Ya respondió
                }

                selectedOption = { index: optionIndex, id: optionId };
                
                console.log('✅ Opción seleccionada:', selectedOption);
                
                // Marcar opción seleccionada
                const optionButtons = document.querySelectorAll('.option-btn');
                optionButtons.forEach(btn => {
                    btn.classList.remove('selected');
                    btn.disabled = true;
                });
                optionButtons[optionIndex].classList.add('selected');

                // Enviar respuesta automáticamente
                setTimeout(() => this.submitAnswer(), 500);
            }

            async submitAnswer() {
                console.log('📤 Enviando respuesta...');
                
                clearInterval(timerInterval);

                const responseTime = Date.now() - questionStartTime;
                
                try {
                    const response = await fetch('/microservices/tata-trivia/api/player_communication.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'submit_answer',
                            trivia_id: triviaId,
                            player_id: playerId,
                            question_id: currentQuestion.id,
                            option_id: selectedOption ? selectedOption.id : null,
                            response_time: responseTime
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.showResult(data.is_correct, responseTime);
                        
                        // Actualizar puntaje
                        if (data.is_correct) {
                            const points = Math.max(1000 - Math.floor(responseTime / 10), 100);
                            playerScore += points;
                            document.getElementById('playerScore').textContent = playerScore;
                        }
                    } else {
                        this.showResult(false, responseTime, data.error);
                    }

                } catch (error) {
                    console.error('Error submitting answer:', error);
                    this.showResult(false, responseTime, 'Error al enviar respuesta');
                }
            }

            showResult(isCorrect, responseTime, message = '') {
                console.log('📊 Mostrando resultado:', { isCorrect, responseTime });
                
                document.getElementById('questionScreen').style.display = 'none';
                document.getElementById('resultsScreen').style.display = 'block';

                const resultIcon = document.getElementById('resultIcon');
                const resultTitle = document.getElementById('resultTitle');
                const resultMessage = document.getElementById('resultMessage');
                const responseTimeElement = document.getElementById('responseTime');

                if (isCorrect) {
                    resultIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                    resultTitle.textContent = '¡Correcto! 🎉';
                    resultTitle.className = 'text-success';
                    resultMessage.textContent = message || '¡Bien hecho! Respuesta correcta.';
                } else {
                    resultIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
                    resultTitle.textContent = 'Incorrecto 😔';
                    resultTitle.className = 'text-danger';
                    resultMessage.textContent = message || 'Respuesta incorrecta.';
                }

                responseTimeElement.textContent = (responseTime / 1000).toFixed(1);

                // Mostrar respuesta correcta si fue incorrecta
                if (!isCorrect && currentQuestion) {
                    const correctOption = currentQuestion.options.find(opt => opt.is_correct);
                    if (correctOption) {
                        const letters = ['A', 'B', 'C', 'D'];
                        const correctIndex = currentQuestion.options.findIndex(opt => opt.is_correct);
                        document.getElementById('correctAnswer').innerHTML = `
                            <div class="alert alert-info">
                                <strong><i class="fas fa-lightbulb me-2"></i>Respuesta correcta:</strong> 
                                ${letters[correctIndex]}. ${correctOption.text}
                            </div>
                        `;
                    }
                }
            }

            showGameOver() {
                console.log('🏁 Juego terminado');
                
                document.getElementById('waitingScreen').style.display = 'none';
                document.getElementById('questionScreen').style.display = 'none';
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('gameOverScreen').style.display = 'block';
            }
        }

        // Inicializar juego del jugador
        const playerGame = new PlayerGame();
    </script>
</body>
</html>