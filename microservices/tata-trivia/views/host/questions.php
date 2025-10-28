<?php
// microservices/tata-trivia/views/host/questions.php - VERSIÓN CORREGIDA

$user = $user ?? getTriviaMicroappsUser();
$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}

// Obtener información del tema de la trivia
try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    $theme = $trivia['theme'] ?? 'default';
    $background_image = $trivia['background_image'] ?? 'default';
    
    // Construir ruta correcta de la imagen de fondo usando el método del controlador
    $backgroundPath = $triviaController->getTriviaBackgroundPath($trivia_id);
    
} catch (Exception $e) {
    $theme = 'default';
    $backgroundPath = '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
}

// Imágenes de fondo predefinidas para preguntas
$questionsDir = '/microservices/tata-trivia/assets/images/themes/questions/';
$defaultBackgrounds = [
    'brain' => $questionsDir . 'brain.jpg',
    'books' => $questionsDir . 'books.jpg',
    'science' => $questionsDir . 'science.png',
    'history' => $questionsDir . 'history.jpg',
    'sports' => $questionsDir . 'sports.jpg',
    'music' => $questionsDir . 'music.png',
    'movies' => $questionsDir . 'movies.jpg',
    'geography' => $questionsDir . 'geography.jpg',
    'art' => $questionsDir . 'art.jpg',
    'technology' => $questionsDir . 'tech.jpg',
    'nature' => $questionsDir . 'nature.jpg',
    'space' => $questionsDir . 'space.jpg',
    'math' => $questionsDir . 'math.jpg',
    'language' => $questionsDir . 'language.jpg',
    'general' => $questionsDir . 'general.jpg'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Preguntas - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .page-container {
            background-image: url('<?php echo $backgroundPath; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            padding: 20px 0;
        }
        /* Fallback si la imagen no carga */
        .page-container.no-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        .question-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
            overflow: hidden;
        }
        .question-display {
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            font-weight: bold;
            font-size: 1.2rem;
            text-align: center;
            padding: 20px;
        }
        .option-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: move;
            transition: all 0.3s ease;
            background: white;
        }
        .option-item:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .option-item.correct {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        .background-option {
            width: 80px;
            height: 60px;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            object-fit: cover;
        }
        .background-option:hover {
            transform: scale(1.05);
            border-color: #007bff;
        }
        .background-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
        }
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .background-preview {
            height: 100px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 0.8rem;
        }
        .custom-upload-small {
            border: 1px dashed #dee2e6;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-size: 0.8rem;
        }
        .custom-upload-small:hover {
            border-color: #007bff;
            background: #e9ecef;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            font-size: 1.2rem;
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="page-container <?php echo (!file_exists($_SERVER['DOCUMENT_ROOT'] . $backgroundPath)) ? 'no-bg' : ''; ?>" 
         id="pageContainer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    
                   

                    <!-- Header -->
                    <div class="content-card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1 class="h3 mb-0 text-primary">
                                        <i class="fas fa-question-circle me-2"></i>
                                        Agregar Preguntas
                                    </h1>
                                    <p class="text-muted mb-0">Trivia ID: <?php echo htmlspecialchars($trivia_id); ?></p>
                                    <small class="text-muted">Tema: <?php echo ucfirst(str_replace('_', ' ', $theme)); ?></small>
                                </div>
                                <div>
                                    <button class="btn btn-success me-2" onclick="saveQuestions()">
                                        <i class="fas fa-save me-1"></i>Guardar y Continuar
                                    </button>
                                    <a href="/microservices/tata-trivia/host/setup" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Volver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Container -->
                    <div id="questionsContainer">
                        <div class="empty-state" id="emptyState">
                            <i class="fas fa-question-circle fa-3x mb-3 text-muted"></i>
                            <h4 class="text-muted">No hay preguntas aún</h4>
                            <p class="text-muted">Agrega la primera pregunta para comenzar</p>
                            <button class="btn btn-primary" onclick="addQuestion()">
                                <i class="fas fa-plus me-1"></i>Agregar Primera Pregunta
                            </button>
                        </div>
                    </div>

                    <!-- Add Question Button -->
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary btn-lg" onclick="addQuestion()">
                            <i class="fas fa-plus me-1"></i>Agregar Otra Pregunta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="text-center">
            <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;"></div>
            <div>Guardando preguntas...</div>
        </div>
    </div>

    <!-- Question Template -->
    <template id="questionTemplate">
        <div class="card question-card" data-question-index="{index}">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pregunta <span class="question-number">{number}</span></h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Enunciado de la pregunta *</label>
                            <textarea class="form-control question-text" rows="3" 
                                      placeholder="Escribe aquí la pregunta..." required></textarea>
                        </div>
                        
                        <!-- Vista previa de la pregunta con fondo -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Vista previa:</label>
                            <div class="question-display" id="questionPreview_{index}">
                                La pregunta aparecerá aquí con el fondo seleccionado
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de pregunta *</label>
                            <select class="form-select question-type" onchange="toggleQuestionOptions(this)">
                                <option value="true_false">Verdadero o Falso</option>
                                <option value="quiz">Quiz (4 opciones)</option>
                            </select>
                        </div>

                        <!-- Options Container -->
                        <div class="options-container">
                            <!-- Options will be generated based on question type -->
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiempo límite (segundos)</label>
                            <select class="form-select time-limit">
                                <option value="10">10 segundos</option>
                                <option value="15">15 segundos</option>
                                <option value="20">20 segundos</option>
                                <option value="30" selected>30 segundos</option>
                                <option value="45">45 segundos</option>
                                <option value="60">60 segundos</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fondo para esta pregunta</label>
                            
                            <!-- Selector de imágenes predefinidas -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Fondos predefinidos:</small>
                                <div class="d-flex flex-wrap mb-2" id="defaultBackgrounds_{index}">
                                    <!-- Las imágenes se cargarán dinámicamente -->
                                </div>
                            </div>
                            
                            <!-- Cargar imagen personalizada -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">O carga tu imagen:</small>
                                <div class="custom-upload-small" onclick="document.getElementById('custom_bg_{index}').click()">
                                    <i class="fas fa-upload me-1"></i>
                                    Seleccionar imagen
                                </div>
                                <input type="file" class="custom-background" 
                                       id="custom_bg_{index}"
                                       accept="image/*" 
                                       data-question-index="{index}"
                                       style="display: none;">
                            </div>
                            
                            <!-- Vista previa del fondo -->
                            <div class="mt-3">
                                <small class="text-muted d-block mb-1">Fondo seleccionado:</small>
                                <div class="background-preview" id="backgroundPreview_{index}">
                                    Sin fondo seleccionado
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        let questionCount = 0;
        const triviaId = '<?php echo $trivia_id; ?>';
        
        // Imágenes de fondo predefinidas
        const defaultBackgrounds = <?php echo json_encode($defaultBackgrounds); ?>;

        function addQuestion() {
            questionCount++;
            
            // Hide empty state
            document.getElementById('emptyState').style.display = 'none';
            
            // Get template
            const template = document.getElementById('questionTemplate').innerHTML;
            const questionHTML = template
                .replace(/{index}/g, questionCount)
                .replace(/{number}/g, questionCount);
            
            // Create element
            const div = document.createElement('div');
            div.innerHTML = questionHTML;
            
            // Add to container
            document.getElementById('questionsContainer').appendChild(div.firstElementChild);
            
            // Initialize options for this question
            initializeQuestionOptions(questionCount);
            
            // Cargar imágenes predefinidas
            loadDefaultBackgrounds(questionCount);
            
            // Configurar event listeners para la pregunta
            setupQuestionEvents(questionCount);
        }

        function setupQuestionEvents(questionIndex) {
            // Actualizar vista previa cuando cambia el texto
            const textarea = document.querySelector(`[data-question-index="${questionIndex}"] .question-text`);
            const preview = document.getElementById(`questionPreview_${questionIndex}`);
            
            textarea.addEventListener('input', function() {
                preview.textContent = this.value || 'La pregunta aparecerá aquí con el fondo seleccionado';
            });
            
            // Configurar upload de imagen personalizada
            const fileInput = document.getElementById(`custom_bg_${questionIndex}`);
            fileInput.addEventListener('change', function(e) {
                handleCustomBackground(this, questionIndex);
            });
        }

        function loadDefaultBackgrounds(questionIndex) {
            const container = document.getElementById(`defaultBackgrounds_${questionIndex}`);
            if (!container) return;
            
            container.innerHTML = '';
            
            Object.entries(defaultBackgrounds).forEach(([key, url]) => {
                const img = document.createElement('img');
                img.src = url;
                img.alt = key;
                img.className = 'background-option';
                img.title = key.charAt(0).toUpperCase() + key.slice(1);
                img.onclick = function() {
                    selectBackground(this, questionIndex);
                };
                container.appendChild(img);
            });
        }

        function selectBackground(element, questionIndex) {
            // Remover selección anterior
            const container = element.parentElement;
            container.querySelectorAll('.background-option').forEach(img => {
                img.classList.remove('selected');
            });
            
            // Seleccionar actual
            element.classList.add('selected');
            
            // Actualizar vista previa del fondo
            const bgPreview = document.getElementById(`backgroundPreview_${questionIndex}`);
            bgPreview.innerHTML = '';
            bgPreview.style.backgroundImage = `url('${element.src}')`;
            
            // Actualizar vista previa de la pregunta
            const questionPreview = document.getElementById(`questionPreview_${questionIndex}`);
            questionPreview.style.backgroundImage = `url('${element.src}')`;
        }

        function handleCustomBackground(input, questionIndex) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Actualizar vista previa del fondo
                    const bgPreview = document.getElementById(`backgroundPreview_${questionIndex}`);
                    bgPreview.innerHTML = '';
                    bgPreview.style.backgroundImage = `url('${e.target.result}')`;
                    
                    // Actualizar vista previa de la pregunta
                    const questionPreview = document.getElementById(`questionPreview_${questionIndex}`);
                    questionPreview.style.backgroundImage = `url('${e.target.result}')`;
                    
                    // Limpiar selección de fondos predefinidos
                    const container = document.getElementById(`defaultBackgrounds_${questionIndex}`);
                    container.querySelectorAll('.background-option').forEach(img => {
                        img.classList.remove('selected');
                    });
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function initializeQuestionOptions(questionIndex) {
            const container = document.querySelector(`[data-question-index="${questionIndex}"] .options-container`);
            generateTrueFalseOptions(container);
        }

        function toggleQuestionOptions(select) {
            const questionCard = select.closest('.question-card');
            const container = questionCard.querySelector('.options-container');
            const questionType = select.value;
            
            container.innerHTML = '';
            
            if (questionType === 'true_false') {
                generateTrueFalseOptions(container);
            } else if (questionType === 'quiz') {
                generateQuizOptions(container);
            }
        }

        function generateTrueFalseOptions(container) {
            const options = [
                { text: 'Verdadero', correct: true },
                { text: 'Falso', correct: false }
            ];
            
            options.forEach((option, index) => {
                const optionHTML = `
                    <div class="option-item ${option.correct ? 'correct' : ''}" data-correct="${option.correct}">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="correctOption" 
                                   ${option.correct ? 'checked' : ''} onchange="setCorrectOption(this)">
                            <label class="form-check-label w-100">
                                ${option.text}
                            </label>
                        </div>
                    </div>
                `;
                container.innerHTML += optionHTML;
            });
        }

        function generateQuizOptions(container) {
            const letters = ['A', 'B', 'C', 'D'];
            
            letters.forEach((letter, index) => {
                const optionHTML = `
                    <div class="option-item ${index === 0 ? 'correct' : ''}" data-correct="${index === 0}">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">${letter}</span>
                            <input type="text" class="form-control option-text" 
                                   placeholder="Opción ${letter}" value="${index === 0 ? 'Opción correcta' : 'Opción ' + letter}">
                            <div class="form-check ms-2">
                                <input class="form-check-input" type="radio" name="correctOption" 
                                       ${index === 0 ? 'checked' : ''} onchange="setCorrectOption(this)">
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += optionHTML;
            });
            
            // Make options sortable
            new Sortable(container, {
                animation: 150,
                ghostClass: 'sortable-ghost'
            });
        }

        function setCorrectOption(radio) {
            const optionItem = radio.closest('.option-item');
            const container = optionItem.parentElement;
            
            // Remove correct class from all options
            container.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('correct');
                item.dataset.correct = 'false';
            });
            
            // Add correct class to selected option
            optionItem.classList.add('correct');
            optionItem.dataset.correct = 'true';
        }

        function removeQuestion(button) {
            const questionCard = button.closest('.question-card');
            questionCard.remove();
            
            // Update question numbers
            updateQuestionNumbers();
            
            // Show empty state if no questions
            if (document.querySelectorAll('.question-card').length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                questionCount = 0;
            }
        }

        function updateQuestionNumbers() {
            document.querySelectorAll('.question-card').forEach((card, index) => {
                const numberElement = card.querySelector('.question-number');
                numberElement.textContent = index + 1;
                card.dataset.questionIndex = index + 1;
            });
            questionCount = document.querySelectorAll('.question-card').length;
        }

        async function saveQuestions() {
            const questions = [];
            const questionCards = document.querySelectorAll('.question-card');
            
            if (questionCards.length === 0) {
                alert('Debes agregar al menos una pregunta');
                return;
            }

            // Validar todas las preguntas antes de enviar
            for (const card of questionCards) {
                const questionText = card.querySelector('.question-text').value.trim();
                if (!questionText) {
                    alert('Todas las preguntas deben tener un enunciado');
                    card.querySelector('.question-text').focus();
                    return;
                }

                const question = {
                    question_text: questionText,
                    question_type: card.querySelector('.question-type').value,
                    time_limit: card.querySelector('.time-limit').value,
                    order_index: Array.from(questionCards).indexOf(card) + 1,
                    options: []
                };

                // Get options
                const optionItems = card.querySelectorAll('.option-item');
                let hasCorrectOption = false;
                
                optionItems.forEach(item => {
                    let optionText = '';
                    if (question.question_type === 'true_false') {
                        optionText = item.querySelector('.form-check-label').textContent.trim();
                    } else {
                        const input = item.querySelector('.option-text');
                        optionText = input.value.trim();
                        if (!optionText) {
                            alert('Todas las opciones del quiz deben tener texto');
                            input.focus();
                            throw new Error('Empty option');
                        }
                    }
                    
                    const isCorrect = item.dataset.correct === 'true';
                    if (isCorrect) hasCorrectOption = true;
                    
                    question.options.push({
                        text: optionText,
                        is_correct: isCorrect
                    });
                });

                if (!hasCorrectOption) {
                    alert('Cada pregunta debe tener al menos una opción correcta');
                    return;
                }

                questions.push(question);
            }

            // Mostrar loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            try {
                const response = await fetch('/microservices/tata-trivia/api/save_questions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        trivia_id: triviaId,
                        questions: questions
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = '/microservices/tata-trivia/host/lobby?trivia_id=' + triviaId;
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar las preguntas. Por favor intenta nuevamente.');
            } finally {
                // Ocultar loading
                document.getElementById('loadingOverlay').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Add first question on load for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-add first question if none exist
            if (document.querySelectorAll('.question-card').length === 0) {
                addQuestion();
            }
        });
    </script>
</body>
</html>