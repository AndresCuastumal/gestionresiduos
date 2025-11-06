<?php
session_start();
require_once '../includes/conexion.php'; // Archivo con la conexión a tu BD

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

// Obtener rol del usuario para permisos
$rol = $_SESSION['usuario_rol'];

include '../includes/header.php'; // Incluye el encabezado HTML
?>
        <!-- Sección hero con explicación -->
        <div class="hero-section text-center">
            <br>
            <div class="hero-content  p-4 " style="background-color: #f8f4ceff;">
                <p class="lead mt-1" style="text-align: justify; text-justify: inter-word;">
                    Plataforma de reporte según Resolución 591 de 2024. De acuerdo con la normatividad vigente, 
                    todos los estableciminetos que generen residuos por la atención en salud y otras actividades deben reportar anualmente información relacionada con 
                    la gestión de los mismos. Para ello se deben diligenciar tres formularios:
                </p>
                <ul class="list-group list-group-flush mt-4">
                    <li class="list-group-item border-0" style="background-color: #ebe5af75;">
                        <i class="bi bi-file-earmark-spreadsheet me-2" style="color: #d97706;"></i>Reporte mensual de residuos generados en atención en salud
                    </li>
                    <li class="list-group-item border-0" style="background-color: #f8f4ceff;">
                        <i class="bi bi-mortarboard me-2" style="color: #059669;"></i>Capacitaciones, accidentes y auditorías realizadas
                    </li>
                    <li class="list-group-item border-0" style="background-color: #ebe5af75;">
                        <i class="bi bi-exclamation-triangle me-2" style="color: #dc2626;"></i>Contingencias
                    </li>
                </ul>    
            </div>
        </div>

        <!-- Tarjetas informativas -->
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-primary fs-1"></i>
                        <h5 class="card-title">Reporte Año <?= date('Y', strtotime('-1 year') ) ?></h5>
                        <p class="card-text">Módulo para reportar información relacionada con gestión de residuos generados en atención en salud y otras activiades para el año <?= date('Y', strtotime('-1 year') ) ?>.</p>
                        <?php if (in_array($rol, ['generador'])): ?>
                            <a href="generador/reporte_mensual_view.php" class="btn btn-sm btn-outline-primary mt-2">Acceder</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-search fs-1 mb-3 text-success"></i>
                        <h5 class="card-title">Revisión Salud Ambiental</h5>
                        <p class="card-text">Módulo de revisión y gestión de validación de la información reportada.</p>
                        <?php if (in_array($rol, ['admin'])): ?>
                            <a href="admin/listado_revisiones_view.php" class="btn btn-sm btn-outline-success mt-2">Revisión de reportes</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-pdf fs-1 mb-3 text-danger"></i>
                        <h5 class="card-title">Certificados</h5>
                        <p class="card-text">Descargue certificados oficiales una vez sus reportes sean aprobados.</p>
                        <?php if (in_array($rol, ['generador'])): ?>
                            <a href="generador/certificados.php" class="btn btn-sm btn-outline-danger mt-2">Descargar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <?php include '../includes/footer.php'; // Incluye el pie de página ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>