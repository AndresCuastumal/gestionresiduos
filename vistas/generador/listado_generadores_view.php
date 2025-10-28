<?php
require_once '../../procesos/generador/listado_generadores_controller.php';
include '../../includes/header.php'; // Incluye el encabezado HTML
?>
<STyle>
/* En tu archivo CSS */
.btn-action.disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none;
}

.btn-action.disabled:hover {
    background-color: transparent !important;
    transform: none !important;
}
</STyle>
<!-- Contenedor principal -->
    <div class="container my-4">
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
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

        <!-- Tarjeta informativa (estilo del dashboard) -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Gestiona los establecimientos donde se generan residuos peligrosos. Cada establecimiento debe contar con su respectivo reporte anual según la Resolución 591 de 2024.
                </p>
            </div>
        </div>

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
                                <th>Estado <?= date('Y', strtotime('-1 year') ) ?></th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($generadores as $generador): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($generador['nom_generador']); ?></td>
                                    <td><?php echo htmlspecialchars($generador['tipo_sujeto']); ?></td>
                                    <td><?php echo htmlspecialchars($generador['dir_establecimiento']); ?></td>
                                    <td>
                                        <?php if ($generador['categoria']): ?>
                                            <?php
                                            // Mapear categorías a clases CSS simplificadas
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
                                        
                                        // Configuración de estados simplificados
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
                                        $anio_revision = date('Y') - 1; // Año anterior para la revisión
                                        ?>
                                        
                                        <span class="<?= $config['clase'] ?>" 
                                            data-bs-toggle="tooltip" 
                                            title="Estado de revisión <?= $anio_revision ?>">
                                            <i class="<?= $config['icono'] ?> me-1"></i>
                                            <?= $config['texto'] ?>
                                        </span>
                                    </td>
                                        
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="reporte_mensual_view.php?id=<?php echo $generador['id']; ?>" 
                                            class="btn-action btn-reportar" title="Reportar residuos">
                                                <i class="bi bi-clipboard-data"></i>
                                            </a>
                                            
                                            <?php
                                            // ✅ USAR LA NUEVA FUNCIÓN del controlador
                                            $estado_contingencias = $controller->obtenerEstadoContingencias($generador['id']);
                                            $contingencias_confirmadas = ($estado_contingencias === 'confirmado');
                                            ?>
                                            
                                            <!-- Botón Editar - Condicional -->
                                            <a href="generador_view.php?id=<?php echo $generador['id']; ?>" 
                                            class="btn-action btn-editar <?= $contingencias_confirmadas ? 'disabled' : '' ?>" 
                                            title="<?= $contingencias_confirmadas ? 'No editable - Contingencias confirmadas' : 'Editar' ?>"
                                            <?= $contingencias_confirmadas ? 'onclick="event.preventDefault(); mostrarAdvertenciaConfirmado();"' : '' ?>>
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            
                                            <!-- Botón Eliminar - Condicional -->
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
    <?php include '../../includes/footer.php'; // Incluye el pie de página ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminacion(id) {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('eliminarBtn').href = `listado_generadores_view.php?eliminar=${id}`;
            modal.show();
        }
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>