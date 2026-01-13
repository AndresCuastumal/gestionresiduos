<?php
// test_google.php
require 'includes/enviar_correo.php';

$mail = configurarMailer();
$mail->addAddress('TU_EMAIL_REAL@hotmail.com');
$mail->Subject = 'Test Google SMTP';
$mail->Body = 'Prueba de conexión';

try {
    if ($mail->send()) {
        echo "✅ Correo enviado - Revisa tu bandeja";
    } else {
        echo "❌ Error: " . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage();
}
?>