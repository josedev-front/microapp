<?php
$usuario = obtenerUsuarioActual();
?>
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
   <div class="d-flex flex-column flex-md-row">
        <div class="col-md-2" id="left-colum"></div>

        <div id="comunicadosCarousel" class="carousel slide col-md-8 mx-auto" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#comunicadosCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#comunicadosCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#comunicadosCarousel" data-bs-slide-to="2"></button>
            </div>
            
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="./img/comunicado1.jpg" alt="Comunicado importante" class="d-block w-100" style="min-height: 500px; max-height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                        <h5>Comunicado Importante</h5>
                        <p>Información relevante para todo el equipo...</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="d-block w-100 bg-primary text-white p-4" style="min-height: 500px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <h4>Nuevas Políticas</h4>
                            <p class="mb-0">Actualización de políticas internas de la empresa...</p>
                            <small>Publicado el: 15/01/2024</small>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="./img/comunicado2.jpg" alt="Evento corporativo" class="d-block w-100" style="min-height: 500px; max-height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                        <h5>Evento Corporativo</h5>
                        <p>Próximo evento de integración...</p>
                    </div>
                </div>
            </div>
            
            <button class="carousel-control-prev" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>

        <div class="col-md-2" id="right-colum"></div>
    </div>
    
    <h2 class="fw-bold mb-4 text-center text-white">
        Bienvenido, <?php echo $_SESSION['nombre'] ?? 'Usuario'; ?>
    </h2>
    <div class="text-center mb-4 text-white">
        <p class="lead">Área: <strong><?php echo $_SESSION['area'] ?? 'General'; ?></strong></p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-12 col-md-6 col-lg-5">
            <a href="./?vista=news" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-newspaper"></i> Comunicados</h5>
                        <p class="card-text">Infórmate y gestiona la última información importante de tu equipo.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-5">
            <a href="./?vista=middy-chat" target="_blank" class="text-decoration-none"> 
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-comment"></i> Middy</h5>
                        <p class="card-text">Chatea con Middy, ella te ayudará con información y procesos de gestión.</p>
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
