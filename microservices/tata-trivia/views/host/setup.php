<?php
// microservices/tata-trivia/views/host/setup.php - VERSIÓN FINAL

require_once __DIR__ . '/../../init.php';

$user = getTriviaMicroappsUser();
$error = null;
$success = false;

// Directorio de imágenes de temas
$themesDir = '/microservices/tata-trivia/assets/images/themes/setup/';

// Temas disponibles con sus imágenes
$themes = [
    'fiestas_patrias' => [
        'name' => 'Fiestas Patrias',
        'image' => $themesDir . 'fiestas-patrias.jpg',
        'description' => 'Tema patriótico chileno'
    ],
    'navidad' => [
        'name' => 'Navidad', 
        'image' => $themesDir . 'navidad.jpg',
        'description' => 'Tema navideño festivo'
    ],
    'halloween' => [
        'name' => 'Halloween',
        'image' => $themesDir . 'halloween.jpg',
        'description' => 'Tema de terror y diversión'
    ],
    'dia_mujer' => [
        'name' => 'Día de la Mujer',
        'image' => $themesDir . 'dia-mujer.jpg',
        'description' => 'Tema para el día de la mujer'
    ],
    'lgbt' => [
        'name' => 'Diversidad LGBT',
        'image' => $themesDir . 'lgbt.jpg',
        'description' => 'Tema arcoíris diversidad'
    ],
    'amor' => [
        'name' => 'Día del Amor',
        'image' => $themesDir . 'dia-sanvalentin.png',
        'description' => 'Tema romántico 14 de febrero'
    ],
    'pascua' => [
        'name' => 'Pascua',
        'image' => $themesDir . 'pascua.jpg',
        'description' => 'Tema de pascua y renovación'
    ],
    'default' => [
        'name' => 'General',
        'image' => $themesDir . 'general.png',
        'description' => 'Tema neutro y profesional'
    ]
];

// Determinar tema actual
$currentTheme = $_POST['theme'] ?? 'default';
$currentBackground = $themes[$currentTheme]['image'] ?? $themes['default']['image'];
$customBackgroundData = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!class_exists('TriviaController')) {
            throw new Exception("Controlador no disponible");
        }
        
        $triviaController = new TriviaController();
        
        // Manejar imagen personalizada
        $background_image = $_POST['theme'] ?? 'default';
        
        if (isset($_FILES['custom_background']) && $_FILES['custom_background']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/microservices/tata-trivia/assets/images/themes/custom/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['custom_background']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Tipo de archivo no permitido. Solo JPG, PNG, GIF y WEBP.");
            }
            
            // Validar tamaño (máximo 5MB)
            if ($_FILES['custom_background']['size'] > 5 * 1024 * 1024) {
                throw new Exception("La imagen es demasiado grande. Máximo 5MB.");
            }
            
            // Generar nombre único
            $fileExt = pathinfo($_FILES['custom_background']['name'], PATHINFO_EXTENSION);
            $fileName = 'custom_' . time() . '_' . uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['custom_background']['tmp_name'], $filePath)) {
                $background_image = 'custom/' . $fileName;
                $customBackgroundData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($filePath));
            } else {
                throw new Exception("Error al subir la imagen");
            }
        }
        
        // Preparar datos
        $hostData = [
            'user_id' => $user['id'] ?? null,
            'title' => trim($_POST['title'] ?? 'Trivia sin título'),
            'theme' => $_POST['theme'] ?? 'default',
            'game_mode' => $_POST['game_mode'] ?? 'individual',
            'max_winners' => intval($_POST['max_winners'] ?? 1),
            'background_image' => $background_image
        ];
        
        // Validaciones básicas
        if (empty($hostData['title'])) {
            throw new Exception("El título es requerido");
        }
        
        if (strlen($hostData['title']) < 3) {
            throw new Exception("El título debe tener al menos 3 caracteres");
        }
        
        // Crear trivia
        $result = $triviaController->createTrivia($hostData);
        
        if ($result['success']) {
            header('Location: /microservices/tata-trivia/host/questions?trivia_id=' . $result['trivia_id'] . '&join_code=' . $result['join_code']);
            exit;
        } else {
            throw new Exception($result['error'] ?? "Error desconocido al crear trivia");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error en setup.php: " . $e->getMessage());
        
        // Mantener valores del POST
        $currentTheme = $_POST['theme'] ?? 'default';
        if ($currentTheme === 'custom' && $customBackgroundData) {
            $currentBackground = $customBackgroundData;
        } else {
            $currentBackground = $themes[$currentTheme]['image'] ?? $themes['default']['image'];
        }
    }
}
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
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .setup-container {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px 0;
            transition: background-image 0.5s ease;
        }
        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .theme-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 0;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
        }
        .theme-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .theme-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
        }
        .theme-preview {
            height: 120px;
            background-size: cover;
            background-position: center;
            border-bottom: 1px solid #dee2e6;
        }
        .theme-info {
            padding: 15px;
        }
        .custom-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .custom-upload:hover {
            border-color: #007bff;
            background: #e9ecef;
        }
        .custom-preview {
            height: 120px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-preview.has-image {
            border: none;
        }
        .preview-placeholder {
            color: #6c757d;
            text-align: center;
        }
        .preview-placeholder i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="setup-container" id="backgroundContainer" style="background-image: url('<?php echo $currentBackground; ?>')">
        <div class="container py-4">
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

                            <!-- Mensajes de error -->
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario -->
                            <form method="POST" id="triviaForm" enctype="multipart/form-data">
                                <!-- Título -->
                                <div class="mb-4">
                                    <label for="title" class="form-label fw-bold">
                                        <i class="fas fa-heading me-2"></i>Título de la Trivia *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="title" name="title" 
                                           placeholder="Ej: Trivia de Cultura General"
                                           required 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : 'Mi Trivia ' . date('d/m'); ?>">
                                    <div class="form-text">Usa un título descriptivo para tu trivia (mínimo 3 caracteres)</div>
                                </div>

                                <!-- Tema -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-palette me-2"></i>Selecciona un Tema *
                                    </label>
                                    <div class="row">
                                        <?php foreach ($themes as $themeKey => $theme): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="theme-option <?php echo $currentTheme === $themeKey ? 'selected' : ''; ?>" 
                                                 data-theme="<?php echo $themeKey; ?>"
                                                 data-background="<?php echo $theme['image']; ?>">
                                                <div class="theme-preview" style="background-image: url('<?php echo $theme['image']; ?>')"></div>
                                                <div class="theme-info">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" 
                                                               id="theme_<?php echo $themeKey; ?>" 
                                                               value="<?php echo $themeKey; ?>" 
                                                               <?php echo $currentTheme === $themeKey ? 'checked' : ''; ?>
                                                               required>
                                                        <label class="form-check-label fw-bold" for="theme_<?php echo $themeKey; ?>">
                                                            <?php echo $theme['name']; ?>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted"><?php echo $theme['description']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Opción personalizada -->
                                        <div class="col-md-6 mb-3">
                                            <div class="theme-option <?php echo $currentTheme === 'custom' ? 'selected' : ''; ?>" data-theme="custom">
                                                <div class="custom-preview <?php echo ($currentTheme === 'custom' && strpos($currentBackground, 'data:image') === 0) ? 'has-image' : ''; ?>" 
                                                     id="customPreview"
                                                     style="<?php echo ($currentTheme === 'custom' && strpos($currentBackground, 'data:image') === 0) ? "background-image: url('$currentBackground')" : ''; ?>">
                                                    <?php if (!($currentTheme === 'custom' && strpos($currentBackground, 'data:image') === 0)): ?>
                                                    <div class="preview-placeholder">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <div>Vista previa</div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="theme-info">
                                                    <div class="custom-upload" onclick="document.getElementById('custom_background').click()">
                                                        <i class="fas fa-cloud-upload-alt me-2"></i>
                                                        <span>Seleccionar Imagen</span>
                                                    </div>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="radio" name="theme" 
                                                               id="theme_custom" value="custom"
                                                               <?php echo $currentTheme === 'custom' ? 'checked' : ''; ?>
                                                               required>
                                                        <label class="form-check-label fw-bold" for="theme_custom">
                                                            Personalizado
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Sube tu propia imagen de fondo (JPG, PNG, GIF, WEBP - Máx. 5MB)</small>
                                                    <input type="file" id="custom_background" name="custom_background" 
                                                           accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" 
                                                           onchange="handleCustomBackground(this)">
                                                    <div id="customFileInfo" class="mt-1 small text-muted" style="display: none;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modalidad de Juego -->
                                <div class="mb-4">
                                    <label for="game_mode" class="form-label fw-bold">
                                        <i class="fas fa-users me-2"></i>Modalidad de Juego *
                                    </label>
                                    <select class="form-select form-select-lg" id="game_mode" name="game_mode" required>
                                        <option value="individual" <?php echo (isset($_POST['game_mode']) && $_POST['game_mode'] === 'individual') ? 'selected' : ''; ?>>Competencia Individual</option>
                                        <option value="teams" <?php echo (isset($_POST['game_mode']) && $_POST['game_mode'] === 'teams') ? 'selected' : ''; ?>>Competencia por Equipos</option>
                                    </select>
                                </div>

                                <!-- Máximo de Ganadores -->
                                <div class="mb-4">
                                    <label for="max_winners" class="form-label fw-bold">
                                        <i class="fas fa-trophy me-2"></i>Máximo de Ganadores *
                                    </label>
                                    <input type="number" class="form-control form-control-lg" 
                                           id="max_winners" name="max_winners" 
                                           value="<?php echo isset($_POST['max_winners']) ? htmlspecialchars($_POST['max_winners']) : '3'; ?>" 
                                           min="1" max="10" required>
                                    <div class="form-text">
                                        Número máximo de jugadores/equipos que pueden ganar
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="/microservices/tata-trivia/" class="btn btn-secondary btn-lg me-md-2">
                                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-plus me-2"></i>Crear Trivia
                                    </button>
                                </div>
                            </form>
                        </div>
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
                    
                    // Actualizar fondo de página
                    const theme = this.dataset.theme;
                    if (theme !== 'custom') {
                        const backgroundUrl = this.dataset.background;
                        document.getElementById('backgroundContainer').style.backgroundImage = `url('${backgroundUrl}')`;
                    }
                });
            });

            // Inicializar con el tema actual
            const currentTheme = '<?php echo $currentTheme; ?>';
            if (currentTheme !== 'custom') {
                const currentOption = document.querySelector(`[data-theme="${currentTheme}"]`);
                if (currentOption) {
                    const backgroundUrl = currentOption.dataset.background;
                    document.getElementById('backgroundContainer').style.backgroundImage = `url('${backgroundUrl}')`;
                }
            }
        });

        function handleCustomBackground(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileInfo = document.getElementById('customFileInfo');
                
                // Mostrar información del archivo
                fileInfo.style.display = 'block';
                fileInfo.innerHTML = `
                    <i class="fas fa-file-image me-1"></i>
                    ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                `;
                
                // Validar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    fileInfo.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Archivo demasiado grande (máx. 5MB)';
                    fileInfo.style.color = '#dc3545';
                    input.value = '';
                    return;
                } else {
                    fileInfo.style.color = '#28a745';
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Mostrar preview
                    const preview = document.getElementById('customPreview');
                    preview.innerHTML = '';
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    preview.classList.add('has-image');
                    
                    // Seleccionar opción custom
                    document.querySelector('[data-theme="custom"]').click();
                    
                    // Actualizar fondo de página
                    document.getElementById('backgroundContainer').style.backgroundImage = `url('${e.target.result}')`;
                }
                reader.onerror = function() {
                    fileInfo.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error al leer el archivo';
                    fileInfo.style.color = '#dc3545';
                };
                reader.readAsDataURL(file);
            }
        }

        // Validación del formulario
        document.getElementById('triviaForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            
            if (!title) {
                e.preventDefault();
                alert('Por favor ingresa un título para la trivia');
                document.getElementById('title').focus();
                return;
            }

            if (title.length < 3) {
                e.preventDefault();
                alert('El título debe tener al menos 3 caracteres');
                document.getElementById('title').focus();
                return;
            }

            // Validar que si se seleccionó custom, se haya subido una imagen
            const customThemeSelected = document.getElementById('theme_custom').checked;
            const customFile = document.getElementById('custom_background').files[0];
            
            if (customThemeSelected && !customFile) {
                e.preventDefault();
                alert('Por favor selecciona una imagen para el tema personalizado');
                return;
            }

            // Mostrar loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>