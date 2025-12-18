<?php
require '../../includes/conexion.php';
require '../../includes/enviar_correo.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    try {
        // Verificar si el email ya está registrado
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            header("Location: ../../vistas/login/registro.php?error=Ya existe una cuenta con este email.");
            exit();
        }
        
        // Generar token y fecha de expiración
        $token = bin2hex(random_bytes(32));
        $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Guardar en tabla temporal de registros pendientes
        $stmt = $conn->prepare("INSERT INTO registros_pendientes (email, token_verificacion, expiracion_token) VALUES (:email, :token, :expiracion)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiracion', $expiracion);
        $stmt->execute();
        
        // Configurar y enviar el correo
        $enlace = "http://192.168.20.7/gestionresiduos/vistas/login/completar_registro.php?token=$token";
        
        $mail = configurarMailer();
        $mail->addAddress($email);
        $mail->Subject = 'Verifica tu correo electrónico';
        $mail->Body    = "Haz clic en el siguiente enlace para completar tu registro: <a href='$enlace'>$enlace</a>. Este enlace expira en 1 hora.";
        $mail->AltBody = "Haz clic en este enlace para completar tu registro: $enlace. Este enlace expira en 1 hora.";
        
        if ($mail->send()) {
            header("Location: ../../vistas/login/registro.php?success=Se ha enviado un enlace de verificación a tu correo.");
        } else {
            header("Location: ../../vistas/login/registro.php?error=Error al enviar el correo. Por favor intenta nuevamente.");
        }
        exit();
        
    } catch (Exception $e) {
        // Para debug - muestra el error real
        error_log("Error en registro paso 1: " . $e->getMessage());
        header("Location: ../../vistas/login/registro.php?error=Ocurrió un error inesperado: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: ../../vistas/login/registro.php");
    exit();
}