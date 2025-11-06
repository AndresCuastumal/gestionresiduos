<?php
class ReporteMensualController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Obtener datos del generador
    public function obtenerDatosGenerador($generador_id) {
        $stmt = $this->conn->prepare("
            SELECT id, nom_generador, nit, dir_establecimiento, tipo_sujeto, nom_responsable
            FROM generador
            WHERE id = ?
        ");
        $stmt->execute([$generador_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener reportes mensuales existentes para un año específico
    public function obtenerReportesExistentes($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT rm.id_mes, rm.total_kg, m.nombre as nombre_mes
            FROM cantidad_x_mes rm
            JOIN mes m ON rm.id_mes = m.id
            WHERE rm.id_generador = ? AND rm.anio = ?
            ORDER BY rm.id_mes ASC
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Guardar o actualizar reporte mensual
    public function guardarReporteMensual($generador_id, $anio, $meses_data) {
        $this->conn->beginTransaction();
        
        try {
            // Eliminar reportes existentes para este año
            $stmt = $this->conn->prepare("
                DELETE FROM reportes_mensuales 
                WHERE generador_id = ? AND anio = ?
            ");
            $stmt->execute([$generador_id, $anio]);
            
            // Insertar nuevos reportes
            $stmt = $this->conn->prepare("
                INSERT INTO reportes_mensuales (generador_id, anio, id_mes, total_kg)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($meses_data as $id_mes => $total_kg) {
                if ($total_kg !== '' && $total_kg !== null) {
                    $stmt->execute([$generador_id, $anio, $id_mes, $total_kg]);
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al guardar reporte mensual: " . $e->getMessage());
            return false;
        }
    }
    
    // Calcular categoría del generador basado en el promedio móvil de 6 meses
    public function calcularCategoria($generador_id) {
        $stmt = $this->conn->prepare("
            SELECT total_kg 
            FROM reportes_mensuales 
            WHERE generador_id = ? 
            ORDER BY anio DESC, id_mes DESC 
            LIMIT 6
        ");
        $stmt->execute([$generador_id]);
        $ultimos_meses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($ultimos_meses) === 0) {
            return 'Micro generador';
        }
        
        $promedio = array_sum($ultimos_meses) / count($ultimos_meses);
        
        if ($promedio < 10) {
            return 'Micro generador';
        } elseif ($promedio < 100) {
            return 'Pequeño generador';
        } elseif ($promedio < 1000) {
            return 'Mediano generador';
        } else {
            return 'Gran generador';
        }
    }
    
    // Obtener el total anual de residuos
    public function obtenerTotalAnual($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT SUM(total_kg) as total_anual
            FROM reportes_mensuales
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_anual'] ?? 0;
    }
    
    // Verificar si ya existe reporte para un año específico
    public function existeReporteAnual($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM reportes_mensuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Obtener historial de reportes por años
    public function obtenerHistorialReportes($generador_id) {
        $stmt = $this->conn->prepare("
            SELECT anio, SUM(total_kg) as total_anual
            FROM reportes_mensuales
            WHERE generador_id = ?
            GROUP BY anio
            ORDER BY anio DESC
        ");
        $stmt->execute([$generador_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>