<?php
class ReportController {
    private $db;
    
    public function __construct() {
        $this->db = getBackAdmisionDB();
    }
    
    /**
     * Obtener estadísticas diarias para el panel
     */
    public function getEstadisticasDiarias() {
        // Total de casos activos
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM casos WHERE estado != 'resuelto'");
        $total_casos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ejecutivos activos
        $stmt = $this->db->query("SELECT COUNT(DISTINCT user_id) as total FROM estado_usuarios WHERE estado = 'activo'");
        $ejecutivos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Promedio por ejecutivo
        $promedio = $ejecutivos_activos > 0 ? round($total_casos / $ejecutivos_activos, 1) : 0;
        
        // Obtener distribución para calcular métricas de balance
        $metricas = $this->getMetricasBalance();
        $casos_por_ejecutivo = array_column($metricas, 'casos_activos');
        
        // Calcular métricas de balance
        $desviacion_estandar = $this->calcularDesviacionEstandar($casos_por_ejecutivo);
        $coeficiente_variacion = $this->calcularCoeficienteVariacion($casos_por_ejecutivo);
        $indice_gini = $this->calcularIndiceGini($casos_por_ejecutivo);
        $indice_balance = $this->calcularIndiceBalance($casos_por_ejecutivo);
        
        return [
            'total_casos' => $total_casos,
            'ejecutivos_activos' => $ejecutivos_activos,
            'promedio_por_ejecutivo' => $promedio,
            'desviacion_estandar' => $desviacion_estandar,
            'coeficiente_variacion' => $coeficiente_variacion,
            'indice_gini' => $indice_gini,
            'indice_balance' => $indice_balance
        ];
    }
    
    /**
     * Obtener distribución de casos por estado
     */
    public function getDistribucionEstados() {
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
                SUM(CASE WHEN estado = 'en_espera' THEN 1 ELSE 0 END) as en_espera,
                SUM(CASE WHEN estado = 'resuelto' THEN 1 ELSE 0 END) as resueltos,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
            FROM casos
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        require_once __DIR__ . '/../lib/ExcelGenerator.php';
        
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
}
?>