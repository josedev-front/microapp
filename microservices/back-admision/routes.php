<?php
// microservices/back-admision/routes.php

// Primero cargar init para verificar acceso
require_once __DIR__ . '/init.php';

// Obtener parámetros
$vista = $_GET['vista'] ?? '';
$action = $_GET['action'] ?? '';

// DEBUG
error_log("Back Admision Routes - Vista: " . $vista . ", Action: " . $action);

// IDENTIFICAR SI ES UNA SOLICITUD API (NO DEBE USAR LAYOUT)
$is_api_request = in_array($action, [
    'procesar-caso', 
    'admision-api-gestionar-caso', 
    'cambiar-estado', 
    'get-casos', 
    'ingresar-caso-supervisor', 
    'cambiar-estado-usuario', 
    'get-horarios', 
    'guardar-horarios', 
    'descargar-excel', 
    'exportar-logs',
    'descargar-plantilla-horarios',
    'importar-horarios-excel'
    // NOTA: 'añadir-analistas' NO está aquí porque es una VISTA, no una API
]);

if ($is_api_request) {
    // PARA APIS: Cargar directamente el archivo y SALIR sin layout
    error_log("Procesando API request: " . $action);
    
    switch ($action) {
        case 'procesar-caso':
            require_once __DIR__ . '/api/ingresar_caso.php';
            exit;
            
        case 'admision-api-gestionar-caso':
            require_once __DIR__ . '/api/gestionar_caso.php';
            exit;
            
        case 'cambiar-estado':
            require_once __DIR__ . '/api/cambiar_estado.php';
            exit;
            
        case 'get-casos':
            require_once __DIR__ . '/api/get_casos.php';
            exit;
            
        case 'ingresar-caso-supervisor':
            require_once __DIR__ . '/api/ingresar_caso_supervisor.php';
            exit;
            
        case 'cambiar-estado-usuario':
            require_once __DIR__ . '/api/cambiar_estado_usuario.php';
            exit;
            
        case 'get-horarios':
            require_once __DIR__ . '/api/get_horarios.php';
            exit;
            
        case 'guardar-horarios':
            require_once __DIR__ . '/api/guardar_horarios.php';
            exit;
            
        case 'descargar-excel':
            require_once __DIR__ . '/api/descargar_excel.php';
            exit;
            
        case 'exportar-logs':
            require_once __DIR__ . '/api/exportar_logs.php';
            exit;
            
        case 'descargar-plantilla-horarios':
            require_once __DIR__ . '/api/descargar_plantilla_horarios.php';
            exit;
            
        case 'importar-horarios-excel':
            require_once __DIR__ . '/api/importar_horarios_excel.php';
            exit;
            
        default:
            // Si es una API no reconocida, retornar error JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'API endpoint no encontrado: ' . $action
            ]);
            exit;
    }
}

// ROUTING INTERNO PARA VISTAS (ESTAS SÍ USARÁN LAYOUT)
error_log("Procesando VISTA request: " . $action);

switch ($action) {
    case 'ingresar-caso':
        $tipo = $_GET['tipo'] ?? 'ejecutivo';
        if ($tipo === 'supervisor') {
            require_once __DIR__ . '/views/supervisor/ingresar-caso-backup.php';
        } else {
            require_once __DIR__ . '/views/ejecutivo/ingresar-caso.php';
        }
        break;
        
    case 'gestionar-solicitud':
        require_once __DIR__ . '/views/ejecutivo/gestionar-solicitud.php';
        break;
        
    case 'gestionar-equipos':
        require_once __DIR__ . '/views/supervisor/gestionar-equipos.php';
        break;
        
    case 'ver-registros':
        require_once __DIR__ . '/views/supervisor/ver-registros.php';
        break;
        
    case 'panel-asignaciones':
        require_once __DIR__ . '/views/supervisor/panel-asignaciones.php';
        break;
        
    case 'bandeja-casos':
        require_once __DIR__ . '/views/ejecutivo/bandeja-casos.php';
        break;
        
    case 'gestionar-horarios':
        require_once __DIR__ . '/views/supervisor/gestionar-horarios.php';
        break;
        
    case 'añadir-analistas':  // NUEVA VISTA - CORREGIDO
        require_once __DIR__ . '/views/supervisor/añadir-analistas.php';
        break;
        
    case 'ingresar-caso-ejecutivo':
        require_once __DIR__ . '/views/supervisor/ingresar-caso-ejecutivo.php';
        break;
        
    case 'reasignacion-rapida':
        require_once __DIR__ . '/views/supervisor/reasignacion-rapida.php';
        break;
        
    default:
        // Página principal según rol
        if ($backAdmision->getUserRole() === 'ejecutivo') {
            require_once __DIR__ . '/views/ejecutivo/menu.php';
        } else {
            require_once __DIR__ . '/views/supervisor/menu.php';
        }
        break;
}

// NOTA: Las vistas se cargarán dentro del layout principal del core
// Las APIs ya salieron con exit() y no llegarán hasta aquí
?>