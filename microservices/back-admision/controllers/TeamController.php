<?php
require_once __DIR__ . '/../models/UserSync.php';

class TeamController {
    private $userSync;
    private $db;
    
    public function __construct() {
        $this->userSync = new UserSync();
        require_once __DIR__ . '/../config/database_back_admision.php';
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Obtener métricas de balance DIARIO - CORREGIDO
     */
    public function getMetricasBalanceDiario($fecha_desde = null, $fecha_hasta = null) {
        try {
            // Si no se proporcionan fechas, usar hoy
            if (!$fecha_desde) $fecha_desde = date('Y-m-d');
            if (!$fecha_hasta) $fecha_hasta = date('Y-m-d');
            
            $query = "
                SELECT 
                    hu.user_id,
                    hu.nombre_completo,
                    hu.area,
                    eu.estado,
                    eu.ultima_actualizacion,
                    -- Casos ingresados en el período
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? THEN c.id END) as casos_periodo,
                    -- Casos activos totales
                    COUNT(CASE WHEN c.estado != 'resuelto' THEN c.id END) as casos_activos,
                    -- Distribución por estado (período)
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'en_curso' THEN c.id END) as en_curso,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'en_espera' THEN c.id END) as en_espera,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'resuelto' THEN c.id END) as resuelto,
                    COUNT(CASE WHEN DATE(c.fecha_ingreso) BETWEEN ? AND ? AND c.estado = 'cancelado' THEN c.id END) as cancelado
                FROM horarios_usuarios hu
                LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
                LEFT JOIN casos c ON hu.user_id = c.analista_id
                WHERE hu.activo = 1 AND hu.area = 'Depto Micro&SOHO'
                GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
                ORDER BY casos_periodo ASC, hu.nombre_completo
            ";
            
            $stmt = $this->db->prepare($query);
            
            // Pasar los parámetros correctamente - solo necesitamos 2 parámetros repetidos
            $params = [
                $fecha_desde, $fecha_hasta,    // Para casos_periodo
                $fecha_desde, $fecha_hasta,    // Para en_curso  
                $fecha_desde, $fecha_hasta,    // Para en_espera
                $fecha_desde, $fecha_hasta,    // Para resuelto
                $fecha_desde, $fecha_hasta     // Para cancelado
            ];
            
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Asegurar que todos los campos existan y mantener compatibilidad
            foreach ($resultados as &$fila) {
                $fila = array_merge([
                    'casos_hoy' => 0,
                    'casos_activos' => 0,
                    'en_curso' => 0,
                    'en_espera' => 0,
                    'resuelto' => 0,
                    'cancelado' => 0
                ], $fila);
                
                // Mantener compatibilidad con el nombre original
                $fila['casos_hoy'] = $fila['casos_periodo'] ?? 0;
            }
            
            error_log("✅ TeamController: Métricas obtenidas para {$fecha_desde} a {$fecha_hasta}: " . count($resultados) . " ejecutivos");
            return $resultados;
            
        } catch (Exception $e) {
            error_log("❌ Error en TeamController::getMetricasBalanceDiario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener ejecutivos activos con información de casos
     */
    public function getEjecutivosActivos() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    hu.user_id,
                    hu.nombre_completo,
                    hu.area,
                    eu.estado,
                    eu.ultima_actualizacion,
                    COUNT(c.id) as casos_activos
                FROM horarios_usuarios hu
                LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
                LEFT JOIN casos c ON hu.user_id = c.analista_id AND c.estado != 'resuelto'
                WHERE hu.activo = 1
                GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
                ORDER BY hu.nombre_completo
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getEjecutivosActivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener ejecutivos disponibles para asignación
     */
    public function getEjecutivosDisponibles() {
        return $this->userSync->getEjecutivosDisponibles();
    }
    
    /**
     * Cambiar estado de un ejecutivo
     */
    public function cambiarEstadoEjecutivo($user_id, $nuevo_estado) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO estado_usuarios (user_id, estado, ultima_actualizacion, ip_ultima_actualizacion) 
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                    estado = VALUES(estado),
                    ultima_actualizacion = NOW(),
                    ip_ultima_actualizacion = VALUES(ip_ultima_actualizacion)
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            return $stmt->execute([$user_id, $nuevo_estado, $ip]);
            
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoEjecutivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener horarios de un ejecutivo
     */
    public function getHorariosEjecutivo($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM horarios_usuarios 
                WHERE user_id = ? 
                ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getHorariosEjecutivo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar horarios de un ejecutivo
     */
    public function actualizarHorarios($user_id, $horarios) {
        $this->db->beginTransaction();
        
        try {
            foreach ($horarios as $dia => $horario) {
                $stmt = $this->db->prepare("
                    UPDATE horarios_usuarios 
                    SET hora_entrada = ?, hora_salida = ?, hora_almuerzo_inicio = ?, hora_almuerzo_fin = ?, 
                        activo = ?, updated_at = NOW()
                    WHERE user_id = ? AND dia_semana = ?
                ");
                
                $stmt->execute([
                    $horario['hora_entrada'] ?: null,
                    $horario['hora_salida'] ?: null,
                    $horario['hora_almuerzo_inicio'] ?: null,
                    $horario['hora_almuerzo_fin'] ?: null,
                    $horario['activo'] ? 1 : 0,
                    $user_id,
                    $dia
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando horarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener métricas de balance de carga
     */
    public function getMetricasBalance() {
        return $this->getMetricasBalanceDiario();
    }
    /**
 * Obtener métricas de casos ingresados HOY para el panel de gestión de equipos
 * Obtener métricas de casos ingresados EXCLUSIVAMENTE HOY para el panel de gestión de equipos
 */
public function getMetricasCasosHoy() {
    try {
        // FORZAR FECHA CORRECTA
        date_default_timezone_set('America/Santiago'); // Ajusta según tu zona
        
        $hoy = date('Y-m-d'); // Fecha actual REAL
        
        error_log("🔍 DIAGNÓSTICO COMPLETO - Fecha usada: " . $hoy);
        
        // PRIMERO: Verificar qué casos existen para hoy
        $stmt_diagnostico = $this->db->prepare("
            SELECT 
                c.analista_nombre,
                c.sr_hijo,
                DATE(c.fecha_ingreso) as fecha_ingreso,
                c.estado
            FROM casos c
            WHERE DATE(c.fecha_ingreso) = ?
            ORDER BY c.analista_nombre, c.fecha_ingreso
        ");
        
        $stmt_diagnostico->execute([$hoy]);
        $casos_hoy = $stmt_diagnostico->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("📊 CASOS ENCONTRADOS PARA {$hoy}: " . count($casos_hoy));
        foreach ($casos_hoy as $caso) {
            error_log("   - " . $caso['analista_nombre'] . " | " . $caso['sr_hijo'] . " | " . $caso['fecha_ingreso'] . " | " . $caso['estado']);
        }
        
        // SEGUNDO: Consulta principal con COUNT DISTINCT para evitar duplicados
        $stmt = $this->db->prepare("
            SELECT 
                hu.user_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                eu.ultima_actualizacion,
                -- Casos ingresados EXCLUSIVAMENTE HOY (usar COUNT DISTINCT)
                COUNT(DISTINCT CASE WHEN DATE(c.fecha_ingreso) = ? THEN c.id END) as casos_hoy,
                -- Distribución por estado (exclusivamente hoy)
                COUNT(DISTINCT CASE WHEN DATE(c.fecha_ingreso) = ? AND c.estado = 'en_curso' THEN c.id END) as en_curso_hoy,
                COUNT(DISTINCT CASE WHEN DATE(c.fecha_ingreso) = ? AND c.estado = 'en_espera' THEN c.id END) as en_espera_hoy,
                COUNT(DISTINCT CASE WHEN DATE(c.fecha_ingreso) = ? AND c.estado = 'resuelto' THEN c.id END) as resuelto_hoy,
                COUNT(DISTINCT CASE WHEN DATE(c.fecha_ingreso) = ? AND c.estado = 'cancelado' THEN c.id END) as cancelado_hoy
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id
            WHERE hu.activo = 1 AND hu.area = 'Depto Micro&SOHO'
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
            ORDER BY casos_hoy ASC, hu.nombre_completo
        ");
        
        $stmt->execute([$hoy, $hoy, $hoy, $hoy, $hoy]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("📈 RESULTADOS DE LA CONSULTA:");
        foreach ($resultados as $resultado) {
            error_log("   - " . $resultado['nombre_completo'] . ": " . $resultado['casos_hoy'] . " casos hoy");
        }
        
        // Asegurar que todos los campos existan
        foreach ($resultados as &$fila) {
            $fila = array_merge([
                'casos_hoy' => 0,
                'en_curso_hoy' => 0,
                'en_espera_hoy' => 0,
                'resuelto_hoy' => 0,
                'cancelado_hoy' => 0
            ], $fila);
        }
        
        return $resultados;
        
    } catch (Exception $e) {
        error_log("❌ Error en getMetricasCasosHoy: " . $e->getMessage());
        return [];
    }
}
public function getHorariosUsuario($user_id) {
        try {
            $sql = "SELECT * FROM horarios_usuarios WHERE user_id = ? ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo horarios: " . $e->getMessage());
            return [];
        }
    }

    // Método para guardar horarios
    public function guardarHorariosUsuario($user_id, $horarios) {
        $this->db->beginTransaction();
        
        try {
            // Primero, obtener información del usuario para el log
            $user_info = $this->getUsuarioById($user_id);
            
            // Eliminar horarios existentes
            $delete_sql = "DELETE FROM horarios_usuarios WHERE user_id = ?";
            $delete_stmt = $this->db->prepare($delete_sql);
            $delete_stmt->execute([$user_id]);
            
            // Insertar nuevos horarios
            $insert_sql = "INSERT INTO horarios_usuarios (user_id, dia_semana, hora_entrada, hora_salida, hora_almuerzo_inicio, hora_almuerzo_fin, activo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $this->db->prepare($insert_sql);
            
            $dias_guardados = 0;
            foreach ($horarios as $dia => $config) {
                $insert_stmt->execute([
                    $user_id,
                    $dia,
                    $config['hora_entrada'] ?? '09:00',
                    $config['hora_salida'] ?? '18:00',
                    $config['hora_almuerzo_inicio'] ?? '13:00',
                    $config['hora_almuerzo_fin'] ?? '14:00',
                    isset($config['activo']) && $config['activo'] ? 1 : 0
                ]);
                $dias_guardados++;
            }
            
            $this->db->commit();
            
            /* Registrar en logs del sistema
            global $backAdmision;
            $backAdmision->registrarLogSistema(
                'HORARIOS_ACTUALIZADOS',
                $user_id,
                [
                    'user_name' => $user_info['nombre_completo'] ?? 'N/A',
                    'dias_configurados' => $dias_guardados,
                    'supervisor_id' => $backAdmision->getUserId()
                ],
                'Configuración de horarios actualizada'
            );*/
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error guardando horarios: " . $e->getMessage());
            return false;
        }
    }
      public function getUsuarioById($user_id) {
    try {
        // Intentar diferentes consultas hasta encontrar la correcta
        $queries = [
            // Opción 1: Tabla en misma base de datos
            "SELECT id, username, first_name, last_name, work_area, 
                    CONCAT(first_name, ' ', last_name) as nombre_completo 
             FROM core_customuser 
             WHERE id = ?",
            
            // Opción 2: Con prefijo de base de datos
            "SELECT id, username, first_name, last_name, work_area, 
                    CONCAT(first_name, ' ', last_name) as nombre_completo 
             FROM microapps.core_customuser 
             WHERE id = ?",
            
            // Opción 3: Solo campos básicos
            "SELECT id, username, first_name, last_name, work_area 
             FROM core_customuser 
             WHERE id = ?",
            
            // Opción 4: Con prefijo y campos básicos
            "SELECT id, username, first_name, last_name, work_area 
             FROM microapps.core_customuser 
             WHERE id = ?"
        ];
        
        foreach ($queries as $sql) {
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    // Si no viene nombre_completo, lo construimos
                    if (!isset($result['nombre_completo']) && isset($result['first_name'])) {
                        $result['nombre_completo'] = $result['first_name'] . ' ' . $result['last_name'];
                    }
                    
                    error_log("Usuario encontrado con query: " . substr($sql, 0, 50) . "...");
                    return $result;
                }
            } catch (Exception $e) {
                error_log("Query falló: " . substr($sql, 0, 50) . "... Error: " . $e->getMessage());
                continue;
            }
        }
        
        error_log("Todas las consultas fallaron para user_id: " . $user_id);
        return null;
        
    } catch (Exception $e) {
        error_log("Error crítico en getUsuarioById: " . $e->getMessage());
        return null;
    }
}
public function getUsuariosMicroSOHO() {
    try {
        // Intentar diferentes consultas para encontrar usuarios de Micro&SOHO
        $queries = [
            // Opción 1: Tabla en misma base de datos
            "SELECT id, username, first_name, last_name, work_area, 
                    CONCAT(first_name, ' ', last_name) as nombre_completo,
                    avatar, avatar_predefinido
             FROM core_customuser 
             WHERE work_area LIKE '%Micro%' OR work_area LIKE '%SOHO%'
             ORDER BY first_name, last_name",
            
            // Opción 2: Con prefijo de base de datos
            "SELECT id, username, first_name, last_name, work_area, 
                    CONCAT(first_name, ' ', last_name) as nombre_completo,
                    avatar, avatar_predefinido
             FROM microapps.core_customuser 
             WHERE work_area LIKE '%Micro%' OR work_area LIKE '%SOHO%'
             ORDER BY first_name, last_name",
            
            // Opción 3: Buscar en cualquier área
            "SELECT id, username, first_name, last_name, work_area, 
                    CONCAT(first_name, ' ', last_name) as nombre_completo,
                    avatar, avatar_predefinido
             FROM core_customuser 
             WHERE is_active = 1
             ORDER BY first_name, last_name
             LIMIT 50"
        ];
        
        foreach ($queries as $sql) {
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($usuarios) {
                    // Procesar avatares
                    foreach ($usuarios as &$usuario) {
                        if (empty($usuario['avatar']) && !empty($usuario['avatar_predefinido'])) {
                            $usuario['avatar'] = '/dashboard/vsm/microapp/public/assets/img/default/' . $usuario['avatar_predefinido'] . '.png';
                        } elseif (empty($usuario['avatar'])) {
                            $usuario['avatar'] = '/dashboard/vsm/microapp/public/assets/img/default/user-default.png';
                        }
                    }
                    
                    return $usuarios;
                }
            } catch (Exception $e) {
                error_log("Query falló: " . $e->getMessage());
                continue;
            }
        }
        
        return [];
        
    } catch (Exception $e) {
        error_log("Error en getUsuariosMicroSOHO: " . $e->getMessage());
        return [];
    }
}

public function getUsuariosBackAdmision() {
    try {
        error_log("🔍 Iniciando getUsuariosBackAdmision()");
        
        // SOLUCIÓN SIMPLIFICADA Y ROBUSTA
        $sql = "SELECT 
                    eu.user_id, 
                    eu.estado, 
                    eu.ultima_actualizacion,
                    eu.ultima_actualizacion as created_at
                FROM estado_usuarios eu
                ORDER BY eu.ultima_actualizacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $usuarios_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("📊 Usuarios en estado_usuarios: " . count($usuarios_estado));
        
        if (empty($usuarios_estado)) {
            return [];
        }
        
        // Para cada usuario, obtener información completa
        $resultado = [];
        foreach ($usuarios_estado as $usuario_estado) {
            $user_info = $this->getUsuarioById($usuario_estado['user_id']);
            
            if ($user_info) {
                // Combinar información
                $usuario_completo = array_merge($usuario_estado, $user_info);
                
                // Procesar avatar
                if (empty($usuario_completo['avatar']) && !empty($usuario_completo['avatar_predefinido'])) {
                    $usuario_completo['avatar'] = '/dashboard/vsm/microapp/public/assets/img/default/' . $usuario_completo['avatar_predefinido'] . '.png';
                } elseif (empty($usuario_completo['avatar'])) {
                    $usuario_completo['avatar'] = '/dashboard/vsm/microapp/public/assets/img/default/user-default.png';
                }
                
                $resultado[] = $usuario_completo;
            } else {
                // Si no se puede obtener info, usar datos básicos
                $usuario_basico = $usuario_estado;
                $usuario_basico['nombre_completo'] = 'Usuario ID: ' . $usuario_estado['user_id'];
                $usuario_basico['username'] = 'user_' . $usuario_estado['user_id'];
                $usuario_basico['work_area'] = 'Desconocido';
                $usuario_basico['avatar'] = '/dashboard/vsm/microapp/public/assets/img/default/user-default.png';
                $resultado[] = $usuario_basico;
            }
        }
        
        error_log("✅ Usuarios finales: " . count($resultado));
        return $resultado;
        
    } catch (Exception $e) {
        error_log("❌ Error en getUsuariosBackAdmision: " . $e->getMessage());
        return [];
    }
}
public function agregarUsuarioBackAdmision($user_id) {
    error_log("🔍 Iniciando agregarUsuarioBackAdmision para user_id: " . $user_id);
    
    $this->db->beginTransaction();
    
    try {
        // 1. Agregar a estado_usuarios
        $sql_estado = "INSERT INTO estado_usuarios (user_id, estado, ultima_actualizacion) 
                       VALUES (?, 'activo', NOW()) 
                       ON DUPLICATE KEY UPDATE estado = 'activo', ultima_actualizacion = NOW()";
        $stmt_estado = $this->db->prepare($sql_estado);
        $result_estado = $stmt_estado->execute([$user_id]);
        
        error_log("📝 Estado usuario - Ejecutado: " . ($result_estado ? 'SI' : 'NO'));
        error_log("📝 Estado usuario - Filas afectadas: " . $stmt_estado->rowCount());
        
        // 2. Crear horarios por defecto
        $dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $sql_horario = "INSERT INTO horarios_usuarios (user_id, dia_semana, hora_entrada, hora_salida, hora_almuerzo_inicio, hora_almuerzo_fin, activo) 
                        VALUES (?, ?, '09:00', '18:00', '13:00', '14:00', 1)";
        $stmt_horario = $this->db->prepare($sql_horario);
        
        $horarios_creados = 0;
        foreach ($dias_semana as $dia) {
            $result_horario = $stmt_horario->execute([$user_id, $dia]);
            if ($result_horario) {
                $horarios_creados++;
            }
            error_log("📅 Horario {$dia} - Ejecutado: " . ($result_horario ? 'SI' : 'NO'));
        }
        
        error_log("📅 Total horarios creados: " . $horarios_creados);
        
        $this->db->commit();
        error_log("✅ Transacción completada exitosamente");
        
        // Registrar en logs usando método alternativo
        $this->registrarLogLocal(
            'USUARIO_AGREGADO',
            $user_id,
            [
                'supervisor_id' => $_SESSION['user_id'] ?? 0,
                'accion' => 'agregar_analista',
                'horarios_creados' => $horarios_creados
            ],
            'Usuario agregado al sistema Back Admisión'
        );
        
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("❌ Error en agregarUsuarioBackAdmision: " . $e->getMessage());
        return false;
    }
}

public function eliminarUsuarioBackAdmision($user_id) {
    $this->db->beginTransaction();
    
    try {
        // 1. Eliminar horarios
        $sql_horarios = "DELETE FROM horarios_usuarios WHERE user_id = ?";
        $stmt_horarios = $this->db->prepare($sql_horarios);
        $stmt_horarios->execute([$user_id]);
        
        // 2. Eliminar estado
        $sql_estado = "DELETE FROM estado_usuarios WHERE user_id = ?";
        $stmt_estado = $this->db->prepare($sql_estado);
        $stmt_estado->execute([$user_id]);
        
        $this->db->commit();
        
        // Registrar en logs usando método alternativo
        $this->registrarLogLocal(
            'USUARIO_ELIMINADO',
            $user_id,
            [
                'supervisor_id' => $_SESSION['user_id'] ?? 0,
                'accion' => 'eliminar_analista'
            ],
            'Usuario eliminado del sistema Back Admisión'
        );
        
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error eliminando usuario de back-admision: " . $e->getMessage());
        return false;
    }
}

/**
 * Método alternativo para registrar logs sin depender de BackAdmision
 */
private function registrarLogLocal($accion, $usuario_id, $detalles = [], $descripcion = '') {
    try {
        $sql = "INSERT INTO logs_sistema (sr_hijo, accion, fecha_accion, usuario_id, usuario_nombre, detalles, ip_address) 
                VALUES (?, ?, NOW(), ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'SISTEMA',
            $accion,
            $usuario_id,
            $_SESSION['user_name'] ?? 'Sistema',
            json_encode($detalles),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
    } catch (Exception $e) {
        error_log("Error registrando log local: " . $e->getMessage());
        return false;
    }
}


}
?>