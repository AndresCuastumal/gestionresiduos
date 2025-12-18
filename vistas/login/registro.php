<?php
require '../../includes/conexion.php';
include '../../includes/header.php';
?>

<main class="auth-container d-flex justify-content-center align-items-center min-vh-70 mb-5 mt-5">
    <div class="auth-card p-4 shadow">
        <h2 class="auth-title">Crear Cuenta</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <form method="post" action="../../procesos/login/procesar_registro_paso1.php">
            <div class="form-group mb-3">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required placeholder="tu@mail.com">
            </div>
            <div class="form-actions d-flex flex-column align-items-center">
                <button type="submit" class="btn btn-primary">Verificar correo</button>
                <a href="login.php" class="auth-link">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>