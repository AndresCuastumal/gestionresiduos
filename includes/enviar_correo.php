<?php
// configurarMailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function configurarMailerSendGrid() {
    $mail = new PHPMailer(true);
    
    // Configuración SendGrid SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.sendgrid.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'apikey'; // ¡Siempre es literalmente 'apikey'!
    $mail->Password = getenv('SENDGRID_API_KEY'); // Reemplaza con tu API Key real
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Configuración del remitente
    $mail->setFrom('no-reply@gestionresiduos.com', 'Gestión de Residuos - Secretaría de Salud Pasto');
    
    // Opcional: Configurar reply-to diferente
    //$mail->addReplyTo('contacto@gestionresiduos.com', 'Contacto Gestión de Residuos');
    
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    
    return $mail;
}