<?php
// microservices/tata-trivia/views/host/setup.php

require_once __DIR__ . '/../../init.php';

$user = getTriviaMicroappsUser();
$error = null;
$success = false;

// Obtener imágenes disponibles
$triviaController = new TriviaController();
$backgroundImages = $triviaController->getSetupBackgrounds();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!class_exists('TriviaController')) {
            throw new Exception("Controlador no disponible");
        }
        
        $triviaController = new TriviaController();
        
        // Preparar datos
        $hostData = [
            'user_id' => $user['id'] ?? null,
            'title' => $_POST['title'] ?? 'Trivia sin título',
            'theme' => $_POST['theme'] ?? 'default',
            'game_mode' => $_POST['game_mode'] ?? 'individual',
            'max_winners' => intval($_POST['max_winners'] ?? 1),
            'background_image' => $_POST['theme'] ?? 'default'
        ];
        
        // Validaciones básicas
        if (empty($hostData['title'])) {
            throw new Exception("El título es requerido");
        }
        
        // Crear trivia
        $result = $triviaController->createTrivia($hostData);
        
        if ($result['success']) {
            // Redirigir a la página de preguntas
            header('Location: /microservices/tata-trivia/host/questions?trivia_id=' . $result['trivia_id'] . '&join_code=' . $result['join_code']);
            exit;
        } else {
            throw new Exception($result['error'] ?? "Error desconocido al crear trivia");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error en setup.php: " . $e->getMessage());
    }
}

// Obtener la imagen de fondo seleccionada para preview
$selectedBackground = $_POST['theme'] ?? 'fiestas_patrias';
$backgroundPath = $triviaController->getBackgroundImagePath(0); // Usar método para obtener path
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Trivia - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        .theme-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .theme-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .theme-option.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .theme-preview {
            width: 100%;
            height: 80px;
            background-size: cover;
            background-position: center;
            border-radius: 5px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .image-option {
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }
        .image-option:hover {
            border-color: #007bff;
            transform: scale(1.05);
        }
        .image-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        .image-preview {
            width: 100%;
            height: 80px;
            background-size: cover;
            background-position: center;
        }
        .background-preview-large {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 3px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="setup-card">
                    <div class="card-body p-4">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h1 class="text-primary">
                                <i class="fas fa-plus-circle me-2"></i>Crear Nueva Trivia
                            </h1>
                            <p class="text-muted">Configura los detalles de tu trivia interactiva</p>
                        </div>

                        <!-- Vista Previa del Fondo -->
                        <div class="text-center mb-4">
                            <h6 class="text-muted mb-2">Vista Previa del Fondo Seleccionado:</h6>
                            <div class="background-preview-large" id="largeBackgroundPreview" 
                                 style="background-image: url('/microservices/tata-trivia/assets/images/themes/setup/fiestas_patrias.jpg')">
                            </div>
                        </div>

                        <!-- Mensajes de error -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" id="triviaForm">
                            <!-- Título -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-bold">
                                    <i class="fas fa-heading me-2"></i>Título de la Trivia
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="title" name="title" 
                                       placeholder="Ej: Trivia de Cultura General"
                                       required value="Test Trivia <?php echo date('Y-m-d H:i:s'); ?>">
                                <div class="form-text">Usa un título descriptivo para tu trivia</div>
                            </div>

                            <!-- Tema con Imágenes -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-palette me-2"></i>Tema y Fondo de la Trivia
                                </label>
                                
                                <!-- Temas Predefinidos -->
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Temas Predefinidos:</h6>
                                    <div class="row">
                                        <?php 
                                        $defaultThemes = [
                                            'fiestas_patrias' => ['name' => 'Fiestas Patrias', 'desc' => 'Tema patriótico chileno'],
                                            'navidad' => ['name' => 'Navidad', 'desc' => 'Tema navideño festivo'],
                                            'halloween' => ['name' => 'Halloween', 'desc' => 'Tema de terror y diversión'],
                                            'default' => ['name' => 'General', 'desc' => 'Tema neutro y profesional'],
                                            'dia_mujer' => ['name' => 'Día de la Mujer', 'desc' => 'Tema conmemorativo'],
                                            'dia_amor' => ['name' => 'Día del Amor', 'desc' => 'Tema romántico'],
                                            'lgbt' => ['name' => 'Diversidad LGBT', 'desc' => 'Tema inclusivo'],
                                            'pascua' => ['name' => 'Pascua', 'desc' => 'Tema de pascua']
                                        ];
                                        
                                        foreach ($defaultThemes as $themeKey => $themeInfo):
                                            $imagePath = "/microservices/tata-trivia/assets/images/themes/setup/{$themeKey}.jpg";
                                            $actualPath = file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath) ? $imagePath : '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
                                        ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="theme-option <?= $themeKey === 'fiestas_patrias' ? 'selected' : '' ?>" 
                                                 data-theme="<?= $themeKey ?>" data-image="<?= $actualPath ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="theme" 
                                                           id="theme_<?= $themeKey ?>" value="<?= $themeKey ?>" 
                                                           <?= $themeKey === 'fiestas_patrias' ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold" for="theme_<?= $themeKey ?>">
                                                        <?= $themeInfo['name'] ?>
                                                    </label>
                                                </div>
                                                <div class="theme-preview" style="background-image: url('<?= $actualPath ?>')"></div>
                                                <small class="text-muted"><?= $themeInfo['desc'] ?></small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Imágenes Personalizadas Disponibles -->
                                <?php if (!empty($backgroundImages)): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Imágenes Personalizadas:</h6>
                                    <div class="image-grid">
                                        <?php foreach ($backgroundImages as $image): ?>
                                        <div class="image-option" data-theme="custom_<?= $image['filename'] ?>" 
                                             data-image="<?= $image['path'] ?>">
                                            <div class="image-preview" style="background-image: url('<?= $image['path'] ?>')"></div>
                                            <input type="radio" name="theme" value="custom_<?= $image['filename'] ?>" 
                                                   id="img_<?= $image['filename'] ?>" style="display: none;">
                                            <label for="img_<?= $image['filename'] ?>" class="d-block text-center small p-1">
                                                <?= substr($image['name'], 0, 10) ?>...
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Modalidad de Juego -->
                            <div class="mb-4">
                                <label for="game_mode" class="form-label fw-bold">
                                    <i class="fas fa-users me-2"></i>Modalidad de Juego
                                </label>
                                <select class="form-select form-select-lg" id="game_mode" name="game_mode">
                                    <option value="individual" selected>Competencia Individual</option>
                                    <option value="teams">Competencia por Equipos</option>
                                </select>
                                <div class="form-text" id="gameModeHelp">
                                    Cada jugador compite por sí mismo
                                </div>
                            </div>

                            <!-- Máximo de Ganadores -->
                            <div class="mb-4">
                                <label for="max_winners" class="form-label fw-bold">
                                    <i class="fas fa-trophy me-2"></i>Número de Ganadores
                                </label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="max_winners" name="max_winners" 
                                       value="3" min="1" max="10">
                                <div class="form-text">
                                    Número máximo de jugadores/equipos que pueden ganar
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="/microservices/tata-trivia/" class="btn btn-secondary btn-lg me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Crear Trivia y Configurar Preguntas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const largePreview = document.getElementById('largeBackgroundPreview');
            
            // Función para actualizar vista previa grande
            function updateLargePreview(imagePath) {
                largePreview.style.backgroundImage = `url('${imagePath}')`;
            }
            
            // Selección de temas
            const themeOptions = document.querySelectorAll('.theme-option');
            themeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remover selección anterior
                    themeOptions.forEach(opt => opt.classList.remove('selected'));
                    document.querySelectorAll('.image-option').forEach(opt => opt.classList.remove('selected'));
                    
                    // Seleccionar actual
                    this.classList.add('selected');
                    
                    // Marcar el radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Actualizar vista previa
                    const imagePath = this.dataset.image;
                    updateLargePreview(imagePath);
                });
            });

            // Selección de imágenes
            const imageOptions = document.querySelectorAll('.image-option');
            imageOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remover selección anterior de temas
                    themeOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Remover selección anterior de imágenes
                    imageOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Seleccionar actual
                    this.classList.add('selected');
                    
                    // Marcar el radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Actualizar vista previa
                    const imagePath = this.dataset.image;
                    updateLargePreview(imagePath);
                });
            });

            // Cambiar texto de ayuda según modalidad
            const gameModeSelect = document.getElementById('game_mode');
            const gameModeHelp = document.getElementById('gameModeHelp');
            
            gameModeSelect.addEventListener('change', function() {
                if (this.value === 'teams') {
                    gameModeHelp.textContent = 'Máximo 8 equipos compitiendo entre sí';
                } else {
                    gameModeHelp.textContent = 'Cada jugador compite por sí mismo';
                }
            });
        });
    </script>
</body>
</html>