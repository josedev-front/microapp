{% extends "base.html" %}
{% load static %}

{% block title %}Editar Comunicado - {{ comunicado.titulo }}{% endblock %}

{% block content %}
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Comunicado: {{ comunicado.titulo }}
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        {% csrf_token %}
                        
                        {% if form.errors %}
                        <div class="alert alert-danger">
                            <strong>Por favor, corrige los siguientes errores:</strong>
                            <ul class="mb-0">
                                {% for field in form %}
                                    {% for error in field.errors %}
                                        <li>{{ field.label }}: {{ error }}</li>
                                    {% endfor %}
                                {% endfor %}
                                {% for error in form.non_field_errors %}
                                    <li>{{ error }}</li>
                                {% endfor %}
                            </ul>
                        </div>
                        {% endif %}
                        
                        <div class="row">
                            <!-- Información básica -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="{{ form.titulo.id_for_label }}" class="form-label">Título <span class="text-danger">*</span></label>
                                    {{ form.titulo }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.contenido.id_for_label }}" class="form-label">Contenido <span class="text-danger">*</span></label>
                                    {{ form.contenido }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.imagen.id_for_label }}" class="form-label">Imagen</label>
                                    {{ form.imagen }}
                                    {% if comunicado.imagen %}
                                    <div class="mt-2">
                                        <img src="{{ comunicado.imagen.url }}" alt="Imagen actual" class="img-thumbnail" style="max-height: 150px;">
                                        <div class="form-check mt-2">
                                            <input type="checkbox" name="eliminar_imagen" id="eliminar_imagen" class="form-check-input">
                                            <label for="eliminar_imagen" class="form-check-label">Eliminar imagen actual</label>
                                        </div>
                                    </div>
                                    {% endif %}
                                </div>
                            </div>
                            
                            <!-- Configuración y destinatarios -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Configuración</h5>
                                
                                <div class="mb-3">
                                    <label for="{{ form.tipo.id_for_label }}" class="form-label">Tipo <span class="text-danger">*</span></label>
                                    {{ form.tipo }}
                                </div>
                                
                                <div class="mb-3" id="area-field">
                                    <label for="{{ form.area.id_for_label }}" class="form-label">Área</label>
                                    {{ form.area }}
                                    <div class="form-text">Solo para comunicados locales</div>
                                </div>
                                
                                <div class="mb-3" id="destinatario-field">
                                    <label for="{{ form.destinatario.id_for_label }}" class="form-label">Destinatario</label>
                                    {{ form.destinatario }}
                                    <div class="form-text">Solo para comunicados personales</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        {{ form.requiere_acuse }}
                                        <label class="form-check-label" for="{{ form.requiere_acuse.id_for_label }}">Requiere acuse de recibo</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.dias_visibilidad.id_for_label }}" class="form-label">Días de visibilidad después del acuse</label>
                                    {{ form.dias_visibilidad }}
                                    <div class="form-text">Número de días que el comunicado permanecerá visible después del acuse</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        {{ form.activo }}
                                        <label class="form-check-label" for="{{ form.activo.id_for_label }}">Comunicado activo</label>
                                    </div>
                                    <div class="form-text">Desactivar para archivar el comunicado</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{% url 'news' %}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información del comunicado -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Comunicado</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> {{ comunicado.created_by.get_full_name }}</p>
                            <p><strong>Fecha de creación:</strong> {{ comunicado.created_at|date:"d/m/Y H:i" }}</p>
                            <p><strong>Total de acuses:</strong> {{ comunicado.acuses.count }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-{% if comunicado.activo %}success{% else %}secondary{% endif %}">
                                    {{ comunicado.activo|yesno:"Activo,Archivado" }}
                                </span>
                            </p>
                            <p><strong>Días de visibilidad:</strong> {{ comunicado.dias_visibilidad }} días</p>
                            <p><strong>Requiere acuse:</strong> 
                                <span class="badge bg-{% if comunicado.requiere_acuse %}info{% else %}warning{% endif %}">
                                    {{ comunicado.requiere_acuse|yesno:"Sí,No" }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para mostrar/ocultar campos según el tipo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleComunicadoFields() {
        const tipo = document.getElementById('id_tipo').value;
        const areaField = document.getElementById('area-field');
        const destinatarioField = document.getElementById('destinatario-field');
        
        // Mostrar/ocultar campos según el tipo
        if (areaField) areaField.style.display = tipo === 'local' ? 'block' : 'none';
        if (destinatarioField) destinatarioField.style.display = tipo === 'personal' ? 'block' : 'none';
        
        // Hacer requeridos los campos según el tipo
        const areaInput = document.getElementById('id_area');
        const destinatarioInput = document.getElementById('id_destinatario');
        
        if (areaInput) areaInput.required = (tipo === 'local');
        if (destinatarioInput) destinatarioInput.required = (tipo === 'personal');
    }
    
    function lockAreaForSpecificRoles() {
        const userRole = "{{ user.role|lower }}";
        const userArea = "{{ user.work_area }}";
        const areaSelect = document.getElementById('id_area');
        
        if (areaSelect && ['supervisor', 'qa'].includes(userRole)) {
            areaSelect.disabled = true;
            areaSelect.title = "Solo puedes enviar comunicados a tu propia área";
        }
    }
    
    // Ejecutar inicialización
    toggleComunicadoFields();
    lockAreaForSpecificRoles();
    
    // Reaplicar cuando cambie el tipo
    const tipoInput = document.getElementById('id_tipo');
    if (tipoInput) {
        tipoInput.addEventListener('change', toggleComunicadoFields);
    }
});
</script>

<style>
.form-label {
    font-weight: 500;
}

.form-control, .form-select {
    border-radius: 8px;
    padding: 10px;
    border: 1px solid #ddd;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.card {
    border: none;
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}
</style>
{% endblock %}