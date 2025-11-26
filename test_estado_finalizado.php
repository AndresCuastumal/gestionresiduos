<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/procesos/admin/revisiones_controller.php';

$controller = new RevisionesController($conn);

// Usa un generador_id que sepas que está finalizado
$generador_id = 56; // Cambia por uno que esté finalizado
$anio = 2024;

echo "<h1>🔍 ESTADO DE REVISIÓN</h1>";

// Verificar estado en la base de datos directamente
$stmt = $conn->prepare("
    SELECT estado_finalizado, fecha_finalizacion, certificado_generado, certificado_pdf,
           formulario_mensual, formulario_accidentes, formulario_contingencias
    FROM revisiones_anuales 
    WHERE generador_id = ? AND anio = ?
");
$stmt->execute([$generador_id, $anio]);
$revision = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>📊 Datos de la revisión en BD:</h3>";
echo "<pre>" . print_r($revision, true) . "</pre>";

echo "<h3>🔒 Verificaciones:</h3>";
echo "¿Está finalizado?: " . ($controller->estaFinalizado($generador_id, $anio) ? '✅ SÍ' : '❌ NO') . "<br>";

// Verificar si puede acceder a los métodos
echo "<h3>📋 Probando métodos:</h3>";
try {
    $revision_data = $controller->obtenerRevision($generador_id, $anio);
    echo "✅ obtenerRevision(): FUNCIONA<br>";
} catch (Exception $e) {
    echo "❌ obtenerRevision(): " . $e->getMessage() . "<br>";
}

try {
    $estados = $controller->obtenerEstadoFormularios($generador_id, $anio);
    echo "✅ obtenerEstadoFormularios(): FUNCIONA<br>";
} catch (Exception $e) {
    echo "❌ obtenerEstadoFormularios(): " . $e->getMessage() . "<br>";
}
?>