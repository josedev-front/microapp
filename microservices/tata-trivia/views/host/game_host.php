<?php
$user = $user ?? getTriviaMicroappsUser();
$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}

// Obtener información de la trivia y preguntas
try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    $questions = $triviaController->getTriviaQuestions($trivia_id);
    $players = $triviaController->getLobbyPlayers($trivia_id);
    
    if (empty($questions)) {
        throw new Exception('No hay preguntas en esta trivia');
    }
    
} catch (Exception $e) {
    // Redirigir si hay error
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host - Juego en Vivo - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .game-container {
            
            min-height: 100vh;
            color: white;
        }
        .titulo {
            background: purple;
        }
        .question-display {
            background: rgba(255,255,255,0.95);
            color: #333;
            border-radius: 15px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .timer-circle {
            width: 100px;
            height: 100px;
            border: 5px solid #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            background: white;
            color: #333;
        }
        .player-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .player-card.answered {
            background: rgba(40, 167, 69, 0.3);
            border: 2px solid #28a745;
        }
        .player-card.correct {
            background: rgba(40, 167, 69, 0.5);
            border: 2px solid #28a745;
        }
        .player-card.incorrect {
            background: rgba(220, 53, 69, 0.3);
            border: 2px solid #dc3545;
        }
        .controls-panel {
            background: rgba(0,0,0,0.7);
            border-radius: 15px;
            padding: 20px;
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        .option-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .leaderboard-item {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
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
<body class="game-container" style="background-image: url('<?php echo get_theme_image('TRIVIA.png'); ?>'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center titulo">
                    <div>
                        <h1 class="mb-0">
                            <i class="fas fa-crown text-warning me-2"></i>
                            Control del Juego
                        </h1>
                        <p class="mb-0 text-light"><?php echo htmlspecialchars($trivia['title']); ?></p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-success fs-6">
                            <i class="fas fa-users me-1"></i>
                            <span id="playersCount"><?php echo count($players); ?></span> Jugadores
                        </div>
                        <div class="mt-1">
                            <small class="text-light">Trivia ID: <?php echo $trivia_id; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Panel Principal - Pregunta y Controles -->
            <div class="col-lg-8 mb-4">
                <div class="card bg-dark border-light">
                    <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" id="questionHeader">Preparando pregunta...</h4>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2" id="questionCounter">1/<?php echo count($questions); ?></span>
                            <div class="timer-circle" id="timerDisplay">30</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Pantalla de Inicio -->
                        <div id="startScreen" class="text-center py-5">
                            <i class="fas fa-play-circle fa-5x text-warning mb-4"></i>
                            <h2 class="text-warning">¡Listo para Comenzar!</h2>
                            <p class="lead">Presiona "Comenzar Siguiente" para iniciar la primera pregunta</p>
                            <div class="mt-4">
                                <button class="btn btn-warning btn-lg pulse" onclick="startNextQuestion()">
                                    <i class="fas fa-play me-2"></i>Comenzar Primera Pregunta
                                </button>
                            </div>
                        </div>

                        <!-- Pantalla de Pregunta -->
                        <div id="questionScreen" style="display: none;">
                            <div class="question-display p-4 mb-4">
                                <h2 id="questionText" class="text-center mb-0"></h2>
                            </div>
                            
                            <div class="row" id="optionsContainer">
                                <!-- Opciones se generan dinámicamente -->
                            </div>

                            <!-- Respuestas en Tiempo Real -->
                            <div class="mt-4">
                                <h5 class="text-light mb-3">
                                    <i class="fas fa-bolt me-2"></i>Respuestas en Tiempo Real
                                </h5>
                                <div class="progress progress-bar-custom mb-2">
                                    <div id="answersProgress" class="progress-bar bg-success" style="width: 0%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-light small">
                                    <span>Respuestas: <span id="answersCount">0</span>/<span id="totalPlayers"><?php echo count($players); ?></span></span>
                                    <span id="correctAnswers">Correctas: 0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pantalla de Resultados -->
                        <div id="resultsScreen" style="display: none;">
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-4x text-info mb-3"></i>
                                <h3 class="text-info">Resultados de la Pregunta</h3>
                                <div id="resultsChart" class="mt-4">
                                    <!-- Gráfico de resultados se genera aquí -->
                                </div>
                                <div class="mt-4">
                                    <button class="btn btn-success btn-lg" onclick="showLeaderboard()">
                                        <i class="fas fa-trophy me-2"></i>Ver Clasificación
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pantalla de Leaderboard -->
                        <div id="leaderboardScreen" style="display: none;">
                            <div class="text-center py-4">
                                <i class="fas fa-trophy fa-4x text-warning mb-3"></i>
                                <h3 class="text-warning">Clasificación Actual</h3>
                                <div id="leaderboardList" class="mt-4">
                                    <!-- Leaderboard se genera aquí -->
                                </div>
                                <div class="mt-4">
                                    <button class="btn btn-primary me-2" onclick="continueToNextQuestion()">
                                        <i class="fas fa-arrow-right me-2"></i>Siguiente Pregunta
                                    </button>
                                    <button class="btn btn-outline-light" onclick="endGame()">
                                        <i class="fas fa-flag-checkered me-2"></i>Finalizar Juego
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Lateral - Jugadores y Controles -->
            <div class="col-lg-4">
                <!-- Panel de Jugadores -->
                <div class="card bg-dark border-light mb-4">
                    <div class="card-header bg-light text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Jugadores Conectados
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div id="playersList">
                            <!-- Lista de jugadores se actualiza dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Panel de Controles -->
                <div class="controls-panel">
                    <h5 class="text-warning mb-3">
                        <i class="fas fa-gamepad me-2"></i>
                        Controles del Juego
                    </h5>
                    
                    <div class="mb-3">
                        <label class="form-label text-light">Acciones Rápidas</label>
                        <div class="d-grid gap-2">
                            <button id="btnNextQuestion" class="btn btn-warning" onclick="startNextQuestion()" disabled>
                                <i class="fas fa-play me-2"></i>Comenzar Siguiente
                            </button>
                            <button id="btnShowResults" class="btn btn-info" onclick="showResults()" style="display: none;">
                                <i class="fas fa-chart-bar me-2"></i>Mostrar Resultados
                            </button>
                            <button id="btnPause" class="btn btn-secondary" onclick="pauseGame()" style="display: none;">
                                <i class="fas fa-pause me-2"></i>Pausar
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light">Configuración</label>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-light btn-sm" onclick="skipQuestion()">
                                <i class="fas fa-forward me-2"></i>Saltar Pregunta
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="endGame()">
                                <i class="fas fa-stop me-2"></i>Finalizar Juego
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="/microservices/tata-trivia/host/lobby?trivia_id=<?php echo $trivia_id; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Lobby
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Variables globales
const triviaId = '<?php echo $trivia_id; ?>';
const questions = <?php echo json_encode($questions); ?>;
let currentQuestionIndex = -1;
let currentQuestion = null;
let timer = null;
let timeLeft = 0;
let gameActive = false;
let playerAnswers = {};
let playerStatus = {};

// Elementos DOM
const startScreen = document.getElementById('startScreen');
const questionScreen = document.getElementById('questionScreen');
const resultsScreen = document.getElementById('resultsScreen');
const leaderboardScreen = document.getElementById('leaderboardScreen');
const questionText = document.getElementById('questionText');
const optionsContainer = document.getElementById('optionsContainer');
const timerDisplay = document.getElementById('timerDisplay');
const questionCounter = document.getElementById('questionCounter');
const questionHeader = document.getElementById('questionHeader');
const answersProgress = document.getElementById('answersProgress');
const answersCount = document.getElementById('answersCount');
const correctAnswers = document.getElementById('correctAnswers');
const totalPlayers = document.getElementById('totalPlayers');
const playersCount = document.getElementById('playersCount');
const playersList = document.getElementById('playersList');

// Sistema de comunicación MEJORADO
class GameHost {
    constructor(triviaId) {
        this.triviaId = triviaId;
        this.baseUrl = '/microservices/tata-trivia/api';
        this.setupCommunication();
    }
    
    setupCommunication() {
        console.log('🎮 Host inicializado para trivia:', this.triviaId);
        
        // Escuchar respuestas de jugadores
        window.addEventListener('storage', (e) => {
            if (e.key === `trivia_${this.triviaId}_to_host` && e.newValue) {
                try {
                    const message = JSON.parse(e.newValue);
                    this.handlePlayerMessage(message);
                    // Limpiar después de procesar
                    setTimeout(() => {
                        localStorage.removeItem(`trivia_${this.triviaId}_to_host`);
                    }, 100);
                } catch (error) {
                    console.error('Error procesando mensaje:', error);
                }
            }
        });
        
        // Limpiar mensajes antiguos al iniciar
        localStorage.removeItem(`trivia_${this.triviaId}_to_host`);
        localStorage.removeItem(`trivia_${this.triviaId}_to_players`);
    }
    
    async apiCall(action, extraData = {}) {
        try {
            console.log(`📡 Llamando API: ${action}`, { 
                trivia_id: this.triviaId, 
                ...extraData 
            });
            
            const payload = {
                action: action,
                trivia_id: this.triviaId,
                ...extraData
            };
            
            console.log('📦 Payload enviado:', payload);
            
            const response = await fetch(`${this.baseUrl}/game_actions.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });
            
            console.log(`📨 Response status: ${response.status} ${response.statusText}`);
            
            if (!response.ok) {
                // Obtener más detalles del error
                const errorText = await response.text();
                console.error('❌ Error response text:', errorText);
                
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log(`✅ Respuesta API ${action}:`, data);
            
            return data;
            
        } catch (error) {
            console.error(`❌ Error en API call ${action}:`, error);
            
            // Mostrar error al usuario
            this.showError(`Error de conexión: ${error.message}`);
            throw error;
        }
    }

    // Función para mostrar errores
    showError(message) {
        // Crear alerta de error bonita
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        errorDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        errorDiv.innerHTML = `
            <strong><i class="fas fa-exclamation-triangle me-2"></i>Error</strong>
            <div>${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(errorDiv);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    // Enviar mensaje a todos los jugadores - VERSIÓN MEJORADA
    broadcastToPlayers(event, data) {
        const message = {
            type: event,
            data: data,
            timestamp: Date.now(),
            trivia_id: this.triviaId,
            from: 'host'
        };
        
        console.log('🟢 BROADCAST a jugadores:', event, data);
        
        // Método principal - localStorage
        localStorage.setItem(`trivia_${this.triviaId}_to_players`, JSON.stringify(message));
        
        // Método secundario - sessionStorage como backup
        sessionStorage.setItem(`trivia_${this.triviaId}_msg`, JSON.stringify(message));
        
        // Limpiar después de un tiempo
        setTimeout(() => {
            localStorage.removeItem(`trivia_${this.triviaId}_to_players`);
            sessionStorage.removeItem(`trivia_${this.triviaId}_msg`);
        }, 2000);
    }
    
    handlePlayerMessage(message) {
        console.log('🔵 Mensaje de jugador:', message);
        
        if (message.type === 'player_answer') {
            this.handlePlayerAnswer(message.data);
        }
    }
    
    handlePlayerAnswer(answerData) {
        playerAnswers[answerData.playerId] = {
            option: answerData.optionIndex,
            correct: answerData.isCorrect,
            time: answerData.responseTime,
            playerName: answerData.playerName
        };
        
        this.updatePlayerDisplay(answerData);
        updateAnswersProgress();
        
        if (answerData.isCorrect && playerStatus[answerData.playerId]) {
            const points = Math.max(10 - Math.floor(answerData.responseTime / 1000), 1);
            playerStatus[answerData.playerId].score += points;
            this.updatePlayerScoreDisplay(answerData.playerId);
        }
    }
    
    updatePlayerDisplay(answerData) {
        const playerElement = document.getElementById(`player-${answerData.playerId}`);
        if (playerElement) {
            playerElement.className = `player-card p-2 mb-2 ${answerData.isCorrect ? 'correct' : 'incorrect'}`;
            
            const icon = playerElement.querySelector('i');
            if (icon) {
                icon.className = answerData.isCorrect ? 'text-success' : 'fas fa-times text-danger';
            }
        }
    }
    
    updatePlayerScoreDisplay(playerId) {
        const playerElement = document.getElementById(`player-${playerId}`);
        if (playerElement) {
            const scoreElement = playerElement.querySelector('.player-score');
            if (scoreElement) {
                scoreElement.textContent = `${playerStatus[playerId].score} pts`;
            }
        }
    }
}

// Inicializar jugadores
function initializePlayers() {
    const players = <?php echo json_encode($players); ?>;
    playersList.innerHTML = '';
    playerStatus = {};
    
    players.forEach(player => {
        playerStatus[player.id] = {
            id: player.id,
            name: player.player_name,
            team: player.team_name,
            avatar: player.avatar,
            score: player.score || 0,
            answered: false,
            correct: false,
            answerTime: null
        };
        
        const playerHTML = `
            <div class="player-card p-2 mb-2" id="player-${player.id}">
                <div class="d-flex align-items-center">
                    <img src="/public/assets/img/default/${player.avatar}.png" 
                         class="rounded-circle me-2" width="30" height="30">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${player.player_name}</div>
                        ${player.team_name ? `<small class="text-muted">${player.team_name}</small>` : ''}
                        <div class="small text-warning player-score">${player.score || 0} pts</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        `;
        playersList.innerHTML += playerHTML;
    });
    
    playersCount.textContent = players.length;
    totalPlayers.textContent = players.length;
}

// FUNCIÓN CRÍTICA - Comenzar siguiente pregunta CORREGIDA
async function startNextQuestion() {
    try {
        console.log('🚀 Iniciando siguiente pregunta...');
        
        // Mostrar indicador de carga
        const btn = document.getElementById('btnNextQuestion');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando...';
        btn.disabled = true;
        
        // PRIMERO: Intentar con la nueva acción
        let response;
        try {
            response = await gameHost.apiCall('advance_question');
        } catch (error) {
            console.log('🔄 Fallback a next_question...');
            response = await gameHost.apiCall('next_question');
        }
        
        if (response.success) {
            if (response.game_finished) {
                // Juego terminado
                gameHost.showError('¡El juego ha terminado!');
                endGame();
                return;
            }
            
            console.log('✅ Pregunta avanzada:', response.question_data.question_text);
            
            currentQuestionIndex = response.question_index;
            currentQuestion = response.question_data;
            
            // MOSTRAR LA PREGUNTA
            showQuestionScreen();
            
            // NOTIFICAR A JUGADORES
            try {
                gameHost.broadcastToPlayers('question_started', {
                    question: currentQuestion,
                    questionIndex: currentQuestionIndex,
                    timeLimit: currentQuestion.time_limit || 30
                });
                console.log('📢 Jugadores notificados correctamente');
            } catch (broadcastError) {
                console.warn('⚠️ Error en broadcast, pero continuando...', broadcastError);
            }
            
        } else {
            throw new Error(response.error || 'Error del servidor');
        }
        
    } catch (error) {
        console.error('❌ Error al iniciar pregunta:', error);
        gameHost.showError('Error al iniciar pregunta: ' + error.message);
        
        // FALLBACK ULTIMATIVO: Usar preguntas locales
        console.log('🔄 Intentando fallback con preguntas locales...');
        startNextQuestionFallback();
        
    } finally {
        // Restaurar botón
        const btn = document.getElementById('btnNextQuestion');
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Comenzar Siguiente';
        btn.disabled = false;
    }
}

// FALLBACK ULTIMATIVO
function startNextQuestionFallback() {
    if (questions && questions.length > 0) {
        currentQuestionIndex = currentQuestionIndex === -1 ? 0 : currentQuestionIndex + 1;
        
        if (currentQuestionIndex < questions.length) {
            currentQuestion = questions[currentQuestionIndex];
            showQuestionScreen();
            
            // Notificar a los jugadores
            gameHost.broadcastToPlayers('question_started', {
                question: currentQuestion,
                questionIndex: currentQuestionIndex,
                timeLimit: currentQuestion.time_limit || 30
            });
            
            console.log('✅ Fallback exitoso - Pregunta local mostrada');
        } else {
            gameHost.showError('No hay más preguntas disponibles');
        }
    } else {
        gameHost.showError('No hay preguntas configuradas en esta trivia');
    }
}

// Función para mostrar pantalla de pregunta
function showQuestionScreen() {
    console.log('🖥️ Mostrando pantalla de pregunta...');
    
    startScreen.style.display = 'none';
    questionScreen.style.display = 'block';
    resultsScreen.style.display = 'none';
    leaderboardScreen.style.display = 'none';
    
    // Actualizar contenido de la pregunta
    questionText.textContent = currentQuestion.question_text;
    questionCounter.textContent = `${currentQuestionIndex + 1}/${questions.length}`;
    questionHeader.textContent = `Pregunta ${currentQuestionIndex + 1}`;
    
    // Generar opciones
    displayOptions();
    
    // Reiniciar estado de respuestas
    playerAnswers = {};
    updateAnswersProgress();
    
    // Iniciar temporizador
    startTimer(currentQuestion.time_limit || 30);
    
    // Actualizar controles
    document.getElementById('btnNextQuestion').disabled = true;
    document.getElementById('btnShowResults').style.display = 'none';
    
    console.log('✅ Pantalla de pregunta activada correctamente');
}

function displayOptions() {
    optionsContainer.innerHTML = '';
    const options = currentQuestion.options;
    const letters = ['A', 'B', 'C', 'D'];
    
    options.forEach((option, index) => {
        const optionHTML = `
            <div class="col-md-6 mb-3">
                <div class="option-item p-3 bg-light text-dark rounded position-relative">
                    <span class="option-indicator bg-primary text-white">${letters[index]}</span>
                    <span class="option-text">${option.text}</span>
                    ${option.is_correct ? '<span class="position-absolute top-0 end-0 badge bg-success"></span>' : ''}
                </div>
            </div>
        `;
        optionsContainer.innerHTML += optionHTML;
    });
}

function startTimer(seconds) {
    timeLeft = seconds;
    timerDisplay.textContent = timeLeft;
    timerDisplay.className = 'timer-circle';
    gameActive = true;
    
    clearInterval(timer);
    timer = setInterval(() => {
        timeLeft--;
        timerDisplay.textContent = timeLeft;
        
        if (timeLeft <= 10) {
            timerDisplay.style.borderColor = '#dc3545';
            timerDisplay.classList.add('blink');
        } else if (timeLeft <= 20) {
            timerDisplay.style.borderColor = '#ffc107';
        }
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            endQuestion();
        }
    }, 1000);
}

function endQuestion() {
    clearInterval(timer);
    gameActive = false;
    
    // Notificar a los jugadores que la pregunta terminó
    gameHost.broadcastToPlayers('question_ended', {
        questionIndex: currentQuestionIndex
    });
    
    // Mostrar botón de resultados
    document.getElementById('btnShowResults').style.display = 'block';
    document.getElementById('btnNextQuestion').disabled = false;
    
    console.log('⏹️ Pregunta finalizada');
}

function showResults() {
    questionScreen.style.display = 'none';
    resultsScreen.style.display = 'block';
    
    const totalAnswers = Object.keys(playerAnswers).length;
    const correctCount = Object.values(playerAnswers).filter(answer => answer.correct).length;
    
    // Notificar a los jugadores los resultados
    gameHost.broadcastToPlayers('show_results', {
        questionIndex: currentQuestionIndex,
        totalAnswers: totalAnswers,
        correctCount: correctCount
    });
    
    const resultsChart = document.getElementById('resultsChart');
    resultsChart.innerHTML = `
        <div class="row text-center">
            <div class="col-6">
                <div class="display-4 text-success">${correctCount}</div>
                <div class="text-light">Correctas</div>
            </div>
            <div class="col-6">
                <div class="display-4 text-danger">${totalAnswers - correctCount}</div>
                <div class="text-light">Incorrectas</div>
            </div>
        </div>
        <div class="mt-3">
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-success" style="width: ${totalAnswers > 0 ? (correctCount / totalAnswers) * 100 : 0}%">
                    ${totalAnswers > 0 ? Math.round((correctCount / totalAnswers) * 100) : 0}%
                </div>
            </div>
        </div>
    `;
}

function showLeaderboard() {
    resultsScreen.style.display = 'none';
    leaderboardScreen.style.display = 'block';
    
    const sortedPlayers = Object.values(playerStatus)
        .sort((a, b) => b.score - a.score)
        .slice(0, 10);
    
    // Notificar a los jugadores el leaderboard
    gameHost.broadcastToPlayers('show_leaderboard', {
        players: sortedPlayers,
        questionIndex: currentQuestionIndex
    });
    
    const leaderboardList = document.getElementById('leaderboardList');
    leaderboardList.innerHTML = '';
    
    sortedPlayers.forEach((player, index) => {
        const positionClass = index === 0 ? 'bg-warning text-dark' : 
                           index === 1 ? 'bg-secondary' : 
                           index === 2 ? 'bg-danger' : 'bg-dark';
        
        const leaderboardHTML = `
            <div class="leaderboard-item p-3 ${positionClass}">
                <div class="d-flex align-items-center">
                    <div class="fw-bold me-3" style="width: 30px;">#${index + 1}</div>
                    <img src="/public/assets/img/default/${player.avatar}.png" 
                         class="rounded-circle me-2" width="30" height="30">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${player.name}</div>
                        ${player.team ? `<small>${player.team}</small>` : ''}
                    </div>
                    <div class="fw-bold">${player.score} pts</div>
                </div>
            </div>
        `;
        leaderboardList.innerHTML += leaderboardHTML;
    });
}

function continueToNextQuestion() {
    leaderboardScreen.style.display = 'none';
    startNextQuestion();
}

function updateAnswersProgress() {
    const total = Object.values(playerStatus).length;
    const answered = Object.values(playerAnswers).length;
    const percentage = total > 0 ? (answered / total) * 100 : 0;
    
    answersProgress.style.width = percentage + '%';
    answersCount.textContent = answered;
    
    const correct = Object.values(playerAnswers).filter(a => a.correct).length;
    correctAnswers.textContent = `Correctas: ${correct}`;
}

function endGame() {
    if (confirm('¿Estás seguro de que quieres finalizar el juego?')) {
        // Notificar a los jugadores
        gameHost.broadcastToPlayers('game_ended', {});
        
        // Redirigir a resultados
        window.location.href = '/microservices/tata-trivia/results?trivia_id=' + triviaId;
    }
}

function skipQuestion() {
    if (confirm('¿Saltar esta pregunta?')) {
        clearInterval(timer);
        endQuestion();
    }
}

// ==================================================
// FUNCIONES DE DIAGNÓSTICO Y PRUEBA
// ==================================================

async function testConnection() {
    try {
        console.log('🧪 Probando conexión con el servidor...');
        const testResponse = await fetch('/microservices/tata-trivia/api/test_connection.php');
        const testData = await testResponse.json();
        console.log('✅ Test de conexión:', testData);
        
        // Probar game_actions.php específicamente
        const gameActionsTest = await fetch('/microservices/tata-trivia/api/game_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_question_status',
                trivia_id: triviaId
            })
        });
        
        const gameActionsData = await gameActionsTest.json();
        console.log('✅ Test game_actions.php:', gameActionsData);
        
    } catch (error) {
        console.error('❌ Test de conexión falló:', error);
    }
}

async function diagnosticTest() {
    console.log('🔍 Iniciando diagnóstico...');
    
    // Test 1: Conexión básica
    try {
        const test1 = await fetch('/microservices/tata-trivia/api/test_api.php');
        const test1Text = await test1.text();
        console.log('🧪 Test 1 - Conexión básica:', test1Text.substring(0, 200));
    } catch (e) {
        console.error('❌ Test 1 falló:', e);
    }
    
    // Test 2: game_actions.php con reset
    try {
        const test2 = await fetch('/microservices/tata-trivia/api/game_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_questions', trivia_id: triviaId })
        });
        const test2Text = await test2.text();
        console.log('🧪 Test 2 - game_actions reset:', test2Text.substring(0, 200));
    } catch (e) {
        console.error('❌ Test 2 falló:', e);
    }
    
    // Test 3: game_actions.php con get_status
    try {
        const test3 = await fetch('/microservices/tata-trivia/api/game_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_question_status', trivia_id: triviaId })
        });
        const test3Text = await test3.text();
        console.log('🧪 Test 3 - game_actions status:', test3Text.substring(0, 200));
    } catch (e) {
        console.error('❌ Test 3 falló:', e);
    }
}

// ==================================================
// INICIALIZACIÓN DE LA PÁGINA
// ==================================================

let gameHost;
document.addEventListener('DOMContentLoaded', function() {
    initializePlayers();
    gameHost = new GameHost(triviaId);
    
    console.log('🎮 Host inicializado para trivia:', triviaId);
    
    // Habilitar botón para comenzar
    document.getElementById('btnNextQuestion').disabled = false;
    
    // Ejecutar tests automáticamente
    setTimeout(testConnection, 1000);
    setTimeout(diagnosticTest, 2000);
    
    // Log del estado inicial
    console.log('📊 Estado inicial:', {
        triviaId: triviaId,
        questionsCount: questions.length,
        playersCount: Object.keys(playerStatus).length,
        currentQuestionIndex: currentQuestionIndex
    });
});

// ==================================================
// FUNCIONES DE DEBUG (para usar en consola)
// ==================================================

// Función para forzar el inicio de una pregunta (debug)
window.debugStartQuestion = function(questionIndex = 0) {
    console.log('🐛 DEBUG: Forzando inicio de pregunta', questionIndex);
    
    if (questions && questions.length > questionIndex) {
        currentQuestionIndex = questionIndex;
        currentQuestion = questions[questionIndex];
        showQuestionScreen();
        
        gameHost.broadcastToPlayers('question_started', {
            question: currentQuestion,
            questionIndex: currentQuestionIndex,
            timeLimit: currentQuestion.time_limit || 30
        });
        
        console.log('🐛 DEBUG: Pregunta forzada iniciada');
    } else {
        console.error('🐛 DEBUG: No hay preguntas disponibles');
    }
};

// Función para simular respuestas de jugadores (debug)
window.debugSimulateAnswers = function() {
    console.log('🐛 DEBUG: Simulando respuestas de jugadores');
    
    Object.keys(playerStatus).forEach(playerId => {
        if (!playerAnswers[playerId]) {
            setTimeout(() => {
                const randomOption = Math.floor(Math.random() * (currentQuestion?.options?.length || 4));
                const isCorrect = currentQuestion?.options?.[randomOption]?.is_correct || false;
                
                const answerData = {
                    playerId: playerId,
                    playerName: playerStatus[playerId].name,
                    questionIndex: currentQuestionIndex,
                    optionIndex: randomOption,
                    isCorrect: isCorrect,
                    responseTime: 5000 + Math.random() * 20000,
                    timestamp: Date.now()
                };
                
                localStorage.setItem(`trivia_${triviaId}_to_host`, JSON.stringify({
                    type: 'player_answer',
                    data: answerData
                }));
                
            }, Math.random() * 15000);
        }
    });
};

// ==================================================
// DIAGNÓSTICO AUTOMÁTICO
// ==================================================

async function diagnosticCheck() {
    console.log('🔍 Ejecutando diagnóstico...');
    
    // Test 1: Verificar que game_actions.php existe y responde
    try {
        const testResponse = await fetch('/microservices/tata-trivia/api/game_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_question_status',
                trivia_id: triviaId
            })
        });
        
        console.log('🧪 Test game_actions - Status:', testResponse.status);
        
        if (testResponse.ok) {
            const testData = await testResponse.json();
            console.log('✅ game_actions.php funciona:', testData);
        } else {
            console.error('❌ game_actions.php error:', testResponse.status);
        }
    } catch (e) {
        console.error('❌ game_actions.php no accesible:', e);
    }
}

// Ejecutar diagnóstico al cargar
setTimeout(diagnosticCheck, 1000);
</script>
</body>
</html>