<?php
// app_core/controllers/ComunicadoController.php

class ComunicadoController {
    
    // Obtener comunicados globales pendientes de acuse para un usuario
    public function obtenerComunicadosGlobalesPendientes($usuario_id) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as creador_nombre
                FROM core_comunicado c
                INNER JOIN core_customuser u ON c.created_by_id = u.id
                WHERE c.tipo = 'global' 
                AND c.activo = 1
                AND c.created_at >= DATE_SUB(NOW(), INTERVAL c.dias_visibilidad DAY)
                AND c.id NOT IN (
                    SELECT comunicado_id 
                    FROM core_acuserecibo 
                    WHERE usuario_id = ?
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerComunicadosGlobalesPendientes: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener comunicados del área pendientes de acuse
    public function obtenerComunicadosAreaPendientes($usuario_id, $area_usuario) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as creador_nombre
                FROM core_comunicado c
                INNER JOIN core_customuser u ON c.created_by_id = u.id
                WHERE c.tipo = 'local' 
                AND c.area = ?
                AND c.activo = 1
                AND c.created_at >= DATE_SUB(NOW(), INTERVAL c.dias_visibilidad DAY)
                AND c.id NOT IN (
                    SELECT comunicado_id 
                    FROM core_acuserecibo 
                    WHERE usuario_id = ?
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$area_usuario, $usuario_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerComunicadosAreaPendientes: " . $e->getMessage());
            return [];
        }
    }
    public function obtenerComunicadoPorId($comunicado_id) {
    $pdo = conexion();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                CONCAT(u.first_name, ' ', u.last_name) as creador_nombre,
                CONCAT(d.first_name, ' ', d.last_name) as destinatario_nombre
            FROM core_comunicado c
            INNER JOIN core_customuser u ON c.created_by_id = u.id
            LEFT JOIN core_customuser d ON c.destinatario_id = d.id
            WHERE c.id = ?
        ");
        $stmt->execute([$comunicado_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error en obtenerComunicadoPorId: " . $e->getMessage());
        return null;
    }
}
    
    // Obtener comunicados personales pendientes de acuse
    public function obtenerComunicadosPersonalesPendientes($usuario_id) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as creador_nombre
                FROM core_comunicado c
                INNER JOIN core_customuser u ON c.created_by_id = u.id
                WHERE c.tipo = 'personal' 
                AND c.destinatario_id = ?
                AND c.activo = 1
                AND c.created_at >= DATE_SUB(NOW(), INTERVAL c.dias_visibilidad DAY)
                AND c.id NOT IN (
                    SELECT comunicado_id 
                    FROM core_acuserecibo 
                    WHERE usuario_id = ?
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$usuario_id, $usuario_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerComunicadosPersonalesPendientes: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener comunicados creados por el usuario
    public function obtenerMisComunicados($usuario_id) {
        $pdo = conexion();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    CONCAT(d.first_name, ' ', d.last_name) as destinatario_nombre,
                    (SELECT COUNT(*) FROM core_acuserecibo WHERE comunicado_id = c.id) as total_acuses,
                    CASE 
                        WHEN c.tipo = 'global' THEN (SELECT COUNT(*) FROM core_customuser WHERE is_active = 1)
                        WHEN c.tipo = 'local' THEN (SELECT COUNT(*) FROM core_customuser WHERE work_area = c.area AND is_active = 1)
                        WHEN c.tipo = 'personal' THEN 1
                        ELSE 0
                    END as total_destinatarios
                FROM core_comunicado c
                LEFT JOIN core_customuser d ON c.destinatario_id = d.id
                WHERE c.created_by_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerMisComunicados: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener historial de comunicados con acuses del usuario
    public function obtenerHistorialComunicados($usuario_id, $filtros = []) {
        $pdo = conexion();
        
        try {
            $sql = "
                SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as creador_nombre,
                    ar.fecha_acuse,
                    ar.ip_address
                FROM core_comunicado c
                INNER JOIN core_customuser u ON c.created_by_id = u.id
                LEFT JOIN core_acuserecibo ar ON c.id = ar.comunicado_id AND ar.usuario_id = ?
                WHERE (
                    (c.tipo = 'global') OR
                    (c.tipo = 'local' AND c.area = (SELECT work_area FROM core_customuser WHERE id = ?)) OR
                    (c.tipo = 'personal' AND c.destinatario_id = ?)
                )
                AND c.activo = 1
            ";
            
            $params = [$usuario_id, $usuario_id, $usuario_id];
            
            // Aplicar filtros
            if (!empty($filtros['q'])) {
                $sql .= " AND (c.titulo LIKE ? OR c.contenido LIKE ?)";
                $search_term = "%" . $filtros['q'] . "%";
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            if (!empty($filtros['tipo'])) {
                $sql .= " AND c.tipo = ?";
                $params[] = $filtros['tipo'];
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerHistorialComunicados: " . $e->getMessage());
            return [];
        }
    }
    
    // Crear nuevo comunicado
    public function crearComunicado($datos, $creador_id) {
        // Validar datos básicos
        if (empty($datos['titulo']) || empty($datos['contenido']) || empty($datos['tipo'])) {
            return ['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados'];
        }
        
        // Validar tipo de comunicado
        if (!in_array($datos['tipo'], ['global', 'local', 'personal'])) {
            return ['success' => false, 'message' => 'Tipo de comunicado no válido'];
        }
        
        // Validar destinatario para comunicados personales
        if ($datos['tipo'] === 'personal' && empty($datos['destinatario_id'])) {
            return ['success' => false, 'message' => 'Debe seleccionar un destinatario para comunicados personales'];
        }
        
        $pdo = conexion();
        
        try {
            // Obtener área del creador para comunicados locales
            $area_creador = null;
            if ($datos['tipo'] === 'local') {
                $stmt = $pdo->prepare("SELECT work_area FROM core_customuser WHERE id = ?");
                $stmt->execute([$creador_id]);
                $usuario = $stmt->fetch();
                $area_creador = $usuario['work_area'];
                
                if (empty($area_creador)) {
                    return ['success' => false, 'message' => 'No tienes un área asignada para crear comunicados locales'];
                }
            }
            
            // Procesar imagen si se subió
            $nombre_imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $_FILES['imagen'];
                
                // Validaciones de imagen
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $tamano_maximo = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($imagen['type'], $tipos_permitidos)) {
                    return ['success' => false, 'message' => 'Formato de imagen no permitido. Use JPG, PNG o GIF.'];
                }
                
                if ($imagen['size'] > $tamano_maximo) {
                    return ['success' => false, 'message' => 'La imagen es demasiado grande. Máximo 5MB.'];
                }
                
                // Crear directorio si no existe
                $directorio_imagenes = __DIR__ . '/../../public/assets/comunicados';
                if (!is_dir($directorio_imagenes)) {
                    mkdir($directorio_imagenes, 0755, true);
                }
                
                // Generar nombre único
                $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                $nombre_imagen = uniqid() . '_' . time() . '.' . $extension;
                $ruta_completa = $directorio_imagenes . '/' . $nombre_imagen;
                
                // Mover archivo
                if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
                    return ['success' => false, 'message' => 'Error al subir la imagen'];
                }
                
                // Convertir a ruta relativa para la base de datos
                $nombre_imagen = 'assets/comunicados/' . $nombre_imagen;
            }
            
            // Insertar comunicado
            $stmt = $pdo->prepare("
                INSERT INTO core_comunicado (
                    titulo, contenido, imagen, tipo, area, created_by_id, 
                    destinatario_id, requiere_acuse, activo, dias_visibilidad, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                trim($datos['titulo']),
                trim($datos['contenido']),
                $nombre_imagen,
                $datos['tipo'],
                $datos['tipo'] === 'local' ? $area_creador : null,
                $creador_id,
                $datos['tipo'] === 'personal' ? intval($datos['destinatario_id']) : null,
                isset($datos['requiere_acuse']) ? 1 : 0,
                isset($datos['activo']) ? 1 : 0,
                isset($datos['dias_visibilidad']) ? intval($datos['dias_visibilidad']) : 7
            ]);
            
            $comunicado_id = $pdo->lastInsertId();
            
            return [
                'success' => true, 
                'message' => 'Comunicado creado exitosamente',
                'comunicado_id' => $comunicado_id
            ];
            
        } catch (Exception $e) {
            error_log("Error en crearComunicado: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear el comunicado: ' . $e->getMessage()];
        }
    }
    
    // Registrar acuse de recibo
    public function registrarAcuseRecibo($comunicado_id, $usuario_id) {
        $pdo = conexion();
        
        try {
            // Verificar que el comunicado existe y está activo
            $stmt = $pdo->prepare("
                SELECT id, tipo, area, destinatario_id, activo, dias_visibilidad
                FROM core_comunicado 
                WHERE id = ? AND activo = 1 
                AND created_at >= DATE_SUB(NOW(), INTERVAL dias_visibilidad DAY)
            ");
            $stmt->execute([$comunicado_id]);
            $comunicado = $stmt->fetch();
            
            if (!$comunicado) {
                return ['success' => false, 'message' => 'Comunicado no encontrado o expirado'];
            }
            
            // Verificar que el usuario puede dar acuse a este comunicado
            $usuario_valido = false;
            
            switch ($comunicado['tipo']) {
                case 'global':
                    $usuario_valido = true;
                    break;
                    
                case 'local':
                    $stmt = $pdo->prepare("SELECT work_area FROM core_customuser WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario = $stmt->fetch();
                    $usuario_valido = ($usuario && $usuario['work_area'] === $comunicado['area']);
                    break;
                    
                case 'personal':
                    $usuario_valido = ($comunicado['destinatario_id'] == $usuario_id);
                    break;
            }
            
            if (!$usuario_valido) {
                return ['success' => false, 'message' => 'No tienes permiso para dar acuse a este comunicado'];
            }
            
            // Verificar que no haya dado acuse previamente
            $stmt = $pdo->prepare("SELECT id FROM core_acuserecibo WHERE comunicado_id = ? AND usuario_id = ?");
            $stmt->execute([$comunicado_id, $usuario_id]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Ya has dado acuse de recibo a este comunicado'];
            }
            
            // Registrar acuse
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
            
            $stmt = $pdo->prepare("
                INSERT INTO core_acuserecibo (comunicado_id, usuario_id, fecha_acuse, ip_address)
                VALUES (?, ?, NOW(), ?)
            ");
            $stmt->execute([$comunicado_id, $usuario_id, $ip_address]);
            
            return ['success' => true, 'message' => 'Acuse de recibo registrado correctamente'];
            
        } catch (Exception $e) {
            error_log("Error en registrarAcuseRecibo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el acuse: ' . $e->getMessage()];
        }
    }
    
    // Obtener lista de acuses para un comunicado - MÉTODO CORREGIDO
    // En ComunicadoController.php - función mejorada
public function obtenerAcusesComunicado($comunicado_id) {
    $pdo = conexion();
    
    try {
        error_log("=== INICIANDO OBTENER ACUSES PARA COMUNICADO: $comunicado_id ===");
        
        $stmt = $pdo->prepare("
            SELECT 
                ar.*,
                CONCAT(u.first_name, ' ', u.last_name) as usuario_nombre,
                u.employee_id,
                u.work_area,
                u.role,
                CONCAT(j.first_name, ' ', j.last_name) as jefe_nombre
            FROM core_acuserecibo ar
            INNER JOIN core_customuser u ON ar.usuario_id = u.id
            LEFT JOIN core_customuser j ON u.manager_id = j.id
            WHERE ar.comunicado_id = ?
            ORDER BY ar.fecha_acuse DESC
        ");
        $stmt->execute([$comunicado_id]);
        $resultados = $stmt->fetchAll();
        
        // Debug detallado
        error_log("Número de acuses encontrados: " . count($resultados));
        if (count($resultados) > 0) {
            error_log("Primer acuse: " . print_r($resultados[0], true));
        }
        
        return $resultados;
        
    } catch (Exception $e) {
        error_log("ERROR en obtenerAcusesComunicado: " . $e->getMessage());
        return [];
    }
}
    
    // Eliminar comunicado (soft delete)
    public function eliminarComunicado($comunicado_id, $usuario_id) {
        $pdo = conexion();
        
        try {
            // Verificar que el usuario es el creador del comunicado
            $stmt = $pdo->prepare("SELECT created_by_id FROM core_comunicado WHERE id = ?");
            $stmt->execute([$comunicado_id]);
            $comunicado = $stmt->fetch();
            
            if (!$comunicado) {
                return ['success' => false, 'message' => 'Comunicado no encontrado'];
            }
            
            if ($comunicado['created_by_id'] != $usuario_id) {
                return ['success' => false, 'message' => 'Solo puedes eliminar tus propios comunicados'];
            }
            
            // Soft delete - desactivar el comunicado
            $stmt = $pdo->prepare("UPDATE core_comunicado SET activo = 0 WHERE id = ?");
            $stmt->execute([$comunicado_id]);
            
            return ['success' => true, 'message' => 'Comunicado eliminado correctamente'];
            
        } catch (Exception $e) {
            error_log("Error en eliminarComunicado: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar el comunicado: ' . $e->getMessage()];
        }
    }
public function actualizarComunicado($comunicado_id, $datos, $usuario_id) {
    // Validaciones básicas
    if (empty($datos['titulo']) || empty($datos['contenido']) || empty($datos['tipo'])) {
        return ['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados'];
    }

    $pdo = conexion();
    
    try {
        // Verificar que el comunicado existe y pertenece al usuario
        $comunicado_actual = $this->obtenerComunicadoPorId($comunicado_id);
        if (!$comunicado_actual) {
            return ['success' => false, 'message' => 'Comunicado no encontrado'];
        }
        
        if ($comunicado_actual['created_by_id'] != $usuario_id) {
            return ['success' => false, 'message' => 'No tienes permisos para editar este comunicado'];
        }

        // Procesar imagen si se subió una nueva
        $nombre_imagen = $comunicado_actual['imagen'];
        
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            // Eliminar imagen anterior si existe
            if (!empty($nombre_imagen)) {
                $ruta_anterior = __DIR__ . '/../../public/' . $nombre_imagen;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }
            
            // Subir nueva imagen
            $imagen = $_FILES['imagen'];
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tamano_maximo = 5 * 1024 * 1024;
            
            if (!in_array($imagen['type'], $tipos_permitidos)) {
                return ['success' => false, 'message' => 'Formato de imagen no permitido'];
            }
            
            if ($imagen['size'] > $tamano_maximo) {
                return ['success' => false, 'message' => 'La imagen es demasiado grande. Máximo 5MB'];
            }
            
            $directorio_imagenes = __DIR__ . '/../../public/assets/comunicados';
            if (!is_dir($directorio_imagenes)) {
                mkdir($directorio_imagenes, 0755, true);
            }
            
            $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
            $nombre_imagen = uniqid() . '_' . time() . '.' . $extension;
            $ruta_completa = $directorio_imagenes . '/' . $nombre_imagen;
            
            if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
                return ['success' => false, 'message' => 'Error al subir la imagen'];
            }
            
            $nombre_imagen = 'assets/comunicados/' . $nombre_imagen;
        } elseif (isset($datos['eliminar_imagen']) && $datos['eliminar_imagen']) {
            // Eliminar imagen si se solicitó
            if (!empty($nombre_imagen)) {
                $ruta_anterior = __DIR__ . '/../../public/' . $nombre_imagen;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }
            $nombre_imagen = null;
        }

        // Actualizar comunicado
        $stmt = $pdo->prepare("
            UPDATE core_comunicado 
            SET titulo = ?, contenido = ?, imagen = ?, tipo = ?, 
                destinatario_id = ?, requiere_acuse = ?, activo = ?, dias_visibilidad = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $destinatario_id = ($datos['tipo'] === 'personal' && isset($datos['destinatario_id'])) 
            ? intval($datos['destinatario_id']) 
            : null;
            
        $stmt->execute([
            trim($datos['titulo']),
            trim($datos['contenido']),
            $nombre_imagen,
            $datos['tipo'],
            $destinatario_id,
            isset($datos['requiere_acuse']) ? 1 : 0,
            isset($datos['activo']) ? 1 : 0,
            isset($datos['dias_visibilidad']) ? intval($datos['dias_visibilidad']) : 7,
            $comunicado_id
        ]);
        
        return [
            'success' => true, 
            'message' => 'Comunicado actualizado exitosamente'
        ];
        
    } catch (Exception $e) {
        error_log("Error en actualizarComunicado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar el comunicado: ' . $e->getMessage()];
    }
}

// Obtener estadísticas del comunicado
public function obtenerEstadisticasComunicado($comunicado_id) {
    $pdo = conexion();
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_acuses 
            FROM core_acuserecibo 
            WHERE comunicado_id = ?
        ");
        $stmt->execute([$comunicado_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticasComunicado: " . $e->getMessage());
        return ['total_acuses' => 0];
    }
}

public function obtenerComunicadosGlobalesParaCarousel() {
    $pdo = conexion();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                CONCAT(u.first_name, ' ', u.last_name) as creador_nombre
            FROM core_comunicado c
            INNER JOIN core_customuser u ON c.created_by_id = u.id
            WHERE c.tipo = 'global' 
            AND c.activo = 1
            AND c.created_at >= DATE_SUB(NOW(), INTERVAL c.dias_visibilidad DAY)
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error en obtenerComunicadosGlobalesParaCarousel: " . $e->getMessage());
        return [];
    }
}

}


