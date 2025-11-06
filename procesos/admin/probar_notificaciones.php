<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';

// Tu conexión a la base de datos
$conn = // ... tu conexión aquí;

$controller = new RevisionesController($conn);

// Usar el generador_id y año que viste en el log
$generador_id = 41;
$anio = 2024;

echo "<h1>Probando notificaciones para generador $generador_id, año $anio</h1>";

try {
    // Forzar el envío de notificaciones
    $controller->enviarNotificaciones($generador_id, $anio);
    echo "<p style='color: green;'>✅ Notificaciones ejecutadas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p>Revisa el archivo debug.log para ver los detalles.</p>";
?>