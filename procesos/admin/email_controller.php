<?php
require_once  __DIR__.'/../../includes/conexion.php';
require __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Configurar PHPMailer
    private function configurarMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP (Gmail)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestionresiduossms@gmail.com';
            $mail->Password = 'qvkk yjcv gktx alvb'; // Contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Configuración del remitente
            $mail->setFrom('gestionresiduossms@gmail.com', 'Sistema de Reporte de Gestión de Residuos en atención en Salud y otras Actividades - Secretaría de Salud Pasto');
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            
            return $mail;
            
        } catch (Exception $e) {
            error_log("❌ Error configurando PHPMailer: " . $e->getMessage());
            return false;
        }
    }
    
    // ========== MÉTODO NUEVO: ENVIAR CONFIRMACIÓN DE RECEPCIÓN ==========
    
    /**
     * Enviar correo de confirmación de recepción cuando el usuario completa los 3 formularios
     */
    public function enviarConfirmacionRecepcion($generador_id, $anio, $usuario_id) {
        error_log("📧 Preparando envío de confirmación de recepción para generador_id: $generador_id");
        
        // ✅ CONSULTA CORREGIDA - Usar solo datos del generador
        $stmt = $this->conn->prepare("
            SELECT g.nom_generador, g.nom_responsable, g.nit, 
                u.email as email_responsable
            FROM generador g
            JOIN usuario_generador ug ON g.id = ug.generador_id
            JOIN usuarios u ON ug.usuario_id = u.id            
            WHERE g.id = ? AND u.id = ?
        ");
        
        $stmt->execute([$generador_id, $usuario_id]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$datos || empty($datos['email_responsable'])) {
            error_log("❌ No se encontró email para el generador $generador_id y usuario $usuario_id");
            return false;
        }
        
        $destinatario = $datos['email_responsable'];
        // ✅ Usar el nombre del responsable del generador, ya que usuarios no tiene nombre
        $nombre_destinatario = $datos['nom_responsable'] ?: $datos['nom_generador'];
        
        $mail = $this->configurarMailer();
        if (!$mail) {
            error_log("❌ Error al configurar PHPMailer");
            return false;
        }
        
        try {
            // Configurar destinatario
            $mail->addAddress($destinatario, $nombre_destinatario);
            $mail->Subject = "✅ Confirmación de Recepción - Reporte Anual {$anio} - {$datos['nom_generador']}";
            
            // Crear contenido HTML del email
            $mail->Body = $this->crearCuerpoEmailConfirmacion($datos, $anio);
            $mail->AltBody = $this->crearCuerpoTextoConfirmacion($datos, $anio);
            
            // Enviar email
            $mail->send();
            error_log("✅ Email de confirmación enviado exitosamente a: $destinatario");
            
            // Guardar registro del envío
            $this->guardarRegistroEmail($generador_id, $anio, 'confirmacion_recepcion', $destinatario);
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error enviando email de confirmación: " . $mail->ErrorInfo);
            return false;
        }
    }
    
 
    /**
     * Enviar certificado de aprobación con PDF adjunto
     */
    public function enviarCertificadoAprobacion($generador_id, $anio, $ruta_pdf) {
        error_log("📧 Preparando envío de certificado para generador_id: $generador_id");
        
        // Obtener datos del generador
        $stmt = $this->conn->prepare("
            SELECT g.nom_generador, g.nom_responsable, g.nit, u.email as email_responsable
            FROM generador g
            JOIN usuario_generador ug ON g.id = ug.generador_id
            JOIN usuarios u ON ug.usuario_id = u.id            
            WHERE g.id = ?
        ");
        $stmt->execute([$generador_id]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$datos || empty($datos['email_responsable'])) {
            error_log("❌ No se encontró email para el generador $generador_id");
            return false;
        }
        
        $destinatario = $datos['email_responsable'];
        $nombre_destinatario = $datos['nom_responsable'] ?: $datos['nom_generador'];
        
        $mail = $this->configurarMailer();
        if (!$mail) {
            error_log("❌ Error al configurar PHPMailer");
            return false;
        }
        
        try {
            // Configurar destinatario
            $mail->addAddress($destinatario, $nombre_destinatario);
            $mail->Subject = "✅ Certificado de Aprobación - Reporte Anual {$anio} - {$datos['nom_generador']}";
            
            // Adjuntar PDF
            if (file_exists($ruta_pdf)) {
                $mail->addAttachment($ruta_pdf, "Certificado_Aprobacion_{$datos['nom_generador']}_{$anio}.pdf");
                error_log("✅ PDF adjuntado: " . basename($ruta_pdf));
            } else {
                error_log("⚠️ PDF no encontrado: $ruta_pdf");
            }
            
            // Crear contenido HTML del email
            $mail->Body = $this->crearCuerpoEmailAprobacion($datos, $anio);
            $mail->AltBody = $this->crearCuerpoTextoAprobacion($datos, $anio);
            
            // Enviar email
            $mail->send();
            error_log("✅ Email de certificado enviado exitosamente a: $destinatario");
            
            // Guardar registro del envío
            $this->guardarRegistroEmail($generador_id, $anio, 'aprobacion', $destinatario);
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error enviando email de certificado: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Enviar notificación de rechazo con observaciones
     */
    public function enviarNotificacionRechazo($generador_id, $anio, $observaciones) {
        error_log("📧 Preparando envío de notificación de rechazo para generador_id: $generador_id");
        
        // Obtener datos del generador
        $stmt = $this->conn->prepare("
            SELECT g.nom_generador, g.nom_responsable, g.nit, u.email as email_responsable
            FROM generador g
            JOIN usuario_generador ug ON g.id = ug.generador_id
            JOIN usuarios u ON ug.usuario_id = u.id            
            WHERE g.id = ?
        ");
        $stmt->execute([$generador_id]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$datos || empty($datos['email_responsable'])) {
            error_log("❌ No se encontró email para el generador $generador_id");
            return false;
        }
        
        $destinatario = $datos['email_responsable'];
        $nombre_destinatario = $datos['nom_responsable'] ?: $datos['nom_generador'];
        
        $mail = $this->configurarMailer();
        if (!$mail) {
            error_log("❌ Error al configurar PHPMailer");
            return false;
        }
        
        try {
            // Configurar destinatario
            $mail->addAddress($destinatario, $nombre_destinatario);
            $mail->Subject = "⚠️ Correcciones Requeridas - Reporte Anual {$anio} - {$datos['nom_generador']}";
            
            // Crear contenido HTML del email
            $mail->Body = $this->crearCuerpoEmailRechazo($datos, $anio, $observaciones);
            $mail->AltBody = $this->crearCuerpoTextoRechazo($datos, $anio, $observaciones);
            
            // Enviar email
            $mail->send();
            error_log("✅ Email de rechazo enviado exitosamente a: $destinatario");
            
            // Guardar registro del envío
            $this->guardarRegistroEmail($generador_id, $anio, 'rechazo', $destinatario);
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error enviando email de rechazo: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    // ========== MÉTODOS PARA CREAR CUERPOS DE EMAIL ==========
    
    /**
     * Crear cuerpo HTML para email de confirmación de recepción
     */
    private function crearCuerpoEmailConfirmacion($datos, $anio) {
        $fecha = date('d/m/Y');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmación de Recepción</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    background: linear-gradient(135deg, #3498db, #2980b9);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    margin: -30px -30px 30px -30px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 20px 0;
                }
                .datos-generador {
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 4px solid #3498db;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 12px;
                }
                .checklist {
                    margin: 20px 0;
                }
                .checklist li {
                    margin: 10px 0;
                    padding-left: 30px;
                    position: relative;
                }
                .checklist li:before {
                    content: '✅';
                    position: absolute;
                    left: 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Confirmación de Recepción</h1>
                    <p>Reporte Anual de Gestión de Residuos Peligrosos - {$anio}</p>
                </div>
                
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['nom_responsable']}</strong>,</p>
                    
                    <p>Hemos recibido exitosamente <strong>TODOS SUS REPORTES</strong> para el generador 
                    <strong>{$datos['nom_generador']}</strong> (NIT: {$datos['nit']}) correspondientes al año {$anio}.</p>
                    
                    <div class='datos-generador'>
                        <h3>📋 Formularios Completados y Confirmados:</h3>
                        <ul class='checklist'>
                            <li>Reporte Mensual de Residuos</li>
                            <li>Capacitaciones, accidentes y auditorías</li>
                            <li>Plan de Contingencias</li>
                        </ul>
                        <p><strong>Fecha de recepción:</strong> {$fecha}</p>
                        <p><strong>Estado actual:</strong> Pendiente</p>
                    </div>
                    
                    <p><strong>📌 Proceso de Revisión:</strong></p>
                    <ol>
                        <li>Su reporte ha sido registrado en nuestro sistema</li>
                        <li>Será asignado a un técnico para revisión</li>
                        <li>Recibirá una notificación cuando se complete la revisión</li>
                        <li>Si se requieren correcciones, se le notificará por este mismo medio</li>
                    </ol>
                    
                    <p>Puede verificar el estado de su reporte ingresando al sistema en cualquier momento.</p>
                    
                    <p><strong>⚠️ Importante:</strong> Este correo es solo una confirmación de recepción. 
                    La revisión técnica se realizará posteriormente y podría requerir ajustes.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Secretaría de Salud de Pasto</strong><br>
                    Sistema de Reporte de Gestión de Residuos Generados en Atención en Salud y Otras Actividades</p>
                    <p>📍 Pasto, Nariño, Colombia<br>                    
                    ✉️ gestionresiduossms@gmail.com</p>
                    <p><em>Este es un mensaje automático, por favor no responda a este correo.</em></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Crear cuerpo HTML para email de aprobación
     */
    private function crearCuerpoEmailAprobacion($datos, $anio) {
        $fecha = date('d/m/Y');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Certificado de Aprobación</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    background: linear-gradient(135deg, #4CAF50, #45a049);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    margin: -30px -30px 30px -30px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 20px 0;
                }
                .datos-generador {
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 4px solid #4CAF50;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 12px;
                }
                .btn {
                    display: inline-block;
                    background: #4CAF50;
                    color: white;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 10px 0;
                }
                .observaciones {
                    background: #fff3cd;
                    padding: 15px;
                    border-left: 4px solid #ffc107;
                    margin: 15px 0;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Certificado de Aprobación</h1>
                    <p>Reporte Anual de Gestión de Residuos Peligrosos</p>
                </div>
                
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['nom_responsable']}</strong>,</p>
                    
                    <p>Nos complace informarle que el <strong>Reporte Anual {$anio}</strong> para el generador 
                    <strong>{$datos['nom_generador']}</strong> (NIT: {$datos['nit']}) ha sido <strong style='color: #4CAF50;'>APROBADO</strong> 
                    satisfactoriamente.</p>
                    
                    <div class='datos-generador'>
                        <h3>📋 Resumen de la Aprobación</h3>
                        <p><strong>Generador:</strong> {$datos['nom_generador']}</p>
                        <p><strong>NIT/Identificación:</strong> {$datos['nit']}</p>
                        <p><strong>Año del Reporte:</strong> {$anio}</p>
                        <p><strong>Fecha de Aprobación:</strong> {$fecha}</p>
                    </div>
                    
                    <p>Se adjunta el certificado de aprobación correspondiente en formato PDF. 
                    Este documento acredita el cumplimiento de los requisitos establecidos en la normativa 
                    ambiental vigente para la gestión de residuos peligrosos.</p>
                    
                    <p><strong>📎 El certificado PDF está adjunto a este correo.</strong></p>
                    
                    <p>Puede descargar el certificado desde el sistema ingresando a su cuenta o utilizar 
                    el archivo adjunto en este correo para sus registros.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Secretaría de Salud de Pasto</strong><br>
                    Sistema de Reporte de Gestión de Residuos Generados en Atención en Salud y Otras Actividades</p>
                    <p>📍 Pasto, Nariño, Colombia<br>                    
                    ✉️ gestionresiduossms@gmail.com</p>
                    <p><em>Este es un mensaje automático, por favor no responda a este correo.</em></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Crear cuerpo HTML para email de rechazo
     */
    private function crearCuerpoEmailRechazo($datos, $anio, $observaciones) {
        $fecha = date('d/m/Y');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Correcciones Requeridas</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    background: linear-gradient(135deg, #ff9800, #f57c00);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    margin: -30px -30px 30px -30px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 20px 0;
                }
                .datos-generador {
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 4px solid #ff9800;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .observaciones {
                    background: #fff3cd;
                    padding: 20px;
                    border-left: 4px solid #ffc107;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 12px;
                }
                .btn {
                    display: inline-block;
                    background: #ff9800;
                    color: white;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Correcciones Requeridas</h1>
                    <p>Reporte de Gestión Integral de Residuos Generados en la Atención en Salud y Otras Actividades - {$anio}</p>
                </div>
                
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['nom_responsable']}</strong>,</p>
                    
                    <p>El <strong>Reporte Anual {$anio}</strong> para el generador 
                    <strong>{$datos['nom_generador']}</strong> (NIT: {$datos['nit']}) requiere correcciones 
                    antes de poder ser aprobado.</p>
                    
                    <div class='datos-generador'>
                        <h3>📋 Información del Reporte</h3>
                        <p><strong>Generador:</strong> {$datos['nom_generador']}</p>
                        <p><strong>NIT/Identificación:</strong> {$datos['nit']}</p>
                        <p><strong>Año del Reporte:</strong> {$anio}</p>
                        <p><strong>Fecha de Revisión:</strong> {$fecha}</p>
                    </div>
                    
                    <div class='observaciones'>
                        <h3>📝 Observaciones del Revisor</h3>
                        " . nl2br(htmlspecialchars($observaciones)) . "
                    </div>
                    
                    <p><strong>📋 Acciones Requeridas:</strong></p>
                    <ol>
                        <li>Ingrese al sistema</li>
                        <li>Realice las correcciones necesarias en los formularios correspondientes de acuerdo con las revisiones planteadas por el revisor enviadas por este correo</li>
                        <li>Vuelva a enviar el reporte para una segunda y última oportunidad para revisión</li>
                    </ol>
                    
                    <p style='text-align: center;'>
                        <a href='http://34.56.157.229/gestionresiduos' class='btn'>
                            📊 Ingresar al Sistema
                        </a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p><strong>Secretaría de Salud de Pasto</strong><br>
                    Sistema de Gestión de Residuos Generados en Ateción en Salud  y Otras Actividades</p>                    
                    <p><em>Este es un mensaje automático, por favor no responda a este correo.</em></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // ========== MÉTODOS PARA VERSIÓN TEXTO PLANO ==========
    
    /**
     * Crear versión texto plano para email de confirmación
     */
    private function crearCuerpoTextoConfirmacion($datos, $anio) {
        $fecha = date('d/m/Y');
        
        return "CONFIRMACION DE RECEPCION - REPORTE ANUAL {$anio}

        Estimado(a) {$datos['nom_responsable']},

        Hemos recibido exitosamente TODOS SUS REPORTES para el generador 
        {$datos['nom_generador']} (NIT: {$datos['nit']}) correspondientes al año {$anio}.

        FORMULARIOS COMPLETADOS Y CONFIRMADOS:
        ✅ Reporte Mensual de Residuos
        ✅ Capacitaciones, accidentes y auditorías  
        ✅ Plan de Contingencias

        Fecha de recepción: {$fecha}
        Estado actual: Pendiente de revisión

        Su reporte ha sido registrado en nuestro sistema y será asignado a un técnico 
        para revisión. Recibirá una notificación cuando se complete la revisión.

        Este correo es solo una confirmación de recepción. La revisión técnica se 
        realizará posteriormente y podría requerir ajustes.

        Secretaría de Salud de Pasto
        Sistema de Gestión de Residuos Peligrosos
        Este es un mensaje automático, por favor no responda.";
            }
            
            /**
             * Crear versión texto plano para email de aprobación
             */
            private function crearCuerpoTextoAprobacion($datos, $anio) {
                $fecha = date('d/m/Y');
                
                return "CERTIFICADO DE APROBACION

        Estimado(a) {$datos['nom_responsable']},

        Nos complace informarle que el Reporte Anual {$anio} para el generador 
        {$datos['nom_generador']} (NIT: {$datos['nit']}) ha sido APROBADO satisfactoriamente.

        RESUMEN DE LA APROBACION:
        - Generador: {$datos['nom_generador']}
        - NIT: {$datos['nit']}
        - Año del Reporte: {$anio}
        - Fecha de Aprobación: {$fecha}

        Se adjunta el certificado de aprobación en formato PDF. Este documento acredita 
        el cumplimiento de los requisitos establecidos en la normativa ambiental vigente.

        Secretaría de Salud de Pasto
        Sistema de Gestión de Residuos Peligrosos
        Este es un mensaje automático, por favor no responda.";
            }
            
            /**
             * Crear versión texto plano para email de rechazo
             */
            private function crearCuerpoTextoRechazo($datos, $anio, $observaciones) {
                $fecha = date('d/m/Y');
                
                return "CORRECCIONES REQUERIDAS - REPORTE ANUAL {$anio}

        Estimado(a) {$datos['nom_responsable']},

        El Reporte Anual {$anio} para el generador {$datos['nom_generador']} 
        (NIT: {$datos['nit']}) requiere correcciones antes de poder ser aprobado.

        OBSERVACIONES DEL REVISOR:
        {$observaciones}

        ACCIONES REQUERIDAS:
        1. Ingrese al sistema de gestión de residuos
        2. Revise las observaciones detalladas
        3. Realice las correcciones necesarias
        4. Vuelva a enviar el reporte

        Enlace al sistema: http://34.56.157.229/gestionresiduos/

        Secretaría de Salud de Pasto
        Sistema de Gestión de Residuos Peligrosos
        Este es un mensaje automático, por favor no responda.";
    }
    
    // ========== MÉTODO PARA REGISTRAR ENVÍOS ==========
    
    /**
     * Guardar registro del envío de email en la base de datos
     */
    private function guardarRegistroEmail($generador_id, $anio, $tipo, $destinatario) {
        try {
            // ✅ ACORTAR EL TIPO DE EMAIL PARA QUE QUEPA EN EL CAMPO
            $tipos_validos = [
                'confirmacion_recepcion' => 'confirmacion',
                'aprobacion' => 'aprobacion',
                'rechazo' => 'rechazo'
            ];
            
            $tipo_corto = $tipos_validos[$tipo] ?? substr($tipo, 0, 20);
            
            error_log("📝 Guardando registro de email - Tipo: $tipo (acortado a: $tipo_corto)");
            
            $stmt = $this->conn->prepare("
                INSERT INTO logs_emails 
                (generador_id, anio, tipo_email, destinatario, fecha_envio)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$generador_id, $anio, $tipo_corto, $destinatario]);
            error_log("✅ Registro de email guardado en base de datos");
            
        } catch (Exception $e) {
            error_log("⚠️ Error guardando registro de email: " . $e->getMessage());
            // ⚠️ IMPORTANTE: No lanzar excepción para no interrumpir el envío del email
        }
    }
}
?>