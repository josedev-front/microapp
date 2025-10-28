<?php
// microservices/tata-trivia/views/player/game_player.php

$trivia_id = $_GET['trivia_id'] ?? '';
$player_id = $_SESSION['player_id'] ?? '';

if (empty($trivia_id) || empty($player_id)) {
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}

// Obtener información del jugador y trivia PRIMERO
try {
    $triviaController = new TriviaController();
    $player = $triviaController->getPlayerById($player_id);
    $trivia = $triviaController->getTriviaById($trivia_id);
    
    if (!$player || !$trivia) {
        throw new Exception('Datos no encontrados');
    }
    
    // Obtener preguntas para los fondos
    $questionBackgrounds = [];
    $questions = $triviaController->getTriviaQuestions($trivia_id);
    foreach ($questions as $question) {
        $questionBackgrounds[$question['id']] = $triviaController->getQuestionBackgroundPath($question);
    }
    
} catch (Exception $e) {
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}

// Obtener el fondo de la trivia para el contenedor principal
$triviaBackgroundPath = $triviaController->getTriviaBackgroundPath($trivia_id);
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
            background-image: url('<?php echo $triviaBackgroundPath; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh; 
            color: white; 
        }
        .game-container.no-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .question-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            z-index: 1;
        }
        .question-display h2 {
            position: relative;
            z-index: 2;
            color: white !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        }
        .option-btn { 
            background: rgba(255,255,255,0.9); 
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 10px;
            margin-bottom: 1rem;
            color: #333;
        }
        .option-btn:hover { 
            background: rgba(255,255,255,1); 
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
            backdrop-filter: blur(10px);
        }
        .pulse {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .screen {
            display: none;
        }
        .screen.active {
            display: block;
        }
    </style>
</head>
<body class="game-container <?php echo (!file_exists($_SERVER['DOCUMENT_ROOT'] . $triviaBackgroundPath)) ? 'no-bg' : ''; ?>">
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
        <div id="waitingScreen" class="screen active">
            <div class="text-center py-5">
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
        </div>

        <!-- Pantalla de Pregunta -->
        <div id="questionScreen" class="screen">
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
                    <div class="question-display mb-4" id="questionDisplay">
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
        <div id="resultsScreen" class="screen">
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
        <div id="gameOverScreen" class="screen">
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
    const questionBackgrounds = <?php echo json_encode($questionBackgrounds); ?>;
    
    let currentQuestion = null;
    let selectedOption = null;
    let questionStartTime = null;
    let timerInterval = null;
    let playerScore = 0;
    let lastQuestionId = null;

    // ✅ FUNCIÓN MEJORADA: Mostrar pantalla activa
    function setActiveScreen(screenId) {
        // Ocultar todas las pantallas
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active');
        });
        
        // Mostrar la pantalla activa
        const activeScreen = document.getElementById(screenId);
        if (activeScreen) {
            activeScreen.classList.add('active');
        }
    }

    class PlayerGame {
        constructor() {
            this.setupCommunication();
            this.checkGameState();
            // Consultar estado cada 2 segundos
            setInterval(() => this.checkGameState(), 2000);
            
            console.log('🎮 Jugador inicializado:', { playerId, playerName, triviaId });
            console.log('🎨 Fondos de preguntas cargados:', questionBackgrounds);
        }

        setupCommunication() {
            // Escuchar mensajes del host vía localStorage
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
        }

        handleHostMessage(message) {
            console.log('📨 Mensaje recibido del host:', message.type, message.data);
            
            switch (message.type) {
                case 'question_started':
                    this.handleQuestionStarted(message.data);
                    break;
                case 'question_ended':
                    this.handleQuestionEnded();
                    break;
                case 'show_results':
                    this.handleShowResults(message.data);
                    break;
                case 'show_leaderboard':
                    this.handleShowLeaderboard(message.data);
                    break;
                case 'game_ended':
                    this.handleGameEnded();
                    break;
            }
        }

        async checkGameState() {
            try {
                const response = await fetch('/microservices/tata-trivia/api/game_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_question_status',
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

            // ✅ CORREGIDO: Lógica mejorada para mostrar preguntas
            if (state.trivia_status === 'active' && state.current_question) {
                // Verificar si es una pregunta nueva
                if (!this.isSameQuestion(state.current_question)) {
                    console.log('🆕 Nueva pregunta detectada');
                    this.showQuestion(state.current_question);
                }
            } else {
                // Si no hay pregunta activa, mostrar pantalla de espera
                if (document.getElementById('questionScreen').classList.contains('active')) {
                    this.showWaiting();
                }
            }
        }

        isSameQuestion(question) {
            // Verificar si es la misma pregunta por ID y contenido
            if (!currentQuestion || !question) return false;
            
            const isSame = currentQuestion.id === question.id && 
                          currentQuestion.question_text === question.question_text;
            
            if (!isSame) {
                console.log('🔄 Cambio de pregunta detectado:', {
                    anterior: currentQuestion?.id,
                    nueva: question?.id
                });
            }
            
            return isSame;
        }

        showWaiting() {
            console.log('⏳ Mostrando pantalla de espera');
            setActiveScreen('waitingScreen');
            
            // Resetear estado de pregunta actual
            currentQuestion = null;
            selectedOption = null;
            clearInterval(timerInterval);
        }

        handleQuestionStarted(data) {
            console.log('🎯 Iniciando pregunta desde host:', data);
            
            if (data.question) {
                this.showQuestion(data.question);
            }
        }

        showQuestion(question) {
            console.log('📝 Mostrando pregunta:', question);
            
            // Validar que la pregunta tenga la estructura correcta
            if (!question || !question.question_text || !question.options) {
                console.error('❌ Pregunta inválida:', question);
                this.showWaiting();
                return;
            }
            
            currentQuestion = question;
            questionStartTime = Date.now();
            selectedOption = null;
            lastQuestionId = question.id;

            // Ocultar otras pantallas, mostrar pregunta
            setActiveScreen('questionScreen');

            // ✅ CORREGIDO: Aplicar fondo específico de la pregunta
            this.applyQuestionBackground(question);

            // Mostrar pregunta
            document.getElementById('questionText').textContent = question.question_text;

            // Mostrar opciones
            this.displayQuestionOptions(question);

            // Iniciar temporizador
            this.startTimer(question.time_limit || 30);
        }

        // ✅ CORREGIDO: Función mejorada para aplicar fondos
        applyQuestionBackground(question) {
            const questionDisplay = document.getElementById('questionDisplay');
            let questionBackground = '/microservices/tata-trivia/assets/images/themes/questions/general.jpg';
            
            // ✅ CORREGIDO: Lógica mejorada para determinar el fondo
            if (question.background_image && question.background_image.trim() !== '' && question.background_image !== 'null') {
                // Usar fondo específico de la pregunta desde la base de datos
                questionBackground = question.background_image;
                console.log('🎨 Player - Usando fondo de la pregunta (DB):', questionBackground);
            } else if (question.id && questionBackgrounds[question.id]) {
                // Usar fondo del array preparado
                questionBackground = questionBackgrounds[question.id];
                console.log('🎨 Player - Usando fondo de array:', questionBackground);
            } else {
                // Usar fondo por defecto
                console.log('🎨 Player - Usando fondo por defecto');
            }
            
            // ✅ CORREGIDO: Asegurar que la URL sea válida
            if (!questionBackground.startsWith('http') && !questionBackground.startsWith('/')) {
                questionBackground = '/microservices/tata-trivia/assets/images/themes/questions/' + questionBackground;
            }
            
            console.log('🎨 Player - Aplicando fondo final:', questionBackground);
            
            questionDisplay.style.backgroundImage = `url('${questionBackground}')`;
            questionDisplay.style.backgroundSize = 'cover';
            questionDisplay.style.backgroundPosition = 'center';
            questionDisplay.style.backgroundRepeat = 'no-repeat';
        }

        displayQuestionOptions(question) {
            const optionsContainer = document.getElementById('optionsContainer');
            optionsContainer.innerHTML = '';
            
            const letters = ['A', 'B', 'C', 'D'];
            
            question.options.forEach((option, index) => {
                // Validar que la opción tenga texto
                if (!option.text) {
                    console.warn('⚠️ Opción sin texto:', option);
                    return;
                }
                
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
        }

        startTimer(seconds) {
            // Limpiar timer anterior si existe
            clearInterval(timerInterval);
            
            let timeLeft = seconds;
            const timerDisplay = document.getElementById('timerDisplay');
            const timerBar = document.getElementById('timerBar');
            
            timerDisplay.textContent = timeLeft;
            timerBar.style.width = '100%';
            timerBar.className = 'timer-bar bg-success';

            timerInterval = setInterval(() => {
                timeLeft--;
                timerDisplay.textContent = timeLeft;
                const percentage = (timeLeft / seconds) * 100;
                timerBar.style.width = percentage + '%';

                // Cambiar color según el tiempo
                if (timeLeft <= 10) {
                    timerBar.className = 'timer-bar bg-danger';
                    timerDisplay.parentElement.classList.add('pulse');
                } else if (timeLeft <= 20) {
                    timerBar.className = 'timer-bar bg-warning';
                    timerDisplay.parentElement.classList.remove('pulse');
                } else {
                    timerDisplay.parentElement.classList.remove('pulse');
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
                const response = await fetch('/microservices/tata-trivia/api/submit_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
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
            
            setActiveScreen('resultsScreen');

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

        handleQuestionEnded() {
            console.log('⏹️ Pregunta finalizada por el host');
            this.showWaiting();
        }

        handleShowResults(data) {
            console.log('📊 Mostrando resultados:', data);
            // El host muestra resultados generales
        }

        handleShowLeaderboard(data) {
            console.log('🏆 Mostrando leaderboard:', data);
            // El host muestra leaderboard
        }

        handleGameEnded() {
            console.log('🏁 Juego terminado por el host');
            this.showGameOver();
        }

        showGameOver() {
            console.log('🏁 Juego terminado');
            setActiveScreen('gameOverScreen');
            
            // Limpiar timers
            clearInterval(timerInterval);
        }
    }

    // Inicializar juego del jugador
    const playerGame = new PlayerGame();
    
    console.log('🔍 Debug - Player inicializado:', {
        currentQuestion,
        questionBackgrounds,
        backgroundFromDB: currentQuestion?.background_image,
        backgroundFromArray: currentQuestion?.id ? questionBackgrounds[currentQuestion.id] : 'N/A'
    });
    </script>
</body>
</html>