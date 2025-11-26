<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

echo "<h1>🔧 TEST PDF FINAL</h1>";

try {
    // 1. Cargar conexión
    echo "<h3>1. Cargando conexión...</h3>";
    require_once __DIR__ . '/includes/conexion.php';
    echo "✅ Conexión cargada<br>";
    
    // 2. Cargar controlador PDF
    echo "<h3>2. Cargando controlador PDF...</h3>";
    require_once __DIR__ . '/procesos/admin/certificado_pdf_controller.php';
    echo "✅ Controlador PDF cargado<br>";
    
    // 3. Instanciar controlador
    echo "<h3>3. Instanciando controlador...</h3>";
    $pdfController = new CertificadoPdfController($conn);
    echo "✅ Controlador instanciado<br>";
    
    // 4. Verificar que el método existe
    echo "<h3>4. Verificando método...</h3>";
    if (method_exists($pdfController, 'generarCertificadoAprobacion')) {
        echo "✅ Método existe<br>";
    } else {
        die("❌ Método NO existe");
    }
    
    // 5. Generar PDF con un ID real
    echo "<h3>5. Generando PDF...</h3>";
    
    // Primero, obtén un generador_id real de tu BD
    $stmt = $conn->query("SELECT id FROM generador LIMIT 1");
    $generador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($generador) {
        $generador_id = $generador['id'];
        $anio = 2024;
        
        echo "📄 Generando PDF para generador_id: $generador_id, año: $anio<br>";
        
        // Llamar al método y capturar el resultado
        $resultado = $pdfController->generarCertificadoAprobacion($generador_id, $anio);
        
        // Verificar el tipo de resultado
        echo "🔍 Tipo de resultado: " . gettype($resultado) . "<br>";
        
        if (is_array($resultado)) {
            echo "📦 El método devuelve un ARRAY<br>";
            echo "🔍 Contenido del array: <pre>" . print_r($resultado, true) . "</pre>";
            
            // Extraer valores del array
            if (isset($resultado['nombre_archivo'])) {
                $nombre_pdf = $resultado['nombre_archivo'];
                echo "✅ nombre_archivo: $nombre_pdf<br>";
            } else {
                $nombre_pdf = $resultado[0] ?? 'desconocido';
                echo "⚠️ nombre_archivo (índice 0): $nombre_pdf<br>";
            }
            
            if (isset($resultado['ruta_completa'])) {
                $ruta_pdf = $resultado['ruta_completa'];
                echo "✅ ruta_completa: $ruta_pdf<br>";
            } else {
                $ruta_pdf = __DIR__ . "/procesos/admin/certificados/" . $nombre_pdf;
                echo "⚠️ ruta_completa construida: $ruta_pdf<br>";
            }
        } else {
            // El método devuelve un string
            $nombre_pdf = $resultado;
            $ruta_pdf = __DIR__ . "/procesos/admin/certificados/" . $nombre_pdf;
            echo "📄 El método devuelve un STRING: $nombre_pdf<br>";
            echo "📁 Ruta construida: $ruta_pdf<br>";
        }
        
        // 6. Verificar que el archivo se creó
        echo "<h3>6. Verificando archivo PDF...</h3>";
        
        if (file_exists($ruta_pdf)) {
            echo "✅ Archivo PDF creado exitosamente<br>";
            echo "📏 Tamaño: " . filesize($ruta_pdf) . " bytes<br>";
            echo "🔒 Permisos: " . substr(sprintf('%o', fileperms($ruta_pdf)), -4) . "<br>";
            
            // 7. Probar el método de obtención de ruta
            echo "<h3>7. Probando obtenerRutaCertificado...</h3>";
            $ruta_obtenida = $pdfController->obtenerRutaCertificado($generador_id, $anio);
            echo "🔍 Ruta obtenida del método: " . ($ruta_obtenida ? $ruta_obtenida : 'NULL') . "<br>";
            
            if ($ruta_obtenida && file_exists($ruta_obtenida)) {
                echo "✅ Método obtenerRutaCertificado funciona correctamente<br>";
            } else {
                echo "⚠️ Método obtenerRutaCertificado no encontró el archivo<br>";
            }
        } else {
            echo "❌ Archivo PDF NO se creó en: $ruta_pdf<br>";
            
            // Diagnóstico del directorio
            echo "<h4>🔍 Diagnóstico del directorio:</h4>";
            $directorio = dirname($ruta_pdf);
            echo "📁 Directorio: $directorio<br>";
            echo "¿Existe?: " . (is_dir($directorio) ? '✅ SÍ' : '❌ NO') . "<br>";
            echo "¿Es escribible?: " . (is_writable($directorio) ? '✅ SÍ' : '❌ NO') . "<br>";
            
            if (is_dir($directorio)) {
                echo "📋 Contenido del directorio: <br>";
                $archivos = scandir($directorio);
                foreach ($archivos as $archivo) {
                    if ($archivo != '.' && $archivo != '..') {
                        echo "&nbsp;&nbsp;- $archivo<br>";
                    }
                }
            }
        }
    } else {
        echo "❌ No se encontró ningún generador en la BD<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR:</h3>";
    echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>🎯 TEST COMPLETADO</h3>";
?>