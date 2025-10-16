<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="./?vista=home">
      <img src="./img/microapps-simbol.png" alt="Micro apps" width="80" class="me-2">
      Micro apps
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">

        <?php if (isset($_SESSION['id'])): ?>
          <!-- Usuario logueado -->
          <li class="nav-item"><a class="nav-link" href="./?vista=home">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="./?vista=news">Noticias</a></li>
          <li class="nav-item"><a class="nav-link" href="./?vista=myaccount">Mi perfil</a></li>
          <li class="nav-item">
            <a href="./?vista=logout" class="btn btn-danger ms-2">Cerrar sesión</a>
          </li>
        <?php else: ?>
          <!-- Usuario no logueado -->
          <li class="nav-item">
            <a href="./?vista=login" class="btn btn-primary ms-2">Iniciar sesión</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
