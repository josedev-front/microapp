<?php
// microservices/trivia-play/views/host/setup.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

// Verificar sesión (opcional para anfitrión)
$usuario_actual = null;
if (validarSesion()) {
    $usuario_actual = obtenerUsuarioActual();
}

require_once __DIR__ . '/../../init.php';

// Temas predefinidos
$themes = [
    'fiestas_patrias' => 'Fiestas Patrias',
    'pascua' => 'Día de Pascua',
    'dia_madre' => 'Día de la Madre',
    'navidad' => 'Navidad',
    'lgbt' => 'Día de la Diversidad LGBT',
    'dia_mujer' => 'Día de la Mujer',
    'halloween' => 'Halloween',
    'amor_amistad' => 'Día del Amor y la Amistad'
];

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $controller = new TriviaPlay\Controllers\TriviaController();
        
        $hostData = [
            'user_id' => $usuario_actual['id'] ?? null,
            'title' => $_POST['trivia_title'],
            'background_image' => $_POST['theme']
        ];
        
        $triviaId = $controller->createTrivia(
            $hostData,
            $_POST['theme'],
            $_POST['game_mode'],
            $_POST['max_winners']
        );
        
        // Redirigir a la edición de preguntas
        header('Location: ' . BASE_URL . '?vista=trivia_questions&id=' . $triviaId);
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Trivia - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .theme-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            height: 120px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        .theme-card.selected {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .theme-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            font-size: 0.8rem;
            text-align: center;
        }
        .game-mode-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        .game-mode-card.selected {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.1);
        }
        .custom-file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .custom-file-upload:hover {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.05);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 bg-light">
        <!-- Header -->
        <nav class="navbar navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>?vista=trivia">
                    <i class="fas fa-arrow-left me-2"></i>
                    <i class="fas fa-trophy me-2"></i>Tata Trivia
                </a>
                <span class="navbar-text">
                    Creando nueva trivia
                </span>
            </div>
        </nav>

        <div class="container py-5">
            <!-- Indicador de pasos -->
            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step">2</div>
                <div class="step">3</div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><i class="fas fa-crown text-warning me-2"></i>Configuración de la Trivia</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <form id="triviaSetupForm" method="POST">
                                <!-- Título de la trivia -->
                                <div class="mb-4">
                                    <label for="trivia_title" class="form-label fw-bold">Título de la Trivia</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="trivia_title" name="trivia_title" 
                                           placeholder="Ej: Trivia de Cultura General" required
                                           maxlength="100">
                                    <div class="form-text">Escribe un título atractivo para tu trivia</div>
                                </div>

                                <!-- Selección de tema -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Tema de Fondo</label>
                                    <div class="row g-3" id="themeSelection">
                                        <!-- Temas predefinidos -->
                                        <?php foreach ($themes as $key => $theme): ?>
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <div class="theme-card" 
                                                 data-theme="<?php echo $key; ?>"
                                                 style="background-image: url('<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/themes/<?php echo $key; ?>.jpg')">
                                                <div class="theme-overlay">
                                                    <?php echo $theme; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>

                                        <!-- Subir imagen personalizada -->
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <div class="theme-card custom-file-upload" id="customThemeUpload">
                                                <i class="fas fa-upload fa-2x text-muted mb-2"></i>
                                                <div class="small text-muted">Subir imagen</div>
                                                <input type="file" id="customThemeFile" accept="image/*" class="d-none">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="theme" id="selectedTheme" required>
                                </div>

                                <!-- Modalidad de juego -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Modalidad de Juego</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="game-mode-card" data-mode="teams">
                                                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                                <h5>Competencia por Equipos</h5>
                                                <p class="text-muted small">Máximo 8 equipos compitiendo</p>
                                                <div class="badge bg-primary">Recomendado para grupos</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="game-mode-card" data-mode="individual">
                                                <i class="fas fa-user fa-3x text-success mb-3"></i>
                                                <h5>Competencia Individual</h5>
                                                <p class="text-muted small">Hasta 7 clasificados</p>
                                                <div class="badge bg-success">Ideal para competencias</div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="game_mode" id="selectedGameMode" required>
                                </div>

                                <!-- Número de ganadores -->
                                <div class="mb-4" id="winnersSection" style="display: none;">
                                    <label for="max_winners" class="form-label fw-bold">
                                        Número de Ganadores
                                    </label>
                                    <select class="form-select" id="max_winners" name="max_winners">
                                        <option value="1">1 Ganador</option>
                                        <option value="2">2 Ganadores</option>
                                        <option value="3">3 Ganadores</option>
                                        <option value="4">4 Ganadores</option>
                                        <option value="5">5 Ganadores</option>
                                        <option value="6">6 Ganadores</option>
                                        <option value="7">7 Ganadores</option>
                                    </select>
                                    <div class="form-text" id="winnersHelp">
                                        Selecciona cuántos participantes serán premiados
                                    </div>
                                </div>

                                <!-- Botones de acción -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo BASE_URL; ?>?vista=trivia" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Continuar a Preguntas <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Información de ayuda -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6><i class="fas fa-lightbulb text-warning me-2"></i>Consejos para una buena trivia:</h6>
                            <ul class="small text-muted mb-0">
                                <li>Elige un tema que sea atractivo para tus participantes</li>
                                <li>La modalidad por equipos es ideal para eventos sociales</li>
                                <li>La modalidad individual funciona mejor para competencias</li>
                                <li>Puedes personalizar completamente las preguntas en el siguiente paso</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Selección de tema
            const themeCards = document.querySelectorAll('.theme-card');
            const selectedThemeInput = document.getElementById('selectedTheme');
            const customThemeUpload = document.getElementById('customThemeUpload');
            const customThemeFile = document.getElementById('customThemeFile');

            themeCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remover selección anterior
                    themeCards.forEach(c => c.classList.remove('selected'));
                    
                    // Seleccionar nuevo tema
                    this.classList.add('selected');
                    
                    if (this === customThemeUpload) {
                        customThemeFile.click();
                    } else {
                        selectedThemeInput.value = this.dataset.theme;
                    }
                });
            });

            // Manejar subida de imagen personalizada
            customThemeFile.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Validar tipo de archivo
                    if (!file.type.startsWith('image/')) {
                        alert('Por favor selecciona una imagen válida');
                        return;
                    }
                    
                    // Validar tamaño (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('La imagen no debe superar los 5MB');
                        return;
                    }
                    
                    // Crear preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        customThemeUpload.style.backgroundImage = `url(${e.target.result})`;
                        customThemeUpload.innerHTML = `
                            <div class="theme-overlay">
                                Imagen Personalizada
                            </div>
                        `;
                        selectedThemeInput.value = 'custom_' + Date.now();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Selección de modalidad de juego
            const gameModeCards = document.querySelectorAll('.game-mode-card');
            const selectedGameModeInput = document.getElementById('selectedGameMode');
            const winnersSection = document.getElementById('winnersSection');
            const winnersHelp = document.getElementById('winnersHelp');

            gameModeCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remover selección anterior
                    gameModeCards.forEach(c => c.classList.remove('selected'));
                    
                    // Seleccionar nueva modalidad
                    this.classList.add('selected');
                    selectedGameModeInput.value = this.dataset.mode;
                    
                    // Mostrar/ocultar configuración de ganadores
                    winnersSection.style.display = 'block';
                    
                    // Actualizar texto de ayuda
                    if (this.dataset.mode === 'teams') {
                        winnersHelp.textContent = 'Selecciona cuántos equipos serán premiados (máximo 8)';
                        document.querySelector('#max_winners option[value="8"]').style.display = 'block';
                    } else {
                        winnersHelp.textContent = 'Selecciona cuántos participantes serán premiados (máximo 7)';
                        document.querySelector('#max_winners option[value="8"]').style.display = 'none';
                    }
                });
            });

            // Validación del formulario
            document.getElementById('triviaSetupForm').addEventListener('submit', function(e) {
                if (!selectedThemeInput.value) {
                    e.preventDefault();
                    alert('Por favor selecciona un tema de fondo');
                    return;
                }
                
                if (!selectedGameModeInput.value) {
                    e.preventDefault();
                    alert('Por favor selecciona una modalidad de juego');
                    return;
                }
            });
        });
    </script>
</body>
</html>