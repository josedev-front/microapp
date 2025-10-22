<?php
// microservices/trivia-play/views/host/questions.php

$base_path = dirname(__DIR__, 3);
require_once $base_path . '/app_core/config/helpers.php';
require_once $base_path . '/app_core/php/main.php';

// Verificar que existe el parámetro ID
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
    $trivia = $hostController->getTrivia($trivia_id, $usuario_actual['id'] ?? null);
    $questions = $hostController->getQuestions($trivia_id);
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '?vista=trivia_host');
    exit;
}

// Fondos predefinidos para preguntas
$question_backgrounds = [
    'blue_abstract', 'science_theme', 'history_theme', 'sports_theme',
    'music_theme', 'movies_theme', 'geography_theme', 'art_theme',
    'technology_theme', 'nature_theme', 'space_theme', 'math_theme',
    'literature_theme', 'food_theme', 'travel_theme'
];

// Procesar AJAX para agregar/eliminar preguntas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_question':
                $questionData = [
                    'question_text' => $_POST['question_text'],
                    'question_type' => $_POST['question_type'],
                    'background_image' => $_POST['background_image'],
                    'time_limit' => intval($_POST['time_limit']),
                    'options' => json_decode($_POST['options'], true)
                ];
                
                $questionId = $hostController->addQuestion($trivia_id, $questionData);
                echo json_encode(['success' => true, 'question_id' => $questionId]);
                break;
                
            case 'delete_question':
                $hostController->deleteQuestion($_POST['question_id'], $trivia_id);
                echo json_encode(['success' => true]);
                break;
                
            case 'start_trivia':
                if ($usuario_actual) {
                    $hostController->startTrivia($trivia_id, $usuario_actual['id']);
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Debes iniciar sesión para empezar la trivia');
                }
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Preguntas - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .question-card {
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .bg-preview {
            height: 80px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .bg-preview.selected {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .option-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        .correct-option {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        .time-badge {
            font-size: 0.8rem;
        }
        .question-type-badge {
            font-size: 0.7rem;
        }
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        .drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .drop-zone.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
    </style>
</head>
<body>
    <div class="min-vh-100 bg-light">
        <!-- Header -->
        <nav class="navbar navbar-dark bg-primary sticky-top">
            <div class="container">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>?vista=trivia_host">
                    <i class="fas fa-arrow-left me-2"></i>
                    <i class="fas fa-trophy me-2"></i>Tata Trivia
                </a>
                <div class="navbar-text">
                    <span class="badge bg-warning me-2"><?php echo count($questions); ?> preguntas</span>
                    <span class="badge bg-light text-dark"><?php echo $trivia['title']; ?></span>
                </div>
            </div>
        </nav>

        <div class="container py-4">
            <div class="row">
                <!-- Panel de preguntas existentes -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Preguntas</h6>
                            <span class="badge bg-primary"><?php echo count($questions); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div id="questionsList" style="max-height: 600px; overflow-y: auto;">
                                <?php if (empty($questions)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-question-circle fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No hay preguntas aún</p>
                                        <small class="text-muted">Agrega tu primera pregunta</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($questions as $index => $question): ?>
                                    <div class="question-card p-3 border-bottom" data-question-id="<?php echo $question['id']; ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">Pregunta <?php echo $index + 1; ?></h6>
                                            <div>
                                                <span class="badge bg-secondary time-badge me-1">
                                                    <i class="fas fa-clock me-1"></i><?php echo $question['time_limit']; ?>s
                                                </span>
                                                <span class="badge bg-info question-type-badge">
                                                    <?php echo $question['question_type'] === 'true_false' ? 'V/F' : 'Quiz'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="small text-muted mb-2"><?php echo mb_substr($question['question_text'], 0, 80); ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo count(array_filter($question['options'], function($opt) { return $opt['is_correct']; })); ?> correcta(s)
                                            </small>
                                            <button class="btn btn-sm btn-outline-danger delete-question" 
                                                    data-question-id="<?php echo $question['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <button class="btn btn-success w-100" id="startTriviaBtn" 
                                    <?php echo empty($questions) ? 'disabled' : ''; ?>>
                                <i class="fas fa-play me-2"></i>Iniciar Trivia
                            </button>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-primary"><?php echo count($questions); ?></div>
                                        <small class="text-muted">Preguntas</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-success">
                                            <?php 
                                            $totalTime = array_sum(array_column($questions, 'time_limit'));
                                            echo ceil($totalTime / 60); 
                                            ?>
                                        </div>
                                        <small class="text-muted">Minutos</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-warning">
                                            <?php echo $trivia['game_mode'] === 'teams' ? 'Equipos' : 'Individual'; ?>
                                        </div>
                                        <small class="text-muted">Modalidad</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editor de preguntas -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Pregunta</h5>
                        </div>
                        <div class="card-body">
                            <form id="questionForm">
                                <!-- Tipo de pregunta -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tipo de Pregunta</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="question_type" 
                                                   id="type_true_false" value="true_false" autocomplete="off" checked>
                                            <label class="btn btn-outline-primary" for="type_true_false">
                                                <i class="fas fa-balance-scale me-2"></i>Verdadero/Falso
                                            </label>

                                            <input type="radio" class="btn-check" name="question_type" 
                                                   id="type_quiz" value="quiz" autocomplete="off">
                                            <label class="btn btn-outline-success" for="type_quiz">
                                                <i class="fas fa-list-ol me-2"></i>Quiz (4 opciones)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="time_limit" class="form-label fw-bold">Tiempo Límite (segundos)</label>
                                        <select class="form-select" id="time_limit" name="time_limit">
                                            <option value="15">15 segundos</option>
                                            <option value="20">20 segundos</option>
                                            <option value="30" selected>30 segundos</option>
                                            <option value="45">45 segundos</option>
                                            <option value="60">60 segundos</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Texto de la pregunta -->
                                <div class="mb-4">
                                    <label for="question_text" class="form-label fw-bold">Enunciado de la Pregunta</label>
                                    <textarea class="form-control" id="question_text" name="question_text" 
                                              rows="3" placeholder="Escribe tu pregunta aquí..." 
                                              maxlength="500" required></textarea>
                                    <div class="form-text">
                                        <span id="charCount">0</span>/500 caracteres
                                    </div>
                                </div>

                                <!-- Imagen de fondo -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Imagen de Fondo</label>
                                    <div class="row g-2" id="backgroundSelection">
                                        <?php foreach ($question_backgrounds as $bg): ?>
                                        <div class="col-3 col-sm-2">
                                            <div class="bg-preview" 
                                                 data-bg="<?php echo $bg; ?>"
                                                 style="background-image: url('<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/backgrounds/<?php echo $bg; ?>.jpg')">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="col-3 col-sm-2">
                                            <div class="bg-preview custom-bg-upload drop-zone" 
                                                 style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                <div class="text-center">
                                                    <i class="fas fa-upload text-muted mb-1"></i>
                                                    <small class="d-block text-muted">Personalizar</small>
                                                </div>
                                                <input type="file" id="customBgFile" accept="image/*" class="d-none">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="background_image" id="selectedBackground" value="<?php echo $question_backgrounds[0]; ?>" required>
                                </div>

                                <!-- Opciones de respuesta -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Opciones de Respuesta</label>
                                    <div id="optionsContainer">
                                        <!-- Las opciones se generan dinámicamente según el tipo -->
                                    </div>
                                </div>

                                <!-- Botones de acción -->
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-outline-secondary" id="previewQuestion">
                                        <i class="fas fa-eye me-2"></i>Vista Previa
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Pregunta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Vista previa -->
                    <div class="card mt-4 d-none" id="previewCard">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Vista Previa</h6>
                        </div>
                        <div class="card-body">
                            <div id="questionPreview" style="min-height: 200px;">
                                <!-- La vista previa se genera dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para iniciar trivia -->
    <div class="modal fade" id="startTriviaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-play-circle me-2 text-success"></i>Iniciar Trivia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás listo para iniciar la trivia "<strong><?php echo $trivia['title']; ?></strong>"?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Los jugadores podrán unirse usando el código: 
                        <strong class="h5"><?php echo $trivia['join_code']; ?></strong>
                    </div>
                    <p class="small text-muted mb-0">
                        Una vez iniciada, los jugadores podrán unirse y comenzará el juego cuando tú lo decidas.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmStartTrivia">
                        <i class="fas fa-play me-2"></i>Iniciar Trivia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        // JavaScript para la gestión de preguntas
        document.addEventListener('DOMContentLoaded', function() {
            const questionForm = document.getElementById('questionForm');
            const optionsContainer = document.getElementById('optionsContainer');
            const backgroundSelection = document.getElementById('backgroundSelection');
            const previewCard = document.getElementById('previewCard');
            const questionsList = document.getElementById('questionsList');
            const startTriviaBtn = document.getElementById('startTriviaBtn');
            const startTriviaModal = new bootstrap.Modal(document.getElementById('startTriviaModal'));

            // Inicializar opciones según el tipo de pregunta
            initializeQuestionType('true_false');

            // Cambiar tipo de pregunta
            document.querySelectorAll('input[name="question_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    initializeQuestionType(this.value);
                });
            });

            // Contador de caracteres
            document.getElementById('question_text').addEventListener('input', function() {
                document.getElementById('charCount').textContent = this.value.length;
            });

            // Selección de fondo
            backgroundSelection.querySelectorAll('.bg-preview').forEach(preview => {
                preview.addEventListener('click', function() {
                    backgroundSelection.querySelectorAll('.bg-preview').forEach(p => p.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    if (this.classList.contains('custom-bg-upload')) {
                        document.getElementById('customBgFile').click();
                    } else {
                        document.getElementById('selectedBackground').value = this.dataset.bg;
                        updatePreview();
                    }
                });
            });

            // Subir imagen personalizada
            document.getElementById('customBgFile').addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (!file.type.startsWith('image/')) {
                        alert('Por favor selecciona una imagen válida');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const customPreview = document.querySelector('.custom-bg-upload');
                        customPreview.style.backgroundImage = `url(${e.target.result})`;
                        customPreview.innerHTML = '';
                        document.getElementById('selectedBackground').value = 'custom_' + Date.now();
                        updatePreview();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Vista previa
            document.getElementById('previewQuestion').addEventListener('click', function() {
                if (validateQuestionForm()) {
                    updatePreview();
                    previewCard.classList.remove('d-none');
                }
            });

            // Enviar pregunta
            questionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (validateQuestionForm()) {
                    addQuestion();
                }
            });

            // Iniciar trivia
            startTriviaBtn.addEventListener('click', function() {
                startTriviaModal.show();
            });

            document.getElementById('confirmStartTrivia').addEventListener('click', function() {
                startTrivia();
            });

            // Eliminar pregunta
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-question')) {
                    const questionId = e.target.closest('.delete-question').dataset.questionId;
                    deleteQuestion(questionId);
                }
            });

            // Funciones
            function initializeQuestionType(type) {
                optionsContainer.innerHTML = '';
                
                if (type === 'true_false') {
                    optionsContainer.innerHTML = `
                        <div class="option-item" data-option-index="0">
                            <div class="form-check">
                                <input class="form-check-input correct-radio" type="radio" name="correct_option" value="0" required>
                                <label class="form-check-label w-100">
                                    <input type="text" class="form-control" value="Verdadero" readonly>
                                </label>
                            </div>
                        </div>
                        <div class="option-item" data-option-index="1">
                            <div class="form-check">
                                <input class="form-check-input correct-radio" type="radio" name="correct_option" value="1" required>
                                <label class="form-check-label w-100">
                                    <input type="text" class="form-control" value="Falso" readonly>
                                </label>
                            </div>
                        </div>
                    `;
                } else {
                    for (let i = 0; i < 4; i++) {
                        optionsContainer.innerHTML += `
                            <div class="option-item" data-option-index="${i}">
                                <div class="form-check">
                                    <input class="form-check-input correct-checkbox" type="checkbox" value="${i}">
                                    <label class="form-check-label w-100">
                                        <input type="text" class="form-control option-text" 
                                               placeholder="Opción ${i + 1}" required>
                                    </label>
                                </div>
                            </div>
                        `;
                    }
                }
            }

            function validateQuestionForm() {
                const questionText = document.getElementById('question_text').value.trim();
                if (!questionText) {
                    alert('Por favor escribe el enunciado de la pregunta');
                    return false;
                }

                const questionType = document.querySelector('input[name="question_type"]:checked').value;
                
                if (questionType === 'true_false') {
                    const correctSelected = document.querySelector('input[name="correct_option"]:checked');
                    if (!correctSelected) {
                        alert('Por favor selecciona la respuesta correcta');
                        return false;
                    }
                } else {
                    const options = document.querySelectorAll('.option-text');
                    let hasCorrect = false;
                    let allFilled = true;

                    options.forEach((input, index) => {
                        if (!input.value.trim()) {
                            allFilled = false;
                        }
                        if (document.querySelector(`.correct-checkbox[value="${index}"]`).checked) {
                            hasCorrect = true;
                        }
                    });

                    if (!allFilled) {
                        alert('Por favor completa todas las opciones');
                        return false;
                    }

                    if (!hasCorrect) {
                        alert('Por favor marca al menos una opción como correcta');
                        return false;
                    }
                }

                return true;
            }

            function getOptionsData() {
                const questionType = document.querySelector('input[name="question_type"]:checked').value;
                const options = [];

                if (questionType === 'true_false') {
                    const correctIndex = parseInt(document.querySelector('input[name="correct_option"]:checked').value);
                    options.push(
                        { text: 'Verdadero', is_correct: correctIndex === 0 },
                        { text: 'Falso', is_correct: correctIndex === 1 }
                    );
                } else {
                    document.querySelectorAll('.option-item').forEach((item, index) => {
                        const text = item.querySelector('.option-text').value.trim();
                        const isCorrect = item.querySelector('.correct-checkbox').checked;
                        options.push({ text, is_correct: isCorrect });
                    });
                }

                return options;
            }

            function addQuestion() {
                const formData = new FormData();
                formData.append('action', 'add_question');
                formData.append('question_text', document.getElementById('question_text').value.trim());
                formData.append('question_type', document.querySelector('input[name="question_type"]:checked').value);
                formData.append('background_image', document.getElementById('selectedBackground').value);
                formData.append('time_limit', document.getElementById('time_limit').value);
                formData.append('options', JSON.stringify(getOptionsData()));

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pregunta agregada correctamente');
                        questionForm.reset();
                        document.getElementById('charCount').textContent = '0';
                        previewCard.classList.add('d-none');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error al agregar la pregunta');
                    console.error('Error:', error);
                });
            }

            function deleteQuestion(questionId) {
                if (!confirm('¿Estás seguro de que quieres eliminar esta pregunta?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete_question');
                formData.append('question_id', questionId);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-question-id="${questionId}"]`).remove();
                        if (document.querySelectorAll('.question-card').length === 0) {
                            questionsList.innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-question-circle fa-3x mb-3 text-muted"></i>
                                    <p class="mb-0">No hay preguntas aún</p>
                                    <small class="text-muted">Agrega tu primera pregunta</small>
                                </div>
                            `;
                            startTriviaBtn.disabled = true;
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error al eliminar la pregunta');
                    console.error('Error:', error);
                });
            }

            function startTrivia() {
                const formData = new FormData();
                formData.append('action', 'start_trivia');

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '<?php echo BASE_URL; ?>?vista=trivia_lobby&id=<?php echo $trivia_id; ?>';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error al iniciar la trivia');
                    console.error('Error:', error);
                });
            }

            function updatePreview() {
                // Implementar vista previa de la pregunta
                const preview = document.getElementById('questionPreview');
                const questionText = document.getElementById('question_text').value;
                const background = document.getElementById('selectedBackground').value;
                
                preview.innerHTML = `
                    <div class="text-center p-4 rounded" 
                         style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?php echo BASE_URL; ?>microservices/trivia-play/assets/images/backgrounds/${background}.jpg'); background-size: cover; color: white;">
                        <h4>${questionText || 'Tu pregunta aparecerá aquí'}</h4>
                        <div class="mt-3">
                            ${generateOptionsPreview()}
                        </div>
                    </div>
                `;
            }

            function generateOptionsPreview() {
                const questionType = document.querySelector('input[name="question_type"]:checked').value;
                
                if (questionType === 'true_false') {
                    return `
                        <div class="row justify-content-center">
                            <div class="col-5">
                                <button class="btn btn-outline-light w-100 mb-2">Verdadero</button>
                            </div>
                            <div class="col-5">
                                <button class="btn btn-outline-light w-100 mb-2">Falso</button>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="row">
                            <div class="col-6">
                                <button class="btn btn-outline-light w-100 mb-2">Opción 1</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light w-100 mb-2">Opción 2</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light w-100 mb-2">Opción 3</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light w-100 mb-2">Opción 4</button>
                            </div>
                        </div>
                    `;
                }
            }
        });
    </script>
</body>
</html>