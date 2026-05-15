<?php
session_start();
require_once '../../includes/conexion.php';
// ✅ SOLO incluir controlador de revisiones
require_once '../admin/revisiones_controller.php';

// Verificar que viene del formulario de contingencias
if (!isset($_SESSION['generador_id_reportando'])) {
    header("Location: ../../vistas/generador/listado_generadores_view.php");
    exit();
}

$generador_id = $_POST['generador_id'];
$anio = $_POST['anio'];
$accion = $_POST['accion'] ?? 'borrador'; // 'borrador' o 'confirmar'
$fecha_reporte = date('Y-m-d');
$persona_reporta = $_SESSION['usuario_id'];

// ✅ Crear controlador de revisiones
$revisionController = new RevisionesController($conn);

try {
    // ✅ VALIDACIÓN DE INTENTOS ANTES DE PROCESAR
    $estado_actual = $revisionController->obtenerEstadoFormulario($generador_id, $anio, 'contingencias');
    
    error_log("=== PROCESANDO CONTINGENCIAS ===");
    error_log("Generador ID: $generador_id, Año: $anio");
    error_log("Estado actual del formulario contingencias: " . $estado_actual);
    error_log("Acción solicitada: $accion");
    error_log("Usuario ID: $persona_reporta");
    
    // Si el formulario está rechazado, validar intentos de corrección SOLO cuando se confirma
    if ($estado_actual === 'rechazado' && $accion == 'confirmar') {
        error_log("Formulario en estado RECHAZADO - Validando intentos...");
        
        // Verificar si puede reenviar correcciones
        if (!$revisionController->puedeReenviarCorreccion($generador_id, $anio)) {
            $infoIntentos = $revisionController->obtenerInfoIntentos($generador_id, $anio);
            
            // ✅ CORREGIDO: Definir el límite manualmente
            $limite_intentos = 2; // Cambia a 1 o 2 según necesites
            $mensaje_error = "Has alcanzado el número máximo de intentos de corrección permitidos. " .
                           "(" . $infoIntentos['intentos_correccion'] . " de " . $limite_intentos . " intento(s) utilizado(s))";
            
            error_log("VALIDACIÓN FALLIDA: " . $mensaje_error);
            $_SESSION['error'] = $mensaje_error;
            header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
            exit();
        }
        
        error_log("✅ VALIDACIÓN EXITOSA: Intentos disponibles para corrección");
    }
    
    // Convertir arrays de acciones a JSON - MANEJO CORRECTO DE CHECKBOXES
    $incendios_acciones = isset($_POST['incendios_acciones']) ? $_POST['incendios_acciones'] : [];
    $agua_acciones = isset($_POST['agua_acciones']) ? $_POST['agua_acciones'] : [];
    $energia_acciones = isset($_POST['energia_acciones']) ? $_POST['energia_acciones'] : [];
    $derrames_acciones = isset($_POST['derrames_acciones']) ? $_POST['derrames_acciones'] : [];
    $recoleccion_acciones = isset($_POST['recoleccion_acciones']) ? $_POST['recoleccion_acciones'] : [];
    $operativas_acciones = isset($_POST['operativas_acciones']) ? $_POST['operativas_acciones'] : [];
    
    // Obtener valores de campos opcionales
    $incendios_otra_accion = isset($_POST['incendios_otra_accion']) ? trim($_POST['incendios_otra_accion']) : '';
    $agua_otra_accion = isset($_POST['agua_otra_accion']) ? trim($_POST['agua_otra_accion']) : '';
    $energia_otra_accion = isset($_POST['energia_otra_accion']) ? trim($_POST['energia_otra_accion']) : '';
    $derrames_otra_accion = isset($_POST['derrames_otra_accion']) ? trim($_POST['derrames_otra_accion']) : '';
    $recoleccion_otra_accion = isset($_POST['recoleccion_otra_accion']) ? trim($_POST['recoleccion_otra_accion']) : '';
    $operativas_otra_accion = isset($_POST['operativas_otra_accion']) ? trim($_POST['operativas_otra_accion']) : '';
    $inundaciones_acciones = isset($_POST['inundaciones_acciones']) ? $_POST['inundaciones_acciones'] : '';
    $derrames_tipo = isset($_POST['derrames_tipo']) ? $_POST['derrames_tipo'] : '';
    
    // Lógica para manejar campos "otra" cuando no se especifica texto
    if (in_array('otro', $incendios_acciones) && empty($incendios_otra_accion)) {
        $stmt_existente = $conn->prepare("SELECT incendios_otra_accion FROM contingencias WHERE generador_id = ? AND anio = ?");
        $stmt_existente->execute([$generador_id, $anio]);
        $existente = $stmt_existente->fetch(PDO::FETCH_ASSOC);
        $incendios_otra_accion = $existente['incendios_otra_accion'] ?? '';
    }
    
    if (in_array('otro', $agua_acciones) && empty($agua_otra_accion)) {
        $stmt_existente = $conn->prepare("SELECT agua_otra_accion FROM contingencias WHERE generador_id = ? AND anio = ?");
        $stmt_existente->execute([$generador_id, $anio]);
        $existente = $stmt_existente->fetch(PDO::FETCH_ASSOC);
        $agua_otra_accion = $existente['agua_otra_accion'] ?? '';
    }
    
    // Limpiar campos "otra" si no se seleccionó la opción correspondiente
    if (!in_array('otro', $incendios_acciones)) $incendios_otra_accion = '';
    if (!in_array('otro', $agua_acciones)) $agua_otra_accion = '';
    if (!in_array('otro', $energia_acciones)) $energia_otra_accion = '';
    if (!in_array('otro', $derrames_acciones)) $derrames_otra_accion = '';
    if (!in_array('otro', $recoleccion_acciones)) $recoleccion_otra_accion = '';
    if (!in_array('otro', $operativas_acciones)) $operativas_otra_accion = '';
    
    // Convertir a JSON después de procesar
    $incendios_acciones_json = !empty($incendios_acciones) ? json_encode($incendios_acciones) : '[]';
    $agua_acciones_json = !empty($agua_acciones) ? json_encode($agua_acciones) : '[]';
    $energia_acciones_json = !empty($energia_acciones) ? json_encode($energia_acciones) : '[]';
    $derrames_acciones_json = !empty($derrames_acciones) ? json_encode($derrames_acciones) : '[]';
    $recoleccion_acciones_json = !empty($recoleccion_acciones) ? json_encode($recoleccion_acciones) : '[]';
    $operativas_acciones_json = !empty($operativas_acciones) ? json_encode($operativas_acciones) : '[]';
    
    // Determinar el estado según la acción
    $estado = ($accion == 'confirmar') ? 'confirmado' : 'borrador';
    
    // Iniciar transacción para asegurar consistencia entre ambas tablas
    $conn->beginTransaction();
    
    try {
        // Verificar si ya existe un registro para este generador y año
        $stmt_check = $conn->prepare("SELECT id, estado FROM contingencias WHERE generador_id = ? AND anio = ?");
        $stmt_check->execute([$generador_id, $anio]);
        $existe_registro = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($existe_registro) {
            // Si ya está confirmado, verificar si fue rechazado para permitir cambios
            if ($existe_registro['estado'] == 'confirmado') {
                // Verificar si el formulario fue rechazado (permite reenvío después de rechazo)
                $stmt_check_rechazo = $conn->prepare("SELECT formulario_contingencias FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
                $stmt_check_rechazo->execute([$generador_id, $anio]);
                $rechazo_existente = $stmt_check_rechazo->fetch(PDO::FETCH_ASSOC);
                
                // Solo bloquear si NO hay rechazo previo
                if (!$rechazo_existente || $rechazo_existente['formulario_contingencias'] != 'rechazado') {
                    throw new Exception("Este reporte ya ha sido confirmado y no puede ser modificado.");
                }
                // Si hay rechazo previo, permitir la modificación
            }
            
            // Actualizar registro existente
            $stmt = $conn->prepare("UPDATE contingencias SET 
                fecha_reporte = ?, persona_reporta = ?, estado = ?,
                incendios_numero = ?, incendios_acciones = ?, incendios_otra_accion = ?,
                inundaciones_numero = ?, inundaciones_acciones = ?,
                agua_numero = ?, agua_acciones = ?, agua_otra_accion = ?,
                energia_numero = ?, energia_acciones = ?, energia_otra_accion = ?,
                derrames_numero = ?, derrames_tipo = ?, derrames_acciones = ?, derrames_otra_accion = ?,
                recoleccion_numero = ?, recoleccion_acciones = ?, recoleccion_otra_accion = ?,
                operativas_numero = ?, operativas_acciones = ?, operativas_otra_accion = ?,
                fecha_creacion = CURRENT_TIMESTAMP
                WHERE generador_id = ? AND anio = ?");
            
            $stmt->execute([
                $fecha_reporte,
                $persona_reporta,
                $estado,
                $_POST['incendios_numero'] ?? 0,
                $incendios_acciones_json,
                $incendios_otra_accion,
                $_POST['inundaciones_numero'] ?? 0,
                $inundaciones_acciones,
                $_POST['agua_numero'] ?? 0,
                $agua_acciones_json,
                $agua_otra_accion,
                $_POST['energia_numero'] ?? 0,
                $energia_acciones_json,
                $energia_otra_accion,
                $_POST['derrames_numero'] ?? 0,
                $derrames_tipo,
                $derrames_acciones_json,
                $derrames_otra_accion,
                $_POST['recoleccion_numero'] ?? 0,
                $recoleccion_acciones_json,
                $recoleccion_otra_accion,
                $_POST['operativas_numero'] ?? 0,
                $operativas_acciones_json,
                $operativas_otra_accion,
                $generador_id,
                $anio
            ]);
            
        } else {
            // Insertar nuevo registro            
            $stmt = $conn->prepare("INSERT INTO contingencias 
                (generador_id, anio, fecha_reporte, persona_reporta, estado,
                incendios_numero, incendios_acciones, incendios_otra_accion,
                inundaciones_numero, inundaciones_acciones,
                agua_numero, agua_acciones, agua_otra_accion,
                energia_numero, energia_acciones, energia_otra_accion,
                derrames_numero, derrames_tipo, derrames_acciones, derrames_otra_accion,
                recoleccion_numero, recoleccion_acciones, recoleccion_otra_accion,
                operativas_numero, operativas_acciones, operativas_otra_accion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $generador_id, $anio,
                $fecha_reporte,
                $persona_reporta,
                $estado,
                $_POST['incendios_numero'] ?? 0,
                $incendios_acciones_json,
                $incendios_otra_accion,
                $_POST['inundaciones_numero'] ?? 0,
                $inundaciones_acciones,
                $_POST['agua_numero'] ?? 0,
                $agua_acciones_json,
                $agua_otra_accion,
                $_POST['energia_numero'] ?? 0,
                $energia_acciones_json,
                $energia_otra_accion,
                $_POST['derrames_numero'] ?? 0,
                $derrames_tipo,
                $derrames_acciones_json,
                $derrames_otra_accion,
                $_POST['recoleccion_numero'] ?? 0,
                $recoleccion_acciones_json,
                $recoleccion_otra_accion,
                $_POST['operativas_numero'] ?? 0,
                $operativas_acciones_json,
                $operativas_otra_accion                
            ]);
        }
        
        // ✅ ACTUALIZAR ESTADO EN REVISIONES_ANUALES
        if ($accion == 'confirmar') {
            // Verificar si existe registro en revisiones_anuales
            $stmt_check_revision = $conn->prepare("SELECT generador_id FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
            $stmt_check_revision->execute([$generador_id, $anio]);
            $existe_revision = $stmt_check_revision->fetch(PDO::FETCH_ASSOC);
            
            if ($existe_revision) {
                // Actualizar estado del formulario de contingencias a "pendiente"
                $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
                    formulario_contingencias = 'pendiente',
                    fecha_revision = NULL,
                    revisado_por = NULL,
                    observaciones_contingencias = NULL
                    WHERE generador_id = ? AND anio = ?");
                
                $stmt_update->execute([$generador_id, $anio]);
            } else {
                // Insertar nuevo registro en revisiones_anuales
                $stmt_insert = $conn->prepare("INSERT INTO revisiones_anuales 
                    (generador_id, anio, formulario_contingencias, estado_general)
                    VALUES (?, ?, 'pendiente', 'incompleto')");
                
                $stmt_insert->execute([$generador_id, $anio]);
            }
        } else {
            // Para borrador, solo asegurarnos que existe registro en revisiones_anuales
            $stmt_check_revision = $conn->prepare("SELECT COUNT(*) FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
            $stmt_check_revision->execute([$generador_id, $anio]);
            $existe_revision = $stmt_check_revision->fetchColumn();
            // y actualizar estado del formulario de contingencias a "pendiente" solo si ya existe registro
            $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
                formulario_contingencias = 'pendiente',
                fecha_revision = NULL,
                revisado_por = NULL,
                observaciones_contingencias = NULL
                WHERE generador_id = ? AND anio = ?");
            $stmt_update->execute([$generador_id, $anio]);
            
            if (!$existe_revision) {
                $stmt_insert = $conn->prepare("INSERT INTO revisiones_anuales 
                    (generador_id, anio, formulario_contingencias, estado_general)
                    VALUES (?, ?, 'pendiente', 'incompleto')");
                $stmt_insert->execute([$generador_id, $anio]);
            }
        }
        
        // ✅ Log del procesamiento exitoso
        error_log("PROCESAMIENTO EXITOSO - Contingencias guardadas para generador: $generador_id, año: $anio, acción: $accion");
        if ($estado_actual === 'rechazado' && $accion == 'confirmar') {
            error_log("CORRECCIÓN ENVIADA - Se procesó corrección del formulario de contingencias");
        }
        
        // Confirmar transacción
        $conn->commit();
        // ✅ ENVIAR CORREO DE CONFIRMACIÓN AL USUARIO
        if ($accion == 'confirmar') {
            error_log("📧 ENVIANDO CORREO DE CONFIRMACIÓN AL USUARIO");
            
            try {
                // Incluir y usar el controlador de email
                require_once __DIR__ . '/../admin/email_controller.php';
                $emailController = new EmailController($conn);
                
                // Enviar confirmación de recepción al usuario
                $enviado = $emailController->enviarConfirmacionRecepcion($generador_id, $anio, $persona_reporta);
                
                if ($enviado) {
                    error_log("✅ Correo de confirmación enviado exitosamente al usuario");
                } else {
                    error_log("⚠️ No se pudo enviar el correo de confirmación");
                }
                
            } catch (Exception $e) {
                error_log("❌ Error al enviar correo de confirmación: " . $e->getMessage());
                // No interrumpir el flujo principal si falla el email
            }
        }

        if ($accion == 'confirmar') {
            // Verificar si los tres formularios están completos
            $stmt_check_completos = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM cantidad_x_mes WHERE id_generador = ? AND anio = ?) as tiene_mensual,
                    (SELECT COUNT(*) FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?) as tiene_adicional,
                    (SELECT COUNT(*) FROM contingencias WHERE generador_id = ? AND anio = ? AND estado = 'confirmado') as tiene_contingencias
            ");
            $stmt_check_completos->execute([$generador_id, $anio, $generador_id, $anio, $generador_id, $anio]);
            $formularios = $stmt_check_completos->fetch(PDO::FETCH_ASSOC);
            
            if ($formularios['tiene_mensual'] > 0 && $formularios['tiene_adicional'] > 0 && $formularios['tiene_contingencias'] > 0) {
                // Los tres formularios están completos
                $mensaje = "¡Los tres formularios han sido confirmados exitosamente! El reporte anual completo está pendiente de revisión.";
                
                // Limpiar sesión
                unset($_SESSION['generador_id_reportando']);
                unset($_SESSION['anio_reportando']);
                
                $_SESSION['mensaje_exito'] = $mensaje;
                header("Location: ../../vistas/generador/listado_generadores_view.php");
                exit();
            } else {
                // Solo este formulario está confirmado
                $_SESSION['mensaje_exito'] = "¡Plan de Contingencias confirmado exitosamente!";
                header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
                exit();
            }
        } else {
            // Guardar como borrador
            $_SESSION['mensaje_exito'] = "¡Borrador guardado exitosamente! Puede continuar editando posteriormente.";
            header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
            exit();
        }
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("ERROR en procesar_contingencias: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=" . $generador_id);
    exit();
}
?>