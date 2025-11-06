document.addEventListener('DOMContentLoaded', function() {
    // Mapeo de códigos a nombres completos
    const mapeoTiposVia = {
        'CL': 'CALLE',
        'CR': 'CARRERA',
        'AV': 'AVENIDA',
        'DG': 'DIAGONAL',
        'TV': 'TRANSVERSAL',
        'CRV': 'CIRCUNVALAR',
        'MZ': 'MANZANA',
        'VIA': 'VÍA',
        'PJ': 'PASAJE',
        'GT': 'GLORIETA'
    };

    const mapeoComplementos = {
        'AP': 'APARTAMENTO',
        '-': ' - ',
        'CA': 'CASA',
        'OF': 'OFICINA',
        'LT': 'LOTE',
        'ED': 'EDIFICIO',
        'BL': 'BLOQUE',
        'TO': 'TORRE',
        'MN': 'MANZANA',
        'A': 'A',
        'B': 'B',
        'C': 'C',
        'D': 'D',
        'E': 'E',
        'F': 'F',
        'BIS': 'BIS',
        'TER': 'TER'
    };

    // Elementos del modal
    const modal = new bootstrap.Modal(document.getElementById('modalDireccion'));
    const btnConfirmar = document.getElementById('btnConfirmarDireccion');
    const vistaPrevia = document.getElementById('vista_previa_direccion');
    
    // Campos del formulario de dirección
    const campos = [
        'tipo_via', 'numero_via', 'letras_adicionales', 'orientacion',
        'numero_cuadra', 'tipo_complemento', 'numero_complemento', 'complemento',
        'barrio'
    ];

    // Función para generar la vista previa
    function actualizarVistaPrevia() {
        const valores = {};
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                valores[campo] = elemento.value.trim();
            } else {
                console.error('Elemento no encontrado:', campo);
                valores[campo] = '';
            }
        });

        let direccion = '';
        
        // Construir la dirección principal
        if (valores.tipo_via && valores.numero_via) {
            const tipoViaCompleto = mapeoTiposVia[valores.tipo_via] || valores.tipo_via;
            direccion = `${tipoViaCompleto} ${valores.numero_via}`;
            
            if (valores.letras_adicionales) {
                direccion += ` ${valores.letras_adicionales}`;
            }
            
            if (valores.orientacion) {
                direccion += ` ${valores.orientacion}`;
            }
            
            if (valores.numero_cuadra) {
                direccion += ` # ${valores.numero_cuadra}`;
            }
        }

        // Agregar complemento estructurado si existe
        if (valores.tipo_complemento) {
            if (direccion) direccion += '';
            const complementoCompleto = mapeoComplementos[valores.tipo_complemento] || valores.tipo_complemento;
            direccion += `${complementoCompleto}`;
            if (valores.numero_complemento) {
                direccion += ` ${valores.numero_complemento}`;
            }
        }

        // Agregar complemento de texto libre si existe (NUEVO CÓDIGO)
        if (valores.complemento) {
            if (direccion) {
                // Si ya hay contenido, agregar separador
                if (valores.tipo_complemento || valores.numero_complemento) {
                    direccion += ' ';
                } else {
                    direccion += ' ';
                }
            }
            direccion += valores.complemento;
        }

        // Agregar barrio si existe
        if (valores.barrio) {
            if (direccion) direccion += ', ';
            direccion += `Barrio ${valores.barrio}`;
        }       

        vistaPrevia.textContent = direccion || 'La dirección se mostrará aquí...';
        vistaPrevia.className = direccion ? 'mb-0' : 'mb-0 text-muted';
        
        return direccion;
    }

    // Actualizar vista previa cuando cambien los campos
    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.addEventListener('input', actualizarVistaPrevia);
            elemento.addEventListener('change', actualizarVistaPrevia);
        } else {
            console.error('No se pudo encontrar el elemento:', campo);
        }
    });

    // Confirmar dirección
    btnConfirmar.addEventListener('click', function() {
        const direccionCompleta = actualizarVistaPrevia();
        
        if (!document.getElementById('tipo_via').value || !document.getElementById('numero_via').value) {
            alert('Por favor complete al menos el tipo de vía y el número.');
            return;
        }

        // Actualizar campos en el formulario principal
        document.getElementById('dir_mostrar').value = direccionCompleta;
        document.getElementById('dir_establecimiento').value = direccionCompleta;

        // Cerrar el modal
        modal.hide();
    });

    // Si hay una dirección existente, intentar parsearla al abrir el modal
    document.getElementById('modalDireccion').addEventListener('show.bs.modal', function() {
        const dirActual = document.getElementById('dir_mostrar').value;
        if (dirActual) {
            // Limpiar todos los campos primero
            campos.forEach(campo => {
                const elemento = document.getElementById(campo);
                if (elemento && elemento.type !== 'hidden') {
                    elemento.value = '';
                }
            });
            
            // Intentar parsear la dirección existente
            const partes = dirActual.split(' ');
            if (partes.length >= 2) {
                // Buscar el tipo de vía en el mapeo inverso
                const tipoVia = partes[0];
                const codigoVia = Object.keys(mapeoTiposVia).find(key => 
                    mapeoTiposVia[key] === tipoVia
                ) || tipoVia;
                
                document.getElementById('tipo_via').value = codigoVia;
                document.getElementById('numero_via').value = partes[1];
            }
        }
        actualizarVistaPrevia();
    });

    // Inicializar vista previa
    actualizarVistaPrevia();
});