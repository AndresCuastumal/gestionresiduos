<?php
// test_password.php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'gestionresiduossms@gmail.com';
$mail->Password = 'qvkk yjcv gktx alvb'; // ← Pega tu App Password aquí
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

try {
    // Solo prueba la conexión, sin enviar
    $mail->smtpConnect();
    echo "✅ Conexión exitosa con Google SMTP";
    $mail->smtpClose();
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>