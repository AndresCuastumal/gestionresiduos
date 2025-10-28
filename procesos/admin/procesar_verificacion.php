<?php
session_start();
require_once '../../includes/conexion.php';
//require_once '../../includes/enviar_correo.php';
require_once '../../includes/generar_certificado.php'; // Función para generar PDF

// Verificar que el usuario es técnico
if ($_SESSION['rol'] != 'admin') {
    header("Location: ../../vistas/admin/verificacion_reportes_view.php");
    exit();
}

$generador_id = $_POST['generador_id'];
$anio = $_POST['anio'];
$email_usuario = $_POST['email_usuario'];
$nombre_usuario = $_POST['nombre_usuario'];
$nombre_generador = $_POST['nombre_generador'];
$decision_final = $_POST['decision_final'];

try {
    // Actualizar estado de los reportes
    if ($decision_final == 'aprobado') {
        // Aprobar todos los reportes
        $stmt = $conn->prepare("UPDATE reporte_mensual SET estado = 'aprobado' WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$generador_id, $anio]);
        
        $stmt = $conn->prepare("UPDATE reporte_anual_adicional SET estado = 'aprobado' WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$generador_id, $anio]);
        
        $stmt = $conn->prepare("UPDATE contingencias SET estado = 'aprobado' WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$generador_id, $anio]);
        
        // Enviar correo de aprobación con certificado
        $certificado_path = generarCertificado($generador_id, $anio, $nombre_usuario, $nombre_generador);
        
        $mail = configurarMailer();
        $mail->addAddress($email_usuario);
        $mail->Subject = '✅ Reporte Aprobado - Sistema de Gestión de Residuos';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reporte Aprobado</title>
        </head>
        <body>
            <h2>¡Felicidades!</h2>
            <p>Estimado(a) $nombre_usuario,</p>
            <p>Su reporte para el generador <strong>$nombre_generador</strong> del año <strong>$anio</strong> ha sido <strong>APROBADO</strong>.</p>
            <p>Se adjunta el certificado de cumplimiento. Todos los formularios fueron verificados satisfactoriamente.</p>
            <p>Gracias por su compromiso con la gestión adecuada de residuos.</p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "¡Felicidades! Su reporte para $nombre_generador del año $anio ha sido APROBADO.";
        
        // Adjuntar certificado
        $mail->addAttachment($certificado_path, 'Certificado_Cumplimiento.pdf');
        
        if ($mail->send()) {
            $_SESSION['mensaje_exito'] = "Reporte aprobado y certificado enviado al usuario.";
        } else {
            $_SESSION['error'] = "Reporte aprobado pero error al enviar el correo: " . $mail->ErrorInfo;
        }
        
    } else {
        // Rechazar reportes
        $razon_rechazo = $_POST['razon_rechazo'];
        
        $stmt = $conn->prepare("UPDATE reporte_mensual SET estado = 'rechazado', observaciones = ? WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$razon_rechazo, $generador_id, $anio]);
        
        $stmt = $conn->prepare("UPDATE reporte_anual_adicional SET estado = 'rechazado', observaciones = ? WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$razon_rechazo, $generador_id, $anio]);
        
        $stmt = $conn->prepare("UPDATE contingencias SET estado = 'rechazado', observaciones = ? WHERE generador_id = ? AND anio = ?");
        $stmt->execute([$razon_rechazo, $generador_id, $anio]);
        
        // Enviar correo de rechazo
        $mail = configurarMailer();
        $mail->addAddress($email_usuario);
        $mail->Subject = '❌ Reporte Rechazado - Sistema de Gestión de Residuos';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reporte Rechazado</title>
        </head>
        <body>
            <h2>Reporte Requiere Correcciones</h2>
            <p>Estimado(a) $nombre_usuario,</p>
            <p>Su reporte para el generador <strong>$nombre_generador</strong> del año <strong>$anio</strong> ha sido <strong>RECHAZADO</strong>.</p>
            <p><strong>Razón del rechazo:</strong></p>
            <p>$razon_rechazo</p>
            <p>Por favor, realice las correcciones necesarias y envíe nuevamente los reportes.</p>
            <p>Tiene 15 días hábiles para corregir y reenviar la información.</p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Su reporte para $nombre_generador del año $anio ha sido RECHAZADO. Razón: $razon_rechazo";
        
        if ($mail->send()) {
            $_SESSION['mensaje_exito'] = "Reporte rechazado y notificación enviada al usuario.";
        } else {
            $_SESSION['error'] = "Reporte rechazado pero error al enviar el correo: " . $mail->ErrorInfo;
        }
    }
    
    header("Location: ../vistas/verificacion_reportes_view.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error al procesar la verificación: " . $e->getMessage();
    header("Location: ../vistas/detalle_verificacion_view.php?generador_id=$generador_id&anio=$anio");
    exit();
}
?>