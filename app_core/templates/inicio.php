{% extends "base.html" %}
{% block title %}Inicio{% endblock %}

{% block content %}
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
   <div class="d-flex flex-column flex-md-row">
        <div class="col-md-2" id="left-colum"></div>

        <div id="comunicadosCarousel" class="carousel slide col-md-8 mx-auto" data-bs-ride="carousel">
            <div class="carousel-indicators">
                {% for comunicado in comunicados_globales %}
                <button type="button" data-bs-target="#comunicadosCarousel" data-bs-slide-to="{{ forloop.counter0 }}" 
                        {% if forloop.first %}class="active"{% endif %}></button>
                {% endfor %}
            </div>
            
            <div class="carousel-inner">
                {% for comunicado in comunicados_globales %}
                <div class="carousel-item {% if forloop.first %}active{% endif %}">
                    {% if comunicado.imagen %}
                    <img src="{{ comunicado.imagen.url }}" alt="{{ comunicado.titulo }}" class="d-block w-100" style="min-height: 500px; max-height: 600px; object-fit: cover;">
                    {% else %}
                    <div class="d-block w-100 bg-primary text-white p-4" style="min-height: 500px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <h4>{{ comunicado.titulo }}</h4>
                            <p class="mb-0">{{ comunicado.mensaje|truncatewords:20 }}</p>
                            <small>Publicado el: {{ comunicado.created_at|date:"d/m/Y" }}</small>
                        </div>
                    </div>
                    {% endif %}
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                        <h5>{{ comunicado.titulo }}</h5>
                        <p>{{ comunicado.mensaje|truncatewords:10 }}</p>
                    </div>
                </div>
                {% empty %}
                <div class="carousel-item active">
                    <div class="d-block w-100 bg-secondary text-white p-4" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <h4>No hay comunicados globales</h4>
                            <p>No hay comunicados importantes en este momento.</p>
                        </div>
                    </div>
                </div>
                {% endfor %}
            </div>
            
            {% if comunicados_globales|length > 1 %}
            <button class="carousel-control-prev" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#comunicadosCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
            {% endif %}
        </div>

        <div class="col-md-2" id="right-colum"></div>
    </div>
  <h2 class="fw-bold mb-4 text-center text-white">
    Bienvenido, {{ user.username }}
  </h2>
  <div class="text-center mb-4 text-white">
    <p class="lead">área: <strong>{{ user.area }}</strong></p>
  </div>

  <div class="row justify-content-center g-4">
    <!-- Equipos -->
    <div class="col-12 col-md-6 col-lg-5">
        <a href="{% url 'news' %}" class="index-card">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="fas fa-newspaper"></i> Comunicados</h5>
                    <p class="card-text">Informate y gestiona la ultima informacion importante de tu equipo.</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Ver registros -->
    <div class="col-12 col-md-6 col-lg-5">
        <a href="{% url 'middy-chat' %}" target="_blank" class="index-card"> 
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="fas fa-comment"></i> Middy</h5>
                    <p class="card-text">Chatea con Middy, ella te ayudará con información, procesos en tu gestión.</p>
                </div>
            </div>    
        </a>    
    </div>
    <div class="col-12 col-md-6 col-lg-5">
        <a href="{% url 'equipo_user' %}" class="index-card">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">👨‍🔧👩‍🔧 Equipos</h5>
                    <p class="card-text">Consulta y gestiona informacion de tu equipo.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-lg-5">
        <a href="{% url 'myconfig' %}" class="index-card">
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
{% endblock %}
