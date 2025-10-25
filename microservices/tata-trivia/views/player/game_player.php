<?php
$user = $user ?? getTriviaMicroappsUser();
$player_id = $_GET['player_id'] ?? null;
$trivia_id = $_GET['trivia_id'] ?? null;

if (!$player_id) {
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}

// Obtener información del jugador y trivia
try {
    $triviaController = new TriviaController();
    
    // Aquí necesitaríamos una función para obtener info del jugador
    // Por ahora usamos datos básicos
    $player_info = [
        'id' => $player_id,
        'name' => $user['first_name'] ?? 'Jugador',
        'avatar' => $user['avatar'] ?? 'default1'
    ];
    
} catch (Exception $e) {
    // En caso de error, redirigir al join
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
        .question-card {
            background: rgba(255,255,255,0.95);
            color: #333;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .option-button {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }
        .option-button:hover {
            border-color: #007bff;
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .option-button.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .option-button.correct {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        .option-button.incorrect {
            border-color: #dc3545;
            background: #dc3545;
            color: white;
        }
        .timer-container {
            background: rgba(0,0,0,0.8);
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
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .blink {
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
    </style>
</head>
<body class="game-container">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-0">
                            <i class="fas fa-gamepad me-2"></i>
                            Tata Trivia
                        </h1>
                        <p class="mb-0">¡Preparado para jugar!</p>
                    </div>
                    <div class="player-info">
                        <div class="d-flex align-items-center">
                            <img src="/public/assets/img/default/<?php echo $player_info['avatar']; ?>.png" 
                                 class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($player_info['name']); ?></div>
                                <small>Puntos: <span id="playerScore">0</span></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <!-- Pantalla de Espera -->
                <div id="waitingScreen" class="text-center">
                    <div class="card question-card">
                        <div class="card-body py-5">
                            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <h3 class="text-primary">Esperando siguiente pregunta</h3>
                            <p class="text-muted">El anfitrión prepara la siguiente ronda...</p>
                            <div class="mt-4">
                                <div class="badge bg-warning text-dark fs-6">
                                    <i class="fas fa-crown me-1"></i>
                                    Modo Jugador
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pantalla de Pregunta -->
                <div id="questionScreen" style="display: none;">
                    <div class="card question-card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" id="questionNumber">Pregunta 1</h4>
                            <div class="timer-container text-warning" id="timerDisplay">
                                30
                            </div>
                        </div>
                        <div class="card-body">
                            <h2 id="questionText" class="text-center mb-4"></h2>
                            
                            <div id="optionsContainer" class="mb-4">
                                <!-- Las opciones se generan dinámicamente -->
                            </div>
                            
                            <div class="text-center">
                                <button id="submitButton" class="btn btn-success btn-lg" onclick="submitAnswer()" disabled>
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pantalla de Resultados -->
                <div id="resultsScreen" style="display: none;">
                    <div class="card question-card">
                        <div class="card-body py-5 text-center">
                            <div id="resultIcon" class="mb-3">
                                <i class="fas fa-check-circle fa-4x text-success"></i>
                            </div>
                            <h3 id="resultTitle" class="text-success">¡Correcto!</h3>
                            <p id="resultMessage" class="text-muted">+10 puntos</p>
                            <div class="mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-trophy me-2"></i>
                                    Esperando a los demás jugadores...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pantalla de Leaderboard -->
                <div id="leaderboardScreen" style="display: none;">
                    <div class="card question-card">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>
                                Clasificación
                            </h4>
                        </div>
                        <div class="card-body">
                            <div id="leaderboardList">
                                <!-- Leaderboard se genera dinámicamente -->
                            </div>
                            <div class="text-center mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Siguiente pregunta en <span id="nextQuestionCountdown">5</span> segundos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pantalla de Juego Terminado -->
                <div id="gameOverScreen" style="display: none;">
                    <div class="card question-card">
                        <div class="card-body py-5 text-center">
                            <i class="fas fa-flag-checkered fa-4x text-primary mb-3"></i>
                            <h3 class="text-primary">¡Juego Terminado!</h3>
                            <p class="text-muted">Gracias por participar</p>
                            <div class="mt-4">
                                <a href="/microservices/tata-trivia/" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const playerId = '<?php echo $player_id; ?>';
        const triviaId = '<?php echo $trivia_id; ?>' || getTriviaIdFromURL();
        let currentQuestion = null;
        let selectedOption = null;
        let timer = null;
        let timeLeft = 0;
        let playerScore = 0;
        let gameActive = false;

        // Elementos DOM
        const waitingScreen = document.getElementById('waitingScreen');
        const questionScreen = document.getElementById('questionScreen');
        const resultsScreen = document.getElementById('resultsScreen');
        const leaderboardScreen = document.getElementById('leaderboardScreen');
        const gameOverScreen = document.getElementById('gameOverScreen');
        const questionText = document.getElementById('questionText');
        const questionNumber = document.getElementById('questionNumber');
        const optionsContainer = document.getElementById('optionsContainer');
        const timerDisplay = document.getElementById('timerDisplay');
        const submitButton = document.getElementById('submitButton');
        const playerScoreElement = document.getElementById('playerScore');
        const resultIcon = document.getElementById('resultIcon');
        const resultTitle = document.getElementById('resultTitle');
        const resultMessage = document.getElementById('resultMessage');
        const leaderboardList = document.getElementById('leaderboardList');
        const nextQuestionCountdown = document.getElementById('nextQuestionCountdown');

        // Obtener trivia ID de la URL
        function getTriviaIdFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('trivia_id') || 'default';
        }

        // Sistema de comunicación real
        class PlayerClient {
            constructor(playerId, triviaId) {
                this.playerId = playerId;
                this.triviaId = triviaId;
                this.setupCommunication();
            }
            
            setupCommunication() {
                console.log('🎮 Jugador conectado. Trivia ID:', this.triviaId);
                
                // Escuchar mensajes del host
                window.addEventListener('storage', (e) => {
                    if (e.key === `trivia_${this.triviaId}_to_players` && e.newValue) {
                        try {
                            const message = JSON.parse(e.newValue);
                            console.log('📨 Mensaje recibido del host:', message.type);
                            this.handleMessage(message);
                        } catch (error) {
                            console.error('❌ Error parsing message:', error);
                        }
                    }
                });
                
                // También verificar periódicamente por si se pierden mensajes
                this.setupPolling();
            }
            
            setupPolling() {
                // Verificar mensajes cada segundo
                setInterval(() => {
                    const message = localStorage.getItem(`trivia_${this.triviaId}_to_players`);
                    if (message) {
                        try {
                            const parsed = JSON.parse(message);
                            this.handleMessage(parsed);
                        } catch (error) {
                            console.error('Error in polling:', error);
                        }
                    }
                }, 1000);
            }
            
            handleMessage(message) {
                console.log('🔄 Procesando mensaje:', message.type);
                
                switch (message.type) {
                    case 'question_started':
                        this.startQuestion(message.data);
                        break;
                    case 'question_ended':
                        this.endQuestion();
                        break;
                    case 'show_results':
                        this.showResults(message.data);
                        break;
                    case 'show_leaderboard':
                        this.showLeaderboard(message.data);
                        break;
                    case 'game_ended':
                        this.endGame();
                        break;
                }
            }
            
            startQuestion(questionData) {
                currentQuestion = questionData.question;
                gameActive = true;
                selectedOption = null;
                
                console.log('❓ Nueva pregunta:', currentQuestion.question_text);
                
                // Mostrar pantalla de pregunta
                waitingScreen.style.display = 'none';
                questionScreen.style.display = 'block';
                resultsScreen.style.display = 'none';
                leaderboardScreen.style.display = 'none';
                gameOverScreen.style.display = 'none';
                
                // Actualizar pregunta
                questionText.textContent = currentQuestion.question_text;
                questionNumber.textContent = `Pregunta ${questionData.questionIndex + 1}`;
                
                // Generar opciones
                this.displayOptions();
                
                // Iniciar temporizador
                this.startTimer(questionData.timeLimit);
                
                // Habilitar/deshabilitar botón
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Respuesta';
                submitButton.className = 'btn btn-success btn-lg';
            }
            
            displayOptions() {
                optionsContainer.innerHTML = '';
                const options = currentQuestion.options;
                const letters = ['A', 'B', 'C', 'D'];
                
                options.forEach((option, index) => {
                    const optionHTML = `
                        <div class="option-button" onclick="selectOption(${index})" id="option-${index}">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-3">${letters[index]}</span>
                                <span>${option.text}</span>
                            </div>
                        </div>
                    `;
                    optionsContainer.innerHTML += optionHTML;
                });
            }
            
            startTimer(seconds) {
                timeLeft = seconds;
                timerDisplay.textContent = timeLeft;
                timerDisplay.className = 'timer-container text-warning';
                
                clearInterval(timer);
                timer = setInterval(() => {
                    timeLeft--;
                    timerDisplay.textContent = timeLeft;
                    
                    // Cambiar color según el tiempo
                    if (timeLeft <= 10) {
                        timerDisplay.className = 'timer-container text-danger blink';
                    } else if (timeLeft <= 20) {
                        timerDisplay.className = 'timer-container text-warning';
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        this.autoSubmit();
                    }
                }, 1000);
            }
            
            endQuestion() {
                gameActive = false;
                clearInterval(timer);
                submitButton.disabled = true;
                console.log('⏹️ Tiempo agotado para la pregunta');
            }
            
            showResults(resultsData) {
                questionScreen.style.display = 'none';
                resultsScreen.style.display = 'block';
                
                console.log('📊 Mostrando resultados');
                
                // Mostrar si la respuesta fue correcta
                if (selectedOption !== null) {
                    const wasCorrect = currentQuestion.options[selectedOption].is_correct;
                    
                    if (wasCorrect) {
                        resultIcon.innerHTML = '<i class="fas fa-check-circle fa-4x text-success"></i>';
                        resultTitle.textContent = '¡Correcto!';
                        resultTitle.className = 'text-success';
                        resultMessage.textContent = '+10 puntos';
                        
                        // Actualizar puntaje
                        playerScore += 10;
                        playerScoreElement.textContent = playerScore;
                    } else {
                        resultIcon.innerHTML = '<i class="fas fa-times-circle fa-4x text-danger"></i>';
                        resultTitle.textContent = 'Incorrecto';
                        resultTitle.className = 'text-danger';
                        resultMessage.textContent = 'Mejor suerte en la siguiente';
                    }
                    
                    // Resaltar opciones correctas/incorrectas
                    this.highlightAnswers();
                } else {
                    // Si no respondió
                    resultIcon.innerHTML = '<i class="fas fa-clock fa-4x text-warning"></i>';
                    resultTitle.textContent = 'Tiempo agotado';
                    resultTitle.className = 'text-warning';
                    resultMessage.textContent = 'No respondiste a tiempo';
                    this.highlightAnswers();
                }
            }
            
            highlightAnswers() {
                currentQuestion.options.forEach((option, index) => {
                    const optionElement = document.getElementById(`option-${index}`);
                    if (option.is_correct) {
                        optionElement.className = 'option-button correct';
                    } else if (index === selectedOption) {
                        optionElement.className = 'option-button incorrect';
                    }
                });
            }
            
            showLeaderboard(leaderboardData) {
                resultsScreen.style.display = 'none';
                leaderboardScreen.style.display = 'block';
                
                console.log('🏆 Mostrando leaderboard');
                
                // Generar leaderboard
                leaderboardList.innerHTML = '';
                leaderboardData.players.forEach((player, index) => {
                    const positionClass = index === 0 ? 'bg-warning text-dark' : 
                                       index === 1 ? 'bg-secondary text-white' : 
                                       index === 2 ? 'bg-danger text-white' : 'bg-light';
                    
                    const isCurrentPlayer = player.id === this.playerId;
                    const highlightClass = isCurrentPlayer ? 'border border-3 border-primary' : '';
                    
                    const playerHTML = `
                        <div class="d-flex align-items-center p-3 mb-2 rounded ${positionClass} ${highlightClass}">
                            <div class="fw-bold me-3" style="width: 30px;">#${index + 1}</div>
                            <div class="flex-grow-1">${player.name} ${isCurrentPlayer ? ' (Tú)' : ''}</div>
                            <div class="fw-bold">${player.score} pts</div>
                        </div>
                    `;
                    leaderboardList.innerHTML += playerHTML;
                });
                
                // Iniciar cuenta regresiva para siguiente pregunta
                this.startNextQuestionCountdown();
            }
            
            startNextQuestionCountdown() {
                let countdown = 5;
                nextQuestionCountdown.textContent = countdown;
                
                const countdownInterval = setInterval(() => {
                    countdown--;
                    nextQuestionCountdown.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        waitingScreen.style.display = 'block';
                        leaderboardScreen.style.display = 'none';
                    }
                }, 1000);
            }
            
            endGame() {
                clearInterval(timer);
                gameActive = false;
                
                waitingScreen.style.display = 'none';
                questionScreen.style.display = 'none';
                resultsScreen.style.display = 'none';
                leaderboardScreen.style.display = 'none';
                gameOverScreen.style.display = 'block';
                
                console.log('🎯 Juego terminado');
            }
            
            autoSubmit() {
                if (gameActive && selectedOption === null) {
                    console.log('⏰ Auto-enviando respuesta aleatoria');
                    // Enviar respuesta aleatoria si no se seleccionó
                    selectedOption = Math.floor(Math.random() * currentQuestion.options.length);
                    this.submitAnswer();
                }
            }
            
            submitAnswer() {
                if (selectedOption === null || !gameActive) return;
                
                const isCorrect = currentQuestion.options[selectedOption].is_correct;
                const responseTime = (currentQuestion.time_limit || 30) - timeLeft;
                
                const answerData = {
                    playerId: this.playerId,
                    playerName: '<?php echo $player_info["name"]; ?>',
                    questionIndex: 0,
                    optionIndex: selectedOption,
                    isCorrect: isCorrect,
                    responseTime: responseTime,
                    timestamp: Date.now()
                };
                
                console.log('📤 Enviando respuesta:', answerData);
                
                // Enviar respuesta al host
                localStorage.setItem(`trivia_${this.triviaId}_to_host`, JSON.stringify({
                    type: 'player_answer',
                    data: answerData
                }));
                
                // Limpiar después de enviar
                setTimeout(() => {
                    localStorage.removeItem(`trivia_${this.triviaId}_to_host`);
                }, 100);
                
                // Deshabilitar más respuestas
                submitButton.disabled = true;
                gameActive = false;
                
                // Mostrar que se envió
                submitButton.innerHTML = '<i class="fas fa-check me-2"></i>Respuesta Enviada';
                submitButton.className = 'btn btn-secondary btn-lg';
                
                console.log('✅ Respuesta enviada correctamente');
            }
        }

        // Funciones globales para los botones
        function selectOption(optionIndex) {
            if (!gameActive) return;
            
            // Deseleccionar anterior
            if (selectedOption !== null) {
                document.getElementById(`option-${selectedOption}`).classList.remove('selected');
            }
            
            // Seleccionar nueva opción
            selectedOption = optionIndex;
            document.getElementById(`option-${selectedOption}`).classList.add('selected');
            
            // Habilitar botón de envío
            submitButton.disabled = false;
            
            console.log('🔘 Opción seleccionada:', optionIndex);
        }
        
        function submitAnswer() {
            if (playerClient) {
                playerClient.submitAnswer();
            }
        }

        // Inicializar cliente del jugador
        let playerClient;
        document.addEventListener('DOMContentLoaded', function() {
            playerClient = new PlayerClient(playerId, triviaId);
            
            console.log('🎮 Jugador inicializado:', playerId, 'en trivia:', triviaId);
        });
    </script>
</body>
</html>