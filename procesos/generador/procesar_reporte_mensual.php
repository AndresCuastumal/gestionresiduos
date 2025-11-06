<?php
session_start();
require_once '../../includes/conexion.php';
require_once 'reporte_mensual_controller.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $generador_id = $_GET['id'];
    $controller = new ReporteMensualController($conn);
    
    try {
        // ✅ AGREGAR LOGS PARA DEBUG
        error_log("=== INICIANDO PROCESO REPORTE MENSUAL ===");
        error_log("Generador ID: $generador_id");
        error_log("Año: " . ($_POST['anio'] ?? 'NO RECIBIDO'));
        
        // Verificar si se subió un archivo
        $archivo_subido = false;
        $nombre_archivo = null;
        
        if (isset($_FILES['soporte_pdf']) && $_FILES['soporte_pdf']['error'] === UPLOAD_ERR_OK) {
            $archivo_subido = true;
            error_log("Archivo PDF recibido: " . $_FILES['soporte_pdf']['name']);
        } else {
            error_log("No se recibió archivo PDF o hay error: " . ($_FILES['soporte_pdf']['error'] ?? 'NO FILE'));
        }
        
        // ✅ LLAMAR A LA FUNCIÓN ANTES DE PROCESAR (IMPORTANTE)
        actualizarEstadoRevisionAnual($conn, $generador_id, $_POST['anio']);
        
        // Guardar en sesión para el siguiente formulario
        $_SESSION['generador_id_reportando'] = $generador_id;
        $_SESSION['anio_reportando'] = $_POST['anio'];
        
        // Procesar reporte mensual
        $resultado = $controller->procesarReporte($generador_id, $_POST, $archivo_subido ? $_FILES['soporte_pdf'] : null);
        
        error_log("Procesamiento completado: " . ($resultado ? 'ÉXITO' : 'FALLÓ'));
                       
        // Redirigir al segundo formulario
        $_SESSION['mensaje_exito'] = "Reporte mensual guardado exitosamente";
        header("Location: ../../vistas/generador/reporte_adicional_view.php?id=" . $generador_id);
        exit();
        
    } catch (Exception $e) {       
        error_log("ERROR en procesar_reporte_mensual: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../../vistas/generador/reporte_mensual_view.php?id=" . $generador_id);
        exit();
    }
} else {    
    header("Location: ../../vistas/generador/listado_generadores_view.php");
    exit();
}

// FUNCIÓN CORREGIDA: Actualizar estado en revisiones_anuales - SOLO formulario_mensual
function actualizarEstadoRevisionAnual($conn, $generador_id, $anio) {
    try {
        error_log("Actualizando estado revisiones_anuales para: $generador_id, $anio");
        
        // Primero verificar si existe el registro
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
        $stmt_check->execute([$generador_id, $anio]);
        $existe_registro = $stmt_check->fetchColumn() > 0;
        
        if ($existe_registro) {
            // Obtener estado actual del formulario_mensual
            $stmt_get = $conn->prepare("SELECT formulario_mensual FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
            $stmt_get->execute([$generador_id, $anio]);
            $estado_actual = $stmt_get->fetch(PDO::FETCH_COLUMN);
            
            // Si el formulario mensual estaba rechazado, cambiar a pendiente
            $nuevo_estado_mensual = ($estado_actual == 'rechazado') ? 'pendiente' : 'pendiente';
            
            // ✅ CORRECCIÓN: Solo actualizar formulario_mensual, mantener los demás campos
            $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
                formulario_mensual = ?,
                observaciones_mensual = NULL,
                fecha_revision = NULL,
                revisado_por = NULL,
                estado_general = 'pendiente'
                WHERE generador_id = ? AND anio = ?");
            
            $resultado = $stmt_update->execute([$nuevo_estado_mensual, $generador_id, $anio]);
            error_log("Actualización formulario_mensual: " . ($resultado ? 'ÉXITO' : 'FALLÓ'));
            
        } else {
            // Insertar nuevo registro si no existe
            $stmt_insert = $conn->prepare("INSERT INTO revisiones_anuales 
                (generador_id, anio, formulario_mensual, estado_general)
                VALUES (?, ?, 'pendiente', 'pendiente')");
            
            $resultado = $stmt_insert->execute([$generador_id, $anio]);
            error_log("Inserción revisiones_anuales: " . ($resultado ? 'ÉXITO' : 'FALLÓ'));
        }
        
    } catch (Exception $e) {
        error_log("ERROR en actualizarEstadoRevisionAnual: " . $e->getMessage());
        // No lanzar excepción para no interrumpir el flujo principal
    }
}
?>