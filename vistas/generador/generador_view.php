<?php
require_once '../../procesos/generador/generador_controller.php';

// Verificar si estamos editando (si viene un ID por GET)
$generadorExistente = [];
if (isset($_GET['id'])) {
    $idGenerador = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($idGenerador) {
        $generadorExistente = $controller->obtenerGeneradorPorId($idGenerador);
        
        // Si no encontramos el generador, redirigir
        if (empty($generadorExistente)) {
            $_SESSION['mensaje_error'] = "El generador solicitado no existe";
            header("Location: listado_generadores_view.php");
            exit();
        }
    }
}

// Obtener lista de barrios
$barrios = $controller->obtenerBarrios();
// Obtener categorías (sujetos)
$sujetos = $controller->getTiposGenerador();
// Obtener subcategorías si estamos editando
$subcategorias = [];
if (isset($generadorExistente['sujeto'])) {
    $subcategorias = $controller->getSubcategoriasPorSujeto($generadorExistente['id_sujeto']);
}

include '../../includes/header.php';
?>
    <!-- Contenedor principal -->
    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listado_generadores_view.php">Mis Establecimientos</a></li>
                <li class="breadcrumb-item active"><?= isset($generadorExistente['id']) ? 'Editar' : 'Nuevo' ?> Establecimiento</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building me-2"></i><?= isset($generadorExistente['id']) ? 'Editar' : 'Registrar' ?> Establecimiento</h2>
            <a href="<?= isset($generadorExistente['id']) ? 'listado_generadores_view.php' : '../dashboard.php' ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Cancelar
            </a>
        </div>

        <!-- Tarjeta informativa -->
        <div class="card mb-4" style="background-color: #f8f4ceff;">
            <div class="card-body">
                <p class="card-text" style="text-align: justify; text-justify: inter-word;">
                    Complete la información del establecimiento generador de residuos en atención en salud y otras actividades. 
                    Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Establecimiento</h5>
            </div>
            <div class="card-body">
                <?php if (isset($controller->error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($controller->error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: '<?= addslashes($_SESSION['mensaje_exito']) ?>',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'listado_generadores_view.php';
                            }
                        });
                    });
                    </script>
                    <?php unset($_SESSION['mensaje_exito']); ?>
                <?php endif; ?>

                <form method="POST" id="formGenerador">
                    <!-- Campo oculto para ID si estamos editando -->
                    <?php if (isset($generadorExistente['id'])): ?>
                        <input type="hidden" name="id_generador" value="<?= htmlspecialchars($generadorExistente['id']) ?>">
                    <?php endif; ?>

                    <!-- Campo oculto para la dirección estandarizada -->
                    <input type="hidden" id="dir_establecimiento" name="dir_establecimiento" 
                           value="<?= htmlspecialchars($generadorExistente['dir_establecimiento'] ?? '') ?>">

                    <!-- Sección 1: Datos del Establecimiento -->
                    <div class="mb-4">
                        <h6 class="text-muted border-bottom pb-2 mb-3">Datos del Establecimiento</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom_generador" class="form-label">Nombre comercial <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom_generador" name="nom_generador" 
                                       value="<?= htmlspecialchars($generadorExistente['nom_generador'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="razon_social" class="form-label">Razon Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="razon_social" name="razon_social" 
                                       value="<?= htmlspecialchars($generadorExistente['razon_social'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nit" class="form-label">NIT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nit" name="nit" 
                                       value="<?= htmlspecialchars($generadorExistente['nit'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tel_establecimiento" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="tel_establecimiento" name="tel_establecimiento" required
                                       value="<?= htmlspecialchars($generadorExistente['tel_establecimiento'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="dir_mostrar" 
                                           value="<?= htmlspecialchars($generadorExistente['dir_establecimiento'] ?? '') ?>" 
                                           placeholder="Seleccione la dirección" readonly
                                           style="cursor: pointer; background-color: #f8f9fa;">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDireccion">
                                        <i class="bi bi-geo-alt me-1"></i>Seleccionar
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> Haga clic en "Seleccionar" para estandarizar la dirección
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="barrio" class="form-label">Barrio <span class="text-danger">*</span></label>
                                <select class="form-select" id="barrio" name="id_comuna" required>
                                    <option value="">Seleccione un barrio...</option>
                                    <?php 
                                    $selectedBarrio = $_POST['id_comuna'] ?? ($generadorExistente['id_comuna'] ?? '');
                                    foreach ($barrios as $barrio): 
                                        $selected = ($selectedBarrio == $barrio['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($barrio['id']) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($barrio['nom_barrio']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> Seleccione el barrio donde se encuentra el establecimiento
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                            <label for="id_sujeto" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_sujeto" name="id_sujeto" required>
                                <option value="">Seleccione...</option>
                                <?php 
                                $selectedSujeto = $_POST['id_sujeto'] ?? ($generadorExistente['id_sujeto'] ?? ''); // Cambiado de 'sujeto' a 'id_sujeto'
                                foreach ($sujetos as $sujeto): 
                                    $selected = ($selectedSujeto == $sujeto['id_sujeto']) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($sujeto['id_sujeto']) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($sujeto['nom_sujeto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_sujeto" class="form-label">Tipo/Subcategoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_sujeto" name="tipo_sujeto" 
                                    data-selected="<?= htmlspecialchars($generadorExistente['tipo_sujeto'] ?? '') ?>" required>
                                <option value="">Primero seleccione una categoría</option>
                                <?php 
                                $selectedTipo = $_POST['tipo_sujeto'] ?? ($generadorExistente['tipo_sujeto'] ?? '');
                                foreach ($subcategorias as $subcategoria): 
                                    $selected = ($selectedTipo == $subcategoria['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($subcategoria['id']) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($subcategoria['nom_clase']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <!-- Después de los selects, antes de cerrar la sección -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-light d-flex align-items-center mb-0" role="alert" style="background-color: #e0ebeb;">
                                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                                    <div class="flex-grow-1">
                                        <small>¿No está seguro de qué categoría o subcategoría seleccionar?</small>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="abrirCatalogo()">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Ver Catálogo PDF en nueva pestaña
                                    </button>
                                </div>
                            </div>
                        </div>                           
                    </div>                
                    <!-- Sección 2: Datos del Responsable -->
                    <div class="mb-4">
                        <h6 class="text-muted border-bottom pb-2 mb-3">Datos del Responsable</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom_responsable" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom_responsable" name="nom_responsable" 
                                       value="<?= htmlspecialchars($generadorExistente['nom_responsable'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cargo_responsable" class="form-label">Cargo</label>
                                <input type="text" class="form-control" id="cargo_responsable" name="cargo_responsable"
                                       value="<?= htmlspecialchars($generadorExistente['cargo_responsable'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="periodo_reporte" class="form-label">Fecha de reporte <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="date" class="form-control bg-light text-muted" id="periodo_reporte" name="periodo_reporte" 
                                        value="<?= htmlspecialchars($generadorExistente['periodo_reporte'] ?? date('Y-m-d')) ?>" readonly
                                        style="cursor: not-allowed; opacity: 0.8;">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-lock text-muted"></i>
                                    </span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> Campo de solo lectura
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= isset($generadorExistente['id']) ? 'listado_generadores_view.php' : '../dashboard.php' ?>" class="btn btn-outline btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Cancelar
                        </a>
                        <div>
                            <?php if (isset($generadorExistente['id'])): ?>
                                <a href="reporte_mensual_view.php?id=<?= $generadorExistente['id'] ?>" class="btn btn-outline btn-outline-success me-2">
                                    <i class="bi bi-clipboard-data me-2"></i>Ver Reportes
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-outline btn-outline-primary">
                                <i class="bi bi-check-circle me-2"></i><?= isset($generadorExistente['id']) ? 'Actualizar' : 'Guardar' ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal para selección de dirección -->
    <?php include '../../includes/direccion.php'; ?>

    <!-- js para el select dependiente -->
    <script src="../../assets/js/select_dependiente.js"></script>

    <!-- js para manejar el formulario y la dirección -->
    <script src="../../assets/js/mostrar_direccion.js"></script>

    <!-- js para abrir el catálogo -->
    <script>
        function abrirCatalogo() {
            window.open('../../includes/sujetos_clases.pdf', '_blank');
        }
    </script>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"></script>