<?php
require '../../includes/conexion.php';
include '../../includes/header.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verificar token y expiración
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE token_recuperacion = :token AND expiracion_token > NOW()");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("<div class='auth-container'><div class='auth-card'><h2 class='auth-title'>Enlace inválido</h2><p class='text-center'>El enlace de recuperación no es válido o ha expirado.</p><div class='text-center'><a href='recuperar.php' class='btn btn-primary'>Solicitar nuevo enlace</a></div></div></div>");
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validaciones
        $errors = [];
        
        if (strlen($password) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Las contraseñas no coinciden";
        }
        
        if (empty($errors)) {
            // Hash de la nueva contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Actualizar contraseña y limpiar token
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password, token_recuperacion = NULL, expiracion_token = NULL WHERE id = :id");
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            
            echo "<div class='auth-container'><div class='auth-card'><h2 class='auth-title'>Contraseña actualizada</h2><div class='alert alert-success'>Contraseña actualizada con éxito.</div><div class='text-center'><a href='login.php' class='btn btn-primary'>Iniciar sesión</a></div></div></div>";
            include '../../includes/footer.php';
            exit();
        }
    }
} else {
    header("Location: recuperar.php");
    exit();
}
?>

<main class="auth-container">
    <div class="d-flex flex-column align-items-center">
        <div class="auth-card shadow p-4 mb-5 mt-5">
            <h2 class="auth-title text-center">Restablecer Contraseña</h2>
            
            <div class="mb-4 text-center email-info">
                <p>Estás restableciendo la contraseña para: </p><p class="text-success"> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="resetForm" onsubmit="return validarFormulario()">
                <div class="form-group mb-3">
                    <label for="password">Nueva Contraseña (mínimo 6 caracteres):</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
                    <small class="form-text">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group mb-5">
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                    <small id="passwordError" class="text-error"></small>
                </div>
                
                <div class="form-actions d-flex flex-column align-items-center mb-3">
                    <button type="submit" class="btn btn-primary btn-block">Guardar nueva contraseña</button>
                </div>    
                <div class="text-center mt-3">
                    <a href="login.php" class="auth-link text-center">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="../../assets/js/validacion-registro.js"></script>
<?php include '../../includes/footer.php'; ?>