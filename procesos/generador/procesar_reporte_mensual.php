<?php
session_start();
require_once '../../includes/conexion.php';
require_once 'reporte_mensual_controller.php';
require_once '../admin/revisiones_controller.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $generador_id = $_GET['id'];
    $controller = new ReporteMensualController($conn);
    $revisionController = new RevisionesController($conn);
    
    try {
        error_log("=== INICIANDO PROCESO REPORTE MENSUAL ===");
        error_log("Generador ID: $generador_id");
        error_log("Año: " . ($_POST['anio'] ?? 'NO RECIBIDO'));
        
        // ✅ VALIDACIÓN DE ARCHIVO PDF (tamaño y tipo)
        $archivo_valido = false;
        $archivo_subido = false;
        $max_size_mb = 10;
        $max_size_bytes = $max_size_mb * 1024 * 1024;
        
        if (isset($_FILES['soporte_pdf']) && $_FILES['soporte_pdf']['error'] === UPLOAD_ERR_OK) {
            $archivo_subido = true;
            $archivo_info = $_FILES['soporte_pdf'];
            
            error_log("Archivo PDF recibido: " . $archivo_info['name']);
            error_log("Tamaño del archivo: " . $archivo_info['size'] . " bytes");
            
            // ✅ Validar tipo de archivo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $archivo_info['tmp_name']);
            finfo_close($finfo);
            
            error_log("Tipo MIME detectado: " . $mime_type);
            
            // Validar que sea PDF
            if (!in_array($mime_type, ['application/pdf', 'application/x-pdf'])) {
                throw new Exception("El archivo debe ser un PDF válido. Tipo detectado: " . $mime_type);
            }
            
            // ✅ Validar tamaño máximo (10 MB)
            if ($archivo_info['size'] > $max_size_bytes) {
                $size_mb = number_format($archivo_info['size'] / (1024 * 1024), 2);
                throw new Exception("El archivo PDF excede el tamaño máximo de {$max_size_mb} MB. Tamaño actual: {$size_mb} MB");
            }
            
            // ✅ Validar tamaño mínimo (opcional)
            if ($archivo_info['size'] == 0) {
                throw new Exception("El archivo PDF está vacío o no es válido.");
            }
            
            $archivo_valido = true;
            error_log("✅ Archivo PDF válido: tipo={$mime_type}, tamaño={$archivo_info['size']} bytes");
            
        } elseif ($_FILES['soporte_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Manejar otros errores de subida
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario.',
                UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
                UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal en el servidor.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
            ];
            
            $error_msg = $upload_errors[$_FILES['soporte_pdf']['error']] ?? 'Error desconocido al subir el archivo.';
            throw new Exception("Error en la subida del archivo: " . $error_msg);
        }
        
        // ✅ Continuar con la validación de intentos...
        $anio = $_POST['anio'];
        $estado_actual = $revisionController->obtenerEstadoFormulario($generador_id, $anio, 'mensual');
        
        error_log("Estado actual del formulario mensual: " . $estado_actual);
        
        // ... (el resto del código existente se mantiene igual)
        
        // ✅ Pasar el archivo solo si es válido
        $archivo_a_procesar = $archivo_valido ? $_FILES['soporte_pdf'] : null;
        
        // Procesar reporte mensual
        $resultado = $controller->procesarReporte($generador_id, $_POST, $archivo_a_procesar);
        
        // ... (continuar con el resto del código existente)
        
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