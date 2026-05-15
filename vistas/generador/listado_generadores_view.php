<?php
//session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'generador') {
    header("Location: ../login/login.php");
    exit();
}

require_once '../../includes/conexion.php';
require_once '../../procesos/generador/listado_generadores_controller.php';

$controller = new GeneradoresController($conn);

// Procesar reenvío de correcciones si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reenviar_correccion'])) {
    $generador_id = $_POST['generador_id'];
    $resultado = $controller->reenviarParaCorreccion($generador_id);
    
    if ($resultado['success']) {
        $_SESSION['mensaje_exito'] = $resultado['message'];
    } else {
        $_SESSION['error'] = $resultado['message'];
    }
    
    header("Location: listado_generadores_view.php");
    exit();
}

$controller->verificarSesion();
$controller->procesarEliminacion();
$controller->obtenerGeneradores();
$controller->obtenerEstadosRevision();

$generadores = $controller->getGeneradores();
$estados_revision = $controller->getEstadosRevision();
$error = $controller->getError();

// Obtener información de correcciones para cada generador
$info_correcciones = [];
foreach ($generadores as $generador) {
    $info_correcciones[$generador['id']] = $controller->obtenerInfoRechazos($generador['id']);
}

// Obtener estados de formularios para cada generador
$estados_formularios = [];
foreach ($generadores as $generador) {
    $estados_formularios[$generador['id']] = $controller->obtenerEstadosFormularios($generador['id']);
}

include '../../includes/header.php';

// ✅ FUNCIONES DE COLOR (igual que en revisiones_view)
function obtenerClaseColor($estado) {
    switch($estado) {
        case 'pendiente': return 'text-warning';
        case 'rechazado': return 'text-danger';
        case 'aprobado': return 'text-success';
        case 'sin_datos': return 'text-secondary';
        default: return 'text-secondary';
    }
}

function obtenerTextoEstado($estado) {
    $estados = [
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'pendiente' => 'Pendiente',
        'sin_datos' => 'Sin datos',
        'sin_revision' => 'Sin revisión'
    ];
    return $estados[$estado] ?? 'Desconocido';
}

// ✅ MODIFICADA: getIconoClase usando colores según estado
function getIconoClase($estado, $habilitado, $rechazados, $tipo) {
    if (!$habilitado) {
        return 'formulario-icon-deshabilitado';
    }
    if (in_array($tipo, $rechazados)) {
        return 'formulario-icon-correccion';
    }
    // Clase base siempre habilitada, el color lo da el estado
    return 'formulario-icon-habilitado';
}

function getIconoTooltip($tipo, $habilitado, $estado, $rechazados) {
    if (!$habilitado) {
        $mensajes = [
            'mensual' => 'Debe diligenciar primero el Reporte Mensual',
            'accidentes' => 'Debe diligenciar primero el Reporte Mensual',
            'contingencias' => 'Debe diligenciar primero el Reporte de Accidentes'
        ];
        return $mensajes[$tipo];
    }
    if (in_array($tipo, $rechazados)) {
        return 'Formulario rechazado - Haga clic para corregir';
    }
    $textos = [
        'mensual' => 'Reporte Mensual de Residuos',
        'accidentes' => 'Capacitaciones, accidentes y auditorías',
        'contingencias' => 'Plan de Contingencias'
    ];
    $estado_texto = obtenerTextoEstado($estado);
    return "{$textos[$tipo]} - Estado: {$estado_texto}";
}
?>

<style>
/* Estilos para los badges de estado */
/* Estilos para iconos de formularios */
.formulario-icon {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 1rem;
    transition: all 0.2s;
    text-decoration: none;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

/* Colores según estado (igual que en revisiones_view) */
.formulario-icon-habilitado.text-warning {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}

.formulario-icon-habilitado.text-danger {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.formulario-icon-habilitado.text-success {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.formulario-icon-habilitado.text-secondary {
    background-color: #e9ecef;
    border-color: #6c757d;
    color: #6c757d;
}

.formulario-icon-habilitado:hover {
    transform: translateY(-2px);
    filter: brightness(0.95);
}

.formulario-icon-deshabilitado {
    background-color: #f5f5f5;
    color: #bdbdbd;
    border: 1px solid #e0e0e0;
    cursor: not-allowed;
    opacity: 0.6;
}

.formulario-icon-correccion {
    background-color: #fff3e0;
    border-color: #f57c00;
    color: #f57c00;
    animation: pulse 1.5s infinite;
}

.formulario-icon-correccion:hover {
    background-color: #f57c00;
    color: white;
    transform: translateY(-2px);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(245, 124, 0, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(245, 124, 0, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(245, 124, 0, 0);
    }
}

/* Estilos para acciones */
.btn-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 1.1rem;
}

.btn-reportar {
    background-color: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.btn-reportar:hover {
    background-color: #1976d2;
    color: white;
}

.btn-corregir-table {
    background-color: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffe0b2;
    cursor: pointer;
}

.btn-corregir-table:hover {
    background-color: #f57c00;
    color: white;
}


.btn-action.disabled,
.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Estilo para alerta de corrección */
.correccion-alerta {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border-left: 4px solid #f57c00;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
}

.btn-corregir {
    background: #f57c00;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-corregir:hover {
    background: #ef6c00;
    transform: translateY(-1px);
}

.formularios-rechazados {
    margin-top: 0.5rem;
}

.formulario-rechazado-item {
    background: white;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 6px;
    border: 1px solid #ffcc80;
}

.intentos-info {
    font-size: 0.875rem;
    color: #e65100;
    margin: 0.5rem 0;
}

.estado-correccion {
    font-size: 0.7rem;
    margin-top: 0.25rem;
}
</style>
<!-- Contenedor principal -->
<div class="container my-4">
    <!-- Mensajes de éxito/error -->
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb y botón de añadir -->
    <nav class="mb-3">
        <div class="nav nav-tabs custom-tabs" role="tablist">
            <a class="nav-link" href="../dashboard.php">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
            <a class="nav-link active" href="listado_generadores_view.php">
                <i class="bi bi-building me-1" ></i>Mis Establecimientos
            </a>
            
        </div>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-building me-2"></i>Gestión de Establecimientos</h2>
        <a href="generador_view.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i>Añadir Nuevo
        </a>
    </div>

    <!-- Tarjeta informativa -->
    <div class="card mb-4" style="background-color: #f8f4ceff;">
        <div class="card-body">
            <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                Gestiona los establecimientos donde se generan residuos peligrosos. Cada establecimiento debe contar con su respectivo reporte anual según la Resolución 591 de 2024.
            </p>
        </div>
    </div>
</div>    
<!-- Contenedor principal -->
<div class="container my-4">
    <!-- ... (Mensajes, Breadcrumb, Tarjeta informativa - igual) ... -->
    
    <!-- Tabla de generadores -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Dirección</th>
                            <th>Categoría</th>
                            <th>Formularios</th>
                            <th>Estado <?= date('Y', strtotime('-1 year')) ?></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generadores as $generador): 
                            $info = $info_correcciones[$generador['id']] ?? null;
                            $tieneRechazos = $info && !empty($info['formularios_rechazados']);
                            $puedeCorregir = $info && $info['puede_corregir'];
                            $estado = $estados_revision[$generador['id']] ?? 'sin_revision';
                            $anio_revision = date('Y') - 1;
                            
                            // Obtener estados de formularios
                            $estado_mensual = $estados_formularios[$generador['id']]['formulario_mensual'] ?? 'sin_datos';
                            $estado_accidentes = $estados_formularios[$generador['id']]['formulario_accidentes'] ?? 'sin_datos';
                            $estado_contingencias = $estados_formularios[$generador['id']]['formulario_contingencias'] ?? 'sin_datos';

                            // Determinar qué iconos están habilitados
                            $mensual_habilitado = true;
                            $accidentes_habilitado = $estado_mensual !== 'sin_datos';
                            $contingencias_habilitado = $estado_accidentes !== 'sin_datos';
                            
                            // Verificar formularios rechazados
                            $formularios_rechazados = [];
                            if ($info && !empty($info['formularios_rechazados'])) {
                                foreach ($info['formularios_rechazados'] as $form_rechazado) {
                                    $formularios_rechazados[] = $form_rechazado['tipo'];
                                }
                            }
                            
                            // Clases de color según estado (igual que en revisiones_view)
                            $clase_color_mensual = obtenerClaseColor($estado_mensual);
                            $clase_color_accidentes = obtenerClaseColor($estado_accidentes);
                            $clase_color_contingencias = obtenerClaseColor($estado_contingencias);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($generador['nom_generador']); ?></td>
                            <td><?php echo htmlspecialchars($generador['tipo_sujeto']); ?></td>
                            <td><?php echo htmlspecialchars($generador['dir_establecimiento']); ?></td>
                            <td>
                                <?php if ($generador['categoria']): ?>
                                    <?php
                                    $clases_badge = [
                                        'Micro generador' => 'badge-categoria badge-micro',
                                        'Pequeño generador' => 'badge-categoria badge-pequeno-generador',
                                        'Mediano generador' => 'badge-categoria badge-mediano-generador',
                                        'Gran generador' => 'badge-categoria badge-gran-generador'
                                    ];
                                    $clase = $clases_badge[$generador['categoria']] ?? 'badge bg-secondary';
                                    ?>
                                    <span class="<?php echo $clase; ?>">
                                        <?php echo htmlspecialchars($generador['categoria']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin datos</span>
                                <?php endif; ?>
                            </td>
                            <!-- Columna de iconos de formularios con colores según estado -->
                            <td>
                                <div class="d-flex justify-content-start gap-2">
                                    <!-- Icono Reporte Mensual -->
                                    <a href="reporte_mensual_view.php?id=<?= $generador['id'] ?>" 
                                       class="formulario-icon <?= getIconoClase($estado_mensual, $mensual_habilitado, $formularios_rechazados, 'mensual') ?> <?= $clase_color_mensual ?>"
                                       data-tooltip="<?= getIconoTooltip('mensual', $mensual_habilitado, $estado_mensual, $formularios_rechazados) ?>"
                                       title="<?= getIconoTooltip('mensual', $mensual_habilitado, $estado_mensual, $formularios_rechazados) ?>">
                                        <i class="bi bi-clipboard-data"></i>
                                    </a>
                                    
                                    <!-- Icono Reporte Accidentes -->
                                    <a href="reporte_adicional_view.php?id=<?= $generador['id'] ?>" 
                                       class="formulario-icon <?= $accidentes_habilitado ? getIconoClase($estado_accidentes, $accidentes_habilitado, $formularios_rechazados, 'accidentes') : 'formulario-icon-deshabilitado' ?> <?= $accidentes_habilitado ? $clase_color_accidentes : '' ?>"
                                       data-tooltip="<?= getIconoTooltip('accidentes', $accidentes_habilitado, $estado_accidentes, $formularios_rechazados) ?>"
                                       title="<?= getIconoTooltip('accidentes', $accidentes_habilitado, $estado_accidentes, $formularios_rechazados) ?>"
                                       <?= !$accidentes_habilitado ? 'onclick="return false;"' : '' ?>>
                                        <i class="bi-clipboard-check"></i>
                                    </a>
                                    
                                    <!-- Icono Plan de Contingencias -->
                                    <a href="reporte_contingencias_view.php?id=<?= $generador['id'] ?>" 
                                       class="formulario-icon <?= $contingencias_habilitado ? getIconoClase($estado_contingencias, $contingencias_habilitado, $formularios_rechazados, 'contingencias') : 'formulario-icon-deshabilitado' ?> <?= $contingencias_habilitado ? $clase_color_contingencias : '' ?>"
                                       data-tooltip="<?= getIconoTooltip('contingencias', $contingencias_habilitado, $estado_contingencias, $formularios_rechazados) ?>"
                                       title="<?= getIconoTooltip('contingencias', $contingencias_habilitado, $estado_contingencias, $formularios_rechazados) ?>"
                                       <?= !$contingencias_habilitado ? 'onclick="return false;"' : '' ?>>
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </a>
                                </div>
                            </td>
                            
                            <!-- Estado general -->
                            <td>
                                <span class="badge-estado badge-estado-<?= $estado ?>" 
                                      data-bs-toggle="tooltip" 
                                      title="Estado de revisión <?= $anio_revision ?>">
                                    <i class="bi <?= $estado === 'aprobado' ? 'bi-check-circle' : ($estado === 'rechazado' ? 'bi-x-circle' : ($estado === 'pendiente' ? 'bi-clock' : 'bi-dash-circle')) ?> me-1"></i>
                                    <?= ucfirst(str_replace('_', ' ', $estado)) ?>
                                </span>
                                <?php if ($info && $estado === 'rechazado'): ?>
                                    <div class="estado-correccion">
                                        <small>Intentos: <?= $info['revision']['intentos_correccion'] ?>/<?= $info['revision']['max_intentos_permitidos'] ?></small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Acciones -->
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if ($puedeCorregir): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="generador_id" value="<?= $generador['id'] ?>">
                                        <button type="submit" name="reenviar_correccion" 
                                                class="btn-action btn-corregir-table"
                                                onclick="return confirm('¿Reenviar correcciones para <?= htmlspecialchars($generador['nom_generador']) ?>?')"
                                                title="Reenviar correcciones">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $estado_contingencias_conf = $controller->obtenerEstadoContingencias($generador['id']);
                                    $contingencias_confirmadas = ($estado_contingencias_conf === 'confirmado');
                                    ?>
                                    
                                    <a href="generador_view.php?id=<?php echo $generador['id']; ?>" 
                                       class="btn-action btn-editar <?= $contingencias_confirmadas ? 'disabled' : '' ?>" 
                                       title="<?= $contingencias_confirmadas ? 'No editable' : 'Editar' ?>"
                                       <?= $contingencias_confirmadas ? 'onclick="event.preventDefault(); mostrarAdvertenciaConfirmado();"' : '' ?>>
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <button onclick="<?= $contingencias_confirmadas ? 'mostrarAdvertenciaConfirmado()' : 'confirmarEliminacion(' . $generador['id'] . ')' ?>" 
                                            class="btn-action btn-eliminar <?= $contingencias_confirmadas ? 'disabled' : '' ?>" 
                                            title="<?= $contingencias_confirmadas ? 'No eliminable' : 'Eliminar' ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal y scripts igual... -->
<?php include '../../includes/footer.php'; ?>