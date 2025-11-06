// select_dependiente.js - CORREGIDO
document.addEventListener('DOMContentLoaded', function() {
    const sujetoSelect = document.getElementById('id_sujeto');
    const tipoSujetoSelect = document.getElementById('tipo_sujeto');

    // Función para cargar subcategorías
    function cargarSubcategorias(idSujeto) {
        // Limpiar el select de subcategorías
        tipoSujetoSelect.innerHTML = '<option value="">Cargando...</option>';
        tipoSujetoSelect.disabled = true;
        
        if (!idSujeto) {
            tipoSujetoSelect.innerHTML = '<option value="">Primero seleccione una categoría</option>';
            tipoSujetoSelect.disabled = true;
            return;
        }

        // Hacer petición AJAX para obtener las subcategorías
        fetch('../../procesos/generador/obtener_subcategorias.php?id_sujeto=' + idSujeto)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                // Limpiar y agregar opción por defecto
                tipoSujetoSelect.innerHTML = '<option value="">Seleccione una subcategoría</option>';
                tipoSujetoSelect.disabled = false;
                
                if (data.length > 0) {
                    data.forEach(subcategoria => {
                        const option = document.createElement('option');
                        option.value = subcategoria.id;
                        option.textContent = subcategoria.nom_clase;
                        tipoSujetoSelect.appendChild(option);
                    });
                    
                    // Si estamos editando, seleccionar el valor existente
                    const selectedTipo = tipoSujetoSelect.getAttribute('data-selected');
                    if (selectedTipo) {
                        tipoSujetoSelect.value = selectedTipo;
                    }
                } else {
                    tipoSujetoSelect.innerHTML = '<option value="">No hay subcategorías disponibles para esta categoría</option>';
                    tipoSujetoSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tipoSujetoSelect.innerHTML = '<option value="">Error al cargar subcategorías</option>';
                tipoSujetoSelect.disabled = true;
            });
    }

    // Event listener para cuando cambie la categoría
    sujetoSelect.addEventListener('change', function() {
        cargarSubcategorias(this.value);
    });

    // Si hay un valor seleccionado en categoría al cargar la página, cargar sus subcategorías
    if (sujetoSelect.value) {
        cargarSubcategorias(sujetoSelect.value);
    } else {
        tipoSujetoSelect.innerHTML = '<option value="">Primero seleccione una categoría</option>';
        tipoSujetoSelect.disabled = true;
    }

    // Validar que se haya seleccionado una subcategoría antes de enviar el formulario
    document.getElementById('formGenerador').addEventListener('submit', function(e) {
        if (sujetoSelect.value && (!tipoSujetoSelect.value || tipoSujetoSelect.disabled)) {
            e.preventDefault();
            alert('Por favor, seleccione una subcategoría antes de enviar el formulario.');
            tipoSujetoSelect.focus();
        }
    });
});