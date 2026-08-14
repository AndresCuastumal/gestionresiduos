<?php
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';

// Verificar sesión y permisos de admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$controller = new RevisionesController($conn);

// Obtener parámetros de filtro
$filtro_tipo = $_GET['tipo_sujeto'] ?? '';
$filtro_estado = $_GET['estado_general'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;

// Verificar si hay filtros aplicados
$hay_filtros = !empty($filtro_tipo) || !empty($filtro_estado) || !empty($filtro_busqueda);

// Obtener revisiones SOLO si hay filtros aplicados
$revisiones = [];
if ($hay_filtros) {
    $revisiones = $controller->obtenerRevisionesConFiltros($filtro_tipo, $filtro_estado, $filtro_busqueda);
}

// Obtener tipos de sujeto para el filtro
$tipos_sujeto = $controller->obtenerTiposSujeto();

// Calcular paginación
$total_registros = count($revisiones);
$total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 0;
$inicio = ($pagina_actual - 1) * $registros_por_pagina;
$revisiones_paginadas = $total_registros > 0 ? array_slice($revisiones, $inicio, $registros_por_pagina) : [];

// FUNCIONES
function determinarEstadoGeneral($formulario_mensual, $formulario_accidentes, $formulario_contingencias) {
    // Si algún formulario tiene sin_datos, el estado general es "sin_datos"
    if ($formulario_mensual === 'sin_datos' || 
        $formulario_accidentes === 'sin_datos' || 
        $formulario_contingencias === 'sin_datos') {
        return 'sin_datos';
    }
    
    if ($formulario_mensual === 'rechazado' || 
        $formulario_accidentes === 'rechazado' || 
        $formulario_contingencias === 'rechazado') {
        return 'rechazado';
    }
    
    if ($formulario_mensual === 'aprobado' && 
        $formulario_accidentes === 'aprobado' && 
        $formulario_contingencias === 'aprobado') {
        return 'aprobado';
    }
    
    return 'pendiente';
}

function obtenerClaseEstado($estado) {
    switch ($estado) {
        case 'aprobado': return 'badge-estado-aprobado';
        case 'rechazado': return 'badge-estado-rechazado';
        case 'pendiente': return 'badge-estado-pendiente';
        case 'sin_datos': return 'badge-estado-sin-datos';
        default: return 'badge-estado-sin-revision';
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

function obtenerClaseColor($estado) {
    switch($estado) {
        case 'pendiente': return 'text-warning';
        case 'rechazado': return 'text-danger';
        case 'aprobado': return 'text-success';
        case 'sin_datos': return 'text-secondary';
        default: return 'text-secondary';
    }
}

include '../../includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb y título -->
    <nav class="mb-3">
        <div class="nav nav-tabs custom-tabs" role="tablist">
            <a class="nav-link" href="../dashboard.php">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
            <a class="nav-link active" href="#">
                <i class="bi bi-building me-1"></i>Establecimientos
            </a>                
        </div>
    </nav>       

    <!-- Tarjeta informativa -->
    <div class="card mb-4" style="background-color: #f8f4ceff;">
        <div class="card-body">
            <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                Revisión y validación de los reportes anuales según la Resolución 591 de 2024. 
                El estado general se actualiza automáticamente según la revisión de los formularios individuales.
                <strong>Seleccione filtros para visualizar los establecimientos.</strong>
            </p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros de búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="busqueda" class="form-label">Nombre o NIT del establecimiento</label>
                    <div class="input-group">                            
                        <span class="input-group-text"><i class="bi bi-search"></i></span>                            
                        <input type="text" 
                            name="busqueda" 
                            id="busqueda" 
                            class="form-control" 
                            placeholder="Escriba aquí"
                            value="<?= htmlspecialchars($filtro_busqueda) ?>">
                        <?php if (!empty($filtro_busqueda)): ?>
                        <a href="?<?= http_build_query(array_diff_key($_GET, ['busqueda' => ''])) ?>" 
                           class="btn btn-outline-secondary" 
                           title="Limpiar búsqueda">
                            <i class="bi bi-x"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">Buscar por nombre del establecimiento o NIT</small>
                </div>
                <div class="col-md-3">
                    <label for="tipo_sujeto" class="form-label">Tipo de Sujeto</label>
                    <select name="tipo_sujeto" id="tipo_sujeto" class="form-select">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos_sujeto as $id => $nombre): ?>
                            <option value="<?= $id ?>" <?= $filtro_tipo == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>              
                <div class="col-md-3">
                    <label for="estado_general" class="form-label">Estado General</label>
                    <select name="estado_general" id="estado_general" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="aprobado" <?= $filtro_estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="rechazado" <?= $filtro_estado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                        <option value="sin_datos" <?= $filtro_estado === 'sin_datos' ? 'selected' : '' ?>>Sin datos (al menos un formulario sin reportar)</option>
                    </select>
                    <small class="text-muted">"Sin datos" muestra establecimientos con al menos un formulario sin reportar</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-outline-primary me-2 w-100">
                        <i class="bi bi-filter me-2"></i>Filtrar
                    </button>
                    <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$hay_filtros): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
            <h5>No hay filtros aplicados</h5>
            <p class="mb-0">Seleccione al menos un filtro (Tipo de Sujeto, Estado General o busque por nombre/NIT) para visualizar los establecimientos.</p>
        </div>
    <?php elseif (empty($revisiones_paginadas)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No se encontraron revisiones con los filtros seleccionados.
            <?php if ($filtro_busqueda): ?>
                <br><small>Búsqueda: "<?= htmlspecialchars($filtro_busqueda) ?>"</small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-2"></i>
            Mostrando <?= count($revisiones_paginadas) ?> de <?= $total_registros ?> revisiones
            <?php if ($filtro_estado === 'sin_datos'): ?>
                <br><small><strong>Nota:</strong> Mostrando establecimientos que tienen al menos un formulario sin datos</small>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Generador</th>
                        <th>Responsable de reporte</th>
                        <th>Tipo sujeto</th>
                        <th>Año</th>
                        <th>Formularios</th>
                        <th>Estado General</th>                                
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revisiones_paginadas as $revision): 
                        // Determinar el estado general REAL
                        $estado_general = determinarEstadoGeneral(
                            $revision['formulario_mensual'],
                            $revision['formulario_accidentes'], 
                            $revision['formulario_contingencias']
                        );
                        
                        // Mostrar badge de qué formularios están sin datos
                        $sin_datos_list = [];
                        if ($revision['formulario_mensual'] === 'sin_datos') $sin_datos_list[] = 'Mensual';
                        if ($revision['formulario_accidentes'] === 'sin_datos') $sin_datos_list[] = 'Accidentes';
                        if ($revision['formulario_contingencias'] === 'sin_datos') $sin_datos_list[] = 'Contingencias';
                        $tooltip_sin_datos = !empty($sin_datos_list) ? 'Sin datos en: ' . implode(', ', $sin_datos_list) : '';
                    ?>
                    <tr title="<?= htmlspecialchars($tooltip_sin_datos) ?>">
                        <td><?= htmlspecialchars($revision['nom_generador']) ?></td>
                        <td><?= htmlspecialchars($revision['nom_responsable']) ?></td>
                        <td><?= htmlspecialchars($revision['nom_tipo']) ?></td>                                
                        <td><?= $revision['anio'] ?></td>
                        <td>
                            <div class="d-flex justify-content-start gap-1">
                                <a href="revisar_formulario_mensual.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual, 'busqueda' => $filtro_busqueda]) ?>" 
                                   class="btn btn-sm btn-link text-decoration-none p-1" 
                                   title="Reporte Mensual - <?= ucfirst(obtenerTextoEstado($revision['formulario_mensual'])) ?>">
                                    <i class="bi bi-clipboard-data fs-5 <?= obtenerClaseColor($revision['formulario_mensual']) ?>"></i>
                                </a>
                                
                                <a href="revisar_formulario_accidentes.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual, 'busqueda' => $filtro_busqueda]) ?>" 
                                   class="btn btn-sm btn-link text-decoration-none p-1" 
                                   title="Accidentes - <?= ucfirst(obtenerTextoEstado($revision['formulario_accidentes'])) ?>">
                                    <i class="bi bi-exclamation-triangle fs-5 <?= obtenerClaseColor($revision['formulario_accidentes']) ?>"></i>
                                </a>
                                
                                <a href="revisar_formulario_contingencias.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual, 'busqueda' => $filtro_busqueda]) ?>" 
                                   class="btn btn-sm btn-link text-decoration-none p-1" 
                                   title="Contingencias - <?= ucfirst(obtenerTextoEstado($revision['formulario_contingencias'])) ?>">
                                    <i class="bi bi-shield-exclamation fs-5 <?= obtenerClaseColor($revision['formulario_contingencias']) ?>"></i>
                                </a>
                                <!-- si el estado general es aprobado, mostrar un ícono para ver el certificado en pdf -->
                                <?php if ($estado_general === 'aprobado'): ?>
                                    <a href="../../procesos/admin/certificados/certificado_aprobacion_<?= $revision['generador_id'] ?>_<?= $revision['anio'] ?>.pdf" 
                                       class="btn btn-sm btn-link text-decoration-none p-1" 
                                       title="Ver Certificado PDF">
                                        <i class="bi bi-file-earmark-pdf fs-5 text-warning"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                         </td>
                         <td>
                            <span class="<?= obtenerClaseEstado($estado_general) ?>" 
                                  title="<?= htmlspecialchars($tooltip_sin_datos) ?>">
                                <?= ucfirst($estado_general) ?>
                                <?php if (!empty($sin_datos_list)): ?>
                                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="<?= htmlspecialchars($tooltip_sin_datos) ?>"></i>
                                <?php endif; ?>
                            </span>
                         </td>                                
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Paginación de revisiones">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>            
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>