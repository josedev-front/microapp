<?php
// microservices/back-admision/controllers/ReportController.php
require_once __DIR__ . '/../lib/ExcelGenerator.php';

class ReportController {
    private $db;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Obtener estadísticas diarias para el panel
     */
    public function getEstadisticasDiarias() {
        // Total casos hoy
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM casos 
            WHERE DATE(fecha_ingreso) = CURDATE()
        ");
        $stmt->execute();
        $total_casos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ejecutivos activos
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT user_id) as total 
            FROM estado_usuarios 
            WHERE estado = 'activo'
        ");
        $stmt->execute();
        $ejecutivos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Promedio por ejecutivo
        $promedio_por_ejecutivo = $ejecutivos_activos > 0 ? round($total_casos_hoy / $ejecutivos_activos, 1) : 0;
        
        // Obtener distribución de casos hoy por ejecutivo para calcular métricas de balance
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as casos_hoy
            FROM casos c
            INNER JOIN horarios_usuarios hu ON c.analista_id = hu.user_id
            WHERE DATE(c.fecha_ingreso) = CURDATE() AND hu.area = 'Depto Micro&SOHO'
            GROUP BY c.analista_id
        ");
        $stmt->execute();
        $distribucion = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Calcular métricas de balance
        $metricas_balance = $this->calcularMetricasBalance($distribucion);
        
        return array_merge([
            'total_casos_hoy' => $total_casos_hoy,
            'ejecutivos_activos' => $ejecutivos_activos,
            'promedio_por_ejecutivo' => $promedio_por_ejecutivo,
            'indice_balance' => $metricas_balance['indice_balance'],
            'desviacion_estandar' => $metricas_balance['desviacion_estandar'],
            'coeficiente_variacion' => $metricas_balance['coeficiente_variacion'],
            'indice_gini' => $metricas_balance['indice_gini']
        ], $metricas_balance);
    }
    
    /**
     * Obtener distribución de estados por fecha
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
     * Obtener métricas de balance DIARIO para el panel de asignaciones
     */
    public function getMetricasBalanceDiario() {
        $stmt = $this->db->prepare("
            SELECT 
                hu.user_id,
                hu.nombre_completo,
                hu.area,
                eu.estado,
                eu.ultima_actualizacion,
                -- Casos ingresados hoy
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() THEN c.id END) as casos_hoy,
                -- Casos activos totales
                COUNT(CASE WHEN c.estado != 'resuelto' THEN c.id END) as casos_activos,
                -- Distribución por estado (hoy)
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'en_curso' THEN c.id END) as en_curso,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'en_espera' THEN c.id END) as en_espera,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'resuelto' THEN c.id END) as resuelto,
                COUNT(CASE WHEN DATE(c.fecha_ingreso) = CURDATE() AND c.estado = 'cancelado' THEN c.id END) as cancelado
            FROM horarios_usuarios hu
            LEFT JOIN estado_usuarios eu ON hu.user_id = eu.user_id
            LEFT JOIN casos c ON hu.user_id = c.analista_id
            WHERE hu.activo = 1 AND hu.area = 'Depto Micro&SOHO'
            GROUP BY hu.user_id, hu.nombre_completo, hu.area, eu.estado, eu.ultima_actualizacion
            ORDER BY casos_hoy ASC, hu.nombre_completo
        ");
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Asegurar que todos los campos existan
        foreach ($resultados as &$fila) {
            $fila = array_merge([
                'casos_hoy' => 0,
                'casos_activos' => 0,
                'en_curso' => 0,
                'en_espera' => 0,
                'resuelto' => 0,
                'cancelado' => 0
            ], $fila);
        }
        
        return $resultados;
    }
    
    /**
     * Calcular desviación estándar
     */
    private function calcularDesviacionEstandar($array) {
        if (count($array) === 0) return 0;
        
        $n = count($array);
        $mean = array_sum($array) / $n;
        $carry = 0.0;
        
        foreach ($array as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        }
        
        return sqrt($carry / $n);
    }
    
    /**
     * Calcular coeficiente de variación
     */
    private function calcularCoeficienteVariacion($array) {
        if (count($array) === 0) return 0;
        
        $mean = array_sum($array) / count($array);
        if ($mean == 0) return 0;
        
        $desviacion = $this->calcularDesviacionEstandar($array);
        return ($desviacion / $mean) * 100;
    }
    
    /**
     * Calcular índice de Gini
     */
    private function calcularIndiceGini($array) {
        if (count($array) === 0) return 0;
        
        sort($array);
        $n = count($array);
        $sum = array_sum($array);
        
        if ($sum == 0) return 0;
        
        $sumAbsoluteDifferences = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $sumAbsoluteDifferences += abs($array[$i] - $array[$j]);
            }
        }
        
        return $sumAbsoluteDifferences / (2 * $n * $sum);
    }
    
    /**
     * Calcular índice de balance (0-100%)
     */
    private function calcularIndiceBalance($array) {
        if (count($array) === 0) return 100;
        
        $max = max($array);
        if ($max == 0) return 100;
        
        $min = min($array);
        $ratio = $min / $max;
        
        return round($ratio * 100);
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
            GROUP BY DATE(fecha_ingreso)
            ORDER BY fecha DESC
        ");
        
        $stmt->execute([$fecha_desde, $fecha_hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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