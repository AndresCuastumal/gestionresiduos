<?php
session_start();
require_once '../../includes/conexion.php';
require_once '../admin/revisiones_controller.php';

// Activar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Mejor no mostrar errores en producción
ini_set('log_errors', 1);

// Verificar que viene del formulario adicional
if (!isset($_SESSION['generador_id_reportando'])) {
    header("Location: ../../vistas/generador/listado_generadores_view.php");
    exit();
}

$generador_id = $_SESSION['generador_id_reportando'];
$anio = $_SESSION['anio_reportando'];

// ✅ Crear controlador de revisiones
$revisionController = new RevisionesController($conn);

try {
    // ✅ VALIDACIÓN DE INTENTOS ANTES DE PROCESAR
    $estado_actual = $revisionController->obtenerEstadoFormulario($generador_id, $anio, 'accidentes');
    
    error_log("=== PROCESANDO REPORTE ADICIONAL ===");
    error_log("Generador ID: $generador_id, Año: $anio");
    error_log("Estado actual del formulario accidentes: " . $estado_actual);
    
    // Si el formulario está rechazado, validar intentos de corrección
    if ($estado_actual === 'rechazado') {
        error_log("Formulario en estado RECHAZADO - Validando intentos...");
        
        // Verificar si puede reenviar correcciones
        if (!$revisionController->puedeReenviarCorreccion($generador_id, $anio)) {
            $infoIntentos = $revisionController->obtenerInfoIntentos($generador_id, $anio);
            
            // ✅ CORREGIDO: No usar 'max_intentos_permitidos' que no existe
            $limite_intentos = 2;
            $mensaje_error = "Has alcanzado el número máximo de intentos de corrección permitidos. " .
                           "(" . $infoIntentos['intentos_correccion'] . " de " . $limite_intentos . " intento(s) utilizado(s))";
            
            error_log("VALIDACIÓN FALLIDA: " . $mensaje_error);
            $_SESSION['error'] = $mensaje_error;
            header("Location: ../../vistas/generador/reporte_adicional_view.php?id=" . $generador_id);
            exit();
        }
        
        error_log("✅ VALIDACIÓN EXITOSA: Intentos disponibles para corrección");
    }
    
    // ✅ FUNCIÓN MEJORADA PARA PROCESAR ARCHIVOS CON VALIDACIÓN DE TAMAÑO
    function procesarArchivo($archivo, $directorio, $prefijo, $generador_id, $anio, $campo_existente) {
        global $conn;
        
        // Si no se subió archivo, mantener el existente
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            if ($archivo['error'] !== UPLOAD_ERR_NO_FILE) {
                // Manejar errores de subida
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario.',
                    UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
                    UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal en el servidor.',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
                ];
                
                $error_msg = $upload_errors[$archivo['error']] ?? 'Error desconocido al subir el archivo.';
                throw new Exception("Error en la subida del archivo: " . $error_msg);
            }
            
            // Si no hay archivo nuevo, mantener el existente
            $stmt = $conn->prepare("SELECT $campo_existente FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
            $stmt->execute([$generador_id, $anio]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $existente[$campo_existente] ?? null;
        }
        
        // ✅ VALIDACIÓN DE TIPO DE ARCHIVO
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        error_log("Validando archivo: " . $archivo['name'] . ", tipo: " . $mime_type);
        
        if (!in_array($mime_type, ['application/pdf', 'application/x-pdf'])) {
            throw new Exception("El archivo '" . $archivo['name'] . "' debe ser un PDF válido.");
        }
        
        // ✅ VALIDACIÓN DE TAMAÑO (10 MB máximo)
        $max_size_bytes = 10 * 1024 * 1024; // 10 MB
        if ($archivo['size'] > $max_size_bytes) {
            $size_mb = number_format($archivo['size'] / (1024 * 1024), 2);
            throw new Exception("El archivo '" . $archivo['name'] . "' excede el tamaño máximo de 10 MB. Tamaño actual: {$size_mb} MB");
        }
        
        // ✅ VALIDACIÓN DE TAMAÑO MÍNIMO (opcional)
        if ($archivo['size'] == 0) {
            throw new Exception("El archivo '" . $archivo['name'] . "' está vacío o no es válido.");
        }
        
        // Crear directorio si no existe
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = $prefijo . $generador_id . '_' . $anio . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            $error = error_get_last();
            throw new Exception("Error al guardar el archivo '" . $archivo['name'] . "': " . ($error['message'] ?? 'Error desconocido'));
        }
        
        error_log("✅ Archivo guardado: " . $nombre_archivo . " (" . $archivo['size'] . " bytes)");
        return $nombre_archivo;
    }
    
    // ✅ Procesar cada archivo con validación
    $directorio = __DIR__ . '/soportes_generador/';
    
    $archivo_cronograma = procesarArchivo(
        $_FILES['archivo_cronograma'], 
        $directorio, 
        'cronograma_', 
        $generador_id, 
        $anio, 
        'archivo_cronograma'
    );
    
    $archivo_soportes = procesarArchivo(
        $_FILES['archivo_soportes_capacitaciones'], 
        $directorio, 
        'soportes_capacitaciones_', 
        $generador_id, 
        $anio, 
        'archivo_soportes_capacitaciones'
    );
    
    $archivo_resultados_auditorias_internas = procesarArchivo(
        $_FILES['archivo_resultados_auditorias_internas'], 
        $directorio, 
        'resultados_auditorias_internas_', 
        $generador_id, 
        $anio, 
        'archivo_resultados_auditorias_internas'
    );

    $archivo_resultados_auditorias_externas = procesarArchivo(
        $_FILES['archivo_resultados_auditorias_externas'], 
        $directorio, 
        'resultados_auditorias_externas_', 
        $generador_id, 
        $anio, 
        'archivo_resultados_auditorias_externas'
    );
    
    $archivo_plan_mejoramiento_interno = procesarArchivo(
        $_FILES['archivo_plan_mejoramiento_interno'], 
        $directorio, 
        'plan_mejoramiento_interno_', 
        $generador_id, 
        $anio, 
        'archivo_plan_mejoramiento_interno'
    );
    

    
    
    // ✅ Resto del código (se mantiene igual)...
    $acciones_preventivas = isset($_POST['acciones_preventivas']) ? $_POST['acciones_preventivas'] : [];
    $acciones_json = !empty($acciones_preventivas) ? json_encode($acciones_preventivas) : '[]';
    
    $num_accidentes = isset($_POST['num_accidentes']) ? $_POST['num_accidentes'] : 0;
    $num_auditorias_externas = isset($_POST['num_auditorias_externas']) && $_POST['num_auditorias_externas'] !== '' 
    ? (int)$_POST['num_auditorias_externas'] 
    : 0;
    $otra_accion_preventiva = isset($_POST['otra_accion_preventiva']) ? trim($_POST['otra_accion_preventiva']) : null;
    
    // Si se seleccionó "otra" pero no se especificó, mantener el valor existente
    if (in_array('otra', $acciones_preventivas) && empty($otra_accion_preventiva)) {
        $stmt_existente = $conn->prepare("SELECT otra_accion_preventiva FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
        $stmt_existente->execute([$generador_id, $anio]);
        $existente = $stmt_existente->fetch(PDO::FETCH_ASSOC);
        $otra_accion_preventiva = $existente['otra_accion_preventiva'] ?? null;
    }
    
    if (!in_array('otra', $acciones_preventivas)) {
        $otra_accion_preventiva = null;
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    try {
        // Verificar si ya existe un registro
        $stmt_check = $conn->prepare("SELECT id FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
        $stmt_check->execute([$generador_id, $anio]);
        $existe_registro = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($existe_registro) {
            // Actualizar registro existente
            $stmt = $conn->prepare("UPDATE reporte_anual_adicional SET 
                num_capacitaciones_programadas = ?,
                archivo_cronograma = ?,
                num_capacitaciones_ejecutadas = ?,
                num_empleados_capacitados = ?,
                archivo_soportes_capacitaciones = ?,
                tiene_accidentes = ?,
                num_accidentes = ?,
                acciones_preventivas = ?,
                otra_accion_preventiva = ?,
                num_auditorias_internas = ?,
                archivo_resultados_auditorias_internas = ?,
                archivo_plan_mejoramiento_interno = ?,
                num_auditorias_externas = ?,
                archivo_resultados_auditorias_externas = ?,                
                fecha_creacion = CURRENT_TIMESTAMP
                WHERE generador_id = ? AND anio = ?");
            
            $stmt->execute([
                $_POST['num_capacitaciones_programadas'],
                $archivo_cronograma,
                $_POST['num_capacitaciones_ejecutadas'],
                $_POST['num_empleados_capacitados'],
                $archivo_soportes,
                $_POST['tiene_accidentes'],
                $num_accidentes,
                $acciones_json,
                $otra_accion_preventiva,
                $_POST['num_auditorias_internas'],
                $archivo_resultados_auditorias_internas,
                $archivo_plan_mejoramiento_interno,
                $num_auditorias_externas,
                $archivo_resultados_auditorias_externas,                
                $generador_id,
                $anio
            ]);
            
            if ($estado_actual === 'rechazado') {
                $_SESSION['mensaje_exito'] = "¡Correcciones de la información adicional guardadas y enviadas para revisión!";
            } else {
                $_SESSION['mensaje_exito'] = "¡Información adicional actualizada! Complete ahora el plan de contingencias.";
            }
        } else {
            // Insertar nuevo registro
            $stmt = $conn->prepare("INSERT INTO reporte_anual_adicional 
                (generador_id, anio, num_capacitaciones_programadas, archivo_cronograma,
                 num_capacitaciones_ejecutadas, num_empleados_capacitados, archivo_soportes_capacitaciones,
                 tiene_accidentes, num_accidentes, acciones_preventivas, otra_accion_preventiva,
                 num_auditorias_internas, archivo_resultados_auditorias_internas, archivo_plan_mejoramiento_interno,
                 num_auditorias_externas, archivo_resultados_auditorias_externas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $generador_id, $anio,
                $_POST['num_capacitaciones_programadas'],
                $archivo_cronograma,
                $_POST['num_capacitaciones_ejecutadas'],
                $_POST['num_empleados_capacitados'],
                $archivo_soportes,
                $_POST['tiene_accidentes'],
                $num_accidentes,
                $acciones_json,
                $otra_accion_preventiva,
                $_POST['num_auditorias_internas'],
                $archivo_resultados_auditorias_internas,
                $archivo_plan_mejoramiento_interno,
                $num_auditorias_externas,
                $archivo_resultados_auditorias_externas                
            ]);
            
            $_SESSION['mensaje_exito'] = "¡Información adicional guardada! Complete ahora el plan de contingencias.";
        }
        
        // ✅ ACTUALIZAR ESTADO EN REVISIONES_ANUALES
        $stmt_check_revision = $conn->prepare("SELECT generador_id FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
        $stmt_check_revision->execute([$generador_id, $anio]);
        $existe_revision = $stmt_check_revision->fetch(PDO::FETCH_ASSOC);
        
        if ($existe_revision) {
            $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
                formulario_accidentes = 'pendiente',
                fecha_revision = NULL,
                revisado_por = NULL,
                observaciones_accidentes = NULL
                WHERE generador_id = ? AND anio = ?");
            
            $stmt_update->execute([$generador_id, $anio]);
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO revisiones_anuales 
                (generador_id, anio, formulario_accidentes, estado_general)
                VALUES (?, ?, 'pendiente', 'incompleto')");
            
            $stmt_insert->execute([$generador_id, $anio]);
        }
        
        error_log("PROCESAMIENTO EXITOSO - Reporte adicional guardado para generador: $generador_id, año: $anio");
        if ($estado_actual === 'rechazado') {
            error_log("CORRECCIÓN ENVIADA - Se procesó corrección del formulario de accidentes");
        }
        
        $conn->commit();
        
        // Redirigir al formulario de contingencias
        header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("ERROR en procesar_reporte_adicional: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../vistas/generador/reporte_adicional_view.php?id=" . $generador_id);
    exit();
}
?>