<?php
$user = $user ?? getTriviaMicroappsUser();
$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    header('Location: /microservices/tata-trivia/host/setup');
    exit;
}
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
        .question-card {
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
        }
        .option-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 5px;
            cursor: move;
        }
        .option-item.correct {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        .time-badge {
            font-size: 0.8rem;
        }
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-light min-vh-100 py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-question-circle text-primary me-2"></i>
                            Agregar Preguntas
                        </h1>
                        <p class="text-muted mb-0">Trivia ID: <?php echo htmlspecialchars($trivia_id); ?></p>
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

                <!-- Questions Container -->
                <div id="questionsContainer">
                    <div class="empty-state" id="emptyState">
                        <i class="fas fa-question-circle fa-3x mb-3"></i>
                        <h4 class="text-muted">No hay preguntas aún</h4>
                        <p class="text-muted">Agrega la primera pregunta para comenzar</p>
                        <button class="btn btn-primary" onclick="addQuestion()">
                            <i class="fas fa-plus me-1"></i>Agregar Primera Pregunta
                        </button>
                    </div>
                </div>

                <!-- Add Question Button -->
                <div class="text-center mt-4">
                    <button class="btn btn-outline-primary" onclick="addQuestion()">
                        <i class="fas fa-plus me-1"></i>Agregar Otra Pregunta
                    </button>
                </div>
            </div>
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
                            <label class="form-label">Enunciado de la pregunta *</label>
                            <textarea class="form-control question-text" rows="3" 
                                      placeholder="Escribe aquí la pregunta..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de pregunta *</label>
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
                            <label class="form-label">Tiempo límite (segundos)</label>
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
                            <label class="form-label">Imagen de fondo (opcional)</label>
                            <input type="file" class="form-control question-image" accept="image/*">
                            <small class="text-muted">Puedes agregar una imagen para esta pregunta</small>
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
            const btn = document.querySelector('.btn-success');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
            btn.disabled = true;

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
                    // Restaurar botón
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar las preguntas. Por favor intenta nuevamente.');
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
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