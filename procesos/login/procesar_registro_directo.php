<?php
// procesos/login/procesar_registro_directo.php

require '../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // 1. Validaciones básicas   
        if ($password !== $confirm_password) {
            header("Location: ../../vistas/login/registro.php?error=Las contraseñas no coinciden.");
            exit();
        }
        
        if (strlen($password) < 6) {
            header("Location: ../../vistas/login/registro.php?error=La contraseña debe tener al menos 6 caracteres.");
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../../vistas/login/registro.php?error=Correo electrónico inválido.");
            exit();
        }
        
        // 2. Verificar si el email ya está registrado
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            header("Location: ../../vistas/login/registro.php?error=Ya existe una cuenta con este email.");
            exit();
        }
        
        // 3. Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // 4. Insertar usuario directamente
        $stmt = $conn->prepare("
            INSERT INTO usuarios (email, password, rol, activo, fecha_registro) 
            VALUES (:email, :password, 'generador', 1, NOW())
        ");
        
        
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        
        if ($stmt->execute()) {
            // Opcional: Iniciar sesión automáticamente
            session_start();
            $_SESSION['usuario_id'] = $conn->lastInsertId();            
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_rol'] = 'generador';
            
            // Redirigir al dashboard o página principal
            header("Location: ../../vistas/login/login.php?success=Cuenta creada exitosamente. Ya puedes iniciar sesión.");
        } else {
            header("Location: ../../vistas/login/registro.php?error=Error al crear la cuenta. Intenta nuevamente.");
        }
        
        exit();
        
    } catch (Exception $e) {
        error_log("Error en registro directo: " . $e->getMessage());
        header("Location: ../../vistas/login/registro.php?error=Ocurrió un error inesperado.");
        exit();
    }
} else {
    header("Location: ../../vistas/login/registro.php");
    exit();
}
?>