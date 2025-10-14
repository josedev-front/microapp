{% extends "base.html" %}
{% load static %}

{% block title %}Crear Usuario{% endblock %}

{% block content %}
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" id="userForm">
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
                                    <label for="{{ form.username.id_for_label }}" class="form-label">Username <span class="text-danger">*</span></label>
                                    {{ form.username }}
                                    <div class="form-text">Nombre de usuario para iniciar sesión.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.first_name.id_for_label }}" class="form-label">Nombres <span class="text-danger">*</span></label>
                                    {{ form.first_name }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.middle_name.id_for_label }}" class="form-label">Segundo Nombre</label>
                                    {{ form.middle_name }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.last_name.id_for_label }}" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                    {{ form.last_name }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.second_last_name.id_for_label }}" class="form-label">Apellido Materno</label>
                                    {{ form.second_last_name }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.gender.id_for_label }}" class="form-label">Género</label>
                                    {{ form.gender }}
                                </div>
                            </div>
                            
                            <!-- Información laboral y contacto -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Información Laboral y Contacto</h5>
                                
                                <div class="mb-3">
                                    <label for="{{ form.email.id_for_label }}" class="form-label">Email <span class="text-danger">*</span></label>
                                    {{ form.email }}
                                    <div class="form-text">El email será utilizado para iniciar sesión.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.employee_id.id_for_label }}" class="form-label">ID de Empleado <span class="text-danger">*</span></label>
                                    {{ form.employee_id }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.role.id_for_label }}" class="form-label">Rol <span class="text-danger">*</span></label>
                                    {{ form.role }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.work_area.id_for_label }}" class="form-label">Área de Trabajo <span class="text-danger">*</span></label>
                                    {{ form.work_area }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.manager.id_for_label }}" class="form-label">Jefe Directo</label>
                                    {{ form.manager }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.phone_number.id_for_label }}" class="form-label">Teléfono</label>
                                    {{ form.phone_number }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Seguridad</h5>
                                
                                <div class="mb-3">
                                    <label for="{{ form.password1.id_for_label }}" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                    {{ form.password1 }}
                                    <div class="form-text">
                                        <ul class="small">
                                            <li>La contraseña no puede ser similar a su información personal.</li>
                                            <li>Debe contener al menos 8 caracteres.</li>
                                            <li>No puede ser una contraseña comúnmente utilizada.</li>
                                            <li>No puede ser entirely numérica.</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="{{ form.password2.id_for_label }}" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    {{ form.password2 }}
                                    <div class="form-text">Introduzca la misma contraseña para verificación.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 text-primary mb-3">Permisos</h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        {{ form.is_active }}
                                        <label class="form-check-label" for="{{ form.is_active.id_for_label }}">Usuario Activo</label>
                                    </div>
                                    <div class="form-text">Desmarque esta opción para desactivar el usuario.</div>
                                </div>
                                
                                {% if request.user.is_superuser %}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        {{ form.is_superuser }}
                                        <label class="form-check-label" for="{{ form.is_superuser.id_for_label }}">Es Superusuario</label>
                                    </div>
                                    <div class="form-text">Los superusuarios tienen todos los permisos.</div>
                                </div>
                                {% endif %}
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{% url 'equipo_user' %}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-label {
        font-weight: 500;
    }
    
    input[type="text"], 
    input[type="email"], 
    input[type="password"], 
    select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    input[type="text"]:focus, 
    input[type="email"]:focus, 
    input[type="password"]:focus, 
    select:focus {
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