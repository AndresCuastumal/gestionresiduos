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

include '../../includes/header.php';
?>

<style>
/* Estilos existentes... */
.btn-action.disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none;
}

.btn-action.disabled:hover {
    background-color: transparent !important;
    transform: none !important;
}

/* ✅ NUEVOS ESTILOS para alertas de corrección */
.correccion-alerta {
    border-left: 4px solid #ffc107;
    background-color: #fff8e1;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
}

.correccion-alerta .btn-corregir {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
}

.correccion-alerta .btn-corregir:hover {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.correccion-alerta-danger {
    border-left: 4px solid #dc3545;
    background-color: #f8d7da;
}

.estado-correccion {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.intentos-info {
    background-color: #f8f9fa;
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 10px;
    border-left: 3px solid #6c757d;
}

.formularios-rechazados {
    background-color: #fefefe;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 10px;
    margin-top: 10px;
}

.formulario-rechazado-item {
    padding: 8px;
    margin-bottom: 5px;
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
    border-radius: 3px;
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
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Mis Establecimientos</li>
        </ol>
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

    <!-- ✅ NUEVA SECCIÓN: Alerta de correcciones pendientes (si aplica) -->
    <?php 
    $tieneCorreccionesPendientes = false;
    foreach ($generadores as $generador):
        $info = $info_correcciones[$generador['id']] ?? null;
        if ($info && $info['puede_corregir']):
            $tieneCorreccionesPendientes = true;
    ?>
    <div class="correccion-alerta">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="mb-2"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Tienes formularios rechazados para <strong><?= htmlspecialchars($generador['nom_generador']) ?></strong>
                </h5>
                
                <div class="intentos-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Intentos usados: <strong><?= $info['revision']['intentos_correccion'] ?></strong> de 
                    <strong><?= $info['revision']['max_intentos_permitidos'] ?></strong> permitidos
                </div>
                
                <?php if (!empty($info['formularios_rechazados'])): ?>
                <div class="formularios-rechazados">
                    <p class="mb-2"><strong>Formularios a corregir:</strong></p>
                    <?php foreach ($info['formularios_rechazados'] as $formulario): ?>
                    <div class="formulario-rechazado-item">
                        <strong><?= $formulario['nombre'] ?></strong>
                        <?php if ($formulario['observaciones']): ?>
                        <p class="mb-1 small">Observaciones: <?= htmlspecialchars($formulario['observaciones']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <p class="mb-0 mt-2">
                    <small class="text-muted">
                        <i class="bi bi-clock-history me-1"></i>
                        Tienes <strong>1 oportunidad</strong> para corregir los formularios rechazados.
                    </small>
                </p>
            </div>
            
            <form method="POST" style="min-width: 200px;">
                <input type="hidden" name="generador_id" value="<?= $generador['id'] ?>">
                <button type="submit" name="reenviar_correccion" class="btn-corregir"
                        onclick="return confirm('¿Estás seguro de reenviar las correcciones?\n\nEsto contará como un intento de corrección.')">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reenviar Correcciones
                </button>
            </form>
        </div>
    </div>
    <?php 
        endif;
    endforeach; 
    ?>

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
                            <th>Estado <?= date('Y', strtotime('-1 year')) ?></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generadores as $generador): 
                            $info = $info_correcciones[$generador['id']] ?? null;
                            $tieneRechazos = $info && !empty($info['formularios_rechazados']);
                            $puedeCorregir = $info && $info['puede_corregir'];
                        ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($generador['nom_generador']); ?>
                                    <?php if ($tieneRechazos && !$puedeCorregir): ?>
                                        <span class="badge bg-danger ms-1" data-bs-toggle="tooltip" 
                                              title="Tiene formularios rechazados sin posibilidad de corrección">
                                            <i class="bi bi-x-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <?php
                                    $estado = $estados_revision[$generador['id']] ?? 'sin_revision';
                                    
                                    $estados_config = [
                                        'pendiente' => [
                                            'clase' => 'badge-estado badge-estado-pendiente',
                                            'texto' => 'Pendiente',
                                            'icono' => 'bi bi-clock'
                                        ],
                                        'aprobado' => [
                                            'clase' => 'badge-estado badge-estado-aprobado', 
                                            'texto' => 'Aprobado',
                                            'icono' => 'bi bi-check-circle'
                                        ],
                                        'rechazado' => [
                                            'clase' => 'badge-estado badge-estado-rechazado',
                                            'texto' => 'Rechazado',
                                            'icono' => 'bi bi-x-circle'
                                        ],
                                        'sin_revision' => [
                                            'clase' => 'badge-estado badge-estado-sin-revision',
                                            'texto' => 'Sin revisión',
                                            'icono' => 'bi bi-dash-circle'
                                        ]
                                    ];
                                    
                                    $config = $estados_config[$estado] ?? $estados_config['sin_revision'];
                                    $anio_revision = date('Y') - 1;
                                    ?>
                                    
                                    <span class="<?= $config['clase'] ?>" 
                                          data-bs-toggle="tooltip" 
                                          title="Estado de revisión <?= $anio_revision ?>">
                                        <i class="<?= $config['icono'] ?> me-1"></i>
                                        <?= $config['texto'] ?>
                                    </span>
                                    
                                    <!-- ✅ NUEVO: Mostrar información de intentos -->
                                    <?php if ($info && $estado === 'rechazado'): ?>
                                        <div class="estado-correccion">
                                            <small>
                                                Intentos: <?= $info['revision']['intentos_correccion'] ?>/<?= $info['revision']['max_intentos_permitidos'] ?>
                                                <?php if (!$puedeCorregir && $info['revision']['intentos_correccion'] > 0): ?>
                                                    <span class="text-danger">(Sin intentos)</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                    
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Botón Reportar -->
                                        <a href="reporte_mensual_view.php?id=<?php echo $generador['id']; ?>" 
                                           class="btn-action btn-reportar" title="Reportar residuos">
                                            <i class="bi bi-clipboard-data"></i>
                                        </a>
                                        
                                        <!-- ✅ NUEVO: Botón para corregir formularios rechazados -->
                                        <?php if ($puedeCorregir): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="generador_id" value="<?= $generador['id'] ?>">
                                            <button type="submit" name="reenviar_correccion" 
                                                    class="btn-action btn-corregir-table"
                                                    onclick="return confirm('¿Reenviar correcciones para <?= htmlspecialchars($generador['nom_generador']) ?>?\n\nEsto contará como un intento.')"
                                                    title="Reenviar correcciones de formularios rechazados">
                                                <i class="bi bi-arrow-clockwise text-warning"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $estado_contingencias = $controller->obtenerEstadoContingencias($generador['id']);
                                        $contingencias_confirmadas = ($estado_contingencias === 'confirmado');
                                        ?>
                                        
                                        <!-- Botón Editar -->
                                        <a href="generador_view.php?id=<?php echo $generador['id']; ?>" 
                                           class="btn-action btn-editar <?= $contingencias_confirmadas ? 'disabled' : '' ?>" 
                                           title="<?= $contingencias_confirmadas ? 'No editable - Contingencias confirmadas' : 'Editar' ?>"
                                           <?= $contingencias_confirmadas ? 'onclick="event.preventDefault(); mostrarAdvertenciaConfirmado();"' : '' ?>>
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <!-- Botón Eliminar -->
                                        <button onclick="<?= $contingencias_confirmadas ? 'mostrarAdvertenciaConfirmado()' : 'confirmarEliminacion(' . $generador['id'] . ')' ?>" 
                                                class="btn-action btn-eliminar <?= $contingencias_confirmadas ? 'disabled' : '' ?>" 
                                                title="<?= $contingencias_confirmadas ? 'No eliminable - Contingencias confirmadas' : 'Eliminar' ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($generadores)): ?>
                <div class="alert alert-info text-center mt-3">
                    <i class="bi bi-info-circle me-2"></i>No tienes establecimientos registrados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de eliminar este establecimiento? Todos sus reportes mensuales también se eliminarán.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="eliminarBtn" href="#" class="btn btn-outline-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../../includes/footer.php'; ?>

<script>
function confirmarEliminacion(id) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    document.getElementById('eliminarBtn').href = `listado_generadores_view.php?eliminar=${id}`;
    modal.show();
}

function mostrarAdvertenciaConfirmado() {
    alert("Este establecimiento no se puede editar ni eliminar porque ya tiene un plan de contingencias confirmado.");
}

// Inicializar tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Estilo para botón de corrección en tabla
document.querySelectorAll('.btn-corregir-table').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#fff3cd';
        this.style.borderColor = '#ffc107';
    });
    btn.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
        this.style.borderColor = '';
    });
});
</script>