<?php
session_start();
require_once '../../includes/header.php';
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';

// Verificar si viene de navegación interna entre formularios
if (isset($_GET['id']) && !isset($_SESSION['generador_id_reportando'])) {
    $_SESSION['generador_id_reportando'] = $_GET['id'];
    $_SESSION['anio_reportando'] = date('Y', strtotime('-1 year'));
}

// Verificar que tiene permisos para acceder
if (!isset($_SESSION['generador_id_reportando']) || $_SESSION['generador_id_reportando'] != $_GET['id']) {
    // Si no tiene sesión activa, verificar permisos de acceso
    require_once '../../includes/conexion.php';
    
    $generador_id = $_GET['id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    if ($_SESSION['usuario_rol'] !== 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuario_generador 
                               WHERE usuario_id = ? AND generador_id = ?");
        $stmt->execute([$usuario_id, $generador_id]);
        $tiene_acceso = $stmt->fetchColumn();
        
        if (!$tiene_acceso) {
            header("Location: acceso_denegado.php");
            exit();             
        }
    }
    
    // Si tiene permisos, crear la sesión
    $_SESSION['generador_id_reportando'] = $generador_id;
    $_SESSION['anio_reportando'] = date('Y', strtotime('-1 year'));
}

$generador_id = $_GET['id'];
$anio_actual = $_SESSION['anio_reportando'];

// ✅ NUEVO: Crear controlador de revisiones
$revisionController = new RevisionesController($conn);

// ✅ NUEVO: Verificar estado del formulario de accidentes
$estado_formulario_accidentes = $revisionController->obtenerEstadoFormulario($generador_id, $anio_actual, 'accidentes');
$puede_editar = ($estado_formulario_accidentes === 'rechazado');

// ✅ NUEVA LÓGICA: Puede editar si está rechazado O si no hay revisión (estado inicial)
$modo_edicion = $puede_editar || ($estado_formulario_accidentes === 'pendiente' || $estado_formulario_accidentes === 'sin_datos');

// Obtener datos del generador
require_once '../../includes/conexion.php';
$stmt = $conn->prepare("SELECT * FROM generador WHERE id = ?");
$stmt->execute([$generador_id]);
$generador = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener información adicional existente si existe
$info_adicional = null;
$acciones_preventivas = []; // INICIALIZAR COMO ARRAY VACÍO

$stmt_adicional = $conn->prepare("SELECT * FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
$stmt_adicional->execute([$generador_id, $anio_actual]);

if ($stmt_adicional->rowCount() > 0) {
    $info_adicional = $stmt_adicional->fetch(PDO::FETCH_ASSOC);
    
    // Decodificar las acciones preventivas si existen
    if (!empty($info_adicional['acciones_preventivas'])) {
        $decoded = json_decode($info_adicional['acciones_preventivas'], true);
        $acciones_preventivas = is_array($decoded) ? $decoded : [];
    }
}

// ✅ ACTUALIZAR: Verificar si las contingencias ya están confirmadas (bloqueadas) - considerar también el estado de revisión
$stmt_contingencias = $conn->prepare("SELECT estado FROM contingencias WHERE generador_id = ? AND anio = ?");
$stmt_contingencias->execute([$generador_id, $anio_actual]);
$contingencia = $stmt_contingencias->fetch(PDO::FETCH_ASSOC);

$reporte_bloqueado = ($contingencia && $contingencia['estado'] == 'confirmado') && !$puede_editar;

// ✅ ACTUALIZAR: Lógica de readonly/disabled
$readonly = ($reporte_bloqueado || !$modo_edicion) ? 'readonly' : '';
$disabled = ($reporte_bloqueado || !$modo_edicion) ? 'disabled' : '';

// Verificar si los tres formularios están completos (pero en estado borrador)
$formularios_completos = false;
$menu_navegacion_activo = false;

if (!$reporte_bloqueado) {
    // Verificar reporte mensual
    $stmt_mensual = $conn->prepare("SELECT COUNT(*) as total FROM cantidad_x_mes WHERE id_generador = ? AND anio = ?");
    $stmt_mensual->execute([$generador_id, $anio_actual]);
    $reporte_mensual = $stmt_mensual->fetch(PDO::FETCH_ASSOC);
    
    // Verificar reporte adicional
    $stmt_adicional_check = $conn->prepare("SELECT COUNT(*) as total FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
    $stmt_adicional_check->execute([$generador_id, $anio_actual]);
    $reporte_adicional_check = $stmt_adicional_check->fetch(PDO::FETCH_ASSOC);
    
    // Verificar contingencias
    $stmt_contingencias_check = $conn->prepare("SELECT COUNT(*) as total FROM contingencias WHERE generador_id = ? AND anio = ?");
    $stmt_contingencias_check->execute([$generador_id, $anio_actual]);
    $contingencias_check = $stmt_contingencias_check->fetch(PDO::FETCH_ASSOC);
    
    // Considerar completos si existen registros en las tres tablas
    $formularios_completos = ($reporte_mensual['total'] > 0 && $reporte_adicional_check['total'] > 0 && $contingencias_check['total'] > 0);
    $menu_navegacion_activo = $formularios_completos;
}
?>  <?php
// mensaje si el reporte ya fue enviado
    if ($estado_formulario_accidentes === 'rechazado'): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Formulario Requiere Correcciones</strong>
            <p class="mb-0 mt-2">Este formulario ha sido <strong>rechazado</strong> por el revisor. 
            Por favor realice las correcciones solicitadas y envíe nuevamente para revisión.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    // ✅ ACTUALIZAR: Mensaje si el reporte ya fue enviado
    if($reporte_bloqueado): ?>
        <div class="alert alert-warning text-center mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>El reporte anual para el año <?= $anio_actual ?> ya fue enviado y está en proceso de revisión.</strong>
            No puede realizar modificaciones adicionales.
        </div>    
    <?php endif; ?>    
    <!-- Contenedor principal -->
    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listado_generadores_view.php">Mis Establecimientos</a></li>
                
                <?php if ($menu_navegacion_activo || $reporte_bloqueado): ?>
                    <!-- Menú completo activo cuando los tres formularios están llenos -->
                    <li class="breadcrumb-item"><a href="reporte_mensual_view.php?id=<?= $generador_id ?>">Reporte Mensual</a></li>
                    <li class="breadcrumb-item active">Capacitaciones</li>
                    <li class="breadcrumb-item"><a href="reporte_contingencias_view.php?id=<?= $generador_id ?>">Contingencias</a></li>
                <?php else: ?>
                    <!-- Menú simplificado cuando no están todos completos -->
                    <li class="breadcrumb-item"><a href="reporte_mensual_view.php?id=<?= $generador_id ?>">Reporte Mensual</a></li>
                    <li class="breadcrumb-item active">Capacitaciones y Accidentes</li>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check me-2"></i>Capacitaciones, Accidentes y Auditorías</h2>
            <a href="reporte_mensual_view.php?id=<?= $generador_id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Mensaje de confirmación del primer formulario -->
        <?php if ($info_adicional and $contingencia['estado']=='borrador'): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>¡Datos guardados exitosamente!</strong> Los datos del reporte mensual de residuos para el año <?= $anio_actual ?> han sido guardados correctamente. 
            Ahora complete la información adicional sobre capacitaciones, accidentes y auditorías.
        </div>

        <!-- Mensaje informativo si ya existe información guardada -->
        
        <?php if ($info_adicional && !$reporte_bloqueado): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Información precargada:</strong> Se han encontrado datos guardados previamente para este año. 
            Puede modificar los campos que necesite y guardar los cambios.
        </div>
        <?php endif; ?>
        
        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Complete la información sobre capacitaciones, accidentes laborales y auditorías relacionadas 
                    con la gestión de residuos peligrosos para el año <?= $anio_actual ?>. 
                    Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </p>
                <p class="mb-0"><strong>Establecimiento:</strong> <?= htmlspecialchars($generador['nom_generador']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Formulario de Reporte Adicional</h5>
                <!-- ✅ NUEVO: Badge de estado -->
                <span class="badge 
                    <?= $estado_formulario_accidentes === 'aprobado' ? 'bg-success' : '' ?>
                    <?= $estado_formulario_accidentes === 'rechazado' ? 'bg-danger' : '' ?>
                    <?= $estado_formulario_accidentes === 'pendiente' ? 'bg-warning' : '' ?>
                    <?= $estado_formulario_accidentes === 'sin_datos' ? 'bg-secondary' : '' ?>">
                    <?= strtoupper($estado_formulario_accidentes) ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- ✅ NUEVO: Mensaje cuando no puede editar -->
                <?php if (!$modo_edicion && $estado_formulario_accidentes === 'aprobado'): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-check-circle me-2"></i>
                        Este formulario ha sido <strong>aprobado</strong> y no requiere modificaciones.
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" action="../../procesos/generador/procesar_reporte_adicional.php">
                    <input type="hidden" name="anio" value="<?= $anio_actual ?>">
                    
                    <!-- SECCIÓN CAPACITACIONES -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-mortarboard me-2"></i>Capacitaciones</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Número de capacitaciones programadas sobre manejo de residuos
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                       name="num_capacitaciones_programadas" 
                                       min="0" required
                                       value="<?= $info_adicional['num_capacitaciones_programadas'] ?? '' ?>"<?= $readonly ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Cronograma de capacitaciones (PDF)
                                    <?php if (!$info_adicional): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="file" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                       name="archivo_cronograma" 
                                       accept=".pdf" <?= !$info_adicional ? 'required' : '' ?> <?= $disabled ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                <?php if ($info_adicional && !empty($info_adicional['archivo_cronograma'])): ?>
                                <div class="form-text">
                                    <a href="../../procesos/uploads/soportes_anuales/<?= $info_adicional['archivo_cronograma'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-download me-1"></i>Ver PDF actual
                                    </a>
                                    <span class="ms-2 text-muted">Si no selecciona un nuevo archivo, se mantendrá el actual.</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Número de capacitaciones ejecutadas sobre manejo de residuos
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                       name="num_capacitaciones_ejecutadas" 
                                       min="0" required
                                       value="<?= $info_adicional['num_capacitaciones_ejecutadas'] ?? '' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Número de empleados capacitados en manejo de residuos
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                       name="num_empleados_capacitados" 
                                       min="0" required
                                       value="<?= $info_adicional['num_empleados_capacitados'] ?? '' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Soportes de capacitaciones (PDF)
                                    <?php if (!$info_adicional): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="file" class="form-control" 
                                       name="archivo_soportes_capacitaciones" 
                                       accept=".pdf" <?= !$info_adicional ? 'required' : '' ?> <?= $disabled ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                <div class="form-text">Relacionados únicamente con manejo de residuos</div>
                                <?php if ($info_adicional && !empty($info_adicional['archivo_soportes_capacitaciones'])): ?>
                                <div class="form-text">
                                    <a href="../../procesos/uploads/soportes_anuales/<?= $info_adicional['archivo_soportes_capacitaciones'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-download me-1"></i>Ver PDF actual
                                    </a>
                                    <span class="ms-2 text-muted">Si no selecciona un nuevo archivo, se mantendrá el actual.</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECCIÓN ACCIDENTES -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Accidentes Laborales</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    ¿Se han presentado accidentes ocurridos por manejo de residuos?
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                name="tiene_accidentes" id="tiene_accidentes" required 
                                <?= $disabled ?>
                                <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                    <option value="no" <?= (isset($info_adicional['tiene_accidentes']) && $info_adicional['tiene_accidentes'] == 'no') ? 'selected' : '' ?>>No</option>
                                    <option value="si" <?= (isset($info_adicional['tiene_accidentes']) && $info_adicional['tiene_accidentes'] == 'si') ? 'selected' : '' ?>>Sí</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div id="numero_accidentes_container" style="display: <?= (isset($info_adicional['tiene_accidentes']) && $info_adicional['tiene_accidentes'] == 'si') ? 'block' : 'none' ?>;">
                                    <label class="form-label">
                                        Número de accidentes ocurridos por manejo de residuos
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                           name="num_accidentes" 
                                           min="0" value="<?= $info_adicional['num_accidentes'] ?? '0' ?>" <?= $readonly ?>
                                            <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?> >
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                Acciones preventivas y/o correctivas sobre accidentes ocurridos por manejo de residuos
                                <span class="text-danger">*</span>
                            </label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="acciones_preventivas[]" 
                                           value="remision_salud" id="accion1"
                                           <?= (in_array('remision_salud', $acciones_preventivas)) ? 'checked' : '' ?> <?= $disabled ?>
                                           <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="accion1">
                                        Remisión a servicios de salud
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="acciones_preventivas[]" 
                                           value="capacitacion_primeros_auxilios" id="accion2"
                                           <?= (in_array('capacitacion_primeros_auxilios', $acciones_preventivas)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="accion2">
                                        Capacitación en primeros auxilios
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="acciones_preventivas[]" 
                                           value="investigacion_accidente" id="accion3"
                                           <?= (in_array('investigacion_accidente', $acciones_preventivas)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="accion3">
                                        Investigación del accidente
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="acciones_preventivas[]" 
                                           value="actualizacion_procedimientos" id="accion4"
                                           <?= (in_array('actualizacion_procedimientos', $acciones_preventivas)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="accion4">
                                        Actualización de procedimientos
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                        name="acciones_preventivas[]" 
                                        value="otra" id="accion_otra"
                                        <?= (in_array('otra', $acciones_preventivas) || !empty($info_adicional['otra_accion_preventiva'])) ? 'checked' : '' ?> 
                                        <?= $disabled ?>
                                        <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="accion_otra">
                                        Otra
                                    </label>
                                </div>
                                <div class="mt-2" id="otra_accion_container" style="display: <?= (!empty($info_adicional['otra_accion_preventiva']) || (isset($acciones_preventivas) && in_array('otra', $acciones_preventivas))) ? 'block' : 'none' ?>;">
                                    <!-- ✅ CORRECCIÓN: Cambiar a type="text" y usar $disabled en lugar de $readonly -->
                                    <input type="text" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                        name="otra_accion_preventiva" 
                                        placeholder="Especifique cuál"
                                        value="<?= $info_adicional['otra_accion_preventiva'] ?? '' ?>" 
                                        <?= $disabled ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECCIÓN AUDITORIAS -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-search me-2"></i>Auditorías Internas y externas</h6>
                        <p class="text-muted mb-3">
                            Recuerde que las auditorías internas son obligatorias según la normatividad vigente.
                            Asegúrese de haber realizado al menos una auditoría interna sobre la gestión de residuos
                            durante el año <?= $anio_actual ?>.
                        </p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Número de auditorías internas y/o externas realizadas sobre el manejo de residuos sólidos
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control <?= !$modo_edicion ? 'bg-light' : '' ?>"
                                       name="num_auditorias" 
                                       min="0" required
                                       value="<?= $info_adicional['num_auditorias'] ?? '' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Resultados de las auditorías (PDF)
                                    <?php if (!$info_adicional): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="file" class="form-control" 
                                       name="archivo_resultados_auditorias" 
                                       accept=".pdf" <?= !$info_adicional ? 'required' : '' ?> <?= $disabled ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                <div class="form-text">Acta(s) de auditorías realizadas</div>
                                <?php if ($info_adicional && !empty($info_adicional['archivo_resultados_auditorias'])): ?>
                                <div class="form-text">
                                    <a href="../../procesos/uploads/soportes_anuales/<?= $info_adicional['archivo_resultados_auditorias'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-download me-1"></i>Ver PDF actual
                                    </a>
                                    <span class="ms-2 text-muted">Si no selecciona un nuevo archivo, se mantendrá el actual.</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Acciones correctivas y de mejoramiento (PDF)
                                    <?php if (!$info_adicional): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="file" class="form-control" 
                                       name="archivo_plan_mejoramiento" 
                                       accept=".pdf" <?= !$info_adicional ? 'required' : '' ?> <?= $disabled ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                                <div class="form-text">Plan de mejoramiento para el año evaluado</div>
                                <?php if ($info_adicional && !empty($info_adicional['archivo_plan_mejoramiento'])): ?>
                                <div class="form-text">
                                    <a href="../../procesos/uploads/soportes_anuales/<?= $info_adicional['archivo_plan_mejoramiento'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-download me-1"></i>Ver PDF actual
                                    </a>
                                    <span class="ms-2 text-muted">Si no selecciona un nuevo archivo, se mantendrá el actual.</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECCIÓN SOPORTE ALERTAS --> 
                    <div class="alert alert-warning mt-4">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Importante:</h6>
                        <ul class="mb-0">
                            <li>El PDF de <strong>Soportes de capacitaciones</strong> deben ser relacionados únicamente 
                            con la temática de manejo de residuos</li>
                            <li>Los archivos de auditoría deben corresponder a las realizadas durante el año <?= $anio_actual ?></li>
                            <li>El estado de su reporte cambiará a "Pendiente de revisión"</li>
                            <li>Recibirá una notificación cuando sea aprobado o rechazado</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="reporte_mensual_view.php?id=<?= $generador_id ?>" 
                        class="btn btn-outline btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                        
                        <!-- ✅ ACTUALIZAR: Mostrar botón solo si puede editar -->
                        <?php if ($modo_edicion && !$reporte_bloqueado): ?>                            
                        <button type="submit" class="btn btn-outline btn-outline-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= $info_adicional ? 'Actualizar' : 'Guardar' ?> Reporte
                            <?= $estado_formulario_accidentes === 'rechazado' ? 'y Reenviar' : '' ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        <?php if ($modo_edicion && !$reporte_bloqueado): ?>
        // Solo habilitar la funcionalidad JavaScript si está en modo edición
        document.getElementById('tiene_accidentes').addEventListener('change', function() {
            document.getElementById('numero_accidentes_container').style.display = 
                this.value === 'si' ? 'block' : 'none';
        });

        document.getElementById('accion_otra').addEventListener('change', function() {
            document.getElementById('otra_accion_container').style.display = 
                this.checked ? 'block' : 'none';
        });

        // Inicializar el estado de los campos al cargar la página
        window.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campo de número de accidentes según selección actual
            const tieneAccidentes = document.getElementById('tiene_accidentes');
            document.getElementById('numero_accidentes_container').style.display = 
                tieneAccidentes.value === 'si' ? 'block' : 'none';
                
            // Mostrar/ocultar campo de otra acción según estado del checkbox
            const accionOtra = document.getElementById('accion_otra');
            document.getElementById('otra_accion_container').style.display = 
                accionOtra.checked ? 'block' : 'none';
        });
        <?php else: ?>
        // Si no está en modo edición, deshabilitar la interactividad
        window.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campo de número de accidentes según selección actual (solo visual)
            const tieneAccidentes = document.getElementById('tiene_accidentes');
            document.getElementById('numero_accidentes_container').style.display = 
                tieneAccidentes.value === 'si' ? 'block' : 'none';
                
            // Mostrar/ocultar campo de otra acción según estado del checkbox (solo visual)
            const accionOtra = document.getElementById('accion_otra');
            document.getElementById('otra_accion_container').style.display = 
                accionOtra.checked ? 'block' : 'none';
        });
        <?php endif; ?>
    </script>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>