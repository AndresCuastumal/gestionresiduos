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
    $mail->Username = 'gresiduossms@gmail.com';
    $mail->Password = 'axse gspv itdl pjop';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // CORREGIR: Usar el mismo email de autenticación como remitente
    $mail->setFrom('gresiduossms@gmail.com', 'Reporte de Gestión de Residuos - Secretaría de Salud Pasto');
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->addCustomHeader('X-Entity-Ref-ID', uniqid());
    // Opcional: Agregar reply-to si quieres otra dirección
    // $mail->addReplyTo('otro-email@dominio.com', 'Nombre');
    
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => true,       // Cambia de false a true
            'verify_peer_name' => true,  // Cambia de false a true
            'allow_self_signed' => false // Cambia de true a false
        ]
    ];
    // IMPORTANTE: Agregar opciones para debug si hay problemas
    // $mail->SMTPDebug = 2; // Habilita solo para debugging
    // $mail->Debugoutput = function($str, $level) {
    //     error_log("PHPMailer: $str");
    // };
    
    return $mail;
}