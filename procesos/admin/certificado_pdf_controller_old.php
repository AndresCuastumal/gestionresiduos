<?php
require_once __DIR__ . '/../../includes/conexion.php';

// Incluir DomPDF via Composer
require_once __DIR__ . '/../../vendor/autoload.php'; // Ruta corregida

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
            $generador = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$generador) {
                throw new Exception("No se encontraron datos del generador");
            }
            
            // Configurar DomPDF - CAMBIO 1: Desactivar HTML5 Parser
            $options = new Options();
            $options->set('isHtml5ParserEnabled', false); // DESACTIVADO
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'helvetica');
            $options->set('isPhpEnabled', true);
            $options->set('isFontSubsettingEnabled', true); // IMPORTANTE: Reduce tamaño de fuentes
            $options->set('dpi', 96); // Reducir DPI para menos detalle pero más compacto
            $dompdf = new Dompdf($options);
            
            // Crear contenido HTML para el PDF
            $html = $this->generarHtmlCertificado($generador, $anio);
            
            // Cargar HTML en DomPDF
            $dompdf->loadHtml($html, 'UTF-8');
            
            // Configurar papel y orientación
            $dompdf->setPaper('A4', 'portrait');
            
            // Renderizar PDF
            $dompdf->render();
            
            // Crear directorio si no existe
            $directorio = __DIR__ . "/certificados/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
                error_log("Directorio creado: $directorio");
            }
            
            // Verificar permisos de escritura
            if (!is_writable($directorio)) {
                throw new Exception("El directorio $directorio no tiene permisos de escritura");
            }
            
            // Nombre del archivo
            $nombre_archivo = "certificado_aprobacion_{$generador_id}_{$anio}.pdf";
            $ruta_archivo = $directorio . $nombre_archivo;
            
            // Guardar PDF en archivo
            $output = $dompdf->output();
            $resultado = file_put_contents($ruta_archivo, $output);
            
            if ($resultado === false) {
                throw new Exception("Error al guardar el archivo PDF");
            }
            
            // Verificar que el archivo se creó
            if (!file_exists($ruta_archivo)) {
                throw new Exception("El archivo PDF no se creó: $ruta_archivo");
            }
            
            $tamano = filesize($ruta_archivo);
            error_log("✅ Certificado PDF generado exitosamente: $ruta_archivo ($tamano bytes)");
            
            // CAMBIO 2: Agregar contenido PDF al retorno
            return [
                'nombre_archivo' => $nombre_archivo,
                'ruta_completa' => $ruta_archivo,
                'contenido_pdf' => $output  // Para adjuntar al email
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error en generación de PDF: " . $e->getMessage());
            throw $e; // Relanzar la excepción
        }
    }
    
    private function generarHtmlCertificado($generador, $anio) {
        $fecha_actual = date('d/m/Y');
        $fecha_revision = $generador['fecha_revision'] ? date('d/m/Y', strtotime($generador['fecha_revision'])) : $fecha_actual;
        
        // Escapar todos los datos para seguridad
        $nom_generador = htmlspecialchars($generador['nom_generador'] ?? '');
        $nit = htmlspecialchars($generador['nit'] ?? '');
        $direccion = htmlspecialchars($generador['dir_establecimiento'] ?? '');
        $tipo_sujeto = htmlspecialchars($generador['nom_tipo'] ?? '');
        $responsable = htmlspecialchars($generador['nom_responsable'] ?? '');
        
        // Obtener la imagen en base64
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/gestionresiduos/assets/css/logoNuevoSMS2024.png';
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        } else {
            $logoBase64 = '';
            error_log("⚠️ Advertencia: No se encontró la imagen en: $logoPath");
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Certificado de Aprobación</title>
            <style>
                @page {
                    margin: 20mm 15mm;
                }
                body { 
                    font-family: 'DejaVu Sans', Arial, sans-serif; 
                    margin: 0;
                    padding: 0;
                    color: #333;
                    line-height: 1.3;
                    font-size: 11px;
                }
                .certificado {                    
                    padding: 10px 15px;
                    width: 100%;
                    box-sizing: border-box;
                }
                .header {
                    margin-bottom: 15px;
                    text-align: center;
                }
                .logo-container {
                    margin-bottom: 10px;
                }
                .logo-img {
                    max-height: 70px;
                    width: auto;
                    display: block;
                    margin: 0 auto;
                }
                .header h1 {
                    color: #af4c4cff;
                    font-size: 16px;
                    margin: 8px 0;
                    text-transform: uppercase;
                    line-height: 1.2;
                }
                .header h2 {
                    color: #666;
                    font-size: 12px;
                    font-weight: normal;
                    margin: 5px 0;
                    line-height: 1.2;
                }
                .texto-centrado {
                    text-align: center;
                    margin: 12px 0;
                    font-size: 11px;
                }
                .datos-generador {
                    background-color: #f9f9f9;
                    padding: 12px;
                    margin: 12px 0;
                    border-left: 3px solid #af784cff;
                    border-radius: 4px;
                    font-size: 10.5px;
                }
                .datos-generador p {
                    margin: 5px 0;
                }
                .content {
                    margin: 15px 0;
                    text-align: justify;
                    font-size: 11px;
                }
                .content p {
                    margin: 8px 0;
                }
                .footer {
                    margin-top: 20px;
                    padding-top: 15px;
                    border-top: 1px solid #ddd;
                    font-size: 10px;
                    text-align: center;
                }
                .firma-area {
                    margin-top: 25px;
                    text-align: center;
                    font-size: 10px;
                }
                .linea-firma {
                    width: 250px;
                    border-top: 1px solid #000;
                    margin: 40px auto 5px auto;
                    display: block;
                }
                .sello {
                    color: #0b8f07ff;
                    font-weight: bold;
                    font-size: 10px;
                    margin-top: 15px;
                    border: 1px solid #0b8f07ff;
                    display: inline-block;
                    padding: 8px 15px;
                    border-radius: 3px;
                    background-color: #baf3d6ff;
                }
                .compacto {
                    margin: 5px 0;
                    padding: 0;
                }
                .texto-pequeno {
                    font-size: 10px;
                    margin: 4px 0;
                }
                .fecha-generacion {
                    text-align: right;
                    font-size: 9px;
                    color: #666;
                    margin-bottom: 10px;
                }
            </style>
        </head>
        <body>
            <div class='fecha-generacion'>
                Generado: $fecha_actual
            </div>
            
            <div class='certificado'>
                <div class='header'>
                    <div class='logo-container'>
                        <img src='$logoBase64' alt='Logo SMS' class='logo-img'>
                    </div>
                    <br><br><br><br>
                    <h2><b>REPORTE DE GESTIÓN INTEGRAL DE RESIDUOS GENERADOS EN ATENCIÓN EN SALUD Y OTRAS ACTIVIDADES</b></h2>
                    <br><br><br>
                    <h1>Certificado de Aprobación - Año $anio</h1>
                </div>
                
                <div class='texto-centrado'>
                    <p>La Secretaría Municipal de Salud de Pasto - Oficina de Salud Ambiental certifica que:</p>
                </div>
                
                <div class='datos-generador'>
                    <p><strong>Nombre del Generador:</strong> $nom_generador</p>
                    <p><strong>NIT/Identificación:</strong> $nit</p>
                    <p><strong>Dirección del Establecimiento:</strong> $direccion</p>
                    <p><strong>Tipo de Sujeto Obligado:</strong> $tipo_sujeto</p>
                    <p><strong>Responsable del Reporte:</strong> $responsable</p>
                </div>
                
                <div class='content'>
                    <p>Ha cumplido satisfactoriamente con la presentación y aprobación del Reporte Anual de Gestión integral de Residuos generados en atención en salud y otras actividades correspondiente al año <strong>$anio</strong>, de acuerdo con lo establecido en la normativa ambiental vigente.</p>
                    
                    <p>El presente certificado acredita que todos los formularios requeridos han sido revisados y aprobados por el administrador del sistema.</p>
                </div>
                <br><br><br><br>
                <div class='firma-area'>       
                    <div class='sello'>
                        APROBADO
                    </div>
                    
                    <p class='texto-pequeno'>Sistema de Gestión Integral de Residuos Generados en Atención en Salud y Otras Activiades</p>
                    <p class='texto-pequeno'><em>Certificado generado automáticamente - Válido por un año</em></p>
                </div>
                
                <div class='footer'>                    
                    <p class='texto-pequeno'>Secretaría Municipal de Salud de Pasto - Oficina de Salud Ambiental</p>
                    <p class='texto-pequeno'>CAM ANGANOY - Barrio Los Rosales II, Pasto, Nariño - Tel: (602) 7244326 Ext. 8009</p>
                </div>              
            </div>
        </body>
        </html>
        ";
    }
    
    // Obtener ruta del certificado si existe
    public function obtenerRutaCertificado($generador_id, $anio) {
        $directorio = __DIR__ . "/certificados/";
        $nombre_archivo = "certificado_aprobacion_{$generador_id}_{$anio}.pdf";
        $ruta = $directorio . $nombre_archivo;
        
        return file_exists($ruta) ? $ruta : null;
    }
}
?>