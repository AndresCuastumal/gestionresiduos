<?php
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';
require_once '../../procesos/admin/reporte_contingencias_controller.php';
require_once '../../procesos/admin/reporte_mensual_controller.php';


// Función para construir URL con filtros
function construirUrlConFiltros($baseUrl, $paramsAdicionales = []) {
    $filtros = [
        'tipo_sujeto' => $_GET['tipo_sujeto'] ?? '',
        'estado_general' => $_GET['estado_general'] ?? '',
        'pagina' => $_GET['pagina'] ?? 1
    ];
    
    $todosParams = array_merge($filtros, $paramsAdicionales);
    return $baseUrl . '?' . http_build_query($todosParams);
}

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
$contingenciasController = new ReporteContingenciasController($conn);
$mensualController = new ReporteMensualController($conn);

// Obtener datos
$revision = $revisionController->obtenerRevision($generador_id, $anio);
$generador = $mensualController->obtenerDatosGenerador($generador_id);
$datosContingencias = $contingenciasController->obtenerDatosContingencias($generador_id, $anio);

// Verificar si la revisión está finalizada
if ($revisionController->estaFinalizado($generador_id, $anio)) {
    $_SESSION['warning'] = "Esta revisión ya ha sido finalizada y no puede ser modificada.";
    header("Location: listado_revisiones_view.php");
    exit();
}

// Obtener listas de acciones
$accionesIncendios = $contingenciasController->obtenerAccionesIncendios();
$accionesAgua = $contingenciasController->obtenerAccionesAgua();
$accionesEnergia = $contingenciasController->obtenerAccionesEnergia();
$accionesDerrames = $contingenciasController->obtenerAccionesDerrames();
$accionesRecoleccion = $contingenciasController->obtenerAccionesRecoleccion();
$accionesOperativas = $contingenciasController->obtenerAccionesOperativas();
$tiposDerrames = $contingenciasController->obtenerTiposDerrames();

// Procesar acciones JSON
$accionesIncendiosData = $contingenciasController->obtenerAccionesJSON($datosContingencias['incendios_acciones'] ?? '');
$accionesAguaData = $contingenciasController->obtenerAccionesJSON($datosContingencias['agua_acciones'] ?? '');
$accionesEnergiaData = $contingenciasController->obtenerAccionesJSON($datosContingencias['energia_acciones'] ?? '');
$accionesDerramesData = $contingenciasController->obtenerAccionesJSON($datosContingencias['derrames_acciones'] ?? '');
$accionesRecoleccionData = $contingenciasController->obtenerAccionesJSON($datosContingencias['recoleccion_acciones'] ?? '');
$accionesOperativasData = $contingenciasController->obtenerAccionesJSON($datosContingencias['operativas_acciones'] ?? '');

// Procesar formulario de revisión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    $data = [
        'formulario_contingencias' => $estado,
        'observaciones_contingencias' => $observaciones,
        'revisado_por' => $_SESSION['usuario_id'],
        'estado_general' => 'pendiente',
        'generador_id' => $generador_id,
        'anio' => $anio
    ];
    
    if ($revisionController->actualizarRevisionContingencias($data)) {
        $_SESSION['success'] = "Revisión del plan de contingencias actualizada correctamente";
        
        // Determinar a qué formulario redirigir
        $siguiente_formulario = $revisionController->determinarSiguienteFormulario($generador_id, $anio);
        
        // Verificar si todos están aprobados para mostrar mensaje especial
        if ($revisionController->verificarFormulariosCompletos($generador_id, $anio)) {
            $_SESSION['info'] = "¡Todos los formularios han sido aprobados! Se procederá con la generación del certificado.";
        } else {
            $_SESSION['info'] = "Revisión completada. Estado general actualizado.";
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
                <li class="breadcrumb-item active">Revisión - Plan de Contingencias</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-exclamation me-2"></i>Revisión - Plan de Contingencias</h2>
            <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Revisión del plan de contingencias y manejo de emergencias relacionadas con residuos peligrosos. 
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
                            switch ($revision['formulario_contingencias']) {
                                case 'aprobado': $clase_estado = 'badge-estado-aprobado'; break;
                                case 'rechazado': $clase_estado = 'badge-estado-rechazado'; break;
                                case 'pendiente': $clase_estado = 'badge-estado-pendiente'; break;
                                case 'sin_datos': $clase_estado = 'badge-estado-sin-datos'; break;
                                default: $clase_estado = 'badge-estado-pendiente';
                            }
                            ?>
                            <span class="badge-estado <?= $clase_estado ?>">
                                <?= ucfirst($revision['formulario_contingencias']) ?>
                            </span>
                        </p>
                        <?php if ($revision['fecha_revision']): ?>
                            <p><strong>Última revisión:</strong> <?= date('d/m/Y H:i', strtotime($revision['fecha_revision'])) ?></p>                            
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($datosContingencias): ?>
                <!-- Información general -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-calendar me-2"></i>Información General</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Fecha de reporte:</strong> <?= date('d/m/Y', strtotime($datosContingencias['fecha_reporte'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Persona que reporta:</strong> <?= htmlspecialchars($datosContingencias['nombre_persona_reporta'] ?? 'No especificado') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Incendios -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-fire me-2"></i>Incendios</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['incendios_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesIncendiosData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesIncendiosData as $accionKey): ?>
                                    <?php if (isset($accionesIncendios[$accionKey])): ?>
                                    <li><?= $accionesIncendios[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['incendios_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['incendios_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Inundaciones -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-droplet me-2"></i>Inundaciones</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['inundaciones_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($datosContingencias['inundaciones_acciones'])): ?>
                            <p><strong>Acciones tomadas:</strong> <?= htmlspecialchars($datosContingencias['inundaciones_acciones']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Agua -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-droplet-fill me-2"></i>Falta de Agua</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['agua_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesAguaData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesAguaData as $accionKey): ?>
                                    <?php if (isset($accionesAgua[$accionKey])): ?>
                                    <li><?= $accionesAgua[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['agua_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['agua_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Energía -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-lightning-charge me-2"></i>Falta de Energía</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['energia_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesEnergiaData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesEnergiaData as $accionKey): ?>
                                    <?php if (isset($accionesEnergia[$accionKey])): ?>
                                    <li><?= $accionesEnergia[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['energia_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['energia_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Derrames -->
                <div class="info-card mb-6">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Derrames</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['derrames_numero'] ?></p>
                        </div>
                        <?php if (!empty($datosContingencias['derrames_tipo'])): ?>
                        <div class="col-md-4">                            
                            <p><strong>Tipo de derrame:</strong> 
                                <?= isset($tiposDerrames[$datosContingencias['derrames_tipo']]) ? $tiposDerrames[$datosContingencias['derrames_tipo']] : $datosContingencias['derrames_tipo'] ?>
                            </p>                            
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <?php if (!empty($accionesDerramesData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesDerramesData as $accionKey): ?>
                                    <?php if (isset($accionesDerrames[$accionKey])): ?>
                                    <li><?= $accionesDerrames[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['derrames_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['derrames_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recolección -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-trash me-2"></i>Fallas en Recolección</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['recoleccion_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesRecoleccionData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesRecoleccionData as $accionKey): ?>
                                    <?php if (isset($accionesRecoleccion[$accionKey])): ?>
                                    <li><?= $accionesRecoleccion[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['recoleccion_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['recoleccion_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Operativas -->
                <div class="info-card mb-4">
                    <h6><i class="bi bi-gear me-2"></i>Fallas Operativas</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número de incidentes:</strong> <?= $datosContingencias['operativas_numero'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($accionesOperativasData)): ?>
                            <p><strong>Acciones tomadas:</strong></p>
                            <ul class="mb-0">
                                <?php foreach ($accionesOperativasData as $accionKey): ?>
                                    <?php if (isset($accionesOperativas[$accionKey])): ?>
                                    <li><?= $accionesOperativas[$accionKey] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if (!empty($datosContingencias['operativas_otra_accion'])): ?>
                            <p class="mt-2"><strong>Otra acción:</strong> <?= htmlspecialchars($datosContingencias['operativas_otra_accion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No se ha encontrado información de contingencias para este año.
                </div>
                <?php endif; ?>

                <?php 
                // Determinar si el formulario tiene datos de manera más precisa
                $tiene_datos_contingencias = $contingenciasController->existeRegistro($generador_id, $anio);
                $formulario_sin_datos = !$tiene_datos_contingencias || ($revision['formulario_contingencias'] === 'sin_datos');
                ?>

                <!-- Formulario de revisión -->
                <form method="POST" class="mt-4">
                    <input type="hidden" name="generador_id" value="<?= $generador_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio ?>">
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Evaluación del Administrador</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($formulario_sin_datos): ?>
                                <!-- Mostrar mensaje cuando no hay datos -->
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <?php if (!$tiene_datos_contingencias): ?>
                                        No se encontraron datos de contingencias reportados para este año.
                                    <?php else: ?>
                                        Este formulario está marcado como "sin datos". No es posible realizar la revisión.
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Campos deshabilitados -->
                                <div class="mb-3">
                                    <label class="form-label">Estado del formulario:</label>
                                    <select name="estado" class="form-select" disabled>
                                        <option value="sin_datos" selected>Sin datos</option>
                                    </select>
                                    <input type="hidden" name="estado" value="sin_datos">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                            placeholder="No se pueden agregar observaciones sin datos..." disabled></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="listado_revisiones_view.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Volver al Listado
                                    </a>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="bi bi-lock me-2"></i>Formulario Bloqueado
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Formulario normal cuando hay datos -->
                                <div class="mb-3">
                                    <label class="form-label">Estado del formulario:</label>
                                    <select name="estado" class="form-select" required>
                                        <option value="">Seleccione un estado...</option>
                                        <option value="aprobado" <?= $revision['formulario_contingencias'] === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="rechazado" <?= $revision['formulario_contingencias'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                            placeholder="Ingrese observaciones sobre la revisión..."><?= htmlspecialchars($revision['observaciones_contingencias'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="revisar_formulario_accidentes.php?generador_id=<?= $generador_id ?>&anio=<?= $anio ?>" 
                                        class="btn btn-outline-primary me-2">
                                            <i class="bi bi-skip-backward me-2"></i>Volver a Accidentes
                                        </a>
                                        <a href="listado_revisiones_view.php?<?= http_build_query([
                                            'tipo_sujeto' => $_GET['tipo_sujeto'] ?? '',
                                            'estado_general' => $_GET['estado_general'] ?? '',
                                            'pagina' => $_GET['pagina'] ?? 1
                                        ]) ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Volver a la lista
                                        </a>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>Guardar y Finalizar
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
