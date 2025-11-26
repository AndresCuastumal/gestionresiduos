<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/procesos/admin/certificado_pdf_controller.php';

echo "<h1>🔧 TEST GENERACIÓN PDF</h1>";

try {
    $pdfController = new CertificadoPdfController($conn);
    
    // Usa un generador_id real de tu base de datos
    $generador_id = 1; // Cambia por uno que exista
    $anio = 2024;
    
    echo "📄 Generando PDF para generador_id: $generador_id, año: $anio<br>";
    
    $resultado = $pdfController->generarCertificadoAprobacion($generador_id, $anio);
    
    echo "✅ Resultado: " . print_r($resultado, true) . "<br>";
    
    // Verificar si el archivo se creó
    if (is_string($resultado)) {
        $ruta_pdf = __DIR__ . "/procesos/admin/certificados/" . $resultado;
        echo "📁 Ruta completa: $ruta_pdf<br>";
        echo "📊 ¿Archivo existe?: " . (file_exists($ruta_pdf) ? '✅ SÍ' : '❌ NO') . "<br>";
        
        if (file_exists($ruta_pdf)) {
            echo "📏 Tamaño: " . filesize($ruta_pdf) . " bytes<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}
?>