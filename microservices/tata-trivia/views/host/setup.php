<?php
// microservices/tata-trivia/views/host/setup.php - VERSIÓN DEBUG

// Incluir init y verificar controlador inmediatamente
require_once __DIR__ . '/../../init.php';

$user = getTriviaMicroappsUser();
$error = null;
$success = false;

// DEBUG EXTENDIDO
$debugInfo = [];
$debugInfo[] = "=== DEBUG SETUP.PHP ===";
$debugInfo[] = "Session ID: " . session_id();
$debugInfo[] = "User: " . ($user ? $user['username'] : 'NO USER');

// Verificar controlador
if (class_exists('TriviaController')) {
    $debugInfo[] = "✅ TriviaController class EXISTS";
    
    try {
        $testController = new TriviaController();
        $debugInfo[] = "✅ TriviaController INSTANCIADO";
        
        // Probar conexión a BD
        $db = getTriviaDatabaseConnection();
        if ($db) {
            $debugInfo[] = "✅ Conexión BD OK";
        } else {
            $debugInfo[] = "❌ Conexión BD FALLÓ";
        }
        
    } catch (Exception $e) {
        $debugInfo[] = "❌ Error instanciando TriviaController: " . $e->getMessage();
    }
} else {
    $debugInfo[] = "❌ TriviaController class NO EXISTE";
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugInfo[] = "=== PROCESSING POST ===";
    $debugInfo[] = "POST data: " . print_r($_POST, true);
    
    try {
        // Verificación exhaustiva del controlador
        if (!class_exists('TriviaController')) {
            throw new Exception("Controlador no disponible");
        }
        
        // Intentar instanciar
        $triviaController = new TriviaController();
        $debugInfo[] = "✅ Controller instanciado para procesar";
        
        // Preparar datos
        $hostData = [
            'user_id' => $user['id'] ?? null,
            'title' => $_POST['title'] ?? 'Trivia sin título',
            'theme' => $_POST['theme'] ?? 'default',
            'game_mode' => $_POST['game_mode'] ?? 'individual',
            'max_winners' => intval($_POST['max_winners'] ?? 1),
            'background_image' => $_POST['background_image'] ?? ($_POST['theme'] ?? 'default')
        ];
        
        $debugInfo[] = "Host data preparado: " . print_r($hostData, true);
        
        // Validaciones básicas
        if (empty($hostData['title'])) {
            throw new Exception("El título es requerido");
        }
        
        // Crear trivia
        $debugInfo[] = "Llamando createTrivia...";
        $result = $triviaController->createTrivia($hostData);
        $debugInfo[] = "Resultado createTrivia: " . print_r($result, true);
        
        if ($result['success']) {
            $debugInfo[] = "✅ Trivia creada, redirigiendo...";
            // Redirigir a la página de preguntas
            header('Location: /microservices/tata-trivia/host/questions?trivia_id=' . $result['trivia_id'] . '&join_code=' . $result['join_code']);
            exit;
        } else {
            throw new Exception($result['error'] ?? "Error desconocido al crear trivia");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $debugInfo[] = "❌ Exception: " . $e->getMessage();
        error_log("Error en setup.php: " . $e->getMessage());
    }
}

$debugInfo[] = "=== END DEBUG ===";
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
        .setup-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .theme-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .theme-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .theme-option.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .debug-info {
            font-family: monospace;
            font-size: 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="setup-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="setup-card">
                    <div class="card-body p-4">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h1 class="text-primary">
                                <i class="fas fa-plus-circle me-2"></i>Crear Nueva Trivia
                            </h1>
                            <p class="text-muted">Configura los detalles de tu trivia interactiva</p>
                        </div>

                        <!-- Debug info -->
                        <div class="debug-info">
                            <strong>Debug Info:</strong><br>
                            <?php echo implode("<br>", $debugInfo); ?>
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
                                    Título de la Trivia
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="title" name="title" 
                                       placeholder="Ej: Trivia de Cultura General"
                                       required value="Test Trivia <?php echo date('Y-m-d H:i:s'); ?>">
                                <div class="form-text">Usa un título descriptivo para tu trivia</div>
                            </div>

                            <!-- Tema -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-palette me-2"></i>Tema de la Trivia
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="theme-option selected" data-theme="fiestas_patrias">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" 
                                                       id="theme_fiestas" value="fiestas_patrias" checked>
                                                <label class="form-check-label fw-bold" for="theme_fiestas">
                                                    Fiestas Patrias
                                                </label>
                                            </div>
                                            <small class="text-muted">Tema patriótico chileno</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="theme-option" data-theme="navidad">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" 
                                                       id="theme_navidad" value="navidad">
                                                <label class="form-check-label fw-bold" for="theme_navidad">
                                                    Navidad
                                                </label>
                                            </div>
                                            <small class="text-muted">Tema navideño festivo</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="theme-option" data-theme="halloween">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" 
                                                       id="theme_halloween" value="halloween">
                                                <label class="form-check-label fw-bold" for="theme_halloween">
                                                    Halloween
                                                </label>
                                            </div>
                                            <small class="text-muted">Tema de terror y diversión</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="theme-option" data-theme="default">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" 
                                                       id="theme_default" value="default">
                                                <label class="form-check-label fw-bold" for="theme_default">
                                                    General
                                                </label>
                                            </div>
                                            <small class="text-muted">Tema neutro y profesional</small>
                                        </div>
                                    </div>
                                </div>
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
                            </div>

                            <!-- Máximo de Ganadores -->
                            <div class="mb-4">
                                <label for="max_winners" class="form-label fw-bold">
                                    <i class="fas fa-trophy me-2"></i>Máximo de Ganadores
                                </label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="max_winners" name="max_winners" 
                                       value="3" min="1" max="10">
                                <div class="form-text">
                                    Número máximo de jugadores/equipos que pueden ganar
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/microservices/tata-trivia/" class="btn btn-secondary btn-lg me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Crear Trivia
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
        // Interactividad para los temas
        document.addEventListener('DOMContentLoaded', function() {
            // Selección de temas
            const themeOptions = document.querySelectorAll('.theme-option');
            themeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remover selección anterior
                    themeOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Seleccionar actual
                    this.classList.add('selected');
                    
                    // Marcar el radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });
        });
    </script>
</body>
</html>