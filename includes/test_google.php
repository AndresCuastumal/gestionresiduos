<?php
// test_google.php
require 'enviar_correo.php';

$mail = configurarMailer();
$mail->addAddress('carlosandres540@hotmail.com');
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