<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="?vista=home">
      <?php if (defined('ASSETS_URL')): ?>
        <img src="<?php echo ASSETS_URL; ?>img/microapps-simbol.png" alt="Micro apps" width="80" class="me-2">
      <?php else: ?>
        <!-- Fallback si no está definida -->
        <img src="./assets/img/microapps-simbol.png" alt="Micro apps" width="80" class="me-2">
      <?php endif; ?>
      Micro apps
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['id'])): ?>
          <li class="nav-item"><a class="nav-link" href="?vista=home">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="?vista=news">Noticias</a></li>
          <li class="nav-item"><a class="nav-link" href="?vista=myaccount">Mi perfil</a></li>
          <li class="nav-item">
            <a href="?vista=logout" class="btn btn-danger ms-2" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">Cerrar sesión</a>
         </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="?vista=login" class="btn btn-primary ms-2">Iniciar sesión</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>