<?php
require_once '../../includes/conexion.php';

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
            
            // Configurar DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isPhpEnabled', true);
            
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
            $directorio = "../../procesos/uploads/certificados/";
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
            
            return $nombre_archivo;
            
        } catch (Exception $e) {
            error_log("❌ Error en generación de PDF: " . $e->getMessage());
            throw $e; // Relanzar la excepción
        }
    }
    
    // Generar HTML para el certificado (mejorado)
    private function generarHtmlCertificado($generador, $anio) {
        $fecha_actual = date('d/m/Y');
        $fecha_revision = $generador['fecha_revision'] ? date('d/m/Y', strtotime($generador['fecha_revision'])) : $fecha_actual;
        
        // Escapar todos los datos para seguridad
        $nom_generador = htmlspecialchars($generador['nom_generador'] ?? '');
        $nit = htmlspecialchars($generador['nit'] ?? '');
        $direccion = htmlspecialchars($generador['dir_establecimiento'] ?? '');
        $tipo_sujeto = htmlspecialchars($generador['nom_tipo'] ?? '');
        $responsable = htmlspecialchars($generador['nom_responsable'] ?? '');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Certificado de Aprobación</title>
            <style>
                body { 
                    font-family: 'DejaVu Sans', Arial, sans-serif; 
                    margin: 0;
                    padding: 40px;
                    color: #333;
                    line-height: 1.4;
                }
                .certificado {                    
                    padding: 40px;
                    text-align: center;
                }
                .header {
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #4CAF50;
                    font-size: 24px;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                }
                .header h2 {
                    color: #666;
                    font-size: 16px;
                    font-weight: normal;
                }
                .content {
                    margin: 30px 0;
                    text-align: left;
                }
                .datos-generador {
                    background-color: #f9f9f9;
                    padding: 20px;
                    margin: 20px 0;
                    border-left: 4px solid #4CAF50;
                    border-radius: 5px;
                }
                .datos-generador p {
                    margin: 8px 0;
                }
                .firma {
                    margin-top: 50px;
                    border-top: 2px solid #333;
                    padding-top: 20px;
                    text-align: center;
                }
                .sello {
                    color: #4CAF50;
                    font-weight: bold;
                    font-size: 14px;
                    margin-top: 30px;
                    border: 2px solid #4CAF50;
                    display: inline-block;
                    padding: 10px 20px;
                    border-radius: 5px;
                }
                .texto-centrado {
                    text-align: center;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='certificado'>
                <div class='header'>
                    <h1>Certificado de Aprobación</h1>
                    <h2>REPORTE ANUAL DE GESTIÓN DE RESIDUOS GENERADOS EN ATENCIÓN EN SALUD Y OTRAS ACTIVIDADES - Año $anio</h2>
                </div>
                
                <div class='texto-centrado'>
                    <p>La Secretaría Municipal de Salud de Pasto - oficina de salud ambiental  certifica que:</p>
                </div>
                
                <div class='datos-generador'>
                    <p><strong>Nombre del Generador:</strong> $nom_generador</p>
                    <p><strong>NIT/Identificación:</strong> $nit</p>
                    <p><strong>Dirección del Establecimiento:</strong> $direccion</p>
                    <p><strong>Tipo de Sujeto Obligado:</strong> $tipo_sujeto</p>
                    <p><strong>Responsable del Reporte:</strong> $responsable</p>
                </div>
                
                <div class='content'>
                    <p>Ha cumplido satisfactoriamente con la presentación y aprobación del Reporte Anual 
                    de Gestión de Residuos generados en atención en salud y otras actividades correspondiente al año <strong>$anio</strong>, 
                    de acuerdo con lo establecido en la normativa ambiental vigente.</p>
                    
                    <p>El presente certificado acredita que todos los formularios requeridos han sido 
                    revisados y aprobados por el administrador del sistema.</p>
                </div>
                
                <div class='firma'>                    
                    <br>
                    <p>_________________________</p>
                    <p><strong>Sistema de Gestión de Residuos Generados en Atención en Salud y Otras Actividades</strong></p>
                    <p><em>Certificado generado automáticamente</em></p>
                </div>              
               
            </div>
        </body>
        </html>
        ";
    }
    
    // Obtener ruta del certificado si existe
    public function obtenerRutaCertificado($generador_id, $anio) {
        $nombre_archivo = "certificado_aprobacion_{$generador_id}_{$anio}.pdf";
        $ruta = "../../procesos/uploads/certificados/" . $nombre_archivo;
        
        return file_exists($ruta) ? $ruta : null;
    }
}
?>