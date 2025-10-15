{% extends "base.html" %}
{% load static %}

{% block title %}Noticias & Comunicados{% endblock %}

{% block content %}
<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
  <div class="row justify-content-center">

    <!-- Contenido principal -->
    <div class="col-lg-12 card p-4">

      <h2 class="fw-bold mb-4 text-primary"><i class="fas fa-newspaper"></i> Noticias & Comunicados</h2>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-4" id="comunicadosTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button" role="tab">🌐 Globales</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="area-tab" data-bs-toggle="tab" data-bs-target="#area" type="button" role="tab">🏢 Mi Área</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">👤 Para mí</button>
        </li>
        
        <!-- Nueva pestaña para Mis Comunicados Emitidos -->
        {% if puede_crear %}
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="mis-comunicados-tab" data-bs-toggle="tab" data-bs-target="#mis-comunicados" type="button" role="tab">📤 Mis Comunicados</button>
        </li>
        {% endif %}
        
        <!-- Nueva pestaña para Historial de Acuses -->
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab">📊 Historial</button>
        </li>
      </ul>

      <div class="tab-content" id="comunicadosTabsContent">

        <!-- Globales -->
        <div class="tab-pane fade show active" id="global" role="tabpanel">
          {% if comunicados_globales %}
            {% for comunicado in comunicados_globales %}
              {% include 'partials/comunicado_card.html' with comunicado=comunicado %}
            {% endfor %}
          {% else %}
            <div class="alert alert-info">No hay comunicados globales por el momento.</div>
          {% endif %}
        </div>

        <!-- De mi área -->
        <div class="tab-pane fade" id="area" role="tabpanel">
          {% if comunicados_area %}
            {% for comunicado in comunicados_area %}
              {% include 'partials/comunicado_card.html' with comunicado=comunicado %}
            {% endfor %}
          {% else %}
            <div class="alert alert-warning">No hay comunicados para tu área.</div>
          {% endif %}
        </div>

        <!-- Personales -->
        <div class="tab-pane fade" id="personal" role="tabpanel">
          {% if comunicados_personales %}
            {% for comunicado in comunicados_personales %}
              {% include 'partials/comunicado_card.html' with comunicado=comunicado %}
            {% endfor %}
          {% else %}
            <div class="alert alert-success">No tienes comunicados personales pendientes 🎉</div>
          {% endif %}
        </div>

        <!-- Mis Comunicados Emitidos -->
        {% if puede_crear %}
        <div class="tab-pane fade" id="mis-comunicados" role="tabpanel">
          {% if mis_comunicados %}
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Destinatario/Área</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {% for comunicado in mis_comunicados %}
                  <tr>
                    <td>{{ comunicado.titulo }}</td>
                    <td>
                      <span class="badge bg-{% if comunicado.tipo == 'global' %}primary{% elif comunicado.tipo == 'local' %}warning{% else %}success{% endif %}">
                        {{ comunicado.get_tipo_display }}
                      </span>
                    </td>
                    <td>
                      {% if comunicado.tipo == 'personal' %}
                        {{ comunicado.destinatario.get_full_name }}
                      {% elif comunicado.tipo == 'local' %}
                        {{ comunicado.area }}
                      {% else %}
                        Todos
                      {% endif %}
                    </td>
                    <td>{{ comunicado.created_at|date:"d/m/Y H:i" }}</td>
                    <td>
                      <span class="badge bg-{% if comunicado.activo %}success{% else %}secondary{% endif %}">
                        {{ comunicado.activo|yesno:"Activo,Inactivo" }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <a href="{% url 'editar_comunicado' comunicado.id %}" class="btn btn-warning">
                          <i class="fas fa-edit"></i>
                        </a>
                        <form action="{% url 'eliminar_comunicado' comunicado.id %}" method="post" class="d-inline">
                          {% csrf_token %}
                          <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este comunicado?')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  {% endfor %}
                </tbody>
              </table>
            </div>
          {% else %}
            <div class="alert alert-info">No has creado ningún comunicado todavía.</div>
          {% endif %}
        </div>
        {% endif %}

        <!-- Historial de Acuses -->
        <div class="tab-pane fade" id="historial" role="tabpanel">
          <div class="mb-3">
            <form method="get" class="row g-2">
              <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Buscar comunicados..." value="{{ request.GET.q }}">
              </div>
              <div class="col-md-3">
                <select name="tipo" class="form-select">
                  <option value="">Todos los tipos</option>
                  <option value="global" {% if request.GET.tipo == 'global' %}selected{% endif %}>Global</option>
                  <option value="local" {% if request.GET.tipo == 'local' %}selected{% endif %}>Local</option>
                  <option value="personal" {% if request.GET.tipo == 'personal' %}selected{% endif %}>Personal</option>
                </select>
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="{% url 'news' %}" class="btn btn-secondary">Limpiar</a>
              </div>
            </form>
          </div>

          {% if acuses_historial %}
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Comunicado</th>
                    <th>Tipo</th>
                    <th>Fecha Acuse</th>
                    <th>Días Visibilidad</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {% for acuse in acuses_historial %}
                  <tr>
                    <td>{{ acuse.comunicado.titulo }}</td>
                    <td>
                      <span class="badge bg-{% if acuse.comunicado.tipo == 'global' %}primary{% elif acuse.comunicado.tipo == 'local' %}warning{% else %}success{% endif %}">
                        {{ acuse.comunicado.get_tipo_display }}
                      </span>
                    </td>
                    <td>{{ acuse.fecha_acuse|date:"d/m/Y H:i" }}</td>
                    <td>{{ acuse.comunicado.dias_visibilidad }} días</td>
                    <td>
                      <span class="badge bg-{% if acuse.esta_visible %}success{% else %}secondary{% endif %}">
                        {{ acuse.esta_visible|yesno:"Visible,Archivado" }}
                      </span>
                    </td>
                  </tr>
                  {% endfor %}
                </tbody>
              </table>
            </div>
          {% else %}
            <div class="alert alert-info">No tienes acuses de recibo en tu historial.</div>
          {% endif %}
        </div>

      </div>

      <!-- Crear comunicado (solo roles permitidos) -->
      {% if puede_crear %}
        <hr>
        <h4 class="text-primary">✍️ Crear nuevo comunicado</h4>
        <form method="post" enctype="multipart/form-data">
          {% csrf_token %}
          {{ form.as_p }}
          <button type="submit" class="btn btn-primary">Publicar</button>
        </form>
        <div class="d-flex justify-content-end mt-4">
                <a href="{% url 'index' %}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>      
            </div>
      {% endif %}

    </div>
  </div>
</div>

<!-- JavaScript para mostrar/ocultar campos según el tipo -->
<script>
function toggleComunicadoFields() {
    const tipo = document.getElementById('id_tipo').value;
    const areaField = document.getElementById('id_area')?.closest('.form-group, p');
    const destinatarioField = document.getElementById('id_destinatario')?.closest('.form-group, p');
    const diasVisibilidadField = document.getElementById('id_dias_visibilidad')?.closest('.form-group, p');

    // Mostrar/ocultar campos según el tipo
    if (areaField) areaField.style.display = tipo === 'local' ? 'block' : 'none';
    if (destinatarioField) destinatarioField.style.display = tipo === 'personal' ? 'block' : 'none';
    if (diasVisibilidadField) diasVisibilidadField.style.display = 'block'; // Siempre visible

    // Hacer requeridos los campos según el tipo
    if (document.getElementById('id_area')) {
        document.getElementById('id_area').required = (tipo === 'local');
    }
    if (document.getElementById('id_destinatario')) {
        document.getElementById('id_destinatario').required = (tipo === 'personal');
    }
}

function lockAreaForSpecificRoles() {
    const userRole = "{{ user.role|lower }}";  // Rol del usuario desde backend
    const userArea = "{{ user.work_area }}";   // Área del usuario
    const areaSelect = document.getElementById('id_area');
    
    if (areaSelect && ['supervisor', 'qa'].includes(userRole)) {
        // Deshabilitar el select
        areaSelect.disabled = true;
        areaSelect.title = "Solo puedes enviar comunicados a tu propia área";
        
        // Buscar y seleccionar automáticamente la opción del área del usuario
        for (let i = 0; i < areaSelect.options.length; i++) {
            if (areaSelect.options[i].text === userArea) {
                areaSelect.selectedIndex = i;
                break;
            }
        }
        
        // Crear un campo oculto con el valor para asegurar el envío
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'area';
        hiddenInput.value = userArea;
        areaSelect.parentNode.appendChild(hiddenInput);
    }
}

document.addEventListener('DOMContentLoaded', function() {
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

{% endblock %}