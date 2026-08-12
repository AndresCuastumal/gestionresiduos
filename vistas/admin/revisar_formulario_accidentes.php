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
$estadoGeneral = $revision['estado_general'] ?? 'pendiente';
$generador = $mensualController->obtenerDatosGenerador($generador_id);
$datosReporte = $accidentesController->obtenerDatosReporteAdicional($generador_id, $anio);
$accionesPreventivas = $accidentesController->obtenerAccionesPreventivas($datosReporte);

// VERIFICAR SI REALMENTE HAY DATOS
$tieneDatos = $accidentesController->existeRegistro($generador_id, $anio);

// Verificar si la revisión está finalizada
$estaFinalizado = $revisionController->estaFinalizado($generador_id, $anio);

$estadoAccidentes = $revision['formulario_accidentes'] ?? '';
$estadosRestrictivos = ['rechazado', 'aprobado'];

// ✅ MODIFICADO: El formulario se bloquea si:
// 1) Está finalizado 
// 2) Este formulario específico ya fue revisado (aprobado/rechazado)
// 3) NO hay datos diligenciados
$formularioBloqueado = $estaFinalizado || in_array($estadoAccidentes, $estadosRestrictivos) || !$tieneDatos; // ✅ NUEVO: Bloquear si no hay datos
$mensajeBloqueo = '';
//Bloquear si estado está pendiente y la fecha limite ya pasó (20 de febrero de cada año)
if ($estadoAccidentes === 'pendiente') {
    $fechaLimite = new DateTime("$anio-02-20");
    $hoy = new DateTime();
    if ($hoy > $fechaLimite) {
        $formularioBloqueado = true;
        $mensajeBloqueo = "La fecha límite para la revisión de este formulario ha pasado. No es posible realizar modificaciones.";
    }
}

// ✅ NUEVO: Mensaje específico para cuando no hay datos

if (!$tieneDatos) {
    $mensajeBloqueo = "No hay datos diligenciados en este formulario. No es posible realizar una revisión hasta que el generador complete la información.";
} elseif ($estaFinalizado) {
    $mensajeBloqueo = "Esta revisión ya ha sido completada y no puede ser modificada.";
} elseif (in_array($estadoAccidentes, $estadosRestrictivos)) {
    $mensajeBloqueo = "Este formulario ya tiene un estado definitivo (" . ucfirst($estadoAccidentes) . ") y no puede ser modificado.";
}

// Obtener información de intentos
$infoIntentos = $revisionController->obtenerInfoIntentos($generador_id, $anio);
$permiteCorreccion = $revisionController->puedeReenviarCorreccion($generador_id, $anio);

// Lista de acciones preventivas posibles
$listaAcciones = [
    'remision_salud' => 'Remisión a servicios de salud',
    'capacitacion_primeros_auxilios' => 'Capacitación en primeros auxilios',
    'investigacion_accidente' => 'Investigación del accidente',
    'actualizacion_procedimientos' => 'Actualización de procedimientos',
    'otra' => 'Otra acción'
];

// Procesar formulario de revisión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si el formulario está bloqueado, no permitir edición
    if ($formularioBloqueado) {
        $_SESSION['warning'] = $mensajeBloqueo;
        header("Location: listado_revisiones_view.php");
        exit();
    }
    
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Validación adicional - Si intenta rechazar y ya no tiene intentos
    if ($estado === 'rechazado' && !$permiteCorreccion) {
        $_SESSION['error'] = "El generador ya ha agotado su única oportunidad de corrección. No puede rechazar este formulario nuevamente.";
        header("Location: revisar_formulario_accidentes.php?generador_id=$generador_id&anio=$anio");
        exit();
    }
    
    $data = [
        'formulario_accidentes' => $estado,
        'observaciones_accidentes' => $observaciones,
        'revisado_por' => $_SESSION['usuario_id'],
        'estado_general' => 'pendiente',
        'generador_id' => $generador_id,
        'anio' => $anio
    ];
    
    try {
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
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav class="mb-3">
        <div class="nav nav-tabs custom-tabs" role="tablist">                
            <a class="nav-link" href="revisar_formulario_mensual.php?generador_id=<?= $generador_id ?>&anio=<?= $anio ?>">
                <i class="bi bi-clipboard-data me-1"></i>Reporte Mensual de Residuos
            </a>                
            <a class="nav-link active" href="#">
                <i class="bi bi-clipboard-check me-2"></i>Capacitaciones, accidentes y auditorías
            </a>
            <a class="nav-link" href="revisar_formulario_contingencias.php?generador_id=<?= $generador_id ?>&anio=<?= $anio ?>">
                <i class="bi bi-exclamation-triangle me-1"></i>Plan de Contingencias
            </a>
        </div>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clipboard-check me-2"></i>Revisión - Capacitaciones, Accidentes y Auditorías</h2>
        <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Lista de Establecimientos
        </a>
    </div>

    <!-- Tarjeta informativa -->
    <div class="card mb-4" style="background-color: #f8f4ceff;">
        <div class="card-body">
            <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                Revisión de capacitaciones, accidentes y auditorías relacionadas con la gestión de residuos peligrosos. 
                Verifique la información y determine el estado del formulario.
                <?php if (!$tieneDatos): ?>
                    <strong class="text-danger">⚠️ Este generador aún no ha diligenciado este formulario.</strong>
                <?php endif; ?>
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
                    <p><strong>Fecha límite de revisión:</strong> 20 de febrero de <?= $anio ?></p>
                    <p><strong>Estado  del formulario:</strong> 
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
                            <?= ucfirst($revision['formulario_accidentes'] ?? 'sin_datos') ?>
                        </span>
                        <?php if($estadoAccidentes === 'pendiente' && $formularioBloqueado): ?>
                            <span class="badge bg-warning ms-2">⏰ VENCIDO</span>
                        <?php endif; ?>
                    </p>                        
                    <p><strong>Estado general:</strong>
                        <?php
                        $clase_estado_general = '';
                        switch ($estadoGeneral) {
                            case 'aprobado': $clase_estado_general = 'badge-estado-aprobado'; break;
                            case 'rechazado': $clase_estado_general = 'badge-estado-rechazado'; break;
                            default: $clase_estado_general = 'badge-estado-pendiente';
                        }
                        ?>
                        <span class="badge-estado <?= $clase_estado_general ?>">
                            <?= ucfirst($estadoGeneral) ?>
                        </span>
                    </p>
                    <p><strong>Intentos de corrección:</strong> 
                        <?= $infoIntentos['intentos_correccion'] ?? 0 ?> de 1 permitidos
                        <?php if (!$permiteCorreccion): ?>
                            <span class="badge bg-danger ms-2">ÚNICA OPORTUNIDAD ALCANZADA</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($infoIntentos['fecha_ultimo_rechazo']): ?>
                        <p><strong>Último rechazo:</strong> <?= date('d/m/Y H:i', strtotime($infoIntentos['fecha_ultimo_rechazo'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($revision['fecha_revision']): ?>
                        <p><strong>Última revisión:</strong> <?= date('d/m/Y H:i', strtotime($revision['fecha_revision'])) ?></p>
                        <p><strong>Por:</strong> <?= isset($revision['nombre_revisor']) ? htmlspecialchars($revision['nombre_revisor']) : 'No disponible' ?></p>                            
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
                                <a href="../../procesos/generador/soportes_generador/<?= $datosReporte['archivo_cronograma'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
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
                                <a href="../../procesos/generador/soportes_generador/<?= $datosReporte['archivo_soportes_capacitaciones'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
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
                    <h6 style="background-color: #fdcaca5d;"><i class="bi bi-clipboard-data me-2"></i>Auditorías internas</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de auditorías internas:</strong> <?= $datosReporte['num_auditorias_internas'] ?></p>
                            <?php if ($datosReporte['archivo_resultados_auditorias_internas']): ?>
                            <p><strong>Resultados de auditorías internas:</strong> 
                                <a href="../../procesos/generador/soportes_generador/<?= $datosReporte['archivo_resultados_auditorias_internas'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivo
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($datosReporte['archivo_plan_mejoramiento_interno']): ?>
                            <p><strong>Plan de mejoramiento interno:</strong> 
                                <a href="../../procesos/generador/soportes_generador/<?= $datosReporte['archivo_plan_mejoramiento_interno'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-2"></i>Ver archivo
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <h6 style="background-color: #cee2f8ff;"><i class="bi bi-clipboard-data me-2"></i>Auditorías externas</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de auditorías externas:</strong> <?= $datosReporte['num_auditorias_externas'] ?></p>
                            <?php if ($datosReporte['archivo_resultados_auditorias_externas']): ?>
                            <p><strong>Resultados de auditorías externas:</strong> 
                                <a href="../../procesos/generador/soportes_generador/<?= $datosReporte['archivo_resultados_auditorias_externas'] ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
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
                    <strong>No hay datos diligenciados</strong><br>
                    El generador aún no ha completado el formulario de capacitaciones, accidentes y auditorías para el año <?= $anio ?>.
                    No es posible realizar una revisión hasta que el generador diligencie la información.
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
                        <?php if ($formularioBloqueado): ?>
                            <!-- Mostrar alerta cuando está bloqueado -->
                            <div class="alert alert-warning">
                                <i class="bi bi-lock-fill me-2"></i>
                                <strong>Revisión <?= $estaFinalizado ? 'Finalizada' : 'Bloqueada' ?></strong> - 
                                <?= $mensajeBloqueo ?>
                                <?php if (!$tieneDatos): ?>
                                    <br><br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <small>El formulario estará disponible para revisión una vez el generador diligencie la información requerida.</small>
                                <?php endif; ?>
                                <?php if ($estaFinalizado && $revision['estado_general'] === 'aprobado'): ?>
                                    <br><br>
                                    El certificado fue enviado al generador.
                                <?php elseif ($estaFinalizado && $revision['estado_general'] === 'rechazado'): ?>
                                    <br><br>
                                    Las observaciones fueron enviadas al generador.
                                <?php endif; ?>
                            </div>
                            
                            <!-- Campos deshabilitados -->
                            <fieldset disabled>
                                <div class="mb-3">
                                    <label class="form-label">Estado del formulario:</label>
                                    <select name="estado" class="form-select">
                                        <option value="<?= $revision['formulario_accidentes'] ?? 'sin_datos' ?>" selected>
                                            <?= ucfirst($revision['formulario_accidentes'] ?? 'Sin datos') ?>
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                              placeholder="No disponible mientras el formulario esté bloqueado"><?= htmlspecialchars($revision['observaciones_accidentes'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="listado_revisiones_view.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Volver
                                    </a>
                                    <button type="button" class="btn btn-secondary">
                                        <i class="bi bi-lock me-2"></i>
                                        <?= !$tieneDatos ? 'Formulario sin datos' : 'Formulario Bloqueado' ?>
                                    </button>
                                </div>
                            </fieldset>
                            
                        <?php else: ?>
                            <!-- Mostrar advertencia si no permite más correcciones -->
                            <?php if (!$permiteCorreccion && $estadoGeneral === 'rechazado'): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>ÚNICA OPORTUNIDAD DE CORRECCIÓN ALCANZADA</strong> - El generador ha agotado su única oportunidad de corrección. 
                                    <strong>No puede rechazar este formulario nuevamente.</strong>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Formulario normal cuando hay datos y NO está bloqueado -->
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

<?php include '../../includes/footer.php'; ?>