<?php
require '../../includes/conexion.php';
include '../../includes/header.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: ../../vistas/login/registro.php?error=Token inválido.");
    exit();
}

try {
    // Verificar token válido y no expirado
    $stmt = $conn->prepare("SELECT * FROM registros_pendientes WHERE token_verificacion = :token AND expiracion_token > NOW()");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $registro_pendiente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro_pendiente) {
        header("Location: ../../vistas/login/registro.php?error=El enlace ha expirado o es inválido.");
        exit();
    }
    
} catch (Exception $e) {
    header("Location: ../../vistas/login/registro.php?error=Error al verificar el enlace.");
    exit();
}
?>

<main class="auth-container d-flex justify-content-center align-items-center min-vh-70 mb-5 mt-5">
    <div class="auth-card p-4 shadow">
        <h2 class="auth-title">Completar Registro</h2>
        <p class="text-muted">Verificando: <?php echo htmlspecialchars($registro_pendiente['email']); ?></p>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <form method="post" action="../../procesos/login/procesar_registro_paso2.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group mb-3">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group mb-3">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-actions d-flex flex-column align-items-center">
                <button type="submit" class="btn btn-primary">Completar Registro</button>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>