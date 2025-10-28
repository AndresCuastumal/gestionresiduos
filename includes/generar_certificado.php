<?php
require_once '../../vendor/autoload.php'; // Para TCPDF o similar

function generarCertificado($generador_id, $anio, $nombre_usuario, $nombre_generador) {
    // Crear directorio si no existe
    $directorio = '../uploads/certificados/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    $nombre_archivo = 'certificado_' . $generador_id . '_' . $anio . '_' . time() . '.pdf';
    $ruta_completa = $directorio . $nombre_archivo;
    
    // Crear PDF con TCPDF (debes tenerlo instalado)
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('Sistema de Gestión de Residuos');
    $pdf->SetAuthor('Sistema de Gestión de Residuos');
    $pdf->SetTitle('Certificado de Cumplimiento');
    $pdf->SetSubject('Certificado de Gestión de Residuos');
    
    $pdf->AddPage();
    
    // Contenido del certificado
    $html = "
    <h1 style='text-align: center;'>CERTIFICADO DE CUMPLIMIENTO</h1>
    <br><br>
    <p style='text-align: center;'>Se certifica que:</p>
    <h2 style='text-align: center;'>$nombre_generador</h2>
    <p style='text-align: center;'>Representado por: $nombre_usuario</p>
    <br>
    <p style='text-align: center;'>Ha cumplido satisfactoriamente con todos los requisitos de reporte</p>
    <p style='text-align: center;'>para la gestión de residuos correspondiente al año $anio.</p>
    <br><br>
    <p style='text-align: center;'>Fecha de emisión: " . date('d/m/Y') . "</p>
    <p style='text-align: center;'>Código de certificado: CERT-" . strtoupper(uniqid()) . "</p>
    ";
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($ruta_completa, 'F');
    
    return $ruta_completa;
}
?>