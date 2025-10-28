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
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 2;

// Obtener revisiones con filtros
$revisiones = $controller->obtenerRevisionesConFiltros($filtro_tipo, $filtro_estado);

// Obtener tipos de sujeto para el filtro
$tipos_sujeto = $controller->obtenerTiposSujeto();

// Calcular paginación
$total_registros = count($revisiones);
$total_paginas = ceil($total_registros / $registros_por_pagina);
$inicio = ($pagina_actual - 1) * $registros_por_pagina;
$revisiones_paginadas = array_slice($revisiones, $inicio, $registros_por_pagina);

foreach ($revisiones_paginadas as $revision): 
// Validar que los campos existan y tengan valores por defecto
$form_mensual = $revision['formulario_mensual'] ?? 'sin_datos';
$form_accidentes = $revision['formulario_accidentes'] ?? 'sin_datos';
$form_contingencias = $revision['formulario_contingencias'] ?? 'sin_datos';
$estado_actual = $revision['estado_general'] ?? 'sin_datos';

// Determinar el estado general
$estado_general = determinarEstadoGeneral($form_mensual, $form_accidentes, $form_contingencias);

// Actualizar el estado general en la base de datos si es diferente
if ($estado_actual !== $estado_general) {
    $controller->actualizarEstadoGeneral(
        $revision['generador_id'], 
        $revision['anio'], 
        $estado_general
    );
    $revision['estado_general'] = $estado_general;
}
endforeach;

// Función para determinar el estado general basado en los tres formularios
// Función para determinar el estado general basado en los tres formularios
function determinarEstadoGeneral($formulario_mensual, $formulario_accidentes, $formulario_contingencias) {
    // Si alguno está rechazado, estado general es "rechazado"
    if ($formulario_mensual === 'rechazado' || 
        $formulario_accidentes === 'rechazado' || 
        $formulario_contingencias === 'rechazado') {
        return 'rechazado';
    }
    
    // Si todos están aprobados, estado general es "aprobado"
    if ($formulario_mensual === 'aprobado' && 
        $formulario_accidentes === 'aprobado' && 
        $formulario_contingencias === 'aprobado') {
        return 'aprobado';
    }
    
    // En cualquier otro caso, está "pendiente"
    return 'pendiente';
}

// Función para obtener la clase CSS del badge según el estado
function obtenerClaseEstado($estado) {
    switch ($estado) {
        case 'aprobado': return 'badge-estado-aprobado';
        case 'rechazado': return 'badge-estado-rechazado';
        case 'pendiente': return 'badge-estado-pendiente';
        case 'sin_datos': return 'badge-estado-sin-datos';
        default: return 'badge-estado-sin-revision';
    }
}

// Función para obtener el texto del estado
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

include '../../includes/header.php';
?>
    <!-- Contenedor principal -->
    <div class="container my-4">
        <!-- Breadcrumb y título -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Revisiones - Reporte Anual</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check me-2"></i>Revisiones - Reporte Anual</h2>
            <a href="../dashboard.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-house me-2"></i>Inicio
            </a>
        </div>

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Revisión y validación de los reportes anuales según la Resolución 591 de 2024. 
                    El estado general se actualiza automáticamente según la revisión de los formularios individuales.
                </p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label for="estado_general" class="form-label">Estado General</label>
                        <select name="estado_general" id="estado_general" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="aprobado" <?= $filtro_estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rechazado" <?= $filtro_estado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                            <!-- Eliminamos la opción "sin_datos" -->
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-filter me-2"></i>Filtrar
                        </button>
                        <a href="listado_revisiones_view.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contador de resultados -->
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-2"></i>
            Mostrando <?= count($revisiones_paginadas) ?> de <?= $total_registros ?> revisiones
            <?php if ($filtro_tipo || $filtro_estado): ?>
                (filtradas)
            <?php endif; ?>
            <br><small><em>Se muestran solo los generadores que reportaron datos en los tres formularios</em></small>
        </div>
        
        <?php if (empty($revisiones_paginadas)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No se encontraron revisiones con los filtros seleccionados.
            </div>
        <?php else: ?>
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
                            // Determinar el estado general usando SOLO los valores de los campos
                            $estado_general = determinarEstadoGeneral(
                                $revision['formulario_mensual'],
                                $revision['formulario_accidentes'], 
                                $revision['formulario_contingencias']
                            );
                            
                            // Actualizar el estado general en la base de datos si es diferente
                            if ($revision['estado_general'] !== $estado_general) {
                                $controller->actualizarEstadoGeneral(
                                    $revision['generador_id'], 
                                    $revision['anio'], 
                                    $estado_general
                                );
                                $revision['estado_general'] = $estado_general;
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($revision['nom_generador']) ?></td>
                            <td><?= htmlspecialchars($revision['nom_responsable']) ?></td>
                            <td><?= htmlspecialchars($revision['nom_tipo']) ?></td>                                
                            <td><?= $revision['anio'] ?></td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    <!-- Botón para Reporte Mensual -->
                                    <a href="revisar_formulario_mensual.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual]) ?>" 
                                    class="btn-formulario btn-formulario-mensual" title="Revisar Reporte Mensual">
                                        <i class="bi bi-clipboard-data me-1"></i>
                                        Reporte Mensual: <span class="fw-semibold"><?= ucfirst(obtenerTextoEstado($revision['formulario_mensual'])) ?></span>
                                    </a>
                                    
                                    <!-- Botón para Capacitaciones, Accidentes y Auditorías -->
                                    <a href="revisar_formulario_accidentes.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual]) ?>" 
                                    class="btn-formulario btn-formulario-accidentes" title="Revisar Capacitaciones y Accidentes">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Capacitaciones y Accidentes: <span class="fw-semibold"><?= ucfirst(obtenerTextoEstado($revision['formulario_accidentes'])) ?></span>
                                    </a>
                                    
                                    <!-- Botón para Plan de Contingencias -->
                                    <a href="revisar_formulario_contingencias.php?generador_id=<?= $revision['generador_id'] ?>&anio=<?= $revision['anio'] ?>&<?= http_build_query(['tipo_sujeto' => $filtro_tipo, 'estado_general' => $filtro_estado, 'pagina' => $pagina_actual]) ?>" 
                                    class="btn-formulario btn-formulario-contingencias" title="Revisar Plan de Contingencias">
                                        <i class="bi bi-shield-exclamation me-1"></i>
                                        Plan de Contingencias: <span class="fw-semibold"><?= ucfirst(obtenerTextoEstado($revision['formulario_contingencias'])) ?></span>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="<?= obtenerClaseEstado($revision['estado_general']) ?>">
                                    <?= ucfirst($revision['estado_general']) ?>
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
                    <!-- Botón Anterior -->
                    <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                    </li>

                    <!-- Números de página -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Botón Siguiente -->
                    <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>            
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>