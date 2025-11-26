<?php
// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔧 TEST PDF SIMPLE</h1>";

try {
    // Test 1: Conexión a BD
    echo "<h3>1. Probando conexión a BD...</h3>";
    require_once 'includes/conexion.php';
    
    if (isset($conn)) {
        echo "✅ Conexión a BD establecida<br>";
        
        // Test 2: Incluir controlador
        echo "<h3>2. Cargando controlador PDF...</h3>";
        require_once 'procesos/admin/certificado_pdf_controller.php';
        echo "✅ Controlador PDF cargado<br>";
        
        // Test 3: Instanciar controlador
        echo "<h3>3. Instanciando controlador...</h3>";
        $pdfController = new CertificadoPdfController($conn);
        echo "✅ Controlador instanciado<br>";
        
        // Test 4: Verificar método existe
        echo "<h3>4. Verificando método...</h3>";
        if (method_exists($pdfController, 'generarCertificadoAprobacion')) {
            echo "✅ Método generarCertificadoAprobacion existe<br>";
        } else {
            echo "❌ Método NO existe<br>";
        }
        
    } else {
        echo "❌ No se pudo conectar a BD<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR CAPTURADO:</h3>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>✅ TEST COMPLETADO</h3>";
?>