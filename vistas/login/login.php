<?php
session_start();
require '../../includes/conexion.php';
include '../../includes/header.php';

// Verificar si el usuario ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['usuario_rol'] = $user['rol'];
        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "Email o contraseña incorrectos";
    }
}
?>

<main class="auth-container d-flex justify-content-center align-items-center min-vh-70">
    <div class="d-flex flex-column align-items-center">
        <div class="auth-card shadow p-4 mb-5 mt-5">
            <h2 class="Login-title">Iniciar Sesión</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group mb-3">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com">
                </div>
                
                <div class="form-group mb-5">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                
                <div class="form-actions d-flex flex-column align-items-center">
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                    <div class="text-center">
                        <a href="recuperar.php" class="auth-link">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
            </form>
            
            <div class="auth-footer text-center mt-3">
                ¿No tienes una cuenta? <a href="registro.php" class="auth-link">Regístrate aquí</a>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>