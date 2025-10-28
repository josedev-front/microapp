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
            background: purple;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        #waitingScreen {
            background: purple;
        }
        /* NUEVOS ESTILOS PARA RESULTADOS FINALES */
        .final-results-screen {
            background: rgba(0,0,0,0.9);
            border-radius: 20px;
            padding: 2rem;
            margin: 1rem 0;
        }
        .winner-card {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
            animation: glow 2s infinite alternate;
        }
        @keyframes glow {
            from { box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3); }
            to { box-shadow: 0 15px 40px rgba(255, 215, 0, 0.5); }
        }
        .podium-item {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .rank-1 { background: linear-gradient(45deg, #FFD700, #FFA500); }
        .rank-2 { background: linear-gradient(45deg, #C0C0C0, #A0A0A0); }
        .rank-3 { background: linear-gradient(45deg, #CD7F32, #A56C27); }
        .rank-other { background: linear-gradient(45deg, #667eea, #764ba2); }
        .player-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid;
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #FFD700;
            border-radius: 50%;
            animation: confetti-fall 5s linear forwards;
            z-index: 1000;
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }
    </style>
</head>
<body class="game-container" style="background-image: url('<?php echo get_theme_image('TRIVIA.png'); ?>'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="container py-4">
        <!-- Información del Jugador -->
        <div class="player-info card">
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
        <div id="waitingScreen" class="text-center py-5 card">
            <div class="spinner-border text-white mb-4" style="width: 4rem; height: 4rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h2 class="text-white">Esperando pregunta...</h2>
            <p class="lead text-white">El anfitrión prepara la siguiente ronda</p>
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

        <!-- Pantalla de Resultados de Pregunta -->
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
                    <!-- Información adicional de la pregunta -->
                    <div class="mt-4" id="questionStats" style="display: none;">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h4 text-success mb-1" id="correctCount">0</div>
                                <small class="text-light">Correctas</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-danger mb-1" id="totalAnswers">0</div>
                                <small class="text-light">Total respuestas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NUEVA PANTALLA: Leaderboard entre preguntas -->
        <div id="leaderboardScreen" style="display: none;">
            <div class="card bg-dark border-light shadow-lg">
                <div class="card-body text-center py-5">
                    <i class="fas fa-trophy fa-4x text-warning mb-3"></i>
                    <h3 class="text-warning">Clasificación Actual</h3>
                    <div id="leaderboardList" class="mt-4">
                        <!-- Leaderboard se genera aquí -->
                    </div>
                    <div class="mt-4">
                        <div class="badge bg-info fs-6">
                            <i class="fas fa-clock me-1"></i>Siguiente pregunta en <span id="nextQuestionCountdown">5</span>s
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NUEVA PANTALLA: Resultados Finales del Juego -->
        <div id="finalResultsScreen" style="display: none;">
            <div class="final-results-screen">
                <!-- Confetti -->
                <div id="confettiContainer"></div>

                <div class="text-center mb-5">
                    <i class="fas fa-flag-checkered fa-4x text-warning mb-3"></i>
                    <h2 class="text-warning">¡Juego Terminado!</h2>
                    <p class="lead text-light">Resultados Finales de <?php echo htmlspecialchars($trivia['title']); ?></p>
                </div>
                
                <!-- Tu Posición -->
                <div class="card bg-dark border-warning mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Tu Posición
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div id="playerRankBadge" class="rank-badge rank-other">0</div>
                            </div>
                            <div class="col-auto">
                                <img id="playerFinalAvatar" src="/public/assets/img/default/<?php echo $player['avatar']; ?>.png" 
                                     alt="Tu avatar" class="player-avatar">
                            </div>
                            <div class="col">
                                <h4 class="mb-1 text-light"><?php echo htmlspecialchars($player['player_name']); ?></h4>
                                <div class="h5 text-warning mb-0">
                                    <i class="fas fa-star me-1"></i><span id="playerFinalScore">0</span> puntos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ganador Principal -->
                <div class="winner-card">
                    <i class="fas fa-crown fa-3x mb-3" style="color: #FFD700;"></i>
                    <h1 class="display-4 fw-bold mb-3">¡Felicidades!</h1>
                    <div class="row justify-content-center align-items-center">
                        <div class="col-auto">
                            <img id="winnerAvatar" src="" alt="Ganador" class="player-avatar border-warning">
                        </div>
                        <div class="col-auto">
                            <h2 id="winnerName" class="fw-bold mb-1"></h2>
                            <p class="lead mb-0">Ganador de la trivia</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span id="winnerScore" class="badge bg-dark fs-6">
                            <i class="fas fa-star me-1"></i>0 puntos
                        </span>
                        <span id="winnerTeam" class="badge bg-primary fs-6 ms-2" style="display: none;"></span>
                    </div>
                </div>

                <!-- Podio de Ganadores -->
                <div class="mt-5">
                    <h4 class="text-center text-light mb-4">
                        <i class="fas fa-trophy me-2"></i>Podio de Ganadores
                    </h4>
                    <div id="finalLeaderboard">
                        <!-- Podio se genera aquí dinámicamente -->
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="text-center mt-5">
                    <button class="btn btn-warning btn-lg me-3" onclick="playAgain()">
                        <i class="fas fa-redo me-2"></i>Jugar Otra Vez
                    </button>
                    <button class="btn btn-outline-light btn-lg" onclick="exitToMain()">
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
        const playerAvatar = '<?php echo $player['avatar']; ?>';
        let currentQuestion = null;
        let selectedOption = null;
        let questionStartTime = null;
        let timerInterval = null;
        let playerScore = 0;
        let gameActive = true;

        class PlayerGame {
            constructor() {
                this.setupCommunication();
                this.checkGameState();
                // Consultar estado cada 2 segundos
                setInterval(() => this.checkGameState(), 2000);
                
                console.log('🎮 Jugador inicializado:', { playerId, playerName, triviaId });
            }

            setupCommunication() {
                // Escuchar mensajes del host
                window.addEventListener('storage', (e) => {
                    if (e.key === `trivia_${triviaId}_to_players` && e.newValue) {
                        try {
                            const message = JSON.parse(e.newValue);
                            this.handleHostMessage(message);
                        } catch (error) {
                            console.error('Error procesando mensaje del host:', error);
                        }
                    }
                });

                // También verificar sessionStorage como backup
                window.addEventListener('storage', (e) => {
                    if (e.key === `trivia_${triviaId}_msg` && e.newValue) {
                        try {
                            const message = JSON.parse(e.newValue);
                            this.handleHostMessage(message);
                        } catch (error) {
                            console.error('Error procesando mensaje backup:', error);
                        }
                    }
                });

                // Verificar mensajes periódicamente
                setInterval(() => {
                    this.checkForMessages();
                }, 1000);
            }

            async checkForMessages() {
                // Revisar localStorage
                try {
                    const message = localStorage.getItem(`trivia_${triviaId}_to_players`);
                    if (message) {
                        const parsed = JSON.parse(message);
                        this.handleHostMessage(parsed);
                    }
                } catch (error) {
                    console.error('Error checking messages:', error);
                }

                // Revisar sessionStorage como backup
                try {
                    const message = sessionStorage.getItem(`trivia_${triviaId}_msg`);
                    if (message) {
                        const parsed = JSON.parse(message);
                        this.handleHostMessage(parsed);
                        sessionStorage.removeItem(`trivia_${triviaId}_msg`);
                    }
                } catch (error) {
                    console.error('Error checking backup messages:', error);
                }
            }

            handleHostMessage(message) {
                console.log('📨 Mensaje del host:', message);
                
                switch (message.type) {
                    case 'question_started':
                        this.showQuestion(message.data.question);
                        break;
                    case 'question_ended':
                        this.handleQuestionEnded();
                        break;
                    case 'show_results':
                        this.showQuestionResults(message.data);
                        break;
                    case 'show_leaderboard':
                        this.showLeaderboard(message.data.players);
                        break;
                    case 'game_ended':
                        if (message.data.final_results) {
                            this.showFinalResults();
                        }
                        break;
                }
            }

            async checkGameState() {
    try {
        const apiUrl = '/microservices/tata-trivia/api/player_communication.php';
        const response = await fetch(apiUrl, {
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
                    this.showFinalResults();
                    gameActive = false;
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
                document.getElementById('leaderboardScreen').style.display = 'none';
                document.getElementById('finalResultsScreen').style.display = 'none';
            }

            showQuestion(question) {
                console.log('📝 Mostrando pregunta:', question.question_text);
                
                currentQuestion = question;
                questionStartTime = Date.now();
                selectedOption = null;
                gameActive = true;

                // Ocultar otras pantallas, mostrar pregunta
                document.getElementById('waitingScreen').style.display = 'none';
                document.getElementById('questionScreen').style.display = 'block';
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('leaderboardScreen').style.display = 'none';
                document.getElementById('finalResultsScreen').style.display = 'none';

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
                        this.handleTimeUp();
                    }
                }, 1000);
            }

            handleTimeUp() {
                if (selectedOption === null) {
                    // Tiempo agotado sin respuesta
                    this.submitAnswer();
                }
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
                this.submitAnswer();
            }

    async submitAnswer() {
    console.log('📤 Enviando respuesta...');
    
    clearInterval(timerInterval);

    const responseTime = Date.now() - questionStartTime;
    
    // PREPARAR DATOS COMPLETOS
    const submitData = {
        action: 'submit_answer',
        trivia_id: triviaId,
        player_id: playerId,
        question_id: currentQuestion.id,
        response_time: responseTime
    };
    
    if (selectedOption !== null) {
        submitData.option_id = selectedOption.id;
    }
    
    console.log('📦 Datos a enviar:', submitData);
    
    try {
        // USAR URL ABSOLUTA para evitar problemas de ruta
        const apiUrl = '/microservices/tata-trivia/api/player_communication.php';
        console.log('🌐 Llamando a:', apiUrl);
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(submitData)
        });

        console.log('📨 Status de respuesta:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('✅ Respuesta del servidor:', data);
        
        if (data.success) {
            this.showResult(data.is_correct, responseTime, data.points_earned);
            
            if (data.is_correct && data.points_earned) {
                playerScore += data.points_earned;
                document.getElementById('playerScore').textContent = playerScore;
            }
        } else {
            this.showResult(false, responseTime, data.error || 'Error del servidor');
        }

    } catch (error) {
        console.error('❌ Error submitting answer:', error);
        this.showResult(false, responseTime, 'Error de conexión: ' + error.message);
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

                // Ocultar stats por defecto, se mostrarán cuando llegue el mensaje del host
                document.getElementById('questionStats').style.display = 'none';
            }

            handleQuestionEnded() {
                console.log('⏹️ Pregunta finalizada por el host');
                clearInterval(timerInterval);
                
                // Si aún no ha respondido, forzar envío
                if (selectedOption === null) {
                    this.submitAnswer();
                }
            }

            showQuestionResults(data) {
                console.log('📈 Mostrando resultados de pregunta:', data);
                
                // Mostrar estadísticas de la pregunta
                document.getElementById('correctCount').textContent = data.correctCount || 0;
                document.getElementById('totalAnswers').textContent = data.totalAnswers || 0;
                document.getElementById('questionStats').style.display = 'block';

                // Esperar 3 segundos y mostrar leaderboard automáticamente
                setTimeout(() => {
                    this.fetchLeaderboard();
                }, 3000);
            }

            async fetchLeaderboard() {
                try {
                    const response = await fetch('/microservices/tata-trivia/api/player_communication.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'get_leaderboard',
                            trivia_id: triviaId
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.showLeaderboard(data.players);
                    }
                } catch (error) {
                    console.error('Error fetching leaderboard:', error);
                }
            }

            showLeaderboard(players) {
                console.log('🏆 Mostrando leaderboard:', players);
                
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('leaderboardScreen').style.display = 'block';

                const leaderboardList = document.getElementById('leaderboardList');
                leaderboardList.innerHTML = '';

                if (players && players.length > 0) {
                    players.forEach((player, index) => {
                        const rankClass = index < 3 ? `rank-${index + 1}` : 'rank-other';
                        const isCurrentPlayer = player.id === playerId;
                        const itemClass = isCurrentPlayer ? 'border-warning border-2' : '';

                        const leaderboardHTML = `
                            <div class="podium-item ${itemClass}">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="rank-badge ${rankClass}">
                                            ${index + 1}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <img src="/public/assets/img/default/${player.avatar}.png" 
                                             alt="Avatar" class="player-avatar">
                                    </div>
                                    <div class="col">
                                        <div class="fw-bold ${isCurrentPlayer ? 'text-warning' : 'text-dark'}">${player.name}</div>
                                        ${player.team ? `<small class="text-muted">${player.team}</small>` : ''}
                                    </div>
                                    <div class="col-auto">
                                        <div class="fw-bold text-dark">${player.score} pts</div>
                                    </div>
                                </div>
                            </div>
                        `;
                        leaderboardList.innerHTML += leaderboardHTML;
                    });
                }

                // Contador para siguiente pregunta
                this.startNextQuestionCountdown();
            }

            startNextQuestionCountdown() {
                let countdown = 5;
                const countdownElement = document.getElementById('nextQuestionCountdown');
                countdownElement.textContent = countdown;

                const countdownInterval = setInterval(() => {
                    countdown--;
                    countdownElement.textContent = countdown;

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        this.showWaiting();
                    }
                }, 1000);
            }

            async showFinalResults() {
                console.log('🏁 Mostrando resultados finales...');
                
                gameActive = false;
                clearInterval(timerInterval);

                document.getElementById('waitingScreen').style.display = 'none';
                document.getElementById('questionScreen').style.display = 'none';
                document.getElementById('resultsScreen').style.display = 'none';
                document.getElementById('leaderboardScreen').style.display = 'none';
                document.getElementById('finalResultsScreen').style.display = 'block';

                // Crear confetti
                this.createConfetti();

                try {
                    // Obtener resultados finales
                    const response = await fetch('/microservices/tata-trivia/api/get_results.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            trivia_id: triviaId,
                            player_id: playerId
                        })
                    });

                    let finalResults = [];
                    let playerRank = 0;

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            finalResults = data.results || [];
                            playerRank = data.player_rank || 0;
                        }
                    }

                    // Mostrar posición del jugador
                    document.getElementById('playerRankBadge').textContent = playerRank;
                    document.getElementById('playerRankBadge').className = `rank-badge ${playerRank <= 3 ? `rank-${playerRank}` : 'rank-other'}`;
                    document.getElementById('playerFinalScore').textContent = playerScore;
                    document.getElementById('playerFinalAvatar').src = `/public/assets/img/default/${playerAvatar}.png`;

                    // Mostrar ganador
                    if (finalResults.length > 0) {
                        const winner = finalResults[0];
                        document.getElementById('winnerName').textContent = winner.name;
                        document.getElementById('winnerScore').innerHTML = `<i class="fas fa-star me-1"></i>${winner.score} puntos`;
                        document.getElementById('winnerAvatar').src = `/public/assets/img/default/${winner.avatar}.png`;
                        
                        if (winner.team) {
                            document.getElementById('winnerTeam').textContent = winner.team;
                            document.getElementById('winnerTeam').style.display = 'inline-block';
                        }
                    }

                    // Mostrar podio completo
                    const finalLeaderboard = document.getElementById('finalLeaderboard');
                    finalLeaderboard.innerHTML = '';

                    finalResults.forEach((player, index) => {
                        const rankClass = index < 3 ? `rank-${index + 1}` : 'rank-other';
                        const borderColor = index < 3 ? ['#FFD700', '#C0C0C0', '#CD7F32'][index] : '#667eea';
                        const isCurrentPlayer = player.id === playerId;
                        const itemClass = isCurrentPlayer ? 'border-warning border-3' : '';

                        const playerHTML = `
                            <div class="podium-item ${itemClass}">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="rank-badge ${rankClass}">
                                            ${index + 1}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <img src="/public/assets/img/default/${player.avatar}.png" 
                                             alt="Avatar" class="player-avatar"
                                             style="border-color: ${borderColor};">
                                    </div>
                                    <div class="col">
                                        <h5 class="mb-1 ${isCurrentPlayer ? 'text-warning' : 'text-dark'}">${player.name}</h5>
                                        ${player.team ? `<span class="badge bg-primary">${player.team}</span>` : ''}
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-end">
                                            <div class="h4 mb-0 ${isCurrentPlayer ? 'text-warning' : 'text-dark'}">${player.score}</div>
                                            <small class="text-muted">puntos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        finalLeaderboard.innerHTML += playerHTML;
                    });

                } catch (error) {
                    console.error('Error cargando resultados finales:', error);
                }
            }

            createConfetti() {
                const confettiContainer = document.getElementById('confettiContainer');
                const colors = ['#FFD700', '#FFA500', '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4'];
                
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 5 + 's';
                    confetti.style.width = Math.random() * 10 + 5 + 'px';
                    confetti.style.height = confetti.style.width;
                    
                    confettiContainer.appendChild(confetti);
                    
                    // Remover después de la animación
                    setTimeout(() => {
                        if (confetti.parentNode) {
                            confetti.remove();
                        }
                    }, 5000);
                }
            }
        }

        // Funciones globales para botones
        function playAgain() {
            window.location.href = '/microservices/tata-trivia/player/join';
        }

        function exitToMain() {
            window.location.href = '/microservices/tata-trivia/';
        }

        // Inicializar juego del jugador
        const playerGame = new PlayerGame();
    </script>
</body>
</html>