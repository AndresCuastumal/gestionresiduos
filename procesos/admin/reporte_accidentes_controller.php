<?php
class ReporteAccidentesController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Obtener datos del formulario de accidentes, capacitaciones y auditorías
    public function obtenerDatosReporteAdicional($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT * FROM reporte_anual_adicional 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Verificar si existe registro para este año
    public function existeRegistro($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM reporte_anual_adicional 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Obtener acciones preventivas como array
    public function obtenerAccionesPreventivas($datos) {
        if (empty($datos['acciones_preventivas'])) {
            return [];
        }
        
        $acciones = json_decode($datos['acciones_preventivas'], true);
        return is_array($acciones) ? $acciones : [];
    }
}
?>