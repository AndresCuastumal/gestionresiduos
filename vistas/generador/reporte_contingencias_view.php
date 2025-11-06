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

// ✅ NUEVO: Verificar estado del formulario de contingencias
$estado_formulario_contingencias = $revisionController->obtenerEstadoFormulario($generador_id, $anio_actual, 'contingencias');
$puede_editar = ($estado_formulario_contingencias === 'rechazado');

// ✅ NUEVA LÓGICA: Puede editar si está rechazado O si no hay revisión (estado inicial)
$modo_edicion = $puede_editar || ($estado_formulario_contingencias === 'pendiente' || $estado_formulario_contingencias === 'sin_datos');

// Obtener datos del generador
//require_once '../../includes/conexion.php';
$stmt = $conn->prepare("SELECT * FROM generador WHERE id = ?");
$stmt->execute([$generador_id]);
$generador = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener información de contingencias existente si existe
$contingencias_existentes = null;

// INICIALIZAR TODAS LAS VARIABLES DE ARRAYS
$acciones_incendios = [];
$acciones_agua = [];
$acciones_energia = [];
$acciones_derrames = [];
$acciones_recoleccion = [];
$acciones_operativas = [];

$stmt_contingencias = $conn->prepare("SELECT * FROM contingencias WHERE generador_id = ? AND anio = ?");
$stmt_contingencias->execute([$generador_id, $anio_actual]);

if ($stmt_contingencias->rowCount() > 0) {
    $contingencias_existentes = $stmt_contingencias->fetch(PDO::FETCH_ASSOC);
    
    // Decodificar las acciones si existen - CON VERIFICACIÓN DE ARRAY
    if (!empty($contingencias_existentes['incendios_acciones'])) {
        $decoded = json_decode($contingencias_existentes['incendios_acciones'], true);
        $acciones_incendios = is_array($decoded) ? $decoded : [];
    }
    
    if (!empty($contingencias_existentes['agua_acciones'])) {
        $decoded = json_decode($contingencias_existentes['agua_acciones'], true);
        $acciones_agua = is_array($decoded) ? $decoded : [];
    }
    
    if (!empty($contingencias_existentes['energia_acciones'])) {
        $decoded = json_decode($contingencias_existentes['energia_acciones'], true);
        $acciones_energia = is_array($decoded) ? $decoded : [];
    }
    
    if (!empty($contingencias_existentes['derrames_acciones'])) {
        $decoded = json_decode($contingencias_existentes['derrames_acciones'], true);
        $acciones_derrames = is_array($decoded) ? $decoded : [];
    }
    
    if (!empty($contingencias_existentes['recoleccion_acciones'])) {
        $decoded = json_decode($contingencias_existentes['recoleccion_acciones'], true);
        $acciones_recoleccion = is_array($decoded) ? $decoded : [];
    }
    
    if (!empty($contingencias_existentes['operativas_acciones'])) {
        $decoded = json_decode($contingencias_existentes['operativas_acciones'], true);
        $acciones_operativas = is_array($decoded) ? $decoded : [];
    }
}
// ✅ ACTUALIZAR: Verificar si el reporte ya está confirmado (bloqueado) - considerar también el estado de revisión
$reporte_confirmado = ($contingencias_existentes && $contingencias_existentes['estado'] == 'confirmado') && !$puede_editar;

// ✅ ACTUALIZAR: Lógica de readonly/disabled
$readonly = ($reporte_confirmado || !$modo_edicion) ? 'readonly' : '';
$disabled = ($reporte_confirmado || !$modo_edicion) ? 'disabled' : '';

// Verificar si los tres formularios están completos (pero en estado borrador)
$formularios_completos = false;
$menu_navegacion_activo = false;

if (!$reporte_confirmado && $modo_edicion) {
    // Verificar reporte mensual
    $stmt_mensual = $conn->prepare("SELECT COUNT(*) as total FROM cantidad_x_mes WHERE id_generador = ? AND anio = ?");
    $stmt_mensual->execute([$generador_id, $anio_actual]);
    $reporte_mensual = $stmt_mensual->fetch(PDO::FETCH_ASSOC);
    
    // Verificar reporte adicional
    $stmt_adicional = $conn->prepare("SELECT COUNT(*) as total FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
    $stmt_adicional->execute([$generador_id, $anio_actual]);
    $reporte_adicional = $stmt_adicional->fetch(PDO::FETCH_ASSOC);
    
    // Verificar contingencias
    $stmt_contingencias_check = $conn->prepare("SELECT COUNT(*) as total FROM contingencias WHERE generador_id = ? AND anio = ?");
    $stmt_contingencias_check->execute([$generador_id, $anio_actual]);
    $contingencias_check = $stmt_contingencias_check->fetch(PDO::FETCH_ASSOC);
    
    // Considerar completos si existen registros en las tres tablas
    $formularios_completos = ($reporte_mensual['total'] > 0 && $reporte_adicional['total'] > 0 && $contingencias_check['total'] > 0);
    $menu_navegacion_activo = $formularios_completos;
}
?>
<?php
// ✅ NUEVO: Mensaje específico para formularios rechazados
if ($estado_formulario_contingencias === 'rechazado'): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Formulario Requiere Correcciones</strong>
        <p class="mb-0 mt-2">Este formulario ha sido <strong>rechazado</strong> por el revisor. 
        Por favor realice las correcciones solicitadas y envíe nuevamente para revisión.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
// ✅ ACTUALIZAR: Mensaje si el reporte ya fue enviado (mantener existente)
if($reporte_confirmado): ?>
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
                
                
                    <!-- Menú completo activo cuando los tres formularios están llenos -->
                    <li class="breadcrumb-item"><a href="reporte_mensual_view.php?id=<?= $generador_id ?>">Reporte Mensual</a></li>
                    <li class="breadcrumb-item"><a href="reporte_adicional_view.php?id=<?= $generador_id ?>">Capacitaciones</a></li>
                    <li class="breadcrumb-item active">Contingencias</li>
                
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Plan de Contingencias</h2>
            <a href="reporte_adicional_view.php?id=<?= $generador_id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Mensaje de confirmación del formulario anterior -->
        <?php if (!$reporte_confirmado): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>¡Datos guardados exitosamente!</strong> La información de capacitaciones y accidentes ha sido guardada correctamente. 
            Ahora complete el registro de contingencias relacionadas con la gestión de residuos.
        </div>

        <!-- Mensaje informativo si ya existe información guardada -->
        <?php if ($contingencias_existentes): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Información precargada:</strong> Se han encontrado datos de contingencias guardados previamente para este año. 
            <?php if ($reporte_confirmado): ?>
            <span class="text-danger">Este reporte ya ha sido confirmado y no puede ser modificado.</span>
            <?php else: ?>
            Puede modificar los campos que necesite y guardar los cambios.
            <?php endif; ?>
        </div>
        <?php endif; ?>   

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Registre las contingencias relacionadas con la gestión de residuos peligrosos durante el año <?= $anio_actual ?>.
                    Complete la información de cada tipo de contingencia y las acciones implementadas.
                </p>
                <p class="mb-0"><strong>Establecimiento:</strong> <?= htmlspecialchars($generador['nom_generador']) ?></p>
            </div>
        </div>
        <div class="alert alert-info mt-4">
            <h6><i class="bi bi-info-circle me-2"></i>Información</h6>
            <ul class="mb-0">
                <li>Complete la información de todas las contingencias presentadas durante el período</li>
                <li>Si no se presentó ninguna contingencia, deje el valor 0 en el número de contingencias</li>
                <li>Puede guardar como <strong>borrador</strong> para continuar después o <strong>enviar para revisión</strong> para finalizar el proceso</li>
            </ul>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Registro de Contingencias</h5>
                <!-- ✅ NUEVO: Badge de estado -->
                <span class="badge 
                    <?= $estado_formulario_contingencias === 'aprobado' ? 'bg-success' : '' ?>
                    <?= $estado_formulario_contingencias === 'rechazado' ? 'bg-danger' : '' ?>
                    <?= $estado_formulario_contingencias === 'pendiente' ? 'bg-warning' : '' ?>
                    <?= $estado_formulario_contingencias === 'sin_datos' ? 'bg-secondary' : '' ?>">
                    <?= strtoupper($estado_formulario_contingencias) ?>
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
                <?php if (!$modo_edicion && $estado_formulario_contingencias === 'aprobado'): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-check-circle me-2"></i>
                        Este formulario ha sido <strong>aprobado</strong> y no requiere modificaciones.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="../../procesos/generador/procesar_contingencias.php" id="formContingencias">
                    <input type="hidden" name="generador_id" value="<?= $generador_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio_actual ?>">
                    <input type="hidden" name="accion" id="accionInput" value="borrador">
                    
                    <!-- Incendios -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-fire me-2 text-danger"></i>Incendios en áreas de almacenamiento</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="incendios_numero" 
                                       min="0" value="<?= $contingencias_existentes['incendios_numero'] ?? '0' ?>" <?= $readonly ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="incendios_acciones[]" 
                                           value="instalacion_extintor" id="incendio1"
                                           <?= (is_array($acciones_incendios) && in_array('instalacion_extintor', $acciones_incendios)) ? 'checked' : '' ?> <?= $disabled ?>
                                           <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="incendio1">
                                        Instalación de extintor, detector de humo, aspersor u otro sistema similar
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="incendios_acciones[]" 
                                           value="redisenio_area" id="incendio2"
                                          <?= (is_array($acciones_incendios) && in_array('redisenio_area', $acciones_incendios)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="incendio2">
                                        Rediseño/reubicación del area
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="incendios_acciones[]" 
                                           value="verificacion_origen" id="incendio3"
                                           <?= (is_array($acciones_incendios) && in_array('verificacion_origen', $acciones_incendios)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="incendio3">
                                        Verificación de origen del fuego
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="incendios_acciones[]" 
                                           value="llamada_bomberos" id="incendio3"
                                           <?= (is_array($acciones_incendios) && in_array('llamada_bomberos', $acciones_incendios)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="incendio3">
                                        Llamada a bomberos
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="incendios_acciones[]" 
                                           value="otro" id="incendio_otro"
                                           <?= (is_array($acciones_incendios) && in_array('otro', $acciones_incendios) || !empty($contingencias_existentes['incendios_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="incendio_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="incendio_otro_container" style="display: <?= (!empty($contingencias_existentes['incendios_otra_accion']) || (isset($acciones_incendios) && is_array($acciones_incendios) && in_array('otro', $acciones_incendios))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="incendios_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['incendios_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inundaciones -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-water me-2 text-primary"></i>Inundación en áreas de almacenamiento</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="inundaciones_numero" 
                                       min="0" value="<?= $contingencias_existentes['inundaciones_numero'] ?? '0' ?>" <?= $readonly ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acciones implementadas</label>
                                <textarea class="form-control" 
                                          name="inundaciones_acciones" 
                                          rows="2" placeholder="Describa las acciones tomadas" <?= $disabled ?>><?= $contingencias_existentes['inundaciones_acciones'] ?? '' ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interrupción suministro de agua -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-droplet me-2 text-info"></i>Interrupción del suministro de agua</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="agua_numero" 
                                       min="0" value="<?= $contingencias_existentes['agua_numero'] ?? '0' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="agua_acciones[]" 
                                           value="tanque_abastecimiento" id="agua1"
                                           <?= (is_array($acciones_agua) && in_array('tanque_abastecimiento', $acciones_agua)) ? 'checked' : '' ?> <?= $disabled ?>
                                           <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="agua1">
                                        Instalación o aumento de capacidad del tanque
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="agua_acciones[]" 
                                           value="sistema_alternativo" id="agua2"
                                           <?= (is_array($acciones_agua) && in_array('sistema_alternativo', $acciones_agua)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="agua2">
                                        Implementación de sistema de suministro alternativo
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="agua_acciones[]" 
                                           value="limpieza_seco" id="agua3"
                                           <?= (is_array($acciones_agua) && in_array('limpieza_seco', $acciones_agua)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="agua3">
                                        Implementación de sistemas de limpieza en seco
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="agua_acciones[]" 
                                           value="reparacion" id="agua4"
                                           <?= (is_array($acciones_agua) && in_array('reparacion', $acciones_agua)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="agua3">
                                        Reparación del sistema
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="agua_acciones[]" 
                                           value="otro" id="agua_otro"
                                           <?= (is_array($acciones_agua) && in_array('otro', $acciones_agua) || !empty($contingencias_existentes['agua_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="agua_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="agua_otro_container" style="display: <?= (!empty($contingencias_existentes['agua_otra_accion']) || (isset($acciones_agua) && is_array($acciones_agua) && in_array('otro', $acciones_agua))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="agua_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['agua_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interrupción suministro de energía -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-lightning-charge me-2 text-warning"></i>Interrupción del suministro de energía</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="energia_numero" 
                                       min="0" value="<?= $contingencias_existentes['energia_numero'] ?? '0' ?>" <?= $readonly ?>
                                       <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="energia_acciones[]" 
                                           value="generador" id="energia1"
                                           <?= (is_array($acciones_energia) && in_array('generador', $acciones_energia)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="energia1">
                                        Instalación de planta eléctrica o sistema alternativo
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="energia_acciones[]" 
                                           value="racionamiento_energia" id="energia2"
                                           <?= (is_array($acciones_energia) && in_array('racionamiento_energia', $acciones_energia)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="energia2">
                                        Racionamiento del uso de energía
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="energia_acciones[]" 
                                           value="reparacion_electrica" id="energia4"
                                           <?= (is_array($acciones_energia) && in_array('reparacion_electrica', $acciones_energia)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="energia4">
                                        Reparación del sistema eléctrico
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="energia_acciones[]" 
                                           value="otro" id="energia_otro"
                                           <?= (in_array('otro', $acciones_energia) || !empty($contingencias_existentes['energia_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="energia_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="energia_otro_container" style="display: <?= (!empty($contingencias_existentes['energia_otra_accion'])|| (isset($acciones_energia) && is_array($acciones_energia) && in_array('otro', $acciones_energia))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="energia_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['energia_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Derrames -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Derrame de residuos peligrosos</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="derrames_numero" 
                                       min="0" value="<?= $contingencias_existentes['derrames_numero'] ?? '0' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>


                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de residuo derramado</label>
                                <select class="form-select <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                name="derrames_tipo" 
                                <?= $disabled ?>
                                <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>>
                                    <option value="">Seleccione el tipo</option>
                                    <option value="corrosivo" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'corrosivo') ? 'selected' : '' ?>>Corrosivo</option>
                                    <option value="reactivo" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'reactivo') ? 'selected' : '' ?>>Reactivo</option>
                                    <option value="explosivo" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'explosivo') ? 'selected' : '' ?>>Explosivo</option>
                                    <option value="toxico" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'toxico') ? 'selected' : '' ?>>Tóxico</option>
                                    <option value="inflamable" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'inflamable') ? 'selected' : '' ?>>Inflamable</option>
                                    <option value="biologico" <?= (isset($contingencias_existentes['derrames_tipo']) && $contingencias_existentes['derrames_tipo'] == 'biologico') ? 'selected' : '' ?>>Biológico/Infeccioso</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="derrames_acciones[]" 
                                           value="kit_derrame" id="derrame1"
                                           <?= (is_array($acciones_derrames) && in_array('kit_derrame', $acciones_derrames)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="derrame1">
                                        Utilización del kit de derrame
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="derrames_acciones[]" 
                                           value="limpieza_manual" id="derrame2"
                                           <?= (is_array($acciones_derrames) && in_array('limpieza_manual', $acciones_derrames)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="derrame2">
                                        Limpieza manual
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="derrames_acciones[]" 
                                           value="apoyo_tercero" id="derrame3"
                                           <?= (is_array($acciones_derrames) && in_array('apoyo_tercero', $acciones_derrames)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="derrame3">
                                        Apoyo de terceros especializados
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="derrames_acciones[]" 
                                           value="otro" id="derrame_otro"
                                           <?= (is_array($acciones_derrames) && in_array('otro', $acciones_derrames) || !empty($contingencias_existentes['derrames_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="derrame_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="derrame_otro_container" style="display: <?= (!empty($contingencias_existentes['derrames_otra_accion'])|| (isset($acciones_derrames) && is_array($acciones_derrames) && in_array('otro', $acciones_derrames))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="derrames_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['derrames_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interrupción recolección -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-truck me-2 text-secondary"></i>Interrupción del servicio de recolección</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="recoleccion_numero" 
                                       min="0" value="<?= $contingencias_existentes['recoleccion_numero'] ?? '0' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="recoleccion_acciones[]" 
                                           value="gestor_alternativo" id="recoleccion1"
                                           <?= (is_array($acciones_recoleccion) && in_array('gestor_alternativo', $acciones_recoleccion)) ? 'checked' : '' ?> <?= $disabled ?>
                                           <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="recoleccion1">
                                        Contratación de un gestor alternativo
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="recoleccion_acciones[]" 
                                           value="ampliacion_almacenamiento" id="recoleccion2"
                                           <?= (is_array($acciones_recoleccion) && in_array('ampliacion_almacenamiento', $acciones_recoleccion)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="recoleccion2">
                                        Ampliación del area de almacenamiento
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="recoleccion_acciones[]" 
                                           value="otro" id="recoleccion_otro"
                                           <?= (is_array($acciones_recoleccion) && in_array('otro', $acciones_recoleccion) || !empty($contingencias_existentes['recoleccion_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="recoleccion_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="recoleccion_otro_container" style="display: <?= (!empty($contingencias_existentes['recoleccion_otra_accion'])|| (isset($acciones_recoleccion) && is_array($acciones_recoleccion) && in_array('otro', $acciones_recoleccion))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="recoleccion_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['recoleccion_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alteración condiciones operativas -->
                    <div class="info-card mb-4">
                        <h6><i class="bi bi-gear me-2 text-success"></i>Alteración de condiciones operativas</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de contingencias</label>
                                <input type="number" class="form-control" 
                                       name="operativas_numero" 
                                       min="0" value="<?= $contingencias_existentes['operativas_numero'] ?? '0' ?>" <?= $readonly ?>
                                        <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones implementadas</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="operativas_acciones[]" 
                                           value="gestion_personal" id="operativa1"
                                           <?= (is_array($acciones_operativas) && in_array('gestion_personal', $acciones_operativas)) ? 'checked' : '' ?> <?= $disabled ?>
                                             <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="operativa1">
                                        Gestión de personal externo
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="operativas_acciones[]" 
                                           value="ampliacion_areas" id="operativa2"
                                           <?= (is_array($acciones_operativas) && in_array('ampliacion_areas', $acciones_operativas)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="operativa2">
                                        Ampliación de las areas de almacenamiento
                                    </label>
                                </div>
                                 <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="operativas_acciones[]" 
                                           value="protocolo_contingencia" id="operativa4"
                                           <?= (is_array($acciones_operativas) && in_array('protocolo_contingencia', $acciones_operativas)) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="operativa2">
                                        Activación de protocolos de contingencia
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="operativas_acciones[]" 
                                           value="otro" id="operativa_otro"
                                           <?= (in_array('otro', $acciones_operativas) || !empty($contingencias_existentes['operativas_otra_accion'])) ? 'checked' : '' ?> <?= $disabled ?>
                                            <?= !$modo_edicion ? 'onclick="return false;"' : '' ?>>
                                    <label class="form-check-label" for="operativa_otro">
                                        Otro (especifique cual)
                                    </label>
                                </div>
                                <div class="mt-2" id="operativa_otro_container" style="display: <?= (!empty($contingencias_existentes['operativas_otra_accion'])|| (isset($acciones_operativas) && is_array($acciones_operativas) && in_array('otro', $acciones_operativas))) ? 'block' : 'none' ?>;">
                                    <input type="text" class="form-control" 
                                           name="operativas_otra_accion" 
                                           placeholder="Especifique la acción"
                                           value="<?= $contingencias_existentes['operativas_otra_accion'] ?? '' ?>" <?= $readonly ?>>
                                </div>
                            </div>
                        </div>
                    </div>                   
                    <div class="d-flex justify-content-between mt-4">
                        <a href="reporte_adicional_view.php?id=<?= $generador_id ?>" 
                        class="btn btn-outline btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                        
                        <!-- ✅ ACTUALIZAR: Mostrar botones solo si puede editar -->
                        <?php if ($modo_edicion && !$reporte_confirmado): ?>
                        <div>
                            <button type="button" onclick="guardarBorrador()" class="btn btn-outline btn-outline-primary me-2">
                                <i class="bi bi-save me-2"></i>Guardar Borrador
                            </button>
                            <button type="button" onclick="mostrarModalConfirmacion()" class="btn btn-outline btn-outline-success">
                                <i class="bi bi-send-check me-2"></i>
                                <?= $estado_formulario_contingencias === 'rechazado' ? 'Reenviar para Revisión' : 'Enviar para Revisión' ?>
                            </button>
                        </div>
                        <?php else: ?>
                        <a href="listado_generadores_view.php" class="btn btn-outline btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Volver al Listado
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
     <!-- Modal de Confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacionLabel"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirmar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>¿Está seguro de enviar el reporte para revisión?</strong>
                    </div>
                    <p>Una vez enviado para revisión:</p>
                    <ul>
                        <li>No podrá realizar modificaciones a ninguno de los tres reportes</li>
                        <li>El administrador revisará toda la información</li>
                        <li>Recibirá una notificación por correo electrónico</li>
                        <li>El estado cambiará a "Pendiente de revisión"</li>
                    </ul>
                    <p>Si necesita realizar cambios posteriores, deberá contactar al administrador.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-2"></i>Seguir Editando
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmarEnvio()">
                        <i class="bi bi-check-circle me-2"></i>Confirmar Envío
                    </button>
                </div>
            </div>
        </div>
    </div>                        
    <script>
        // Funcionalidad para mostrar campos de texto cuando se selecciona "Otro"
        <?php if ($modo_edicion && !$reporte_confirmado): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Incendios
            document.getElementById('incendio_otro').addEventListener('change', function() {
                document.getElementById('incendio_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Agua
            document.getElementById('agua_otro').addEventListener('change', function() {
                document.getElementById('agua_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Energía
            document.getElementById('energia_otro').addEventListener('change', function() {
                document.getElementById('energia_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Derrames
            document.getElementById('derrame_otro').addEventListener('change', function() {
                document.getElementById('derrame_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Recolección
            document.getElementById('recoleccion_otro').addEventListener('change', function() {
                document.getElementById('recoleccion_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Operativas
            document.getElementById('operativa_otro').addEventListener('change', function() {
                document.getElementById('operativa_otro_container').style.display = 
                    this.checked ? 'block' : 'none';
            });
            
            // Inicializar el estado de los campos al cargar la página
            document.getElementById('incendio_otro_container').style.display = 
                document.getElementById('incendio_otro').checked ? 'block' : 'none';
            document.getElementById('agua_otro_container').style.display = 
                document.getElementById('agua_otro').checked ? 'block' : 'none';
            document.getElementById('energia_otro_container').style.display = 
                document.getElementById('energia_otro').checked ? 'block' : 'none';
            document.getElementById('derrame_otro_container').style.display = 
                document.getElementById('derrame_otro').checked ? 'block' : 'none';
            document.getElementById('recoleccion_otro_container').style.display = 
                document.getElementById('recoleccion_otro').checked ? 'block' : 'none';
            document.getElementById('operativa_otro_container').style.display = 
                document.getElementById('operativa_otro').checked ? 'block' : 'none';
        });
        <?php endif; ?>
        // Funciones para manejar el envío del formulario
        function guardarBorrador() {
            document.getElementById('accionInput').value = 'borrador';
            document.getElementById('formContingencias').submit();
        }

        function mostrarModalConfirmacion() {
            var modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
            modal.show();
        }

        function confirmarEnvio() {
            document.getElementById('accionInput').value = 'confirmar';
            document.getElementById('formContingencias').submit();
        }
    </script>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>