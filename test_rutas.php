<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 TEST DE RUTAS</h1>";

$rutas = [
    'includes/conexion.php' => __DIR__ . '/includes/conexion.php',
    'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
    'procesos/admin/certificado_pdf_controller.php' => __DIR__ . '/procesos/admin/certificado_pdf_controller.php',
    'procesos/admin/certificados/' => __DIR__ . '/procesos/admin/certificados/'
];

foreach ($rutas as $nombre => $ruta) {
    echo "<h3>📁 $nombre</h3>";
    echo "Ruta: $ruta<br>";
    echo "¿Existe?: " . (file_exists($ruta) ? '✅ SÍ' : '❌ NO') . "<br>";
    
    if (is_dir($ruta)) {
        echo "¿Es directorio?: ✅ SÍ<br>";
        echo "¿Es escribible?: " . (is_writable($ruta) ? '✅ SÍ' : '❌ NO') . "<br>";
    }
    
    echo "<br>";
}

echo "<h3>🎯 ESTRUCTURA DE DIRECTORIO:</h3>";
echo "<pre>";
system("find " . __DIR__ . " -type f -name '*.php' | head -15");
echo "</pre>";
?>