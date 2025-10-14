
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">
      <img src="{% static 'img/microapps-simbol.png' %}" alt="Micro apps" width="80" class="me-2">
      Micro apps
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
      
        <li class="nav-item"><a class="nav-link" href="{% url 'index' %}">Inicio</a></li> 
        <li class="nav-item"><a class="nav-link" href="{% url 'news' %}">Noticias</a></li>
        <!-- Usuario logueado -->
        <li class="nav-item"><a class="nav-link" href="{% url 'myaccount' %}">Mi perfil</a></li>
         <!-- Usuario no logueado -->  
         <li class="nav-item">
          <!-- Usuario logueado -->
           <form action="" method="post" style="display:inline;">
    
              <button type="submit" class="btn btn-danger ms-2">
                Cerrar Sesión
              </button>
           </form>
            <!-- Usuario no logueado -->
    
            <a href="{% url 'login' %}" class="btn btn-primary ms-2">Iniciar sesión</a>
        </li>
	    
      </ul>
    </div>
  </div>
</nav>