<?php include '../../includes/header.php'; ?>
<main class="auth-container d-flex justify-content-center align-items-center min-vh-70">
    <div class="auth-card shadow p-4 mb-5 mt-5">
        <h2 class = "auth-title">Registro de usuario</h2>
    <?php if (isset($_GET['error'])): ?>
        <div class = "alert alert-error">
            <code><?php echo htmlspecialchars($_GET['error']); ?></code>
        </div>
    <?php endif; ?>
        <form  class = "auth-form" id="registroForm" method="post" action="../../procesos/login/registrar.php" onsubmit="return validarFormulario()">
            <div class = "form-group mb-3">
                <label for="email">Correo electrónico:</label>
                <input type="email" name="email" required placeholder="tu@mail.com">
            </div>
            
            <div class = "form-group mb-3">
                <label>Contraseña (mínimo 6 caracteres):</label>
                <input type="password" name="password" id="password" required minlength="6" placeholder="••••••••">
            </div>
            
            <div class = "form-group mb-3">
                <label>Confirmar Contraseña:</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••">
                <span id="mensajeError" style="color:red;"></span>
            </div>
            <div class = "form-actions d-flex flex-column align-items-center">
                <button type="submit">Registrarse</button>
                <a href="login.php" class="auth-link">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </form>
    </div>
</main>


<!-- Incluir JavaScript externo -->
<script src="../../assets/js/validacion-registro.js"></script>
<?php include '../../includes/footer.php'; ?>