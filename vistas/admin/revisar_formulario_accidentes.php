<?php
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';
require_once '../../procesos/admin/reporte_accidentes_controller.php';
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
$accidentesController = new ReporteAccidentesController($conn);
$mensualController = new ReporteMensualController($conn);

// Obtener datos
$revision = $revisionController->obtenerRevision($generador_id, $anio);
$generador = $mensualController->obtenerDatosGenerador($generador_id);
$datosReporte = $accidentesController->obtenerDatosReporteAdicional($generador_id, $anio);
$accionesPreventivas = $accidentesController->obtenerAccionesPreventivas($datosReporte);

// Verificar si la revisión está finalizada
if ($revisionController->estaFinalizado($generador_id, $anio)) {
    $_SESSION['warning'] = "Esta revisión ya ha sido finalizada y no puede ser modificada.";
    header("Location: listado_revisiones_view.php");
    exit();
}

// VERIFICAR SI REALMENTE HAY DATOS - NUEVA LÓGICA
$tieneDatos = $accidentesController->existeRegistro($generador_id, $anio);

// Lista de acciones preventivas posibles
// Lista de acciones preventivas posibles - DEBE COINCIDIR CON LOS VALORES DEL FORMULARIO
$listaAcciones = [
    'remision_salud' => 'Remisión a servicios de salud',
    'capacitacion_primeros_auxilios' => 'Capacitación en primeros auxilios',
    'investigacion_accidente' => 'Investigación del accidente',
    'actualizacion_procedimientos' => 'Actualización de procedimientos',
    'otra' => 'Otra acción'
];

// Procesar formulario de revisión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    $data = [
        'formulario_accidentes' => $estado,
        'observaciones_accidentes' => $observaciones,
        'revisado_por' => $_SESSION['usuario_id'],
        'estado_general' => 'pendiente',
        'generador_id' => $generador_id,
        'anio' => $anio
    ];
    
    if ($revisionController->actualizarRevisionAccidentes($data)) {
        $_SESSION['success'] = "Revisión de capacitaciones y accidentes actualizada correctamente";
        
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
<style>

</style>
    <!-- Contenedor principal -->
    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listado_revisiones_view.php">Revisiones</a></li>
                <li class="breadcrumb-item active">Revisión - Capacitaciones y Accidentes</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check me-2"></i>Revisión - Capacitaciones, Accidentes y Auditorías</h2>
            <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Revisión de capacitaciones, accidentes y auditorías relacionadas con la gestión de residuos peligrosos. 
                    Verifique la información y determine el estado del formulario.
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
                        <p><strong>Responsable:</strong> <?= htmlspecialchars($generador['nom_responsable']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Detalles de la Revisión</h6>
                        <p><strong>Año:</strong> <?= $anio ?></p>
                        <p><strong>Estado actual:</strong> 
                            <?php
                            $clase_estado = '';
                            switch ($revision['formulario_accidentes']) {
                                case 'aprobado': $clase_estado = 'badge-estado-aprobado'; break;
                                case 'rechazado': $clase_estado = 'badge-estado-rechazado'; break;
                                case 'pendiente': $clase_estado = 'badge-estado-pendiente'; break;
                                case 'sin_datos': $clase_estado = 'badge-estado-sin-datos'; break;
                                default: $clase_estado = 'badge-estado-pendiente';
                            }
                            ?>
                            <span class="badge-estado <?= $clase_estado ?>">
                                <?= ucfirst($revision['formulario_accidentes']) ?>
                            </span>
                        </p>
                        <?php if ($revision['fecha_revision']): ?>
                            <p><strong>Última revisión:</strong> <?= date('d/m/Y H:i', strtotime($revision['fecha_revision'])) ?></p>                            
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($tieneDatos): ?>
                <!-- Datos de capacitaciones -->
                <div class="info-card">
                    <h6><i class="bi bi-mortarboard me-2"></i>Capacitaciones</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Capacitaciones programadas:</strong> <?= $datosReporte['num_capacitaciones_programadas'] ?></p>
                            <?php if ($datosReporte['archivo_cronograma']): ?>
                            <p><strong>Cronograma:</strong> 
                                <a href="../../procesos/uploads/soportes_anuales/<?= $datosReporte['archivo_cronograma'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivo
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Capacitaciones ejecutadas:</strong> <?= $datosReporte['num_capacitaciones_ejecutadas'] ?></p>
                            <?php if ($datosReporte['archivo_soportes_capacitaciones']): ?>
                            <p><strong>Número de personas capacitadas:</strong> <?= $datosReporte['num_empleados_capacitados'] ?></p>
                            <p><strong>Soportes:</strong> 
                                <a href="../../procesos/uploads/soportes_anuales/<?= $datosReporte['archivo_soportes_capacitaciones'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivos
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Datos de accidentes -->
                <div class="info-card">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Accidentes</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>¿Tuvo accidentes?:</strong> <?= ucfirst($datosReporte['tiene_accidentes']) ?></p>
                            <?php if ($datosReporte['tiene_accidentes'] === 'si'): ?>
                            <p><strong>Número de accidentes:</strong> <?= $datosReporte['num_accidentes'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesPreventivas)): ?>
                            <p><strong>Acciones preventivas implementadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesPreventivas as $accionKey): ?>
                                    <?php if (isset($listaAcciones[$accionKey])): ?>
                                    <li><?= $listaAcciones[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($datosReporte['otra_accion_preventiva'])): ?>
                            <p class="mt-2"><strong>Otra acción preventiva:</strong> <?= htmlspecialchars($datosReporte['otra_accion_preventiva']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Datos de auditorías -->
                <div class="info-card">
                    <h6><i class="bi bi-clipboard-data me-2"></i>Auditorías</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de auditorías:</strong> <?= $datosReporte['num_auditorias'] ?></p>
                            <?php if ($datosReporte['archivo_resultados_auditorias']): ?>
                            <p><strong>Resultados de auditorías:</strong> 
                                <a href="../../procesos/uploads/soportes_anuales/<?= $datosReporte['archivo_resultados_auditorias'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivo
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($datosReporte['archivo_plan_mejoramiento']): ?>
                            <p><strong>Plan de mejoramiento:</strong> 
                                <a href="../../procesos/uploads/soportes_anuales/<?= $datosReporte['archivo_plan_mejoramiento'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivo
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No se ha encontrado información para este año.
                </div>
                <?php endif; ?>

                <!-- Formulario de revisión -->
                <form method="POST" class="mt-4">
                    <input type="hidden" name="generador_id" value="<?= $generador_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio ?>">
                    
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
                                            <option value="<?= $revision['formulario_accidentes'] ?>" selected>
                                                <?= ucfirst($revision['formulario_accidentes']) ?>
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Observaciones:</label>
                                        <textarea name="observaciones" class="form-control" rows="4"><?= htmlspecialchars($revision['observaciones_accidentes'] ?? '') ?></textarea>
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
                                <!-- Formulario normal cuando hay datos y NO está finalizado -->
                                <div class="mb-3">
                                    <label class="form-label">Estado del formulario:</label>
                                    <select name="estado" class="form-select" required>
                                        <option value="">Seleccione un estado...</option>
                                        <option value="aprobado" <?= $revision['formulario_accidentes'] === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="rechazado" <?= $revision['formulario_accidentes'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                            placeholder="Ingrese observaciones sobre la revisión..."><?= htmlspecialchars($revision['observaciones_accidentes'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="listado_revisiones_view.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Volver
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