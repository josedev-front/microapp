<?php
// microservices/trivia-play/views/history/player_history.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

// Verificar sesión
if (!validarSesion()) {
    header('Location: ' . BASE_URL . '?vista=login');
    exit;
}

$usuario_actual = obtenerUsuarioActual();
if (!$usuario_actual) {
    header('Location: ' . BASE_URL . '?vista=login');
    exit;
}

require_once __DIR__ . '/../../init.php';

try {
    $triviaController = new TriviaPlay\Controllers\TriviaController();
    $playerHistory = $triviaController->getPlayerHistory($usuario_actual['id']);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $playerHistory = [];
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .history-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .history-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .history-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .position-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .position-1 { background: linear-gradient(45deg, #FFD700, #FFA500); }
        .position-2 { background: linear-gradient(45deg, #C0C0C0, #A0A0A0); }
        .position-3 { background: linear-gradient(45deg, #CD7F32, #A56C27); }
        .position-other { background: linear-gradient(45deg, #667eea, #764ba2); }
        .stats-badge {
            font-size: 0.8rem;
        }
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: #6c757d;
        }
        .filter-buttons .btn {
            border-radius: 20px;
            margin: 0 2px 5px 0;
        }
    </style>
</head>
<body class="history-container">
    <!-- Header -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>?vista=trivia">
                <i class="fas fa-arrow-left me-2"></i>
                <i class="fas fa-trophy me-2"></i>Tata Trivia
            </a>
            <div class="navbar-text">
                <span class="badge bg-light text-dark">Mi Historial</span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header del historial -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-history fa-3x text-primary mb-3"></i>
                        <h1 class="h2 fw-bold text-dark mb-2">Mi Historial de Partidas</h1>
                        <p class="text-muted mb-0">
                            Revisa tus resultados y estadísticas en todas las trivias que has jugado
                        </p>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="text-dark mb-3"><i class="fas fa-filter me-2"></i>Filtrar Partidas</h6>
                        <div class="filter-buttons">
                            <button class="btn btn-outline-primary active" data-filter="all">
                                Todas <span class="badge bg-primary ms-1"><?php echo count($playerHistory); ?></span>
                            </button>
                            <button class="btn btn-outline-success" data-filter="top3">
                                Top 3 <span class="badge bg-success ms-1" id="top3Count">0</span>
                            </button>
                            <button class="btn btn-outline-warning" data-filter="recent">
                                Último Mes <span class="badge bg-warning ms-1" id="recentCount">0</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de partidas -->
                <div id="historyList">
                    <?php if (empty($playerHistory)): ?>
                        <div class="empty-state">
                            <i class="fas fa-gamepad fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aún no has jugado trivias</h4>
                            <p class="text-muted mb-4">Únete a una trivia para empezar a acumular historial</p>
                            <a href="<?php echo BASE_URL; ?>?vista=trivia_join" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i>Jugar Mi Primera Trivia
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($playerHistory as $history): ?>
                        <div class="history-card p-4 mb-3" 
                             data-position="<?php echo $history['position']; ?>"
                             data-date="<?php echo $history['played_at']; ?>">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="position-badge <?php echo $history['position'] <= 3 ? 'position-' . $history['position'] : 'position-other'; ?>">
                                        <?php echo $history['position']; ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <h5 class="text-dark mb-1"><?php echo htmlspecialchars($history['title']); ?></h5>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge bg-primary stats-badge">
                                            <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($history['game_date'])); ?>
                                        </span>
                                        <span class="badge bg-success stats-badge">
                                            <i class="fas fa-star me-1"></i><?php echo $history['final_score']; ?> puntos
                                        </span>
                                        <span class="badge bg-info stats-badge">
                                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($history['theme']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Jugado <?php echo time_elapsed_string($history['played_at']); ?>
                                    </small>
                                </div>
                                <div class="col-auto text-end">
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo $history['position'] == 1 ? 'warning' : ($history['position'] <= 3 ? 'success' : 'secondary'); ?> fs-6">
                                            <?php echo $history['position']; ?>º Lugar
                                        </span>
                                    </div>
                                    <a href="#" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas resumen -->
                <?php if (!empty($playerHistory)): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="text-dark mb-3"><i class="fas fa-chart-pie me-2"></i>Mis Estadísticas</h6>
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-primary mb-1"><?php echo count($playerHistory); ?></div>
                                    <small class="text-muted">Partidas Jugadas</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-warning mb-1">
                                        <?php 
                                        $top3Count = count(array_filter($playerHistory, function($h) { 
                                            return $h['position'] <= 3; 
                                        }));
                                        echo $top3Count;
                                        ?>
                                    </div>
                                    <small class="text-muted">Veces en Top 3</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-success mb-1">
                                        <?php 
                                        $victories = count(array_filter($playerHistory, function($h) { 
                                            return $h['position'] == 1; 
                                        }));
                                        echo $victories;
                                        ?>
                                    </div>
                                    <small class="text-muted">Victorias</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-info mb-1">
                                        <?php 
                                        $avgPosition = array_sum(array_column($playerHistory, 'position')) / count($playerHistory);
                                        echo number_format($avgPosition, 1);
                                        ?>
                                    </div>
                                    <small class="text-muted">Posición Promedio</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-buttons .btn');
            const historyItems = document.querySelectorAll('.history-card');
            const top3Count = document.getElementById('top3Count');
            const recentCount = document.getElementById('recentCount');

            // Calcular conteos iniciales
            updateFilterCounts();

            // Filtros
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Actualizar botones activos
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    filterHistory(filter);
                });
            });

            function filterHistory(filter) {
                const now = new Date();
                const oneMonthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                
                historyItems.forEach(item => {
                    let show = true;
                    const position = parseInt(item.dataset.position);
                    const date = new Date(item.dataset.date);
                    
                    switch(filter) {
                        case 'top3':
                            show = position <= 3;
                            break;
                        case 'recent':
                            show = date >= oneMonthAgo;
                            break;
                        case 'all':
                        default:
                            show = true;
                    }
                    
                    item.style.display = show ? 'block' : 'none';
                });
            }

            function updateFilterCounts() {
                const now = new Date();
                const oneMonthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                
                let top3 = 0;
                let recent = 0;
                
                historyItems.forEach(item => {
                    const position = parseInt(item.dataset.position);
                    const date = new Date(item.dataset.date);
                    
                    if (position <= 3) top3++;
                    if (date >= oneMonthAgo) recent++;
                });
                
                top3Count.textContent = top3;
                recentCount.textContent = recent;
            }

            // Animación de entrada
            historyItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });
    </