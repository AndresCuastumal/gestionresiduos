<?php
session_start();
require_once '../../includes/conexion.php';
require_once '../../includes/enviar_correo.php';

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

try {
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
    
    // ===== LÓGICA SIMPLIFICADA PARA EL ESTADO =====
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
        
        // ===== ACTUALIZAR REVISIONES_ANUALES - SOLUCIÓN MEJORADA =====
        // Primero obtener el estado actual del formulario_mensual
        $stmt_check_mensual = $conn->prepare("SELECT formulario_mensual FROM revisiones_anuales WHERE generador_id = ? AND anio = ?");
        $stmt_check_mensual->execute([$generador_id, $anio]);
        $estado_mensual_actual = $stmt_check_mensual->fetch(PDO::FETCH_COLUMN);

        // Si no existe el registro o el estado es nulo, usar 'pendiente' como valor por defecto
        $nuevo_estado_mensual = $estado_mensual_actual ? $estado_mensual_actual : 'pendiente';

        // SOLO ACTUALIZAR - preservar el estado del formulario_mensual
        $stmt_update = $conn->prepare("UPDATE revisiones_anuales SET 
            formulario_contingencias = 'pendiente',
            formulario_mensual = ?,
            estado_general = 'pendiente',
            fecha_revision = NULL,
            revisado_por = NULL,
            observaciones_contingencias = NULL
            WHERE generador_id = ? AND anio = ?");
            
        $stmt_update->execute([$nuevo_estado_mensual, $generador_id, $anio]);
        
        // Confirmar transacción
        $conn->commit();
        
        // Enviar correo solo si se confirma definitivamente
        if ($accion == 'confirmar') {
            // Enviar correo de notificación
            $stmt_usuario = $conn->prepare("SELECT u.email, g.nom_responsable, g.nom_generador 
                                       FROM usuarios u 
                                       JOIN usuario_generador ug ON ug.usuario_id = u.id
                                       JOIN generador g ON g.id = ug.generador_id 
                                       WHERE g.id = :generador_id and u.id = :usuario_id");
            $stmt_usuario->bindParam(':generador_id', $generador_id);
            $stmt_usuario->bindParam(':usuario_id', $persona_reporta);
            $stmt_usuario->execute();
            $info_usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
            
            if ($info_usuario) {
                $destinatario = $info_usuario['email'];
                $nombre_usuario = $info_usuario['nom_responsable'];
                $nombre_generador = $info_usuario['nom_generador'];
                
                // Configurar y enviar el correo
                $mail = configurarMailer();
                $mail->addAddress($destinatario);

                // Codificar correctamente el asunto con tildes y caracteres especiales
                $asunto = 'Confirmación de Reporte Completo - Sistema de Gestión de Residuos';
                $mail->Subject = mb_encode_mimeheader($asunto, 'UTF-8', 'Q');
                
                // Cuerpo del mensaje en HTML
                $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Confirmación de Reporte</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Sistema de Gestión de Residuos</h1>
                        </div>
                        <div class='content'>
                            <h2>Confirmación de Recepción</h2>
                            <p>Estimado(a) <strong>$nombre_usuario</strong>,</p>
                            <p>Hemos recibido exitosamente todos sus reportes para el generador <strong>$nombre_generador</strong> correspondiente al año <strong>$anio</strong>.</p>
                            <p>Los siguientes formularios han sido completados y confirmados:</p>
                            <ul>
                                <li>Reporte Mensual de Residuos</li>
                                <li>Información Adicional y Capacitaciones</li>
                                <li>Plan de Contingencias</li>
                            </ul>
                            <p>El reporte completo ha sido registrado en nuestro sistema y se encuentra en estado: <strong>Pendiente de revisión</strong>.</p>
                            <p>Recibirá una notificación una vez que el técnico asignado haya revisado la información.</p>
                            <p>Gracias por utilizar nuestro sistema.</p>
                        </div>
                        <div class='footer'>
                            <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                            <p>&copy; " . date('Y') . " Sistema de Gestión de Residuos. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Versión alternativa en texto plano
                $mail->AltBody = "Confirmación de Reporte Completo\n\n" .
                                "Estimado(a) $nombre_usuario,\n\n" .
                                "Hemos recibido exitosamente todos sus reportes para el generador $nombre_generador correspondiente al año $anio.\n\n" .
                                "Los siguientes formularios han sido completados:\n" .
                                "- Reporte Mensual de Residuos\n" .
                                "- Información Adicional y Capacitaciones\n" .
                                "- Plan de Contingencias\n\n" .
                                "El reporte completo ha sido registrado en nuestro sistema y se encuentra en estado: Pendiente de revisión.\n\n" .
                                "Recibirá una notificación una vez que el técnico asignado haya revisado la información.\n\n" .
                                "Gracias por utilizar nuestro sistema.\n\n" .
                                "Este es un mensaje automático, por favor no responda a este correo.";
                
                // Intentar enviar el correo
                if ($mail->send()) {
                    error_log("Correo de confirmación enviado a: " . $destinatario);
                } else {
                    error_log("Error al enviar correo de confirmación: " . $mail->ErrorInfo);
                }
            }
            
            // Limpiar sesión y redirigir
            unset($_SESSION['generador_id_reportando']);
            unset($_SESSION['anio_reportando']);
            
            $_SESSION['mensaje_exito'] = "¡Reporte confirmado exitosamente! Se ha enviado un correo de confirmación.";
            header("Location: ../../vistas/generador/listado_generadores_view.php");
            exit();
        } else {
            // Guardar como borrador
            $_SESSION['mensaje_exito'] = "¡Borrador guardado exitosamente! Puede continuar editando posteriormente.";
            header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=".$generador_id);
            exit();
        }
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../vistas/generador/reporte_contingencias_view.php?id=".$generador_id);    
    exit();
}
?>