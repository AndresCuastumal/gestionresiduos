<!-- Modal para selección de dirección -->
<div class="modal fade" id="modalDireccion" tabindex="-1" aria-labelledby="modalDireccionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDireccionLabel">
                    <i class="bi bi-geo-alt me-2"></i>Seleccionar Dirección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Complete la dirección según la normativa DANE para estandarizar la información.
                </div>

                <div class="row g-3">
                    <!-- Tipo de vía -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Vía <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo_via">
                            <option value="">Seleccione...</option>
                            <option value="CL">CALLE</option>
                            <option value="CR">CARRERA</option>
                            <option value="AV">AVENIDA</option>
                            <option value="DG">DIAGONAL</option>
                            <option value="TV">TRANSVERSAL</option>
                            <option value="CRV">CIRCUNVALAR</option>
                            <option value="MZ">MANZANA</option>
                            <option value="VIA">VÍA</option>                               
                            <option value="PJ">PASAJE</option>
                            <option value="GT">GLORIETA</option>                                                             
                        </select>
                    </div>

                    <!-- Número de vía -->
                    <div class="col-md-6">
                        <label class="form-label">Número <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="numero_via" placeholder="Ej: 12, 5, 23">
                    </div>
                    
                    <!-- Letras adicionales -->
                    <div class="col-md-6">
                        <label class="form-label">Letras Adicionales (opcional)</label>
                        <select class="form-select" id="letras_adicionales">
                            <option value="">Ninguna</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="BIS">BIS</option>
                            <option value="TER">TER</option>
                        </select>
                    </div>
                    
                    <!-- Orientación -->
                    <div class="col-md-6">
                        <label class="form-label">Orientación (opcional)</label>
                        <select class="form-select" id="orientacion">
                            <option value="">Seleccione...</option>
                            <option value="SUR">SUR</option>
                            <option value="NORTE">NORTE</option>
                            <option value="ESTE">ESTE</option>
                            <option value="OESTE">OESTE</option>
                        </select>
                    </div>
                    
                    <!-- Número de cuadra -->
                    <div class="col-md-6">
                        <label class="form-label">Número (opcional)</label>
                        <input type="text" class="form-control" id="numero_cuadra" placeholder="Ej: 11, 5, 45">
                    </div>

                    <!-- Tipo de complemento -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Complemento (opcional)</label>
                        <select class="form-select" id="tipo_complemento">
                            <option value="">...</option> 
                            <option value="-">GUIÓN SEPARADOR</option>                           
                            <option value="AP">APARTAMENTO</option>
                            <option value="CA">CASA</option>
                            <option value="OF">OFICINA</option>
                            <option value="LT">LOTE</option>
                            <option value="ED">EDIFICIO</option>
                            <option value="BL">BLOQUE</option>
                            <option value="TO">TORRE</option>
                            <option value="MN">MANZANA</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="BIS">BIS</option>
                            <option value="TER">TER</option>
                        </select>
                    </div>
                    
                    <!-- Número de complemento -->
                    <div class="col-md-6">
                        <label class="form-label">Número (opcional)</label>
                        <input type="text" class="form-control" id="numero_complemento" placeholder="Ej: 101, 2, A">
                    </div>   
                     <!-- Texto complemento -->
                    <div class="col-md-6">
                        <label class="form-label">Complemento (opcional)</label>
                        <input type="text" class="form-control" id="complemento" placeholder="Ej: CONDOMINIO, INTERIOR, PISO, 1103B">
                    </div>
                    
                                                      
                    <!-- Dirección completa (vista previa) -->
                    <div class="col-12">
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Vista Previa</h6>
                            </div>
                            <div class="card-body">
                                <p id="vista_previa_direccion" class="mb-0 text-muted">
                                    La dirección se mostrará aquí...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarDireccion">
                    <i class="bi bi-check-circle me-2"></i>Confirmar Dirección
                </button>
            </div>
        </div>
    </div>
</div>