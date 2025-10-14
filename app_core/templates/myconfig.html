{% extends "base.html" %} 
{% load static %} 
{% block title %}Configuración de Cuenta{% endblock %} 

{% block content %} 
<div class="container py-5" style="margin-top: 5%; margin-bottom: 5%;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Card perfil -->
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <!-- Mensajes -->
                    {% for message in messages %}
                    <div class="alert alert-{% if message.tags == 'error' %}danger{% else %}success{% endif %} alert-dismissible fade show" role="alert">
                        {{ message }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    {% endfor %}
                    
                    <!-- Encabezado con avatar -->
                    <div class="d-flex align-items-center mb-4 flex-column flex-md-row text-center text-md-start">
                        <!-- Mostrar avatar -->
                        <img src="{{ avatar_url }}" alt="Avatar" class="rounded-circle me-md-3 mb-3 mb-md-0" style="width: 100px; height: 100px; object-fit: cover;">
                        
                        <div class="ms-md-4">
                            <h4 class="fw-bold mb-0">{{ user.first_name }} {{ user.last_name }}</h4>
                            <p class="text-muted mb-1">{{ user.get_role_display }}</p>
                            <p class="text-muted small"><i class="fas fa-id-badge me-1"></i> ID: {{ user.employee_id }}</p>
                        </div>
                    </div>
                    
                    <!-- Formulario de avatar -->
                    <form method="post" enctype="multipart/form-data">
                        {% csrf_token %}
                        
                        <!-- Sección Avatar -->
                        <div class="mb-4 p-3 bg-light rounded-3">
                            <h5 class="fw-bold mb-3">Configuración de Avatar</h5>
                            
                            <!-- Avatar personalizado -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subir avatar personalizado</label>
                                <input type="file" class="form-control" name="avatar_personalizado" accept="image/*">
                                <div class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB.</div>
                            </div>
                            
                            <!-- Selección de avatar predefinido -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Seleccionar avatar predefinido</label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    {% for value, label in user.AVATARES_PREDEFINIDOS %}
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="avatar_predefinido" 
                                               id="avatar_{{ value }}" value="{{ value }}"
                                               {% if user.avatar_predefinido == value %}checked{% endif %}>
                                        <label class="form-check-label d-flex flex-column align-items-center" for="avatar_{{ value }}">
                                            <img src="{% static 'avatares/default/' %}{{ value }}.png" 
                                                 alt="{{ label }}" class="rounded-circle mb-1" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                            <span class="small">{{ label }}</span>
                                        </label>
                                    </div>
                                    {% endfor %}
                                </div>
                            </div>
                            
                            <!-- Eliminar avatar personalizado -->
                            {% if user.avatar %}
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="eliminar_avatar" name="eliminar_avatar">
                                <label class="form-check-label" for="eliminar_avatar">Eliminar avatar personalizado y usar predefinido</label>
                            </div>
                            {% endif %}
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios de avatar
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Información personal (SOLO LECTURA) -->
                    <h4 class="fw-bold mb-4 text-center text-primary">Información personal</h4>
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover">
                            <tbody>
                                <tr>
                                    <th class="text-muted" width="30%">Nombre</th>
                                    <td>{{ user.first_name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Segundo Nombre</th>
                                    <td>{{ user.middle_name|default:"No especificado" }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Apellido</th>
                                    <td>{{ user.last_name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Segundo Apellido</th>
                                    <td>{{ user.second_last_name|default:"No especificado" }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Correo electrónico</th>
                                    <td>{{ user.email }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Número de teléfono</th>
                                    <td>{{ user.phone_number|default:"No especificado" }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Fecha de cumpleaños</th>
                                    <td>
                                        {% if user.birth_date %}
                                            {{ user.birth_date|date:"d/m/Y" }}
                                        {% else %}
                                            No especificado
                                        {% endif %}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Género</th>
                                    <td>{{ user.get_gender_display|default:"No especificado" }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Área de trabajo</th>
                                    <td>{{ user.work_area|default:"No especificado" }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Jefe directo</th>
                                    <td>
                                        {% if user.manager %}
                                            {{ user.manager.first_name }} {{ user.manager.last_name }}
                                        {% else %}
                                            No especificado
                                        {% endif %}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Cambio de contraseña -->
                    <div class="mt-5">
                        <h4 class="fw-bold text-center text-primary mb-4">Cambiar contraseña</h4>
                        <form method="POST">
                            {% csrf_token %}
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="old_password" class="form-label fw-semibold">Contraseña actual</label>
                                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="new_password1" class="form-label fw-semibold">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="new_password1" name="new_password1" required>
                                    <div class="form-text">La contraseña debe contener al menos 8 caracteres.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="new_password2" class="form-label fw-semibold">Repetir nueva contraseña</label>
                                    <input type="password" class="form-control" id="new_password2" name="new_password2" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary justify-content-start">
                                <i class="fa-solid fa-key me-1"></i> Actualizar contraseña
                            </button>
                            <div class="d-flex justify-content-end mt-4">
                                 <a href="{% url 'index' %}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>      
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
    .table th {
        width: 30%;
    }
    .table td {
        width: 70%;
    }
</style>
{% endblock %}