<?php
// microservices/trivia-play/views/results.php

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
    $finalResults = $triviaController->getFinalResults($trivia_id);
    $players = $hostController->getLobbyPlayers($trivia_id);
    $questions = $hostController->getQuestions($trivia_id);
    
    // Marcar trivia como finalizada
    if ($trivia['status'] !== 'finished') {
        $hostController->finishTrivia($trivia_id, $usuario_actual['id'] ?? null);
    }
    
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '?vista=trivia_host');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados: <?php echo $trivia['title']; ?> - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .results-container {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: white;
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
        .stats-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .player-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid;
        }
        .progress-custom {
            height: 20px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        .progress-bar-custom {
            border-radius: 10px;
            transition: width 1s ease-in-out;
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
        .share-btn {
            background: linear-gradient(45deg, #25D366, #128C7E);
            border: none;
            color: white;
        }
        .play-again-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
        }
    </style>
</head>
<body class="results-container">
    <!-- Confetti -->
    <div id="confettiContainer"></div>

    <!-- Header -->
    <nav class="navbar navbar-dark border-bottom border-secondary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>?vista=trivia">
                <i class="fas fa-trophy me-2"></i>Tata Trivia
            </a>
            <div class="navbar-text">
                <span class="badge bg-success me-2">Finalizada</span>
                <span class="badge bg-light text-dark"><?php echo $trivia['title']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Ganador principal -->
        <?php if (!empty($finalResults)): ?>
        <div class="winner-card">
            <i class="fas fa-crown fa-3x mb-3" style="color: #FFD700;"></i>
            <h1 class="display-4 fw-bold mb-3">¡Felicidades!</h1>
            <div class="row justify-content-center align-items-center">
                <div class="col-auto">
                    <img src="<?php echo $finalResults[0]['avatar']; ?>" 
                         alt="Ganador" class="player-avatar border-warning">
                </div>
                <div class="col-auto">
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($finalResults[0]['player_name']); ?></h2>
                    <p class="lead mb-0">Ganador de la trivia</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="badge bg-dark fs-6">
                    <i class="fas fa-star me-1"></i><?php echo $finalResults[0]['score']; ?> puntos
                </span>
                <?php if ($finalResults[0]['team_name']): ?>
                <span class="badge bg-primary fs-6 ms-2">
                    <?php echo htmlspecialchars($finalResults[0]['team_name']); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Podio -->
            <div class="col-lg-8">
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary">
                        <h4 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Podio de Ganadores
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($finalResults)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay resultados disponibles</h5>
                                <p class="text-muted mb-0">Los jugadores no completaron la trivia</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($finalResults as $index => $player): ?>
                            <div class="podium-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="rank-badge <?php echo $index < 3 ? 'rank-' . ($index + 1) : 'rank-other'; ?>">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <img src="<?php echo $player['avatar']; ?>" 
                                             alt="Avatar" class="player-avatar"
                                             style="border-color: <?php echo $index < 3 ? ['#FFD700', '#C0C0C0', '#CD7F32'][$index] : '#667eea'; ?>;">
                                    </div>
                                    <div class="col">
                                        <h5 class="mb-1 text-dark"><?php echo htmlspecialchars($player['player_name']); ?></h5>
                                        <?php if ($player['team_name']): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($player['team_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-end">
                                            <div class="h4 mb-0 text-dark"><?php echo $player['score']; ?></div>
                                            <small class="text-muted">puntos</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Barra de progreso -->
                                <div class="mt-3">
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom bg-success" 
                                             style="width: <?php echo ($player['score'] / max(array_column($finalResults, 'score'))) * 100; ?>%">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">
                                            <?php echo $player['correct_answers']; ?> respuestas correctas
                                        </small>
                                        <small class="text-muted">
                                            <?php echo number_format(($player['correct_answers'] / count($questions)) * 100, 1); ?>%
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estadísticas detalladas -->
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas de la Trivia
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <div class="h4 mb-1"><?php echo count($players); ?></div>
                                    <small class="text-muted">Jugadores</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <i class="fas fa-question-circle fa-2x text-info mb-2"></i>
                                    <div class="h4 mb-1"><?php echo count($questions); ?></div>
                                    <small class="text-muted">Preguntas</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <div class="h4 mb-1">
                                        <?php 
                                        $total_time = array_sum(array_column($questions, 'time_limit'));
                                        echo ceil($total_time / 60); 
                                        ?>
                                    </div>
                                    <small class="text-muted">Minutos</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                    <div class="h4 mb-1">
                                        <?php 
                                        $total_answers = count($players) * count($questions);
                                        $correct_answers = array_sum(array_column($finalResults, 'correct_answers'));
                                        echo $total_answers > 0 ? number_format(($correct_answers / $total_answers) * 100, 1) : 0;
                                        ?>%
                                    </div>
                                    <small class="text-muted">Precisión</small>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de distribución de puntajes -->
                        <div class="mt-4">
                            <h6 class="text-light mb-3">Distribución de Puntajes</h6>
                            <?php
                            $scoreRanges = [0, 10, 20, 30, 40, 50];
                            $scoreDistribution = array_fill(0, count($scoreRanges) - 1, 0);
                            
                            foreach ($finalResults as $player) {
                                for ($i = 0; $i < count($scoreRanges) - 1; $i++) {
                                    if ($player['score'] >= $scoreRanges[$i] && $player['score'] < $scoreRanges[$i + 1]) {
                                        $scoreDistribution[$i]++;
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <?php foreach ($scoreDistribution as $index => $count): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-light">
                                        <?php echo $scoreRanges[$index]; ?>-<?php echo $scoreRanges[$index + 1]; ?> pts
                                    </small>
                                    <small class="text-muted"><?php echo $count; ?> jugadores</small>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-custom bg-info" 
                                         style="width: <?php echo count($players) > 0 ? ($count / count($players)) * 100 : 0; ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Acciones -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary">
                        <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Compartir Resultados</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn share-btn" id="shareResults">
                                <i class="fab fa-whatsapp me-2"></i>Compartir en WhatsApp
                            </button>
                            <button class="btn btn-outline-light" id="copyResults">
                                <i class="fas fa-copy me-2"></i>Copiar Enlace
                            </button>
                            <button class="btn btn-outline-info" id="downloadResults">
                                <i class="fas fa-download me-2"></i>Descargar PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Jugar de nuevo -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary">
                        <h6 class="mb-0"><i class="fas fa-redo me-2"></i>Jugar de Nuevo</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-light mb-3">
                            ¿Quieres repetir esta trivia con la misma configuración?
                        </p>
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>?vista=trivia_questions&id=<?php echo $trivia_id; ?>" 
                               class="btn play-again-btn">
                                <i class="fas fa-edit me-2"></i>Editar y Jugar
                            </a>
                            <a href="<?php echo BASE_URL; ?>?vista=trivia_host" 
                               class="btn btn-outline-warning">
                                <i class="fas fa-plus me-2"></i>Nueva Trivia
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Código de la trivia -->
                <div class="card bg-dark border-secondary">
                    <div class="card-body text-center">
                        <h6 class="text-light mb-3">Código de la Trivia</h6>
                        <div class="display-6 fw-bold text-warning mb-3">
                            <?php echo $trivia['join_code']; ?>
                        </div>
                        <small class="text-muted">
                            Comparte este código para que otros vean los resultados
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shareResultsBtn = document.getElementById('shareResults');
            const copyResultsBtn = document.getElementById('copyResults');
            const downloadResultsBtn = document.getElementById('downloadResults');
            const confettiContainer = document.getElementById('confettiContainer');

            // Crear confetti
            createConfetti();

            // Compartir resultados
            shareResultsBtn.addEventListener('click', function() {
                const resultsText = `🏆 Resultados de la trivia "<?php echo $trivia['title']; ?>"\n\n` +
                    `🥇 Ganador: <?php echo !empty($finalResults) ? htmlspecialchars($finalResults[0]['player_name']) : 'N/A'; ?>\n` +
                    `📊 Puntuación: <?php echo !empty($finalResults) ? $finalResults[0]['score'] : 0; ?> puntos\n` +
                    `👥 Jugadores: <?php echo count($players); ?>\n\n` +
                    `Código: <?php echo $trivia['join_code']; ?>\n` +
                    `Ver resultados: ${window.location.href}`;

                if (navigator.share) {
                    navigator.share({
                        title: 'Resultados - Tata Trivia',
                        text: resultsText,
                        url: window.location.href
                    });
                } else {
                    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(resultsText)}`;
                    window.open(whatsappUrl, '_blank');
                }
            });

            // Copiar enlace
            copyResultsBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(window.location.href).then(function() {
                    const originalText = copyResultsBtn.innerHTML;
                    copyResultsBtn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
                    copyResultsBtn.classList.remove('btn-outline-light');
                    copyResultsBtn.classList.add('btn-success');
                    
                    setTimeout(function() {
                        copyResultsBtn.innerHTML = originalText;
                        copyResultsBtn.classList.remove('btn-success');
                        copyResultsBtn.classList.add('btn-outline-light');
                    }, 2000);
                });
            });

            // Descargar PDF (simulado)
            downloadResultsBtn.addEventListener('click', function() {
                // En una implementación real, esto generaría un PDF
                alert('La descarga de PDF estará disponible próximamente');
            });

            // Animación de barras de progreso
            animateProgressBars();

            function createConfetti() {
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
                        confetti.remove();
                    }, 5000);
                }
            }

            function animateProgressBars() {
                const progressBars = document.querySelectorAll('.progress-bar-custom');
                progressBars.forEach(bar => {
                    const finalWidth = bar.style.width;
                    bar.style.width = '0%';
                    
                    setTimeout(() => {
                        bar.style.width = finalWidth;
                    }, 500);
                });
            }

            // Animar entrada de elementos
            const podiumItems = document.querySelectorAll('.podium-item');
            podiumItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>