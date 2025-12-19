<?php
// ======================================================
// SOLUCIÓN DEFINITIVA - CARGA GARANTIZADA
// ======================================================

// Log inicial
error_log("=== [PDF_CONTROLLER] INICIANDO ===");

// 1. CARGAR MASTERMINDS/HTML5 PRIMERO - ES CRÍTICO
error_log("[PDF_CONTROLLER] Cargando Masterminds/HTML5...");

$html5_loaded = false;
$html5_paths = [
    '/var/www/html/gestionresiduos/vendor/masterminds/html5/src/HTML5.php',
    __DIR__ . '/../../vendor/masterminds/html5/src/HTML5.php',
    dirname(dirname(dirname(__FILE__))) . '/vendor/masterminds/html5/src/HTML5.php',
];

foreach ($html5_paths as $path) {
    if (file_exists($path)) {
        error_log("[PDF_CONTROLLER] Encontrado en: $path");
        
        // Cargar archivo principal
        require_once $path;
        
        // Cargar dependencias críticas
        $html5_dir = dirname($path);
        $deps = [
            'HTML5/Parser.php',
            'HTML5/Serializer.php', 
            'HTML5/Exception.php',
            'HTML5/Elements.php'
        ];
        
        foreach ($deps as $dep) {
            $dep_path = $html5_dir . '/' . $dep;
            if (file_exists($dep_path)) {
                require_once $dep_path;
            }
        }
        
        $html5_loaded = true;
        break;
    }
}

if (!$html5_loaded) {
    error_log("[PDF_CONTROLLER] ERROR: Masterminds/HTML5 NO encontrado");
    throw new Exception("ERROR CRÍTICO: No se pudo cargar Masterminds/HTML5");
}

// Verificar que la clase existe
if (!class_exists('Masterminds\HTML5')) {
    error_log("[PDF_CONTROLLER] ERROR: Clase Masterminds\HTML5 no existe");
    throw new Exception("Clase Masterminds\HTML5 no disponible");
}

error_log("[PDF_CONTROLLER] ✓ Masterminds/HTML5 cargado");

// 2. Cargar autoloader de Composer (para otras librerías)
error_log("[PDF_CONTROLLER] Cargando autoloader...");
$autoloader_paths = [
    '/var/www/html/gestionresiduos/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloader_loaded = false;
foreach ($autoloader_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloader_loaded = true;
        error_log("[PDF_CONTROLLER] Autoloader cargado: $path");
        break;
    }
}

if (!$autoloader_loaded) {
    error_log("[PDF_CONTROLLER] WARNING: Autoloader no encontrado");
}

// 3. Cargar Dompdf manualmente si es necesario
if (!class_exists('Dompdf\Dompdf')) {
    error_log("[PDF_CONTROLLER] Cargando Dompdf manualmente...");
    $dompdf_paths = [
        '/var/www/html/gestionresiduos/vendor/dompdf/dompdf/src/Dompdf.php',
        __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php',
    ];
    
    foreach ($dompdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            require_once dirname($path) . '/Options.php';
            error_log("[PDF_CONTROLLER] Dompdf cargado: $path");
            break;
        }
    }
}

// 4. VERIFICACIÓN FINAL
error_log("[PDF_CONTROLLER] Verificando clases...");
if (!class_exists('Masterminds\HTML5')) {
    throw new Exception("Masterminds\HTML5 no disponible después de carga");
}
if (!class_exists('Dompdf\Dompdf')) {
    throw new Exception("Dompdf no disponible después de carga");
}
if (!class_exists('Dompdf\Options')) {
    throw new Exception("Dompdf\Options no disponible");
}

error_log("[PDF_CONTROLLER] ✓ Todas las clases verificadas");

// ======================================================
// AHORA EL RESTO DEL CÓDIGO ORIGINAL
// ======================================================

require_once __DIR__ . '/../../includes/conexion.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class CertificadoPdfController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Generar certificado PDF real para generador aprobado
    public function generarCertificadoAprobacion($generador_id, $anio) {
        error_log("Generando certificado PDF real para generador_id: $generador_id, año: $anio");
        
        try {
            // Obtener datos del generador
            $stmt = $this->conn->prepare("
                SELECT 
                    g.id, g.nom_generador, g.nit, g.dir_establecimiento, g.nom_responsable,
                    ts.nom_clase as nom_tipo, 
                    r.fecha_revision
                FROM generador g
                JOIN subcategoria ts ON g.tipo_sujeto = ts.id
                JOIN revisiones_anuales r ON g.id = r.generador_id
                WHERE g.id = ? AND r.anio = ?
            ");
            
            $stmt->execute([$generador_id, $anio]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos) {
                throw new Exception("No se encontraron datos para el generador ID: $generador_id, año: $anio");
            }
            
            // Crear contenido HTML del certificado
            $html = $this->generarHtmlCertificado($datos, $anio);
            
            // Configurar DomPDF
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            $dompdf = new Dompdf($options);
            
            // Cargar HTML
            $dompdf->loadHtml($html);
            
            // Configurar papel
            $dompdf->setPaper('A4', 'portrait');
            
            // Renderizar PDF
            $dompdf->render();
            
            // Obtener contenido del PDF
            $pdfContent = $dompdf->output();
            
            // Guardar PDF en sistema de archivos (opcional)
            $pdfPath = $this->guardarPdf($pdfContent, $generador_id, $anio);
            
            error_log("PDF generado exitosamente. Ruta: $pdfPath");
            
            return [
                'pdf_content' => $pdfContent,
                'pdf_path' => $pdfPath,
                'nombre_archivo' => "certificado_aprobacion_{$generador_id}_{$anio}.pdf"
            ];
            
        } catch (Exception $e) {
            error_log("Error al generar certificado PDF: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function generarHtmlCertificado($datos, $anio) {
        $fecha_actual = date('d/m/Y');
        $fecha_revision = date('d/m/Y', strtotime($datos['fecha_revision']));
        
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Certificado de Aprobación</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 40px; }
                .header h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
                .content { line-height: 1.6; }
                .firma { margin-top: 60px; text-align: center; }
                .sello { text-align: right; margin-top: 30px; font-style: italic; color: #7f8c8d; }
                .datos-generador { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #3498db; color: white; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>CERTIFICADO DE APROBACIÓN</h1>
                <h3>Sistema de Gestión de Residuos Peligrosos</h3>
                <p>Año de reporte: {$anio}</p>
            </div>
            
            <div class="content">
                <p>Se certifica que el generador:</p>
                
                <div class="datos-generador">
                    <h3>{$datos['nom_generador']}</h3>
                    <p><strong>NIT:</strong> {$datos['nit']}</p>
                    <p><strong>Dirección:</strong> {$datos['dir_establecimiento']}</p>
                    <p><strong>Responsable:</strong> {$datos['nom_responsable']}</p>
                    <p><strong>Tipo de sujeto:</strong> {$datos['nom_tipo']}</p>
                </div>
                
                <p>Ha cumplido satisfactoriamente con todos los requisitos establecidos para la gestión de residuos peligrosos 
                durante el año {$anio}, de acuerdo a la normativa vigente.</p>
                
                <table>
                    <tr>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th>Fecha de Revisión</th>
                    </tr>
                    <tr>
                        <td>Reporte Mensual de Residuos</td>
                        <td>APROBADO</td>
                        <td>{$fecha_revision}</td>
                    </tr>
                    <tr>
                        <td>Reporte de Accidentes/Incidentes</td>
                        <td>APROBADO</td>
                        <td>{$fecha_revision}</td>
                    </tr>
                    <tr>
                        <td>Reporte de Contingencias</td>
                        <td>APROBADO</td>
                        <td>{$fecha_revision}</td>
                    </tr>
                </table>
                
                <p>El presente certificado es válido por un año a partir de la fecha de emisión y 
                confirma que el generador se encuentra en cumplimiento de los estándares establecidos.</p>
            </div>
            
            <div class="firma">
                <p>_________________________</p>
                <p><strong>Autoridad Competente</strong></p>
                <p>Sistema de Gestión de Residuos Peligrosos</p>
            </div>
            
            <div class="sello">
                <p>Certificado emitido el: {$fecha_actual}</p>
                <p>Código de verificación: SG-{$datos['id']}-{$anio}-" . strtoupper(uniqid()) . "</p>
            </div>
        </body>
        </html>
        HTML;
        
        return $html;
    }
    
    private function guardarPdf($pdfContent, $generador_id, $anio) {
        // Crear directorio si no existe
        $uploadDir = '/var/www/html/gestionresiduos/uploads/certificados/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Nombre del archivo
        $filename = "certificado_{$generador_id}_{$anio}_" . time() . ".pdf";
        $filepath = $uploadDir . $filename;
        
        // Guardar archivo
        if (file_put_contents($filepath, $pdfContent)) {
            error_log("PDF guardado en: $filepath");
            return $filepath;
        } else {
            error_log("Error al guardar PDF en: $filepath");
            return null;
        }
    }
    
    // Método para enviar certificado por email
    public function enviarCertificadoPorEmail($generador_id, $anio, $destinatario, $asunto = null) {
        error_log("Enviando certificado por email a: $destinatario");
        
        try {
            // Generar PDF
            $pdfData = $this->generarCertificadoAprobacion($generador_id, $anio);
            
            // Aquí iría la lógica para enviar el email con PHPMailer
            // Esta función debería integrarse con tu sistema de email existente
            
            return [
                'success' => true,
                'message' => 'Certificado generado exitosamente',
                'pdf_path' => $pdfData['pdf_path'],
                'nombre_archivo' => $pdfData['nombre_archivo']
            ];
            
        } catch (Exception $e) {
            error_log("Error al enviar certificado por email: " . $e->getMessage());
            throw $e;
        }
    }
}
?>