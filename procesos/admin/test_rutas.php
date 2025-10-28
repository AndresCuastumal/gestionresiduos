<?php
error_log("=== TEST RUTAS ===");
error_log("DIR: " . __DIR__);

$rutas = [
    'certificado_pdf_controller.php',
    __DIR__ . '/certificado_pdf_controller.php', 
    __DIR__ . '/../../procesos/admin/certificado_pdf_controller.php'
];

foreach ($rutas as $ruta) {
    error_log("$ruta: " . (file_exists($ruta) ? 'EXISTE' : 'NO EXISTE'));
}
?>