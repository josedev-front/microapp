<?php
// microservices/back-admision/data/sincronizar_usuarios_micro_soho.php

// Ignorar warnings de carga de módulos
error_reporting(E_ALL & ~E_WARNING);

// Incluir el init para tener acceso a las conexiones de BD
require_once __DIR__ . '/../init.php';

/**
 * Función para obtener conexión a la base de datos principal (microapps)
 */
function getMainDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $host = '127.0.0.1';
            $dbname = 'microapps';
            $username = 'root';
            $password = '';
            
            $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            echo "✅ Conexión a BD principal establecida\n";
        } catch (PDOException $e) {
            die("❌ Error conectando a BD principal: " . $e->getMessage() . "\n");
        }
    }
    
    return $db;
}

/**
 * Sincroniza usuarios de Micro&SOHO desde la BD principal a back_admision
 */
function sincronizarUsuariosMicroSOHO() {
    echo "🔄 INICIANDO SINCRONIZACIÓN DE USUARIOS MICRO&SOHO\n";
    echo "============================================\n";
    
    $db_back = getBackAdmisionDB();
    $db_main = getMainDB();
    
    if (!$db_back) {
        die("❌ Error: No se pudo conectar a la BD back_admision\n");
    }
    
    if (!$db_main) {
        die("❌ Error: No se pudo conectar a la BD principal microapps\n");
    }
    
    // Obtener usuarios de Micro&SOHO de la base principal
    try {
        $query = "
            SELECT 
                id as user_id,
                username,
                first_name,
                last_name, 
                email,
                role,
                work_area,
                is_active
            FROM core_customuser 
            WHERE (work_area LIKE '%Micro&SOHO%' OR work_area LIKE '%Micro&amp;SOHO%')
            AND is_active = 1
            ORDER BY role, first_name
        ";
        
        $stmt = $db_main->prepare($query);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Usuarios encontrados en Micro&SOHO: " . count($usuarios) . "\n";
        
        if (empty($usuarios)) {
            echo "⚠️ No se encontraron usuarios en el área Micro&SOHO\n";
            return;
        }
        
    } catch (PDOException $e) {
        die("❌ Error consultando usuarios: " . $e->getMessage() . "\n");
    }
    
    $insertados = 0;
    $actualizados = 0;
    $errores = 0;
    
    foreach ($usuarios as $usuario) {
        $user_id = $usuario['user_id'];
        $nombre_completo = trim($usuario['first_name'] . ' ' . $usuario['last_name']);
        $area = $usuario['work_area'];
        $role = $usuario['role'];
        
        echo "👤 Procesando: {$nombre_completo} (ID: {$user_id}, Rol: {$role})\n";
        
        try {
            // Verificar si ya existe en back_admision
            $stmt = $db_back->prepare("SELECT id, nombre_completo, area FROM horarios_usuarios WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $existe = $stmt->fetch();
            
            if (!$existe) {
                // Insertar nuevo usuario
                $stmt = $db_back->prepare("
                    INSERT INTO horarios_usuarios 
                    (user_id, user_external_id, nombre_completo, area, activo, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ");
                
                $result = $stmt->execute([
                    $user_id,
                    $user_id, // user_external_id mismo que user_id
                    $nombre_completo,
                    $area
                ]);
                
                if ($result) {
                    echo "   ✅ INSERTADO: {$nombre_completo} ({$role})\n";
                    $insertados++;
                    
                    // Insertar estado inicial por defecto
                    $stmt_estado = $db_back->prepare("
                        INSERT INTO estado_usuarios 
                        (user_id, user_external_id, estado, ultima_actualizacion) 
                        VALUES (?, ?, 'activo', NOW())
                    ");
                    $stmt_estado->execute([$user_id, $user_id]);
                    
                } else {
                    echo "   ❌ ERROR insertando: {$nombre_completo}\n";
                    $errores++;
                }
                
            } else {
                // Actualizar usuario existente
                $stmt = $db_back->prepare("
                    UPDATE horarios_usuarios 
                    SET nombre_completo = ?, area = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                
                $result = $stmt->execute([
                    $nombre_completo,
                    $area,
                    $user_id
                ]);
                
                if ($result) {
                    echo "   🔄 ACTUALIZADO: {$nombre_completo}\n";
                    $actualizados++;
                } else {
                    echo "   ⚠️ SIN CAMBIOS: {$nombre_completo}\n";
                }
            }
            
        } catch (PDOException $e) {
            echo "   💥 ERROR BD: " . $e->getMessage() . "\n";
            $errores++;
        }
    }
    
    // Resumen final
    echo "\n============================================\n";
    echo "🎉 SINCRONIZACIÓN COMPLETADA\n";
    echo "📈 RESUMEN:\n";
    echo "   ✅ Insertados: {$insertados}\n";
    echo "   🔄 Actualizados: {$actualizados}\n";
    echo "   ❌ Errores: {$errores}\n";
    echo "   📊 Total procesados: " . count($usuarios) . "\n";
    
    // Mostrar lista de usuarios sincronizados
    echo "\n👥 USUARIOS SINCRONIZADOS:\n";
    try {
        $stmt = $db_back->prepare("
            SELECT user_id, nombre_completo, area, activo 
            FROM horarios_usuarios 
            WHERE area LIKE '%Micro%SOHO%'
            ORDER BY nombre_completo
        ");
        $stmt->execute();
        $usuarios_sincronizados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usuarios_sincronizados as $usuario) {
            $estado = $usuario['activo'] ? '✅ Activo' : '❌ Inactivo';
            echo "   • {$usuario['nombre_completo']} (ID: {$usuario['user_id']}) - {$estado}\n";
        }
        
    } catch (PDOException $e) {
        echo "   ⚠️ Error mostrando usuarios sincronizados: " . $e->getMessage() . "\n";
    }
}

/**
 * Función para crear las tablas si no existen
 */
function verificarEstructuraTablas() {
    $db_back = getBackAdmisionDB();
    
    if (!$db_back) {
        die("❌ No se pudo conectar a la BD para verificar tablas\n");
    }
    
    echo "🔍 VERIFICANDO ESTRUCTURA DE TABLAS...\n";
    
    // Verificar tabla horarios_usuarios
    try {
        $stmt = $db_back->query("SELECT 1 FROM horarios_usuarios LIMIT 1");
        echo "✅ Tabla 'horarios_usuarios' existe\n";
    } catch (PDOException $e) {
        echo "❌ Tabla 'horarios_usuarios' no existe. Ejecuta el schema.sql primero.\n";
        return false;
    }
    
    // Verificar tabla estado_usuarios
    try {
        $stmt = $db_back->query("SELECT 1 FROM estado_usuarios LIMIT 1");
        echo "✅ Tabla 'estado_usuarios' existe\n";
    } catch (PDOException $e) {
        echo "❌ Tabla 'estado_usuarios' no existe. Ejecuta el schema.sql primero.\n";
        return false;
    }
    
    return true;
}

// EJECUCIÓN PRINCIPAL
echo "🚀 SCRIPT DE SINCRONIZACIÓN - MICRO&SOHO USERS\n";
echo "============================================\n";

// Verificar que las tablas existan
if (!verificarEstructuraTablas()) {
    die("❌ No se puede continuar. Faltan tablas necesarias.\n");
}

// Ejecutar sincronización
sincronizarUsuariosMicroSOHO();

echo "\n✨ Proceso finalizado. Ahora los usuarios deberían aparecer en el panel.\n";
?>