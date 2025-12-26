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
        
        // Si el formulario está rechazado, validar intentos de corrección
        if ($estado_actual === 'rechazado') {
            error_log("Formulario en estado RECHAZADO - Validando intentos...");
            
            // Verificar si puede reenviar correcciones
            if (!$revisionController->puedeReenviarCorreccion($generador_id, $anio)) {
                $infoIntentos = $revisionController->obtenerInfoIntentos($generador_id, $anio);
                
                // ✅ CORREGIDO: No usar 'max_intentos_permitidos' que no existe
                // Definir el límite manualmente según tu configuración
                $limite_intentos = 1; // Cambia a 1 o 2 según necesites
                $mensaje_error = "Has alcanzado el número máximo de intentos de corrección permitidos. " .
                               "(" . ($infoIntentos['intentos_correccion'] ?? 0) . " de " . $limite_intentos . " intento(s) utilizado(s))";
                
                error_log("VALIDACIÓN FALLIDA: " . $mensaje_error);
                $_SESSION['error'] = $mensaje_error;
                header("Location: ../../vistas/generador/reporte_mensual_view.php?id=" . $generador_id);
                exit();
            }
            
            error_log("✅ VALIDACIÓN EXITOSA: Intentos disponibles");
        }
        
        // ✅ LLAMAR A LA FUNCIÓN ANTES DE PROCESAR
        actualizarEstadoRevisionAnual($conn, $generador_id, $_POST['anio']);
        
        // Guardar en sesión para el siguiente formulario
        $_SESSION['generador_id_reportando'] = $generador_id;
        $_SESSION['anio_reportando'] = $_POST['anio'];
        
        // ✅ Pasar el archivo solo si es válido
        $archivo_a_procesar = $archivo_valido ? $_FILES['soporte_pdf'] : null;
        
        // Procesar reporte mensual
        $resultado = $controller->procesarReporte($generador_id, $_POST, $archivo_a_procesar);
        
        error_log("Procesamiento completado: " . ($resultado ? 'ÉXITO' : 'FALLÓ'));
        
        // ✅ Preparar mensaje según el tipo de envío
        $mensaje_exito = "Reporte mensual guardado exitosamente";
        if ($estado_actual === 'rechazado') {
            $mensaje_exito = "Correcciones del reporte mensual guardadas y enviadas para revisión";
            
            // ✅ OPCIONAL: Log de corrección
            error_log("Corrección enviada - Generador: $generador_id, Año: $anio");
        }
                       
        // Redirigir al segundo formulario
        $_SESSION['mensaje_exito'] = $mensaje_exito;
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