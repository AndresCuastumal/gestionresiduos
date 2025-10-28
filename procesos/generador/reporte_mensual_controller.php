<?php
//session_start();
require_once '../../includes/conexion.php';

class ReporteMensualController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // === MÉTODO MODIFICADO: Sin redirecciones ===
    public function verificarPermisos($generador_id) {
        if (!isset($_SESSION['usuario_id'])) {
            throw new Exception("No autenticado");
        }
        
        // Verificar que el usuario tiene acceso a este generador
        if ($_SESSION['usuario_rol'] !== 'admin') {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM usuario_generador 
                                       WHERE usuario_id = ? AND generador_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $generador_id]);
            $tiene_acceso = $stmt->fetchColumn();
            
            if (!$tiene_acceso) {
                throw new Exception("Acceso denegado a este generador");
            }
        }
        
        return true;
    }
    
    // Los demás métodos se mantienen igual...
    public function obtenerDatosGenerador($generador_id) {
        $stmt = $this->conn->prepare("SELECT * FROM generador WHERE id = ?");
        $stmt->execute([$generador_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerReportesExistentes($generador_id, $anio) {
        $stmt = $this->conn->prepare("SELECT cm.*, m.nombre as mes_nombre 
                                   FROM cantidad_x_mes cm 
                                   JOIN mes m ON cm.id_mes = m.id 
                                   WHERE cm.id_generador = ? AND cm.anio = ? 
                                   ORDER BY m.numero");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function procesarReporte($generador_id, $datos, $archivo = null) {
        try {
            // Verificar permisos antes de procesar
            $this->verificarPermisos($generador_id);
            
            $this->conn->beginTransaction();
            
            // ===== PROCESAR ARCHIVO PDF SOLO SI SE SUBIÓ UNO NUEVO =====
            $nombre_archivo = null;
            
            if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
                $nombre_archivo = $this->procesarArchivoPDF($archivo, $generador_id, $datos['anio']);
            } else {
                // Si no se subió archivo, mantener el existente
                $stmt = $this->conn->prepare("SELECT soporte_pdf FROM revisiones_anuales 
                                            WHERE generador_id = ? AND anio = ?");
                $stmt->execute([$generador_id, $datos['anio']]);
                $revision_existente = $stmt->fetch(PDO::FETCH_ASSOC);
                $nombre_archivo = $revision_existente['soporte_pdf'] ?? null;
            }
            // ===========================================================
            
            // Eliminar reportes existentes para este año
            $stmt = $this->conn->prepare("DELETE FROM cantidad_x_mes 
                                    WHERE id_generador = ? AND anio = ?");
            $stmt->execute([$generador_id, $datos['anio']]);
            
            // Insertar nuevos reportes
            foreach ($datos['meses'] as $id_mes => $total_kg) {
                if (!empty($total_kg) || $total_kg === '0') {
                    $stmt = $this->conn->prepare("INSERT INTO cantidad_x_mes 
                                            (id_generador, id_mes, anio, total_kg) 
                                            VALUES (?, ?, ?, ?)");
                    $stmt->execute([$generador_id, $id_mes, $datos['anio'], $total_kg]);
                }
            }
            
            // ===== ACTUALIZAR REVISIÓN ANUAL =====
            $this->actualizarRevisionAnual($generador_id, $datos['anio'], $nombre_archivo);
            // ============================================
            
            // Actualizar categoría del generador
            $this->actualizarCategoriaGenerador($generador_id, $datos['anio']);
            
            $this->conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            // Eliminar archivo si se subió pero hubo error
            if (isset($nombre_archivo) && $archivo && file_exists('../uploads/soportes_anuales/' . $nombre_archivo)) {
                unlink('../uploads/soportes_anuales/' . $nombre_archivo);
            }
            
            throw new Exception("Error al guardar reporte: " . $e->getMessage());
        }
    }
    
    
    // ===== MÉTODO: PROCESAR ARCHIVO PDF =====
    private function procesarArchivoPDF($archivo, $generador_id, $anio) {
        $directorio_uploads = '../uploads/soportes_anuales/';
        
        // Crear directorio si no existe
        if (!is_dir($directorio_uploads)) {
            mkdir($directorio_uploads, 0755, true);
        }
        
        // Validar que sea PDF
        $tipo_archivo = mime_content_type($archivo['tmp_name']);
        if ($tipo_archivo !== 'application/pdf') {
            throw new Exception("Solo se permiten archivos PDF");
        }
        
        // Validar tamaño (máximo 10MB)
        if ($archivo['size'] > 10 * 1024 * 1024) {
            throw new Exception("El archivo no puede ser mayor a 10MB");
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'soporte_' . $generador_id . '_' . $anio . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio_uploads . $nombre_archivo;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception("Error al guardar el archivo");
        }
        error_log("Archivo guardado en: " . $ruta_completa);
        error_log("Tamaño del archivo: " . filesize($ruta_completa) . " bytes");
        
        return $nombre_archivo;
    }
    
    private function actualizarRevisionAnual($generador_id, $anio, $nombre_archivo) {
        // SOLO ACTUALIZAR - si no existe, no pasa nada
        if ($nombre_archivo !== null) {
            $stmt = $this->conn->prepare("UPDATE revisiones_anuales 
                                SET soporte_pdf = ?, 
                                    formulario_mensual = 'pendiente',
                                    observaciones_mensual = NULL,
                                    fecha_revision = NULL,
                                    revisado_por = NULL,
                                    estado_general = 'pendiente'
                                WHERE generador_id = ? AND anio = ?");
            $stmt->execute([$nombre_archivo, $generador_id, $anio]);
        } else {
            $stmt = $this->conn->prepare("UPDATE revisiones_anuales 
                                SET formulario_mensual = 'pendiente',
                                    observaciones_mensual = NULL,
                                    fecha_revision = NULL,
                                    revisado_por = NULL,
                                    estado_general = 'pendiente'
                                WHERE generador_id = ? AND anio = ?");
            $stmt->execute([$generador_id, $anio]);
        }
        
        // Si no existe el registro, se creará cuando el usuario confirme todo
    }
    
    private function actualizarCategoriaGenerador($generador_id, $anio) {
        try {
            // Obtener todos los datos mensuales del año
            $stmt = $this->conn->prepare("SELECT id_mes, total_kg FROM cantidad_x_mes 
                                        WHERE id_generador = ? AND anio = ? 
                                        ORDER BY id_mes");
            $stmt->execute([$generador_id, $anio]);
            $datos_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear array con todos los meses (1-12) con valores 0 si no existen
            $meses_completos = array_fill(1, 12, 0);
            foreach ($datos_mensuales as $dato) {
                $meses_completos[$dato['id_mes']] = (float)$dato['total_kg'];
            }
            
            // Calcular promedios móviles de 6 meses para meses 7-12
            $medias_moviles = [];
            for ($mes = 7; $mes <= 12; $mes++) {
                $suma = 0;
                $meses_validos = 0;
                
                // Sumar los últimos 6 meses (desde mes-5 hasta mes)
                for ($i = $mes - 5; $i <= $mes; $i++) {
                    if ($i >= 1) { // Asegurar que no sea mes negativo
                        $suma += $meses_completos[$i];
                        $meses_validos++;
                    }
                }
                
                // Calcular media solo si hay meses válidos
                $media = ($meses_validos > 0) ? $suma / $meses_validos : 0;
                $medias_moviles[$mes] = $media;
            }
            
            // Calcular media total de los últimos 6 meses del año
            $suma_medias = array_sum($medias_moviles);
            $media_total = (count($medias_moviles) > 0) ? $suma_medias / count($medias_moviles) : 0;
            
            // Determinar categoría según los rangos especificados
            if ($media_total >= 1000) {
                $categoria = 'Gran generador';
            } elseif ($media_total >= 100 && $media_total < 1000) {
                $categoria = 'Mediano generador';
            } elseif ($media_total >= 10 && $media_total < 100) {
                $categoria = 'Pequeño generador';
            } else {
                $categoria = 'Micro generador';
            }
            
            // Guardar la categoría y la media total para referencia
            $stmt = $this->conn->prepare("UPDATE generador 
                                        SET categoria = ?, media_total = ?, fecha_actualizacion = NOW() 
                                        WHERE id = ?");
            $stmt->execute([$categoria, $media_total, $generador_id]);
            
            // Para debugging (opcional)
            error_log("Generador $generador_id - Media total: $media_total - Categoría: $categoria");
            
        } catch (PDOException $e) {
            error_log("Error al actualizar categoría: " . $e->getMessage());
            throw new Exception("Error al calcular categoría del generador");
        }
    }
}
?>