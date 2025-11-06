<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function configurarMailer() {
    $mail = new PHPMailer(true);
    
    // Configuración SMTP (Gmail)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'reportegestionresiduos@gmail.com'; // Tu dirección Gmail
    $mail->Password = 'inpz hpqw pdyq gdmq'; // Contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Configuración del remitente
    $mail->setFrom('no-reply@gmail.com', 'Reporte de Gestión de Residuos - Secretaría de Salud Pasto');    
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    
    
    
    return $mail;
}