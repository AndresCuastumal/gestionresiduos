<?php
// pruebas/probar_cola.php - Para ejecutar manualmente
echo "<h2>Procesador de Cola de Correos</h2>";
echo "<p>Ejecutando en: " . date('Y-m-d H:i:s') . "</p>";

// Incluir y ejecutar el procesador
require_once '../cron/procesar_cola.php';

echo "<hr>";
echo "<h3>Estado actual de la cola:</h3>";

// Mostrar estado
require_once 'includes/conexion.php';

$sql = "SELECT 
    status,
    COUNT(*) as cantidad,
    SUM(attempts) as intentos_totales
FROM email_queue 
GROUP BY status";

$resultados = $conn->query($sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Estado</th><th>Cantidad</th><th>Intentos</th></tr>";
while ($row = $resultados->fetch()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['cantidad'] . "</td>";
    echo "<td>" . $row['intentos_totales'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='probar_cola.php'>Ejecutar nuevamente</a></p>";
?>