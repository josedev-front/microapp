<?php
// microservices/tata-trivia/views/host/game_host.php

require_once __DIR__ . '/../../init.php';

$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}

// Obtener información de la trivia
$triviaController = new TriviaController();
$trivia = $triviaController->getTriviaById($trivia_id);
$questions = $triviaController->getTriviaQuestions($trivia_id);
$players = $triviaController->getLobbyPlayers($trivia_id);

// Obtener imagen de fondo
$backgroundImage = $triviaController->getBackgroundImagePath($trivia_id);

// Si no hay preguntas, redirigir a questions
if (empty($questions)) {
    header('Location: /microservices/tata-trivia/host/questions?trivia_id=' . $trivia_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduciendo Trivia - <?= htmlspecialchars($trivia['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url('<?= $backgroundImage ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .game-host-container {
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
        .players-sidebar {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .player-item {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .player-item.current {
            background: #e7f3ff;
            border-left: 3px solid #007bff;
        }
        .timer-display {
            font-size: 4rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
        }
        .question-display {
            font-size: 1.5rem;
            line-height: 1.4;
        }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .option-card {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-card:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .option-card.correct {
            border-color: #28a745;
            background: #d4edda;
        }
        .option-card.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
        }
        .results-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="game-host-container">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar de jugadores -->
                <div class="col-md-3">
                    <div class="players-sidebar">
                        <h5 class="mb-3">
                            <i class="fas fa-users me-2"></i>
                            Jugadores (<span id="playerCount"><?= count($players) ?></span>)
                        </h5>
                        <div id="playersList">
                            <?php foreach ($players as $player): ?>
                            <div class="player-item" data-player-id="<?= $player['id'] ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($player['player_name']) ?></strong>
                                        <br>
                                        <small class="text-muted">Puntos: <span class="player-score"><?= $player['score'] ?></span></small>
                                    </div>
                                    <div class="player-status">
                                        <i class="fas fa-circle text-success fa-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Controles del juego -->
                    <div class="mt-3">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="nextQuestion()" id="nextBtn">
                                <i class="fas fa-forward me-2"></i>Siguiente Pregunta
                            </button>
                            <button class="btn btn-warning" onclick="showResults()" id="resultsBtn">
                                <i class="fas fa-chart-bar me-2"></i>Ver Resultados
                            </button>
                            <button class="btn btn-danger" onclick="endGame()" id="endBtn">
                                <i class="fas fa-stop me-2"></i>Terminar Juego
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Área principal del juego -->
                <div class="col-md-9">
                    <div class="question-card">
                        <div class="card-body p-4">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="text-primary mb-0">
                                    <?= htmlspecialchars($trivia['title']) ?>
                                </h2>
                                <div class="text-end">
                                    <div class="timer-display" id="timer">30</div>
                                    <small class="text-muted">Tiempo restante</small>
                                </div>
                            </div>
                            
                            <!-- Pregunta actual -->
                            <div id="questionArea">
                                <div class="text-center mb-4">
                                    <h4 class="text-muted">Preparando primera pregunta...</h4>
                                </div>
                            </div>
                            
                            <!-- Opciones -->
                            <div id="optionsArea" class="options-grid" style="display: none;">
                                <!-- Las opciones se cargarán dinámicamente -->
                            </div>
                            
                            <!-- Resultados de la pregunta -->
                            <div id="resultsArea" class="mt-4" style="display: none;">
                                <h5>Resultados de esta pregunta:</h5>
                                <div id="questionResults"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de posiciones -->
                    <div class="results-table mt-4" id="leaderboard" style="display: none;">
                        <h4 class="mb-3">
                            <i class="fas fa-trophy me-2"></i>Tabla de Posiciones
                        </h4>
                        <div id="leaderboardContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const triviaId = '<?= $trivia_id ?>';
        let currentQuestionIndex = -1;
        let questions = <?= json_encode($questions) ?>;
        let timerInterval;
        let timeLeft = 30;
        let gameActive = true;

        // Iniciar el juego
        function startGame() {
            currentQuestionIndex = -1;
            nextQuestion();
        }

        // Siguiente pregunta
        function nextQuestion() {
            currentQuestionIndex++;
            
            // Ocultar resultados anteriores
            document.getElementById('resultsArea').style.display = 'none';
            document.getElementById('leaderboard').style.display = 'none';
            
            if (currentQuestionIndex < questions.length) {
                showQuestion(currentQuestionIndex);
            } else {
                endGame();
            }
        }

        // Mostrar pregunta
        function showQuestion(index) {
            const question = questions[index];
            const questionArea = document.getElementById('questionArea');
            const optionsArea = document.getElementById('optionsArea');
            
            // Mostrar pregunta
            questionArea.innerHTML = `
                <div class="question-display text-center mb-4">
                    <h3>Pregunta ${index + 1} de ${questions.length}</h3>
                    <p class="lead">${question.question_text}</p>
                </div>
            `;
            
            // Mostrar opciones
            optionsArea.style.display = 'grid';
            optionsArea.innerHTML = '';
            
            // Cargar opciones via AJAX
            fetch(`/microservices/tata-trivia/api/get_question_options.php?question_id=${question.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.options.forEach((option, optIndex) => {
                            const optionCard = document.createElement('div');
                            optionCard.className = 'option-card';
                            optionCard.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-3 fs-6">${String.fromCharCode(65 + optIndex)}</span>
                                    <span class="fs-5">${option.option_text}</span>
                                </div>
                            `;
                            optionsArea.appendChild(optionCard);
                        });
                    }
                });
            
            // Iniciar timer
            startTimer(question.time_limit || 30);
            
            // Deshabilitar botón siguiente temporalmente
            document.getElementById('nextBtn').disabled = true;
            
            // Después del tiempo, mostrar resultados
            setTimeout(() => {
                showQuestionResults(question.id);
                document.getElementById('nextBtn').disabled = false;
            }, (question.time_limit || 30) * 1000);
        }

        // Timer
        function startTimer(seconds) {
            timeLeft = seconds;
            const timerElement = document.getElementById('timer');
            timerElement.textContent = timeLeft;
            timerElement.style.color = '#28a745';
            
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                // Cambiar color según el tiempo
                if (timeLeft <= 10) {
                    timerElement.style.color = '#dc3545';
                } else if (timeLeft <= 20) {
                    timerElement.style.color = '#ffc107';
                }
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                }
            }, 1000);
        }

        // Mostrar resultados de la pregunta
        function showQuestionResults(questionId) {
            fetch(`/microservices/tata-trivia/api/get_question_results.php?question_id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const resultsArea = document.getElementById('resultsArea');
                        const questionResults = document.getElementById('questionResults');
                        
                        questionResults.innerHTML = `
                            <div class="alert alert-info">
                                <strong>Respuesta correcta:</strong> ${data.correct_answer}
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Respondieron correctamente:</strong>
                                    <ul class="mt-2">
                                        ${data.correct_players.map(player => `
                                            <li>${player.player_name} (+${player.points_gained} pts)</li>
                                        `).join('')}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Respondieron incorrectamente:</strong>
                                    <ul class="mt-2">
                                        ${data.incorrect_players.map(player => `
                                            <li>${player.player_name}</li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>
                        `;
                        
                        resultsArea.style.display = 'block';
                        updateLeaderboard();
                    }
                });
        }

        // Actualizar tabla de posiciones
        function updateLeaderboard() {
            fetch(`/microservices/tata-trivia/api/get_leaderboard.php?trivia_id=${triviaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const playersList = document.getElementById('playersList');
                        const leaderboardContent = document.getElementById('leaderboardContent');
                        
                        // Actualizar sidebar
                        playersList.innerHTML = data.players.map(player => `
                            <div class="player-item" data-player-id="${player.id}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${player.player_name}</strong>
                                        <br>
                                        <small class="text-muted">Puntos: <span class="player-score">${player.score}</span></small>
                                    </div>
                                    <div class="player-status">
                                        <i class="fas fa-circle text-success fa-xs"></i>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        
                        // Actualizar tabla de posiciones
                        leaderboardContent.innerHTML = `
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Posición</th>
                                        <th>Jugador</th>
                                        <th>Puntos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.players.map((player, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${player.player_name}</td>
                                            <td><strong>${player.score}</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                    }
                });
        }

        // Mostrar resultados finales
        function showResults() {
            document.getElementById('leaderboard').style.display = 'block';
            updateLeaderboard();
        }

        // Terminar juego
        function endGame() {
            if (confirm('¿Estás seguro de que quieres terminar el juego?')) {
                gameActive = false;
                clearInterval(timerInterval);
                
                fetch('/microservices/tata-trivia/api/finish_game.php', {
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
                        // Mostrar resultados finales
                        document.getElementById('questionArea').innerHTML = `
                            <div class="text-center">
                                <i class="fas fa-flag-checkered fa-4x text-success mb-3"></i>
                                <h3>¡Juego Terminado!</h3>
                                <p class="lead">Gracias por jugar</p>
                            </div>
                        `;
                        document.getElementById('optionsArea').style.display = 'none';
                        document.getElementById('resultsArea').style.display = 'none';
                        showResults();
                        
                        // Deshabilitar controles
                        document.getElementById('nextBtn').disabled = true;
                        document.getElementById('resultsBtn').disabled = true;
                        document.getElementById('endBtn').disabled = true;
                    }
                });
            }
        }

        // Iniciar el juego cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            startGame();
            
            // Actualizar jugadores periódicamente
            setInterval(updateLeaderboard, 3000);
        });
    </script>
</body>
</html>