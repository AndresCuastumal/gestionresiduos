<?php
// procesos/login/procesar_registro_paso1.php - VERSIÓN CON COLA

require '../../includes/conexion.php';
require '../../includes/enviar_correo.php';  // Para PHPMailer
require '../../includes/email_queue.php';    // NUEVO: Para manejo de cola

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    try {
        // 1. Verificar si el email ya está registrado
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            header("Location: ../../vistas/login/registro.php?error=Ya existe una cuenta con este email.");
            exit();
        }
        
        // 2. Generar token y fecha de expiración
        $token = bin2hex(random_bytes(32));
        $expiracion = date("Y-m-d H:i:s", strtotime("+24 hours"));
        
        // 3. Guardar en tabla temporal de registros pendientes
        $stmt = $conn->prepare("INSERT INTO registros_pendientes (email, token_verificacion, expiracion_token) VALUES (:email, :token, :expiracion)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiracion', $expiracion);
        $stmt->execute();
        
        // 4. Configurar el correo (PERO NO ENVIARLO DIRECTAMENTE)
        $enlace = "http://34.56.157.229/gestionresiduos/vistas/login/completar_registro.php?token=$token";
        
        $asunto = 'Verifica tu correo electrónico - Sistema de Gestión de Residuos';
        
        $cuerpo_html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { 
                    display: inline-block; 
                    padding: 12px 24px; 
                    background-color: #4CAF50; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Verificación de Correo Electrónico</h2>
                <p>Haz clic en el siguiente enlace para completar tu registro:</p>
                <p><a href='$enlace' class='button'>Verificar mi correo</a></p>
                <p>Este enlace expira en 24 horas.</p>
                <p>Si no solicitaste este registro, ignora este mensaje.</p>
            </div>
        </body>
        </html>";
        
        $cuerpo_texto = "Verifica tu correo electrónico:\n$enlace\n\nEste enlace expira en 24 horas.\n\nSi no solicitaste este registro, ignora este mensaje.";
        
        // 5. AGREGAR A LA COLA en lugar de enviar directamente
        $queue_id = agregarCorreoALaCola($email, '', $asunto, $cuerpo_html, $cuerpo_texto);
        
        if ($queue_id) {
            // Éxito al agregar a la cola
            header("Location: ../../vistas/login/registro.php?success=Registro procesado. Recibirás el correo de verificación en breve (revisa spam). El enlace expira en 24 horas.");
        } else {
            // Fallo al agregar a la cola
            header("Location: ../../vistas/login/registro.php?error=Error al procesar tu registro. Por favor intenta nuevamente.");
        }
        exit();
        
    } catch (Exception $e) {
        // Para debug - muestra el error real
        error_log("Error en registro paso 1: " . $e->getMessage());
        header("Location: ../../vistas/login/registro.php?error=Ocurrió un error inesperado. Por favor intenta nuevamente.");
        exit();
    }
} else {
    header("Location: ../../vistas/login/registro.php");
    exit();
}