<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistema de Autenticación'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/reportegestionresiduos/assets/css/estilos_simples.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    
</head>
<body>
    <div class="container">
        <div class="hero-section text-center">
            <div style="background-color: #eed296ff;" class="p-4">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <img src="/reportegestionresiduos/assets/css/logoNuevoSMS2024.png" alt="Logo SMS" 
                            class="me-3 img-fluid d-none d-md-block" 
                            style="max-height: 100px; width: auto;">
                        <img src="../../assets/css/logoNuevoSMS2024.png" alt="Logo SMS" 
                            class="me-2 img-fluid d-md-none" 
                            style="max-height: 40px; width: auto;">
                        <h1 class="h3 mb-0 text-center">Sistema de Reporte de Gestión de Residuos Generados en Atención en Salud y Otras Actividades</h1>
                    </div>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <div >
                        <span class="navbar-text me-3 d-none d-md-inline">
                            <?php echo $_SESSION['usuario_email']; ?>
                        </span>
                        <a href="/reportegestionresiduos/logout.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-door-open me-1"></i> Cerrar Sesión
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>