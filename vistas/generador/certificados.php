<?php
session_start();
require_once '../../includes/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

// Verificar que el usuario es generador
if ($_SESSION['usuario_rol'] !== 'generador') {
    header("Location: acceso_denegado.php");
    exit();
}

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario_id'];
$anio_actual = date('Y', strtotime('-1 year'));

// Función para obtener certificados del usuario
function obtenerCertificadosUsuario($conn, $usuario_id, $anio) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                g.id as generador_id,
                g.nom_generador,
                ra.certificado_pdf,
                ra.estado_general,
                ra.fecha_finalizacion
            FROM generador g
            JOIN usuario_generador ug ON g.id = ug.generador_id
            LEFT JOIN revisiones_anuales ra ON g.id = ra.generador_id AND ra.anio = ?
            WHERE ug.usuario_id = ? 
            AND ra.certificado_pdf IS NOT NULL
            AND ra.estado_general = 'aprobado'
            ORDER BY g.nom_generador
        ");
        $stmt->execute([$anio, $usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener certificados: " . $e->getMessage());
        return [];
    }
}

// Obtener certificados del usuario
$certificados = obtenerCertificadosUsuario($conn, $usuario_id, $anio_actual);
$tiene_certificados = !empty($certificados);

include '../../includes/header.php';
?>

<!-- Contenedor principal -->
<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Certificados</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-pdf me-2 text-danger"></i>Certificados de Aprobación</h2>
        <a href="../dashboard.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
        </a>
    </div>

    <!-- Tarjeta informativa -->
    <div class="card mb-4" style="background-color: #f8f4ceff;">
        <div class="card-body">
            <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                En esta sección puede descargar los certificados oficiales de sus establecimientos que han completado 
                exitosamente el proceso de revisión para el año <?= $anio_actual ?>. Los certificados estarán disponibles 
                una vez que todos sus formularios hayan sido aprobados por el equipo de salud ambiental.
            </p>
        </div>
    </div>

    <?php if ($tiene_certificados): ?>
        <!-- Lista de certificados disponibles -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2 text-success"></i>Certificados Disponibles</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Establecimiento</th>
                                <th>Estado</th>
                                <th>Fecha de Aprobación</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificados as $certificado): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($certificado['nom_generador']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge-estado badge-estado-aprobado">
                                            <i class="bi bi-check-circle me-1"></i>Aprobado
                                        </span>
                                    </td>
                                    <td>
                                        <?= $certificado['fecha_finalizacion'] ? date('d/m/Y', strtotime($certificado['fecha_finalizacion'])) : 'No disponible' ?>
                                    </td>
                                    <td>
                                        <a href="../../procesos/uploads/certificados/<?= $certificado['certificado_pdf'] ?>" 
                                           target="_blank"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Descargar Certificado">
                                            <i class="bi bi-download me-1"></i>Descargar PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Modal que se muestra automáticamente cuando no hay certificados -->
        <div class="modal fade show d-block" id="modalSinCertificados" tabindex="-1" aria-labelledby="modalSinCertificadosLabel" aria-modal="true" role="dialog" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-warning" id="modalSinCertificadosLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>Certificados No Disponibles
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>No hay certificados disponibles para descargar en este momento.</strong>
                        </div>
                        <p>Los certificados estarán disponibles cuando:</p>
                        <ul>
                            <li>Haya completado los tres formularios de reporte anual</li>
                            <li>Sus formularios hayan sido revisados y aprobados por salud ambiental</li>
                            <li>El proceso de revisión haya finalizado completamente</li>
                        </ul>
                        <p class="mb-0">
                            <strong>Año de reporte actual:</strong> <?= $anio_actual ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <a href="../dashboard.php" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>Volver al Dashboard
                        </a>
                        <a href="listado_generadores_view.php" class="btn btn-outline-primary">
                            <i class="bi bi-clipboard-data me-2"></i>Ver Mis Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card informativa adicional -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-text text-muted fs-1 mb-3"></i>
                <h5 class="text-muted">Proceso de Certificación</h5>
                <p class="text-muted">
                    Una vez que complete y envíe todos sus formularios, y estos sean aprobados por el revisor, 
                    podrá descargar aquí su certificado oficial.
                </p>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <i class="bi bi-clipboard-check text-primary fs-4"></i>
                            <h6>Complete Formularios</h6>
                            <small class="text-muted">Reporte mensual, capacitaciones y contingencias</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <i class="bi bi-search text-warning fs-4"></i>
                            <h6>Espere Revisión</h6>
                            <small class="text-muted">Salud ambiental revisará la información</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <i class="bi bi-award text-success fs-4"></i>
                            <h6>Obtenga Certificado</h6>
                            <small class="text-muted">Descargue su certificado aprobado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script para evitar que el modal se cierre al hacer clic fuera -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('modalSinCertificados'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            });
        </script>

    <?php endif; ?>
</div>

<!-- Footer -->
<?php include '../../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>