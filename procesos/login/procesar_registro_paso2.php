<?php
require '../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verificar que las contraseñas coincidan
        if ($password !== $confirm_password) {
            header("Location: ../../vistas/login/completar_registro.php?token=$token&error=Las contraseñas no coinciden.");
            exit();
        }
        
        // Verificar token válido
        $stmt = $conn->prepare("SELECT * FROM registros_pendientes WHERE token_verificacion = :token AND expiracion_token > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $registro_pendiente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registro_pendiente) {
            header("Location: ../../vistas/login/registro.php?error=El enlace ha expirado o es inválido.");
            exit();
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Crear usuario en la tabla principal
        $stmt = $conn->prepare("INSERT INTO usuarios (email, password, rol, fecha_creacion) VALUES (:email, :password, 'generador', NOW())");
        $stmt->bindParam(':email', $registro_pendiente['email']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->execute();
        
        // Eliminar registro pendiente
        $stmt = $conn->prepare("DELETE FROM registros_pendientes WHERE token_verificacion = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        header("Location: ../../vistas/login/login.php?success=Cuenta creada exitosamente. Ya puedes iniciar sesión.");
        exit();
        
    } catch (Exception $e) {
        header("Location: ../../vistas/login/completar_registro.php?token=$token&error=Error al completar el registro.");
        exit();
    }
} else {
    header("Location: ../../vistas/login/registro.php");
    exit();
}