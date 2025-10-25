<?php
$user = $user ?? getTriviaMicroappsUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Trivia - Tata Trivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .theme-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        .theme-card:hover {
            transform: translateY(-5px);
        }
        .theme-card.selected {
            border-color: #007bff;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .theme-image {
            height: 120px;
            object-fit: cover;
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
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .btn-continue {
            padding: 12px 30px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-light min-vh-100 py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-crown text-warning me-2"></i>
                        Crear Nueva Trivia
                    </h1>
                    <a href="/microservices/tata-trivia/" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <!-- Progress Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-fill">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px;">1</div>
                                <div class="small">Configuración</div>
                            </div>
                            <div class="flex-fill">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="text-center flex-fill">
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px;">2</div>
                                <div class="small">Preguntas</div>
                            </div>
                            <div class="flex-fill">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="text-center flex-fill">
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px;">3</div>
                                <div class="small">Lobby</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setup Form -->
                <form id="triviaSetupForm">
                    <!-- Step 1: Basic Info -->
                    <div class="step active" id="step1">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Básica</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="triviaTitle" class="form-label">Título de la Trivia *</label>
                                        <input type="text" class="form-control" id="triviaTitle" 
                                               placeholder="Ej: Conocimientos Generales" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="maxWinners" class="form-label">Máximo de Ganadores</label>
                                        <select class="form-select" id="maxWinners">
                                            <option value="1">1 Ganador</option>
                                            <option value="3">Top 3</option>
                                            <option value="5">Top 5</option>
                                            <option value="7">Top 7</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Modalidad de Juego *</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check card p-3">
                                                <input class="form-check-input" type="radio" name="gameMode" 
                                                       id="modeIndividual" value="individual" checked>
                                                <label class="form-check-label" for="modeIndividual">
                                                    <strong>Competencia Individual</strong>
                                                    <small class="d-block text-muted">Cada jugador compite por sí mismo</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check card p-3">
                                                <input class="form-check-input" type="radio" name="gameMode" 
                                                       id="modeTeams" value="teams">
                                                <label class="form-check-label" for="modeTeams">
                                                    <strong>Competencia por Equipos</strong>
                                                    <small class="d-block text-muted">Máximo 8 equipos</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Theme Selection -->
                    <div class="step" id="step2">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Tema y Fondo</h5>
                            </div>
                            <div class="card-body">
                                <label class="form-label mb-3">Selecciona un tema predefinido o sube tu propia imagen:</label>
                                
                                <!-- Predefined Themes -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="mb-3">Temas Predefinidos</h6>
                                    </div>
                                    <?php
                                    $themes = [
                                        'fiestas_patrias' => 'Fiestas Patrias',
                                        'pascua' => 'Día de Pascua', 
                                        'dia_madre' => 'Día de la Madre',
                                        'navidad' => 'Navidad',
                                        'lgbt' => 'Día de la Diversidad LGBT',
                                        'dia_mujer' => 'Día de la Mujer',
                                        'halloween' => 'Halloween',
                                        'amor' => 'Día del Amor y la Amistad'
                                    ];
                                    
                                    foreach ($themes as $key => $theme):
                                    ?>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="card theme-card" data-theme="<?php echo $key; ?>">
                                            <div class="theme-image bg-<?php echo $key === 'navidad' ? 'success' : 'primary'; ?> d-flex align-items-center justify-content-center text-white">
                                                <i class="fas fa-image fa-2x"></i>
                                            </div>
                                            <div class="card-body text-center p-2">
                                                <small class="fw-bold"><?php echo $theme; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Custom Image Upload -->
                                <div class="mb-3">
                                    <h6 class="mb-3">O sube tu propia imagen</h6>
                                    <div class="custom-file-upload" id="customUploadArea">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <p class="mb-2">Haz clic para subir tu imagen</p>
                                        <small class="text-muted">Formatos: JPG, PNG, GIF (Máx. 2MB)</small>
                                        <input type="file" id="customBackground" accept="image/*" class="d-none">
                                    </div>
                                    <div id="customImagePreview" class="mt-3 text-center" style="display: none;">
                                        <img id="previewImage" class="img-fluid rounded" style="max-height: 200px;">
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeCustomImage()">
                                            <i class="fas fa-times me-1"></i>Quitar imagen
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" id="selectedTheme" name="selectedTheme" value="">
                                <input type="hidden" id="customImageData" name="customImageData" value="">
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="btnPrev" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i>Anterior
                        </button>
                        <button type="button" class="btn btn-primary btn-continue" id="btnNext">
                            Continuar <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentStep = 1;
    const totalSteps = 2;
    let selectedTheme = '';
    let customImage = '';

    // Step Navigation
    document.getElementById('btnNext').addEventListener('click', function() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            } else {
                createTrivia();
            }
        }
    });

    document.getElementById('btnPrev').addEventListener('click', function() {
        showStep(currentStep - 1);
    });

    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        
        // Show current step
        const currentStepElement = document.getElementById('step' + step);
        if (currentStepElement) {
            currentStepElement.classList.add('active');
        }
        
        // Update navigation buttons
        document.getElementById('btnPrev').style.display = step > 1 ? 'block' : 'none';
        document.getElementById('btnNext').textContent = step === totalSteps ? 
            'Crear Trivia <i class="fas fa-check ms-1"></i>' : 
            'Continuar <i class="fas fa-arrow-right ms-1"></i>';
        
        // Update progress bar
        updateProgressBar(step);
        
        currentStep = step;
    }

    function updateProgressBar(step) {
        const progress = ((step - 1) / (totalSteps - 1)) * 100;
        document.querySelectorAll('.progress-bar').forEach(pb => {
            pb.style.width = progress + '%';
        });
        
        // Update step indicators - Solo los que están en la barra de progreso
        const progressContainer = document.querySelector('.card-body .d-flex.justify-content-between');
        if (progressContainer) {
            const stepIndicators = progressContainer.querySelectorAll('.text-center');
            stepIndicators.forEach((el, index) => {
                const stepNumber = index + 1;
                const circle = el.querySelector('.rounded-circle');
                if (circle) {
                    if (stepNumber <= step) {
                        circle.classList.remove('bg-secondary');
                        circle.classList.add('bg-primary');
                    } else {
                        circle.classList.remove('bg-primary');
                        circle.classList.add('bg-secondary');
                    }
                }
            });
        }
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                const title = document.getElementById('triviaTitle').value.trim();
                if (!title) {
                    alert('Por favor ingresa un título para la trivia');
                    return false;
                }
                return true;
                
            case 2:
                if (!selectedTheme && !customImage) {
                    alert('Por favor selecciona un tema o sube una imagen personalizada');
                    return false;
                }
                return true;
                
            default:
                return true;
        }
    }

    // Theme Selection
    document.querySelectorAll('.theme-card').forEach(card => {
        card.addEventListener('click', function() {
            // Remove selection from all cards
            document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('selected'));
            
            // Select current card
            this.classList.add('selected');
            selectedTheme = this.dataset.theme;
            customImage = '';
            
            // Clear custom image
            document.getElementById('customImagePreview').style.display = 'none';
            document.getElementById('customBackground').value = '';
            
            document.getElementById('selectedTheme').value = selectedTheme;
        });
    });

    // Custom Image Upload
    document.getElementById('customUploadArea').addEventListener('click', function() {
        document.getElementById('customBackground').click();
    });

    document.getElementById('customBackground').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen debe ser menor a 2MB');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                customImage = e.target.result;
                selectedTheme = '';
                
                // Show preview
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('customImagePreview').style.display = 'block';
                
                // Clear theme selection
                document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('selected'));
                
                document.getElementById('customImageData').value = customImage;
            };
            reader.readAsDataURL(file);
        }
    });

    function removeCustomImage() {
        customImage = '';
        document.getElementById('customImagePreview').style.display = 'none';
        document.getElementById('customBackground').value = '';
        document.getElementById('customImageData').value = '';
    }

    // Create Trivia
    async function createTrivia() {
        const formData = {
            title: document.getElementById('triviaTitle').value.trim(),
            gameMode: document.querySelector('input[name="gameMode"]:checked').value,
            maxWinners: document.getElementById('maxWinners').value,
            theme: selectedTheme,
            customImage: customImage,
            hostData: {
                user_id: <?php echo $user['id'] ?? 'null'; ?>,
                first_name: '<?php echo $user['first_name'] ?? 'Anfitrión'; ?>'
            }
        };

        // Mostrar loading
        const btnNext = document.getElementById('btnNext');
        const originalText = btnNext.innerHTML;
        btnNext.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creando...';
        btnNext.disabled = true;

        try {
            const response = await fetch('/microservices/tata-trivia/api/create_trivia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/microservices/tata-trivia/host/questions?trivia_id=' + result.data.trivia_id;
            } else {
                alert('Error: ' + result.error);
                // Restaurar botón
                btnNext.innerHTML = originalText;
                btnNext.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al crear la trivia. Por favor intenta nuevamente.');
            // Restaurar botón
            btnNext.innerHTML = originalText;
            btnNext.disabled = false;
        }
    }

    // Inicializar selección de tema por defecto
    document.addEventListener('DOMContentLoaded', function() {
        // Seleccionar el primer tema por defecto
        const firstThemeCard = document.querySelector('.theme-card');
        if (firstThemeCard) {
            firstThemeCard.click();
        }
    });
</script>
</body>
</html>