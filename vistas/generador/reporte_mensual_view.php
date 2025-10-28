<?php
session_start();
require_once '../../includes/conexion.php';
require_once '../../procesos/generador/reporte_mensual_controller.php';
require_once '../../procesos/admin/revisiones_controller.php';

// Verificar si viene de navegación interna entre formularios
if (isset($_GET['id']) && !isset($_SESSION['generador_id_reportando'])) {
    $_SESSION['generador_id_reportando'] = $_GET['id'];
    $_SESSION['anio_reportando'] = date('Y', strtotime('-1 year'));
}

// Obtener datos del generador
if (isset($_GET['id'])) {
    $generador_id = $_GET['id'];
    
    // Crear controlador y obtener datos
    $controller = new ReporteMensualController($conn);
    $revisionController = new RevisionesController($conn);
    
    // Verificar permisos
    if ($_SESSION['usuario_rol'] !== 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuario_generador 
                               WHERE usuario_id = ? AND generador_id = ?");
        $stmt->execute([$_SESSION['usuario_id'], $generador_id]);
        $tiene_acceso = $stmt->fetchColumn();
        
        if (!$tiene_acceso) {
            header("Location: acceso_denegado.php");
            exit();             
        }
    }
    
    // Obtener datos del generador
    $generador = $controller->obtenerDatosGenerador($generador_id);
    $anio_actual = date('Y', strtotime('-1 year'));
    $reportes_existentes = $controller->obtenerReportesExistentes($generador_id, $anio_actual);

    // ✅ NUEVO: Verificar estado del formulario mensual
    $estado_formulario_mensual = $revisionController->obtenerEstadoFormulario($generador_id, $anio_actual, 'mensual');
    $puede_editar = ($estado_formulario_mensual === 'rechazado');

    // ✅ NUEVA LÓGICA: Puede editar si está rechazado O si no hay revisión (estado inicial)
    $modo_edicion = $puede_editar || ($estado_formulario_mensual === 'pendiente' || $estado_formulario_mensual === 'sin_datos');
    
    // Obtener información de revisión anual existente
    $stmt = $conn->prepare("SELECT * FROM revisiones_anuales 
                           WHERE generador_id = ? AND anio = ?");
    $stmt->execute([$generador_id, $anio_actual]);
    $revision_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si las contingencias ya están confirmadas (bloqueadas)
    $stmt_contingencias = $conn->prepare("SELECT estado FROM contingencias WHERE generador_id = ? AND anio = ?");
    $stmt_contingencias->execute([$generador_id, $anio_actual]);
    $contingencia = $stmt_contingencias->fetch(PDO::FETCH_ASSOC);
    
    // ✅ ACTUALIZAR: Considerar bloqueado solo si contingencias confirmadas Y no puede editar por rechazo
    $reporte_bloqueado = ($contingencia && isset($contingencia['estado']) && $contingencia['estado'] == 'confirmado') && !$puede_editar;
    $readonly = $reporte_bloqueado ? 'readonly' : '';
    $disabled = $reporte_bloqueado ? 'disabled' : '';
    
    // Verificar si los tres formularios están completos (pero en estado borrador)
    $formularios_completos = false;
    $menu_navegacion_activo = false;

    if (!$reporte_bloqueado) {
        // Verificar reporte mensual
        $stmt_mensual_check = $conn->prepare("SELECT COUNT(*) as total FROM cantidad_x_mes WHERE id_generador = ? AND anio = ?");
        $stmt_mensual_check->execute([$generador_id, $anio_actual]);
        $reporte_mensual_check = $stmt_mensual_check->fetch(PDO::FETCH_ASSOC);
        
        // Verificar reporte adicional
        $stmt_adicional = $conn->prepare("SELECT COUNT(*) as total FROM reporte_anual_adicional WHERE generador_id = ? AND anio = ?");
        $stmt_adicional->execute([$generador_id, $anio_actual]);
        $reporte_adicional = $stmt_adicional->fetch(PDO::FETCH_ASSOC);
        
        // Verificar contingencias
        $stmt_contingencias_check = $conn->prepare("SELECT COUNT(*) as total FROM contingencias WHERE generador_id = ? AND anio = ?");
        $stmt_contingencias_check->execute([$generador_id, $anio_actual]);
        $contingencias_check = $stmt_contingencias_check->fetch(PDO::FETCH_ASSOC);
        
        // Considerar completos si existen registros en las tres tablas
        $formularios_completos = ($reporte_mensual_check['total'] > 0 && $reporte_adicional['total'] > 0 && $contingencias_check['total'] > 0);
        $menu_navegacion_activo = $formularios_completos;
    }
    
} else {
    header("Location: listado_generadores_view.php");
    exit();
}

include '../../includes/header.php';
?>
<?php
// ✅ NUEVO: Mensaje específico para formularios rechazados
if ($estado_formulario_mensual === 'rechazado'): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Formulario Requiere Correcciones</strong>
        <p class="mb-0 mt-2">Este formulario ha sido <strong>rechazado</strong> por el revisor. 
        Por favor realice las correcciones solicitadas y envíe nuevamente para revisión.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php
// Mensaje si el reporte ya fue enviado (mantener existente)
if(isset($contingencia) && is_array($contingencia) && isset($contingencia['estado']) && $contingencia['estado']=='confirmado' && !$puede_editar): ?>
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
                    <li class="breadcrumb-item active">Reporte Mensual</li>
                    <li class="breadcrumb-item"><a href="reporte_adicional_view.php?id=<?= $generador_id ?>">Capacitaciones</a></li>
                    <li class="breadcrumb-item"><a href="reporte_contingencias_view.php?id=<?= $generador_id ?>">Contingencias</a></li>
                <?php else: ?>
                    <!-- Menú simplificado cuando no están todos completos -->
                    <li class="breadcrumb-item active">Reporte Mensual</li>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-data me-2"></i>Reporte Mensual de Residuos</h2>
            <a href="listado_generadores_view.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Mensaje informativo si ya existe información guardada -->
        <?php if ($revision_existente && isset($contingencia) && is_array($contingencia) && isset($contingencia['estado']) && $contingencia['estado']=='borrador'): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Información precargada:</strong> Se han encontrado datos guardados previamente para este año. 
            Puede modificar los campos que necesite y guardar los cambios.
        </div>
        
        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Complete el reporte mensual de residuos en atención en salud y otras activides generados durante el año <?= $anio_actual ?>.
                    Ingrese la cantidad en kilogramos (kg) para cada mes y adjunte el soporte documental correspondiente.
                </p>
                <p class="mb-0"><strong>Establecimiento:</strong> <?= htmlspecialchars($generador['nom_generador']) ?></p>
            </div>
        </div>
        <!-- Información adicional -->
        <div class="info-card mt-4">
            <h6><i class="bi bi-info-circle me-2"></i>Instrucciones y Categorías</h6>
            <ul class="mb-3">
                <li>Ingrese la cantidad total de residuos peligrosos generados cada mes en kilogramos (kg)</li>
                <li>El sistema calculará automáticamente su categoría basado en el promedio móvil de los últimos 6 meses</li>
                <li>Puede dejar en blanco los meses sin generación de residuos</li>
            </ul>
            
            <h6 class="mt-3">Rangos de Categorización:</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="border rounded p-2 text-center mb-2">
                        <strong class="d-block">Micro generador</strong>
                        <small class="text-muted">&lt; 10 kg</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 text-center mb-2">
                        <strong class="d-block">Pequeño generador</strong>
                        <small class="text-muted">10 - 99.99 kg</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 text-center mb-2">
                        <strong class="d-block">Mediano generador</strong>
                        <small class="text-muted">100 - 999.99 kg</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 text-center mb-2">
                        <strong class="d-block">Gran generador</strong>
                        <small class="text-muted">≥ 1000 kg</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Importante</h6>
            <ul class="mb-0">
                <li>El PDF debe incluir <strong>todos los soportes mensuales</strong> del año <?= $anio_actual ?></li>
                <li>El archivo será revisado por un administrador para validar la información</li>
                <li>El estado de su reporte cambiará de estado "Sin revisión" a "Pendiente"</li>
                <li>Recibirá una notificación cuando sea aprobado o rechazado</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Reporte año <?= $anio_actual ?></h5>
                <!-- ✅ NUEVO: Badge de estado -->
                <span class="badge 
                    <?= $estado_formulario_mensual === 'aprobado' ? 'bg-success' : '' ?>
                    <?= $estado_formulario_mensual === 'rechazado' ? 'bg-danger' : '' ?>
                    <?= $estado_formulario_mensual === 'pendiente' ? 'bg-warning' : '' ?>
                    <?= $estado_formulario_mensual === 'sin_datos' ? 'bg-secondary' : '' ?>">
                    <?= strtoupper($estado_formulario_mensual) ?>
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
                <?php if (!$modo_edicion && $estado_formulario_mensual === 'aprobado'): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-check-circle me-2"></i>
                        Este formulario ha sido <strong>aprobado</strong> y no requiere modificaciones.
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" action="../../procesos/generador/procesar_reporte_mensual.php?id=<?= $generador_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio_actual ?>">
                    
                    <div class="mb-3">                        
                        <div class="form-text">Sistema de reporte anual según Resolución 591 de 2024</div>
                    </div>
                    
                    <!-- Datos del reporte mensual -->
                    <h6 class="text-muted mb-3">Cantidad de residuos generados en atención en salud y otras actividades por mes (kg)</h6>
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
                                name="meses[<?= $id_mes ?>]" 
                                value="<?= $valor_actual ?>"
                                class="form-control form-control-sm mes-cantidad 
                                        <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                placeholder="0.00" 
                                <?= $readonly ?>
                                <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Soporte documental -->
                    <div class="info-card mt-4">
                        <h6><i class="bi bi-file-pdf me-2"></i>Soporte Documental Anual</h6>
                        <div class="mb-3">
                            <label for="soporte_pdf" class="form-label">
                                Cargar PDF con soportes de los 12 meses
                                <?php if (!$revision_existente && $modo_edicion): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <input type="file" class="form-control 
                                <?= !$modo_edicion ? 'bg-light' : '' ?>" 
                                id="soporte_pdf" name="soporte_pdf" 
                                accept=".pdf" 
                                <?= !$revision_existente && $modo_edicion ? 'required' : '' ?> 
                                <?= $disabled ?>
                                <?= !$modo_edicion ? 'style="background-color: #f8f9fa; border-color: #dee2e6;"' : '' ?>>
                            <div class="form-text">
                                Suba un solo archivo PDF que incluya todos los certificados, actas o soportes 
                                de la empresa recolectora para los 12 meses del año. Tamaño máximo: 10MB.
                            </div>
                            
                            <?php if ($revision_existente && !empty($revision_existente['soporte_pdf'])): ?>
                            <div class="form-text mt-2">
                                <a href="../../procesos/uploads/soportes_anuales/<?= $revision_existente['soporte_pdf'] ?>" 
                                target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>Ver PDF actual
                                </a>
                                <span class="ms-2 text-muted">Si no selecciona un nuevo archivo, se mantendrá el actual.</span>
                            </div>
                            <?php endif; ?>
                        </div>                             
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="listado_generadores_view.php" class="btn btn-outline btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                        <?php  
                        // ✅ ACTUALIZAR: Mostrar botón solo si puede editar
                        if($modo_edicion && !$reporte_bloqueado): 
                            ?>
                            <button type="submit" class="btn btn-outline btn-outline-success">
                                <i class="bi bi-cloud-upload me-2"></i>
                                <?= $revision_existente ? 'Actualizar' : 'Guardar' ?> Reporte
                                <?= $estado_formulario_mensual === 'rechazado' ? 'y Reenviar' : '' ?>
                            </button>
                        <?php endif; ?>
                    </div> 
                </form>
            </div>
        </div>      
    </div>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>