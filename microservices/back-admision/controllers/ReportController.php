<?php
// microservices/back-admision/controllers/ReportController.php
require_once __DIR__ . '/../lib/ExcelGenerator.php';

class ReportController {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database_back_admision.php';
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Obtener estadísticas diarias para el panel - ACTUALIZADO CON PARÁMETROS
     */
      public function getEstadisticasDiarias($fecha_desde = null, $fecha_hasta = null) {
        try {
            // Si no se proporcionan fechas, usar hoy
            if (!$fecha_desde) $fecha_desde = date('Y-m-d');
            if (!$fecha_hasta) $fecha_hasta = date('Y-m-d');
            
            // Total casos en el período
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM casos 
                WHERE DATE(fecha_ingreso) BETWEEN ? AND ?
                AND area_ejecutivo LIKE '%Micro&SOHO%'
            ");
            $stmt->execute([$fecha_desde, $fecha_hasta]);
            $total_casos_periodo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ejecutivos activos
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_id) as total 
                FROM estado_usuarios 
                WHERE estado = 'activo'
                AND user_id IN (SELECT user_id FROM horarios_usuarios WHERE area = 'Depto Micro&SOHO' AND activo = 1)
            ");
            $stmt->execute();
            $ejecutivos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Promedio por ejecutivo
            $promedio_por_ejecutivo = $ejecutivos_activos > 0 ? round($total_casos_periodo / $ejecutivos_activos, 1) : 0;
            
            // Obtener distribución de casos por ejecutivo para calcular métricas de balance
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as casos_periodo
                FROM casos c
                INNER JOIN horarios_usuarios hu ON c.analista_id = hu.user_id
                WHERE DATE(c.fecha_ingreso) BETWEEN ? AND ? 
                AND hu.area = 'Depto Micro&SOHO'
                GROUP BY c.analista_id
            ");
            $stmt->execute([$fecha_desde, $fecha_hasta]);
            $distribucion = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Calcular métricas de balance
            $metricas_balance = $this->calcularMetricasBalance($distribucion);
            
            $resultado = array_merge([
                'total_casos_hoy' => $total_casos_periodo,
                'ejecutivos_activos' => $ejecutivos_activos,
                'promedio_por_ejecutivo' => $promedio_por_ejecutivo,
                'indice_balance' => $metricas_balance['indice_balance'],
                'desviacion_estandar' => $metricas_balance['desviacion_estandar'],
                'coeficiente_variacion' => $metricas_balance['coeficiente_variacion'],
                'indice_gini' => $metricas_balance['indice_gini']
            ], $metricas_balance);
            
            error_log("✅ ReportController: Estadísticas para {$fecha_desde} a {$fecha_hasta}: " . $total_casos_periodo . " casos");
            return $resultado;
            
        } catch (Exception $e) {
            error_log("❌ Error en ReportController::getEstadisticasDiarias: " . $e->getMessage());
            return [
                'total_casos_hoy' => 0,
                'ejecutivos_activos' => 0,
                'promedio_por_ejecutivo' => 0,
                'indice_balance' => 0,
                'desviacion_estandar' => 0,
                'coeficiente_variacion' => 0,
                'indice_gini' => 0
            ];
        }
    }
    
    /**
     * Obtener distribución de estados por fecha - ACTUALIZADO CON PARÁMETROS
     */
    public function getDistribucionEstadosPorFecha($fecha = null) {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN estado = 'en_curso' THEN id END) as en_curso,
                COUNT(CASE WHEN estado = 'en_espera' THEN id END) as en_espera,
                COUNT(CASE WHEN estado = 'resuelto' THEN id END) as resuelto,
                COUNT(CASE WHEN estado = 'cancelado' THEN id END) as cancelado
            FROM casos 
            WHERE DATE(fecha_ingreso) = ?
            AND area_ejecutivo LIKE '%Micro&SOHO%'
        ");
        
        $stmt->execute([$fecha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que todos los estados existan
        return array_merge([
            'en_curso' => 0,
            'en_espera' => 0,
            'resuelto' => 0,
            'cancelado' => 0
        ], $result ?: []);
    }
    
    /**
     * Calcular métricas de balance estadístico
     */
    private function calcularMetricasBalance($distribucion) {
        if (empty($distribucion)) {
            return [
                'desviacion_estandar' => 0,
                'coeficiente_variacion' => 0,
                'indice_gini' => 0,
                'indice_balance' => 100
            ];
        }
        
        $n = count($distribucion);
        $media = array_sum($distribucion) / $n;
        
        // Desviación estándar
        $suma_cuadrados = 0;
        foreach ($distribucion as $valor) {
            $suma_cuadrados += pow($valor - $media, 2);
        }
        $desviacion_estandar = sqrt($suma_cuadrados / $n);
        
        // Coeficiente de variación
        $coeficiente_variacion = $media > 0 ? ($desviacion_estandar / $media) * 100 : 0;
        
        // Índice de Gini (simplificado)
        sort($distribucion);
        $suma_diferencias = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $suma_diferencias += abs($distribucion[$i] - $distribucion[$j]);
            }
        }
        $indice_gini = $media > 0 ? ($suma_diferencias / (2 * $n * $n * $media)) : 0;
        
        // Índice de balance (0-100%, mayor es mejor)
        $indice_balance = max(0, 100 - ($coeficiente_variacion / 2));
        
        return [
            'desviacion_estandar' => round($desviacion_estandar, 2),
            'coeficiente_variacion' => round($coeficiente_variacion, 1),
            'indice_gini' => round($indice_gini, 3),
            'indice_balance' => round($indice_balance, 1)
        ];
    }
    
    /**
     * Método anterior para mantener compatibilidad
     */
    public function getDistribucionEstados() {
        return $this->getDistribucionEstadosPorFecha(date('Y-m-d'));
    }
    
    /**
     * Obtener métricas detalladas de balance
     */
    public function getMetricasBalance() {
        $stmt = $this->db->prepare("
            SELECT 
                hu.user_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                COUNT(c.id) as casos_activos,
                SUM(CASE WHEN c.estado = 'resuelto' THEN 1 ELSE 0 END) as casos_resueltos,
                SUM(CASE WHEN c.estado = 'en_espera' THEN 1 ELSE 0 END) as casos_espera,
                MAX(c.fecha_actualizacion) as ultima_actividad,
                eu.ultima_actualizacion
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id AND c.estado != 'resuelto'
            WHERE hu.activo = 1
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
            ORDER BY casos_activos ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener métricas de balance DIARIO para el panel de asignaciones - ACTUALIZADO CON PARÁMETROS
     */
    public function getMetricasBalanceDiario($fecha_desde = null, $fecha_hasta = null) {
        // Si no se proporcionan fechas, usar hoy
        if (!$fecha_desde) $fecha_desde = date('Y-m-d');
        if (!$fecha_hasta) $fecha_hasta = date('Y-m-d');
        
        $stmt = $this->db->prepare("
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
        ");
        
        $stmt->execute([$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Asegurar que todos los campos existan y renombrar casos_periodo a casos_hoy para compatibilidad
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
        
        return $resultados;
    }
    
    /**
     * Generar reporte Excel
     */
    public function generarReporteExcel($filtros = []) {
        $excelGenerator = new ExcelGenerator();
        return $excelGenerator->generarReporteAsignaciones($filtros);
    }
    
    /**
     * Obtener logs del sistema
     */
    public function getLogsSistema($filtros = []) {
        $sql = "SELECT * FROM logs_sistema WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(fecha_accion) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(fecha_accion) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['sr_hijo'])) {
            $sql .= " AND sr_hijo = ?";
            $params[] = $filtros['sr_hijo'];
        }
        
        if (!empty($filtros['usuario_id'])) {
            $sql .= " AND usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        $sql .= " ORDER BY fecha_accion DESC LIMIT 1000";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas por rango de fechas
     */
    public function getEstadisticasPorRango($fecha_desde, $fecha_hasta) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(fecha_ingreso) as fecha,
                COUNT(*) as total_casos,
                COUNT(CASE WHEN estado = 'en_curso' THEN id END) as en_curso,
                COUNT(CASE WHEN estado = 'en_espera' THEN id END) as en_espera,
                COUNT(CASE WHEN estado = 'resuelto' THEN id END) as resuelto,
                COUNT(CASE WHEN estado = 'cancelado' THEN id END) as cancelado
            FROM casos 
            WHERE DATE(fecha_ingreso) BETWEEN ? AND ?
            AND area_ejecutivo LIKE '%Micro&SOHO%'
            GROUP BY DATE(fecha_ingreso)
            ORDER BY fecha DESC
        ");
        
        $stmt->execute([$fecha_desde, $fecha_hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
 * Obtener lista de SRs únicas para el filtro
 */
public function getSRsUnicas($fecha_desde, $fecha_hasta) {
    try {
        $stmt = $this->db->prepare("
            SELECT DISTINCT sr_hijo 
            FROM logs_sistema 
            WHERE DATE(fecha_accion) BETWEEN ? AND ?
            ORDER BY sr_hijo
            LIMIT 100
        ");
        
        $stmt->execute([$fecha_desde, $fecha_hasta]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        error_log("Error obteniendo SRs únicas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener detalles específicos de un log
 */
public function getDetallesLog($log_id) {
    try {
        $stmt = $this->db->prepare("
            SELECT * FROM logs_sistema 
            WHERE id = ?
        ");
        
        $stmt->execute([$log_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error obteniendo detalles del log: " . $e->getMessage());
        return null;
    }
}
    /**
     * Obtener distribución por ejecutivo en rango de fechas
     */
    public function getDistribucionPorEjecutivo($fecha_desde, $fecha_hasta) {
        $stmt = $this->db->prepare("
            SELECT 
                hu.nombre_completo,
                COUNT(c.id) as total_casos,
                COUNT(CASE WHEN c.estado = 'en_curso' THEN c.id END) as en_curso,
                COUNT(CASE WHEN c.estado = 'en_espera' THEN c.id END) as en_espera,
                COUNT(CASE WHEN c.estado = 'resuelto' THEN c.id END) as resuelto,
                COUNT(CASE WHEN c.estado = 'cancelado' THEN c.id END) as cancelado
            FROM casos c
            INNER JOIN horarios_usuarios hu ON c.analista_id = hu.user_id
            WHERE DATE(c.fecha_ingreso) BETWEEN ? AND ?
            AND hu.area = 'Depto Micro&SOHO'
            GROUP BY hu.user_id, hu.nombre_completo
            ORDER BY total_casos DESC
        ");
        
        $stmt->execute([$fecha_desde, $fecha_hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>