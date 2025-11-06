// assets/js/main.js
class App {
    constructor() {
        this.init();
    }

    init() {
        console.log('🎯 Aplicación inicializada');
        this.setupEvents();
        this.loadComponents();
    }

    setupEvents() {
        // Eventos globales
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        window.addEventListener('resize', this.debounce(this.handleResize.bind(this), 250));
    }

    loadComponents() {
        // Cargar componentes dinámicamente
        this.loadModule('navigation');
        this.loadModule('modals');
    }

    loadModule(moduleName) {
        console.log(`📦 Cargando módulo: ${moduleName}`);
        // Aquí cargarías módulos adicionales
    }

    handleGlobalClick(e) {
        // Manejar clicks globales
        console.log('🖱️ Click global detectado', e.target);
    }

    handleResize() {
        console.log('📏 Ventana redimensionada');
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.MyApp = new App();
});

// Funciones globales adicionales
function showAlert(message, type = 'info') {
    console.log(`💡 ${type.toUpperCase()}: ${message}`);
}

// Exportar para uso global
window.showAlert = showAlert;