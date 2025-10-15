{% extends "base.html" %}

{% block title %}Editar Usuario - {{ usuario.username }}{% endblock %}

{% block content %}
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-edit"></i> Editar Usuario: {{ usuario.get_full_name }}
                    </h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        {% csrf_token %}
                        
                        {% if form.non_field_errors %}
                        <div class="alert alert-danger">
                            {% for error in form.non_field_errors %}
                                {{ error }}
                            {% endfor %}
                        </div>
                        {% endif %}
                        
                        <div class="row">
                            {% for field in form %}
                            <div class="col-md-6 mb-3">
                                <label for="{{ field.id_for_label }}" class="form-label">
                                    {{ field.label }}{% if field.field.required %}*{% endif %}
                                </label>
                                
                                {% if field.name == 'work_area' and field.field.disabled %}
                                    <!-- Campo work_area deshabilitado pero con valor visible -->
                                    <input type="text" class="form-control" value="{{ field.value }}" disabled>
                                    <input type="hidden" name="{{ field.name }}" value="{{ field.value }}">
                                    <small class="form-text text-muted">Los supervisores no pueden cambiar el área de trabajo.</small>
                                {% else %}
                                    {{ field }}
                                {% endif %}
                                
                                {% if field.help_text %}
                                <small class="form-text text-muted">{{ field.help_text }}</small>
                                {% endif %}
                                {% for error in field.errors %}
                                <div class="text-danger">{{ error }}</div>
                                {% endfor %}
                            </div>
                            {% endfor %}
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{% url 'equipo_user' %}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al equipo
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información de debug -->
            <div class="alert alert-info mt-3">
                <small>
                    <strong>Información de usuario:</strong><br>
                    • Username: {{ usuario.username }}<br>
                    • Rol actual: {{ usuario.get_role_display }}<br>
                    • Área actual: {{ usuario.work_area|default:"No asignada" }}<br>
                    • Email: {{ usuario.email }}<br>
                    • Último login: {{ usuario.last_login|date:"d/m/Y H:i"|default:"Nunca" }}<br>
                    • Editor: {{ request.user.get_role_display }} ({{ request.user.work_area|default:"Sin área" }})
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Estilos para los campos -->
<style>
.form-control, .form-select {
    border-radius: 8px;
    padding: 10px;
    border: 1px solid #ddd;
}

.form-label {
    font-weight: 500;
    margin-bottom: 5px;
}

.text-danger {
    font-size: 12px;
    margin-top: 5px;
}

/* Estilo para campos deshabilitados */
.form-control:disabled {
    background-color: #f8f9fa;
    opacity: 1;
}
</style>
{% endblock %}