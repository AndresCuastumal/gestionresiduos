<?php
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';
require_once '../../procesos/admin/reporte_mensual_controller.php';

// Verificar sesión y permisos de admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

if (!isset($_GET['generador_id']) || !isset($_GET['anio'])) {
    header("Location: ../admin/listado_revisiones_view.php");
    exit();
}

$generador_id = $_GET['generador_id'];
$anio = $_GET['anio'];

$revisionController = new RevisionesController($conn);
$mensualController = new ReporteMensualController($conn);

// Obtener datos
$revision = $revisionController->obtenerRevision($generador_id, $anio);
$generador = $mensualController->obtenerDatosGenerador($generador_id);
$reportes_existentes = $mensualController->obtenerReportesExistentes($generador_id, $anio);

// Verificar si la revisión está finalizada
if ($revisionController->estaFinalizado($generador_id, $anio)) {
    $_SESSION['warning'] = "Esta revisión ya ha sido finalizada y no puede ser modificada.";
    header("Location: listado_revisiones_view.php");
    exit();
}

//procesar formulario de revisión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    $data = [
        'formulario_mensual' => $estado,
        'observaciones_mensual' => $observaciones,
        'revisado_por' => $_SESSION['usuario_id'],
        'estado_general' => 'pendiente', // Se calculará automáticamente
        'generador_id' => $generador_id,
        'anio' => $anio
    ];
    
    if ($revisionController->actualizarRevision($data)) {
        $_SESSION['success'] = "Revisión del reporte mensual actualizada correctamente";
        
        // Determinar a qué formulario redirigir
        $siguiente_formulario = $revisionController->determinarSiguienteFormulario($generador_id, $anio);
        
        // Verificar si todos están aprobados
        if ($revisionController->verificarFormulariosCompletos($generador_id, $anio)) {
            $_SESSION['info'] = "¡Todos los formularios han sido aprobados!";
        }
        
        header("Location: $siguiente_formulario");
        exit();
    } else {
        $_SESSION['error'] = "Error al actualizar la revisión";
    }
}

include '../../includes/header.php';
?>
    <!-- Contenedor principal -->
    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listado_revisiones_view.php">Revisiones</a></li>
                <li class="breadcrumb-item active">Revisión - Reporte Mensual</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check me-2"></i>Revisión - Reporte Mensual de Residuos</h2>
            <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Revisión del reporte mensual de residuos generados en atención en salud. Verifique la información y determine el estado del formulario.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Reporte</h5>
            </div>
            <div class="card-body">
                <!-- Información del generador -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Información del Generador</h6>
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($generador['nom_generador']) ?></p>
                        <p><strong>NIT:</strong> <?= htmlspecialchars($generador['nit']) ?></p>
                        <p><strong>Dirección:</strong> <?= htmlspecialchars($generador['dir_establecimiento']) ?></p>
                        <p><strong>Responsable:</strong> <?= htmlspecialchars($generador['nom_responsable']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Detalles de la Revisión</h6>
                        <p><strong>Año:</strong> <?= $anio ?></p>
                        <p><strong>Estado actual:</strong> 
                            <?php
                            $clase_estado = '';
                            switch ($revision['formulario_mensual']) {
                                case 'aprobado': $clase_estado = 'badge-estado-aprobado'; break;
                                case 'rechazado': $clase_estado = 'badge-estado-rechazado'; break;
                                default: $clase_estado = 'badge-estado-pendiente';
                            }
                            ?>
                            <span class="badge-estado <?= $clase_estado ?>">
                                <?= ucfirst($revision['formulario_mensual']) ?>
                            </span>
                        </p>
                        <?php if ($revision['fecha_revision']): ?>
                            <p><strong>Última revisión:</strong> <?= date('d/m/Y H:i', strtotime($revision['fecha_revision'])) ?></p>
                            <p><strong>Por:</strong> <?= htmlspecialchars($revision['nombre_revisor']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Datos del reporte mensual en 2 columnas -->
                <h6 class="text-muted mb-3">Datos del Reporte Mensual (kg)</h6>
                <div class="meses-grid">
                    <?php
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ];
                    
                    foreach ($meses as $id_mes => $nombre_mes):
                        $valor_actual = '';
                        foreach ($reportes_existentes as $reporte) {
                            if ($reporte['id_mes'] == $id_mes) {
                                $valor_actual = $reporte['total_kg'];
                                break;
                            }
                        }
                    ?>
                    <div class="mes-item">
                        <span class="mes-nombre"><?= $nombre_mes ?></span>
                        <input type="number" step="0.01" min="0" 
                               value="<?= $valor_actual ?>"
                               class="form-control form-control-sm mes-cantidad" 
                               disabled>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Soporte documental -->
                <?php if (!empty($revision['soporte_pdf'])): ?>
                <div class="mt-4">
                    <h6 class="text-muted">Soporte Documental</h6>
                    <a href="../../procesos/uploads/soportes_anuales/<?= $revision['soporte_pdf'] ?>" 
                       target="_blank" class="btn btn-outline btn-outline-primary">
                        <i class="bi bi-download me-2"></i>Ver PDF de soporte
                    </a>
                </div>
                <?php endif; ?>

                <!-- Formulario de revisión -->
                <form method="POST" class="mt-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Evaluación del Administrador</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($revisionController->estaFinalizado($generador_id, $anio)): ?>
                                <!-- ⭐ NUEVO: Mostrar alerta cuando está finalizado -->
                                <div class="alert alert-warning">
                                    <i class="bi bi-lock-fill me-2"></i>
                                    <strong>Revisión Finalizada</strong> - Esta revisión ya ha sido completada y no puede ser modificada. 
                                    <?php if ($revision['estado_general'] === 'aprobado'): ?>
                                        El certificado fue enviado al generador.
                                    <?php else: ?>
                                        Las observaciones fueron enviadas al generador.
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Campos deshabilitados -->
                                <fieldset disabled>
                                    <div class="mb-3">
                                        <label class="form-label">Estado del formulario:</label>
                                        <select name="estado" class="form-select">
                                            <option value="<?= $revision['formulario_mensual'] ?>" selected>
                                                <?= ucfirst($revision['formulario_mensual']) ?>
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Observaciones:</label>
                                        <textarea name="observaciones" class="form-control" rows="4"><?= htmlspecialchars($revision['observaciones_mensual'] ?? '') ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="listado_revisiones_view.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Volver
                                        </a>
                                        <button type="button" class="btn btn-secondary">
                                            <i class="bi bi-lock me-2"></i>Formulario Bloqueado
                                        </button>
                                    </div>
                                </fieldset>
                                
                            <?php else: ?>
                                <!-- Formulario normal cuando NO está finalizado -->
                                <div class="mb-3">
                                    <label class="form-label">Estado del formulario:</label>
                                    <select name="estado" class="form-select" required>
                                        <option value="">Seleccione un estado...</option>
                                        <option value="aprobado" <?= $revision['formulario_mensual'] === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="rechazado" <?= $revision['formulario_mensual'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                            placeholder="Ingrese observaciones sobre la revisión..."><?= htmlspecialchars($revision['observaciones_mensual'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="listado_revisiones_view.php?<?= http_build_query([
                                        'tipo_sujeto' => $_GET['tipo_sujeto'] ?? '',
                                        'estado_general' => $_GET['estado_general'] ?? '',
                                        'pagina' => $_GET['pagina'] ?? 1
                                    ]) ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Volver a la lista
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>Guardar Revisión
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
