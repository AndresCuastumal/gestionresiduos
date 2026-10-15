<?php
// cron/procesar_cola.php - Procesa correos pendientes

// 1. Configurar zona horaria y errores
date_default_timezone_set('America/Bogota');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Incluir dependencias
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/enviar_correo.php';

// 3. Crear archivo de log
$log_file = __DIR__ . '/../logs/email_queue_' . date('Y-m-d') . '.log';
$log_message = "[" . date('Y-m-d H:i:s') . "] Iniciando procesamiento\n";

// 4. Configuración
$max_correos_por_ejecucion = 15;    // Máximo 15 correos por ejecución
$segundos_entre_correos = 10;       // 10 segundos entre cada correo
$max_intentos = 3;                  // Máximo 3 intentos por correo

try {
    // 5. Obtener correos pendientes
    $sql = "SELECT * FROM email_queue 
            WHERE status IN ('pending', 'failed') 
            AND attempts < ? 
            AND (scheduled_for IS NULL OR scheduled_for <= NOW())
            ORDER BY 
                CASE WHEN status = 'failed' THEN 0 ELSE 1 END,
                created_at ASC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $max_intentos, PDO::PARAM_INT);
    $stmt->bindValue(2, $max_correos_por_ejecucion, PDO::PARAM_INT);
    $stmt->execute();
    
    $correos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_correos = count($correos);
    
    $log_message .= "Correos a procesar: $total_correos\n";
    
    if ($total_correos == 0) {
        $log_message .= "No hay correos pendientes\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        exit(0);
    }
    
    $enviados = 0;
    $fallidos = 0;
    
    // 6. Procesar cada correo
    foreach ($correos as $indice => $correo) {
        $correo_id = $correo['id'];
        $destinatario = $correo['to_email'];
        
        $log_message .= "Procesando ID $correo_id para $destinatario: ";
        
        try {
            // Marcar como procesando
            $update = $conn->prepare("UPDATE email_queue 
                                     SET status = 'processing', 
                                         attempts = attempts + 1,
                                         last_attempt = NOW() 
                                     WHERE id = ?");
            $update->execute([$correo_id]);
            
            // Configurar PHPMailer
            $mail = configurarMailer();
            
            // Configurar destinatario
            $mail->addAddress($correo['to_email'], $correo['to_name']);
            $mail->Subject = $correo['subject'];
            $mail->Body = $correo['body'];
            $mail->AltBody = $correo['alt_body'] ?: strip_tags($correo['body']);
            
            // Enviar correo
            if ($mail->send()) {
                // Éxito
                $update = $conn->prepare("UPDATE email_queue 
                                         SET status = 'sent', 
                                             sent_at = NOW(),
                                             error_message = NULL 
                                         WHERE id = ?");
                $update->execute([$correo_id]);
                
                $log_message .= "✅ ENVIADO\n";
                $enviados++;
            } else {
                throw new Exception($mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            // Error
            $error_msg = substr($e->getMessage(), 0, 500);
            
            $update = $conn->prepare("UPDATE email_queue 
                                     SET status = 'failed', 
                                         error_message = ? 
                                     WHERE id = ?");
            $update->execute([$error_msg, $correo_id]);
            
            $log_message .= "❌ FALLADO: " . $error_msg . "\n";
            $fallidos++;
        }
        
        // 7. Pausa estratégica entre correos (EVITA BLOQUEO DE GMAIL)
        if ($indice < $total_correos - 1) {
            sleep($segundos_entre_correos);
        }
    }
    
    // 8. Resumen final
    $log_message .= "\nRESUMEN: Total=$total_correos, Enviados=$enviados, Fallidos=$fallidos\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Procesamiento completado\n\n";
    
} catch (Exception $e) {
    $log_message .= "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}

// 9. Guardar log
file_put_contents($log_file, $log_message, FILE_APPEND);

// 10. Mostrar resultado si se ejecuta manualmente
if (php_sapi_name() === 'cli') {
    echo $log_message;
} else {
    echo "<pre>$log_message</pre>";
}
?>