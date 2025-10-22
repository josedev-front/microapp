<?php
$usuario = obtenerUsuarioActual();

// Cargar controlador de comunicados
require_once __DIR__ . '/../controllers/ComunicadoController.php';
$comunicadoController = new ComunicadoController();

// Obtener comunicados globales activos para el carousel
$comunicados_globales = $comunicadoController->obtenerComunicadosGlobalesParaCarousel();
?>

<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
   <div class="d-flex flex-column flex-md-row">
        <div class="col-md-2" id="left-colum"></div>

        <!-- Carousel de Comunicados Globales -->
        <?php if (!empty($comunicados_globales)): ?>
        <div id="comunicadosCarousel" class="carousel slide col-md-8 mx-auto" data-bs-ride="carousel">
            <!-- Indicadores -->
            <div class="carousel-indicators">
                <?php foreach ($comunicados_globales as $index => $comunicado): ?>
                <button type="button" data-bs-target="#comunicadosCarousel" 
                        data-bs-slide-to="<?php echo $index; ?>" 
                        class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Items del Carousel -->
            <div class="carousel-inner">
                <?php foreach ($comunicados_globales as $index => $comunicado): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php if (!empty($comunicado['imagen'])): ?>
                        <!-- Comunicado con imagen -->
                        <img src="<?php echo htmlspecialchars($comunicado['imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($comunicado['titulo']); ?>" 
                             class="d-block w-100" 
                             style="min-height: 500px; max-height: 600px; object-fit: cover;">
                    <?php else: ?>
                        <!-- Comunicado sin imagen - fondo de color -->
                        <div class="d-block w-100 bg-primary text-white p-4" 
                             style="min-height: 500px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                            <div class="text-center">
                                <h4><?php echo htmlspecialchars($comunicado['titulo']); ?></h4>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($comunicado['contenido'])); ?></p>
                                <small>Publicado el: <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Captión del carousel -->
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-3">
                        <h5><?php echo htmlspecialchars($comunicado['titulo']); ?></h5>
                        <p class="mb-1"><?php 
                            $contenido = strip_tags($comunicado['contenido']);
                            echo strlen($contenido) > 100 ? substr($contenido, 0, 100) . '...' : $contenido;
                        ?></p>
                        <small>
                            Por: <?php echo htmlspecialchars($comunicado['creador_nombre'] ?? 'Administración'); ?> | 
                            <?php echo date('d/m/Y H:i', strtotime($comunicado['created_at'])); ?>
                            <?php if ($comunicado['requiere_acuse']): ?>
                                <span class="badge bg-warning ms-2">Requiere acuse</span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Controles del carousel -->
            <?php if (count($comunicados_globales) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Espacio reservado cuando no hay comunicados -->
        <div class="col-md-8 mx-auto text-center">
            <div class="bg-light rounded p-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay comunicados globales activos</h5>
                <p class="text-muted">Los comunicados importantes aparecerán aquí</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-2" id="right-colum"></div>
    </div>
    
    <h2 class="fw-bold mb-4 text-center text-white">
        Bienvenido, <?php echo htmlspecialchars($usuario['first_name'] ?? 'Usuario'); ?>
    </h2>
    <div class="text-center mb-4 text-white">
        <p class="lead">Área: <strong><?php echo htmlspecialchars($usuario['work_area'] ?? 'General'); ?></strong></p>
        <p class="lead">Rol: <strong><?php echo htmlspecialchars($usuario['role'] ?? 'Usuario'); ?></strong></p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-12 col-md-6 col-lg-5">
            <a href="./?vista=news" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-newspaper"></i> Comunicados</h5>
                        <p class="card-text">Infórmate y gestiona la última información importante de tu equipo.</p>
                        <?php if (!empty($comunicados_globales)): ?>
                        <small class="text-muted"><?php echo count($comunicados_globales); ?> comunicado(s) global(es) activo(s)</small>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-5">
             <a href="./?vista=middy" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-comment"></i> Middy</h5>
                        <p class="card-text">Chatea con Middy, ella te ayudará con información y procesos de gestión.</p>
                    </div>
                </div>    
            </a>    
        </div>
        
        <!-- NUEVA TARJETA PARA TATA TRIVIA -->
        <div class="col-12 col-md-6 col-lg-5">
    <a href="/microservices/tata-trivia/" class="text-decoration-none" target="_blank">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="fas fa-trophy"></i> Tata Trivia</h5>
                <p class="card-text">Crea y participa en trivias interactivas. ¡Diversión asegurada!</p>
                <small class="text-muted">Crea equipos o compite individualmente</small>
            </div>
        </div>
    </a>
</div>
        
        <div class="col-12 col-md-6 col-lg-5">
            <a href="./?vista=equiposuser" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">👨‍🔧👩‍🔧 Equipos</h5>
                        <p class="card-text">Consulta y gestiona información de tu equipo.</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-12 col-md-6 col-lg-5">
            <a href="./?vista=myconfig" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">⚙️ Configuración</h5>
                        <p class="card-text">Actualiza tus datos personales y contraseña.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>