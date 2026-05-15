<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;

class CertificadoPdfController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function generarCertificadoAprobacion($generador_id, $anio) {
        error_log("Generando certificado con mPDF para generador_id: $generador_id, año: $anio");
        
        try {
            // Obtener datos del generador
            $stmt = $this->conn->prepare("
                SELECT 
                    g.id, g.nom_generador, g.nit, g.dir_establecimiento, g.nom_responsable,
                    ts.nom_clase as nom_tipo
                FROM generador g
                JOIN subcategoria ts ON g.tipo_sujeto = ts.id
                WHERE g.id = ?
            ");
            $stmt->execute([$generador_id]);
            $generador = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$generador) {
                throw new Exception("No se encontraron datos del generador");
            }
            
            // Configurar mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 15,
                'margin_right' => 15,
                'default_font_size' => 11,
                'default_font' => 'helvetica'
            ]);
            
            // Generar HTML
            $html = $this->generarHtmlCertificado($generador, $anio);
            
            // Escribir HTML
            $mpdf->WriteHTML($html);
            
            // Crear directorio si no existe
            $directorio = __DIR__ . "/certificados/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }
            
            // Nombre del archivo
            $nombre_archivo = "certificado_aprobacion_{$generador_id}_{$anio}.pdf";
            $ruta_archivo = $directorio . $nombre_archivo;
            
            // Guardar archivo
            $mpdf->Output($ruta_archivo, 'F');
            
            if (!file_exists($ruta_archivo)) {
                throw new Exception("No se pudo guardar el archivo PDF");
            }
            
            error_log("✅ Certificado PDF generado exitosamente: $ruta_archivo");
            
            return [
                'nombre_archivo' => $nombre_archivo,
                'ruta_completa' => $ruta_archivo
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error generando certificado con mPDF: " . $e->getMessage());
            
            // Fallback: crear archivo HTML
            return $this->crearCertificadoHTML($generador_id, $anio);
        }
    }
    
    private function generarHtmlCertificado($generador, $anio) {
        $fecha_actual = date('d/m/Y');
        $nom_generador = htmlspecialchars($generador['nom_generador'] ?? '');
        $nit = htmlspecialchars($generador['nit'] ?? '');
        $direccion = htmlspecialchars($generador['dir_establecimiento'] ?? '');
        $tipo_sujeto = htmlspecialchars($generador['nom_tipo'] ?? '');
        $responsable = htmlspecialchars($generador['nom_responsable'] ?? '');
        
        // Logo en base64 si existe
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/gestionresiduos/assets/css/logoNuevoSMS2024.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Certificado de Aprobación</title>
            <style>
                body {
                    font-family: helvetica, arial, sans-serif;
                    margin: 0;
                    padding: 0;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .logo {
                    max-height: 80px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #af4c4c;
                    font-size: 18pt;
                    margin: 10px 0;
                }
                h2 {
                    font-size: 14pt;
                    margin: 5px 0;
                }
                h3 {
                    font-size: 12pt;
                    margin: 5px 0;
                }
                .datos {
                    background: #f9f9f9;
                    padding: 15px;
                    margin: 20px 0;
                    border-left: 4px solid #af784c;
                }
                .firma {
                    margin-top: 50px;
                    text-align: center;
                }
                .sello {
                    border: 2px solid #0b8f07;
                    padding: 8px 30px;
                    display: inline-block;
                    margin-top: 20px;
                    font-weight: bold;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 9pt;
                    color: #666;
                }
                .fecha {
                    text-align: right;
                    font-size: 9pt;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                }
                td {
                    padding: 5px;
                }
            </style>
        </head>
        <body>
            <div class='fecha'>
                Generado: $fecha_actual
            </div>
            
            <div class='header'>
                " . ($logoBase64 ? "<img src='$logoBase64' class='logo'>" : "") . "
                <h2>SECRETARÍA MUNICIPAL DE SALUD DE PASTO</h2>
                <h3>Oficina de Salud Ambiental</h3>
                <h1>Certificado de Aprobación</h1>
                <h3>Año $anio</h3>
            </div>
            
            <p>La Secretaría Municipal de Salud de Pasto certifica que:</p>
            
            <div class='datos'>
                <table>
                    <tr>
                        <td width='30%'><strong>Nombre del Generador:</strong></td>
                        <td>$nom_generador</td>
                    </tr>
                    <tr>
                        <td><strong>NIT/Identificación:</strong></td>
                        <td>$nit</td>
                    </tr>
                    <tr>
                        <td><strong>Dirección del Establecimiento:</strong></td>
                        <td>$direccion</td>
                    </tr>
                    <tr>
                        <td><strong>Tipo de Sujeto Obligado:</strong></td>
                        <td>$tipo_sujeto</td>
                    </tr>
                    <tr>
                        <td><strong>Responsable del Reporte:</strong></td>
                        <td>$responsable</td>
                    </tr>
                </table>
            </div>
            
            <p>Ha cumplido satisfactoriamente con la presentación y aprobación del Reporte Anual 
            de Gestión Integral de Residuos Generados en Atención en Salud y Otras Actividades 
            correspondiente al año <strong>$anio</strong>, de acuerdo con lo establecido en la 
            normativa ambiental vigente.</p>
            
            <div class='firma'>
                <div class='sello'>
                    APROBADO
                </div>
                <p><small>Certificado generado automáticamente - Válido por un año</small></p>
            </div>
            
            <div class='footer'>
                <p>Secretaría Municipal de Salud de Pasto - Oficina de Salud Ambiental</p>
                <p>CAM ANGANOY - Barrio Los Rosales II, Pasto, Nariño - Tel: (602) 7244326 Ext. 8009</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function crearCertificadoHTML($generador_id, $anio) {
        $directorio = __DIR__ . "/certificados/";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        $nombre_archivo = "certificado_{$generador_id}_{$anio}.html";
        $ruta_archivo = $directorio . $nombre_archivo;
        
        // Obtener datos
        $stmt = $this->conn->prepare("
            SELECT g.nom_generador, g.nit, g.nom_responsable 
            FROM generador g 
            WHERE g.id = ?
        ");
        $stmt->execute([$generador_id]);
        $generador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $html = "<!DOCTYPE html>";
        $html .= "<html><head><meta charset='UTF-8'><title>Certificado</title>";
        $html .= "<style>body{font-family:Arial;margin:40px;text-align:center;} ";
        $html .= ".certificado{border:2px solid #af4c4c;padding:30px;border-radius:10px;} ";
        $html .= "h1{color:#af4c4c;} .sello{color:green;font-weight:bold;margin-top:30px;}</style>";
        $html .= "</head><body>";
        $html .= "<div class='certificado'>";
        $html .= "<h1>CERTIFICADO DE APROBACIÓN</h1>";
        $html .= "<h3>Año $anio</h3>";
        $html .= "<hr>";
        $html .= "<p><strong>Generador:</strong> " . htmlspecialchars($generador['nom_generador']) . "</p>";
        $html .= "<p><strong>NIT:</strong> " . htmlspecialchars($generador['nit']) . "</p>";
        $html .= "<p><strong>Responsable:</strong> " . htmlspecialchars($generador['nom_responsable']) . "</p>";
        $html .= "<hr>";
        $html .= "<p>Este certificado acredita que el generador ha cumplido con la presentación del reporte anual.</p>";
        $html .= "<div class='sello'>APROBADO</div>";
        $html .= "<p><small>Fecha: " . date('d/m/Y') . "</small></p>";
        $html .= "</div>";
        $html .= "</body></html>";
        
        file_put_contents($ruta_archivo, $html);
        
        return [
            'nombre_archivo' => $nombre_archivo,
            'ruta_completa' => $ruta_archivo
        ];
    }
    
    public function obtenerRutaCertificado($generador_id, $anio) {
        $directorio = __DIR__ . "/certificados/";
        $nombre_archivo = "certificado_aprobacion_{$generador_id}_{$anio}.pdf";
        $ruta_pdf = $directorio . $nombre_archivo;
        
        if (file_exists($ruta_pdf)) {
            return $ruta_pdf;
        }
        
        // Buscar HTML alternativo
        $nombre_html = "certificado_{$generador_id}_{$anio}.html";
        $ruta_html = $directorio . $nombre_html;
        
        return file_exists($ruta_html) ? $ruta_html : null;
    }
}