<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/procesos/admin/certificado_pdf_controller.php';
require_once __DIR__ . '/procesos/admin/email_controller.php';

echo "<h1>🔧 TEST PROCESO COMPLETO</h1>";

try {
    $generador_id = 56;
    $anio = 2024;
    
    // 1. Generar PDF
    $pdfController = new CertificadoPdfController($conn);
    $resultado_pdf = $pdfController->generarCertificadoAprobacion($generador_id, $anio);
    $ruta_pdf = $resultado_pdf['ruta_completa'];
    
    echo "✅ PDF generado: $ruta_pdf<br>";
    
    // 2. Enviar email
    $emailController = new EmailController($conn);
    $email_result = $emailController->enviarCertificadoAprobacion($generador_id, $anio, $ruta_pdf);
    
    echo "📧 Email enviado: " . ($email_result ? '✅ EXITOSO' : '❌ FALLIDO') . "<br>";
    
    if ($email_result) {
        echo "🎯 ¡PROCESO COMPLETADO EXITOSAMENTE!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}
?>