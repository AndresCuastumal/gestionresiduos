/**
 * Validación de formularios con contraseña
 * Reutilizable para registro y reset de contraseña
 */

function validarContrasenas(passwordId, confirmPasswordId, errorElementId, minLength = 6) {
    const password = document.getElementById(passwordId).value;
    const confirmPassword = document.getElementById(confirmPasswordId).value;
    const mensajeError = document.getElementById(errorElementId);
    
    // Validar longitud mínima
    if (password.length < minLength) {
        mensajeError.textContent = `La contraseña debe tener al menos ${minLength} caracteres`;
        return false;
    }
    
    // Validar coincidencia
    if (password !== confirmPassword) {
        mensajeError.textContent = "Las contraseñas no coinciden";
        return false;
    }
    
    mensajeError.textContent = "";
    return true;
}

// Configuración para formulario de registro
function configurarValidacionRegistro() {
    const form = document.getElementById('registroForm');
    if (!form) return;
    
    document.getElementById('confirm_password').addEventListener('input', function() {
        validarContrasenas('password', 'confirm_password', 'mensajeError');
    });
    
    form.onsubmit = function() {
        return validarContrasenas('password', 'confirm_password', 'mensajeError');
    };
}

// Configuración para formulario de reset
function configurarValidacionReset() {
    const form = document.getElementById('resetForm');
    if (!form) return;
    
    document.getElementById('confirm_password').addEventListener('input', function() {
        validarContrasenas('password', 'confirm_password', 'passwordError');
    });
    
    form.onsubmit = function() {
        return validarContrasenas('password', 'confirm_password', 'passwordError');
    };
}

// Inicialización automática según la página
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('registroForm')) {
        configurarValidacionRegistro();
    }
    
    if (document.getElementById('resetForm')) {
        configurarValidacionReset();
    }
});