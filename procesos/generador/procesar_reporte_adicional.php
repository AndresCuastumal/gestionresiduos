<?php
session_start();
require_once '../../includes/conexion.php';

// Verificar que viene del formulario adicional
if (!isset($_SESSION['generador_id_reportando'])) {
    header("Location: ../../vistas/generador/listado_generadores_view.php");
    exit();
}

$generador_id = $_SESSION['generador_id_reportando'];
$anio = $_SESSION['anio_reportando'];

try {
    // Procesar archivos - SOLO si se subieron nuevos
    function procesarArchivo($archivo, $directorio, $prefijo, $generador_id, $anio, $campo_existente) {
        global $conn;
        
        // Si no se subió archivo, mantener el existente
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $stmt = $conn->prepare("SELECT $campo_existente FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
            $stmt->execute([$generador_id, $anio]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $existente[$campo_existente] ?? null;
        }
        
        if (!is_dir($directorio)) mkdir($directorio, 0755, true);
        
        $tipo_archivo = mime_content_type($archivo['tmp_name']);
        if ($tipo_archivo !== 'application/pdf') throw new Exception("Solo se permiten PDF");
        if ($archivo['size'] > 10 * 1024 * 1024) throw new Exception("Archivo muy grande");
        
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = $prefijo . $generador_id . '_' . $anio . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;
        
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception("Error al guardar archivo");
        }
        
        return $nombre_archivo;
    }
    
    $directorio = '../uploads/soportes_anuales/';
    $archivo_cronograma = procesarArchivo($_FILES['archivo_cronograma'], $directorio, 'cronograma_', $generador_id, $anio, 'archivo_cronograma');
    $archivo_soportes = procesarArchivo($_FILES['archivo_soportes_capacitaciones'], $directorio, 'soportes_capacitaciones_', $generador_id, $anio, 'archivo_soportes_capacitaciones');
    $archivo_resultados_auditorias = procesarArchivo($_FILES['archivo_resultados_auditorias'], $directorio, 'resultados_auditorias_', $generador_id, $anio, 'archivo_resultados_auditorias');
    $archivo_plan_mejoramiento = procesarArchivo($_FILES['archivo_plan_mejoramiento'], $directorio, 'plan_mejoramiento_', $generador_id, $anio, 'archivo_plan_mejoramiento');
    
    // Convertir acciones a JSON - MANEJO CORRECTO DE CHECKBOXES
    $acciones_preventivas = isset($_POST['acciones_preventivas']) ? $_POST['acciones_preventivas'] : [];
    $acciones_json = !empty($acciones_preventivas) ? json_encode($acciones_preventivas) : '[]';
    
    // Obtener valores de campos opcionales
    $num_accidentes = isset($_POST['num_accidentes']) ? $_POST['num_accidentes'] : 0;
    $otra_accion_preventiva = isset($_POST['otra_accion_preventiva']) ? trim($_POST['otra_accion_preventiva']) : null;
    
    // Si se seleccionó "otra" pero no se especificó, mantener el valor existente
    if (in_array('otra', $acciones_preventivas) && empty($otra_accion_preventiva)) {
        $stmt_existente = $conn->prepare("SELECT otra_accion_preventiva FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
        $stmt_existente->execute([$generador_id, $anio]);
        $existente = $stmt_existente->fetch(PDO::FETCH_ASSOC);
        $otra_accion_preventiva = $existente['otra_accion_preventiva'] ?? null;
    }
    
    // Si no se seleccionó "otra", limpiar el campo
    if (!in_array('otra', $acciones_preventivas)) {
        $otra_accion_preventiva = null;
    }
    
    // Iniciar transacción para asegurar consistencia entre ambas tablas
    $conn->beginTransaction();
    
    try {
        // Verificar si ya existe un registro para este generador y año
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
                num_auditorias = ?,
                archivo_resultados_auditorias = ?,
                archivo_plan_mejoramiento = ?,
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
                $_POST['num_auditorias'],
                $archivo_resultados_auditorias,
                $archivo_plan_mejoramiento,
                $generador_id,
                $anio
            ]);
            
            $_SESSION['mensaje_exito'] = "¡Información adicional actualizada! Complete ahora el plan de contingencias.";
        } else {
            // Insertar nuevo registro
            $stmt = $conn->prepare("INSERT INTO reporte_anual_adicional 
                (generador_id, anio, num_capacitaciones_programadas, archivo_cronograma,
                 num_capacitaciones_ejecutadas, num_empleados_capacitados, archivo_soportes_capacitaciones,
                 tiene_accidentes, num_accidentes, acciones_preventivas, otra_accion_preventiva,
                 num_auditorias, archivo_resultados_auditorias, archivo_plan_mejoramiento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?)");
            
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
                $_POST['num_auditorias'],
                $archivo_resultados_auditorias,
                $archivo_plan_mejoramiento
            ]);
            
            $_SESSION['mensaje_exito'] = "¡Información adicional guardada! Complete ahora el plan de contingencias.";
        }
        
        // ACTUALIZAR ESTADO EN REVISIONES_ANUALES - NUEVO CÓDIGO
        // Verificar si existe registro en revisiones_anuales
        $stmt_check_revision = $conn->prepare("SELECT generador_id FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
        $stmt_check_revision->execute([$generador_id, $anio]);
        $existe_revision = $stmt_check_revision->fetch(PDO::FETCH_ASSOC);
        
        if ($existe_revision) {
            // Actualizar estado del formulario de accidentes a "pendiente"
            $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
                formulario_accidentes = 'pendiente',
                fecha_revision = NULL,
                revisado_por = NULL,
                observaciones_accidentes = NULL
                WHERE generador_id = ? AND anio = ?");
            
            $stmt_update->execute([$generador_id, $anio]);
        } else {
            // Insertar nuevo registro en revisiones_anuales
            $stmt_insert = $conn->prepare("INSERT INTO revisiones_anuales 
                (generador_id, anio, formulario_accidentes, estado_general)
                VALUES (?, ?, 'pendiente', 'incompleto')");
            
            $stmt_insert->execute([$generador_id, $anio]);
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Mantener los datos de sesión y redirigir al formulario de contingencias    
        header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../vistas/generador/reporte_adicional_view.php?id=" . $generador_id);
    exit();
}
?>