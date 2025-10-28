<?php
require '../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    $errores = [];
    
    // Verificar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Verificar longitud mínima
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Verificar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email no es válido";
    }
    
    // Si hay errores, redirigir con mensajes
    if (!empty($errores)) {
        header("Location: ../../vistas/login/registro.php?error=" . urlencode(implode("<br>", $errores)));
        exit();
    }
    
    try {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            header("Location: ../../vistas/login/registro.php?error=" . urlencode("Este email ya está registrado"));
            exit();
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (email, password, rol) VALUES (:email, :password, 'generador')");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->execute();
        
        header("Location: ../../vistas/login/login.php?registro=exito");
        exit();
    } catch(PDOException $e) {
        header("Location: ../../vistas/login/registro.php?error=" . urlencode("Error en el registro: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: ../../vistas/login/registro.php");
    exit();
}
?>