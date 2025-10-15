{% extends "base.html" %}

{% block title %}Iniciar sesión{% endblock %}

{% block content %}

<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0 rounded-4" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Bienvenido</h4>
                <p class="text-muted">Ingresa a tu cuenta</p>
            </div>

            <form method="POST">
                {% csrf_token %}
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" class="form-control rounded-pill" id="username" name="username" placeholder="Tu usuario" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control rounded-pill" id="password" name="password" placeholder="Tu contraseña" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="remember">
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <a href="{% url 'password_reset' %}" class="small text-decoration-none">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill">Ingresar</button>
            </form>
        </div>
    </div>
</div>
{% endblock %}
