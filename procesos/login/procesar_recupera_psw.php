<?php
require '../../includes/conexion.php';
require '../../includes/enviar_correo.php'; // Archivo con configuración de PHPMailer

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    try {
        // Verificar si el email existe
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Generar token y fecha de expiración
            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            // Guardar token en la base de datos
            $stmt = $conn->prepare("UPDATE usuarios SET token_recuperacion = :token, expiracion_token = :expiracion WHERE email = :email");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiracion', $expiracion);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            // Configurar y enviar el correo
            $enlace = "http://192.168.20.122/reportegestionresiduos/vistas/login/reset.php?token=$token";
            
            $mail = configurarMailer(); // Función definida en mailer.php
            $mail->addAddress($email);
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body    = "Haz clic en el siguiente enlace para restablecer tu contraseña: <a href='$enlace'>$enlace</a>";
            $mail->AltBody = "Haz clic en este enlace para restablecer tu contraseña: $enlace";
            
            if ($mail->send()) {
                header("Location: ../../vistas/login/recuperar.php?success=Se ha enviado un enlace de recuperación a tu correo.");
            } else {
                header("Location: ../../vistas/login/recuperar.php?error=Error al enviar el correo. Por favor intenta nuevamente.");
            }
            exit();
        } else {
            header("Location: ../../vistas/login/recuperar.php?error=No existe una cuenta con ese email.");
            exit();
        }
    } catch (Exception $e) {
        header("Location: ../../vistas/login/recuperar.php?error=Ocurrió un error inesperado.");
        exit();
    }
} else {
    header("Location: ../../vistas/login/recuperar.php");
    exit();
}