{% extends "base.html" %}

{% block title %}Email Enviado{% endblock %}

{% block content %}
<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0 rounded-4" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4 text-center">
            <div class="mb-4">
                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                <h4 class="fw-bold text-primary">Email Enviado</h4>
                <p class="text-muted">
                    Te hemos enviado un email con instrucciones para restablecer tu contraseña.
                    Revisa tu bandeja de entrada y la carpeta de spam.
                </p>
            </div>
            
            <div class="mt-3">
                <a href="{% url 'login' %}" class="btn btn-primary rounded-pill">Volver al Login</a>
            </div>
        </div>
    </div>
</div>
{% endblock %}