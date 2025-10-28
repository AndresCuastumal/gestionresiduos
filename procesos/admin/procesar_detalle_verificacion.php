<?php
require_once '../../includes/conexion.php';

// Verificar sesión y permisos de admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

class ProcesarDetalleVerificacion {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Obtener todas las revisiones pendientes
    public function obtenerRevisionesPendientes() {
        $stmt = $this->conn->prepare("
            SELECT r.*, g.nom_generador, g.dir_establecimiento, g.nom_responsable
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            WHERE r.estado_general = 'pendiente'
            ORDER BY r.fecha_creacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener revisión específica
    public function obtenerRevision($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT r.*, g.nom_generador, g.dir_establecimiento, g.nom_responsable,
                   u.email as nombre_revisor
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            LEFT JOIN usuarios u ON r.revisado_por = u.id
            WHERE r.generador_id = ? AND r.anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Actualizar estado de revisión del formulario mensual
    public function actualizarRevisionMensual($data) {
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_mensual = ?, 
                observaciones_mensual = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        return $stmt->execute([
            $data['formulario_mensual'],
            $data['observaciones_mensual'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
    }
    
    // Actualizar estado de revisión del formulario de contingencias
    public function actualizarRevisionContingencias($data) {
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_contingencias = ?, 
                observaciones_contingencias = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        return $stmt->execute([
            $data['formulario_contingencias'],
            $data['observaciones_contingencias'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
    }
    
    // Actualizar estado de revisión del formulario de accidentes
    public function actualizarRevisionAccidentes($data) {
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_accidentes = ?, 
                observaciones_accidentes = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        return $stmt->execute([
            $data['formulario_accidentes'],
            $data['observaciones_accidentes'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
    }
    
    // Verificar si todos los formularios están aprobados
    public function verificarFormulariosCompletos($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT formulario_mensual, formulario_contingencias, formulario_accidentes
            FROM revisiones_anuales
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($revision['formulario_mensual'] === 'aprobado' &&
                $revision['formulario_contingencias'] === 'aprobado' &&
                $revision['formulario_accidentes'] === 'aprobado');
    }
    
    // Obtener el estado general de los formularios
    public function obtenerEstadoFormularios($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT formulario_mensual, formulario_contingencias, formulario_accidentes
            FROM revisiones_anuales
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Procesar la revisión desde el formulario
    public function procesarRevision($postData) {
        $generador_id = $postData['generador_id'];
        $anio = $postData['anio'];
        $tipo_formulario = $postData['tipo_formulario'];
        $estado = $postData['estado'];
        $observaciones = $postData['observaciones'] ?? '';
        
        $data = [
            'revisado_por' => $_SESSION['usuario_id'],
            'generador_id' => $generador_id,
            'anio' => $anio,
            'estado_general' => 'completo'
        ];
        
        switch ($tipo_formulario) {
            case 'mensual':
                $data['formulario_mensual'] = $estado;
                $data['observaciones_mensual'] = $observaciones;
                $success = $this->actualizarRevisionMensual($data);
                break;
                
            case 'contingencias':
                $data['formulario_contingencias'] = $estado;
                $data['observaciones_contingencias'] = $observaciones;
                $success = $this->actualizarRevisionContingencias($data);
                break;
                
            case 'accidentes':
                $data['formulario_accidentes'] = $estado;
                $data['observaciones_accidentes'] = $observaciones;
                $success = $this->actualizarRevisionAccidentes($data);
                break;
                
            default:
                return ['success' => false, 'message' => 'Tipo de formulario no válido'];
        }
        
        if ($success) {
            $message = "Revisión del formulario " . ucfirst($tipo_formulario) . " actualizada correctamente";
            
            // Verificar si todos los formularios están aprobados
            if ($this->verificarFormulariosCompletos($generador_id, $anio)) {
                $message .= ". ¡Todos los formularios están aprobados! Se procederá a enviar el certificado.";
                // Aquí se llamaría a la función para generar y enviar el certificado
                // $this->generarYEnviarCertificado($generador_id, $anio);
            }
            
            return ['success' => true, 'message' => $message];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la revisión'];
        }
    }
}

// Procesar solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $procesador = new ProcesarDetalleVerificacion($conn);
    $resultado = $procesador->procesarRevision($_POST);
    
    if ($resultado['success']) {
        $_SESSION['success'] = $resultado['message'];
    } else {
        $_SESSION['error'] = $resultado['message'];
    }
    
    header("Location: ../admin/listado_revisiones_view.php");
    exit();
}

// Para uso en otras vistas, podemos crear una instancia global
function obtenerProcesadorRevisiones() {
    global $conn;
    return new ProcesarDetalleVerificacion($conn);
}
?>