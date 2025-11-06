<?php
class RevisionesController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Obtener todas las revisiones pendientes
    public function obtenerRevisionesPendientes() {
        $stmt = $this->conn->prepare("
            SELECT r.*, g.nom_generador, g.nom_responsable, g.dir_establecimiento, g.tipo_sujeto, s.nom_tipo
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            JOIN tipo_generador s ON g.tipo_sujeto = s.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener revisión específica
    public function obtenerRevision($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT r.*, g.nom_generador, g.dir_establecimiento, g.tipo_sujeto,
                   u.email as nombre_revisor
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            LEFT JOIN usuarios u ON r.revisado_por = u.id
            WHERE r.generador_id = ? AND r.anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Actualizar estado de revisión del formulario mensual
    public function actualizarRevision($data) {
        // Verificar si está finalizado
        if ($this->estaFinalizado($data['generador_id'], $data['anio'])) {
            throw new Exception("Esta revisión ya ha sido finalizada y no puede ser modificada.");
        }
        
        // Asegurar que el registro exista
        $this->crearRevisionSiNoExiste($data['generador_id'], $data['anio']);
        
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_mensual = ?, 
                observaciones_mensual = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        $success = $stmt->execute([
            $data['formulario_mensual'],
            $data['observaciones_mensual'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
        
        if ($success) {
            // ✅ NUEVO: Actualizar estado de contingencias si hay rechazo
            if ($data['formulario_mensual'] === 'rechazado') {
                $this->actualizarEstadoContingencias($data['generador_id'], 'rechazado');
            }
            
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es el último formulario y enviar notificaciones
            $this->verificarYEnviarNotificaciones($data['generador_id'], $data['anio']);
        }
        
        return $success;
    }

    // Verificar y enviar notificaciones si corresponde    
    private function verificarYEnviarNotificaciones($generador_id, $anio) {
        error_log("🎯 === VERIFICANDO NOTIFICACIONES ===");
        error_log("🎯 Llamado desde: " . debug_backtrace()[1]['function']);
        error_log("🎯 Para: generador_id=$generador_id, anio=$anio");
        
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        error_log("🎯 Estados actuales: " . print_r($estados, true));
        
        // Solo enviar notificaciones si todos los formularios tienen estado definitivo
        $todosRevisados = (
            $estados['formulario_mensual'] !== 'pendiente' && 
            $estados['formulario_mensual'] !== 'sin_datos' &&
            $estados['formulario_accidentes'] !== 'pendiente' && 
            $estados['formulario_accidentes'] !== 'sin_datos' &&
            $estados['formulario_contingencias'] !== 'pendiente' && 
            $estados['formulario_contingencias'] !== 'sin_datos'
        );
        
        error_log("🎯 ¿Todos revisados?: " . ($todosRevisados ? '✅ SÍ' : '❌ NO'));
        
        if ($todosRevisados) {
            error_log("🎯 🚀 EJECUTANDO enviarNotificaciones...");
            $this->enviarNotificaciones($generador_id, $anio);
        } else {
            error_log("🎯 ⏳ Aún no están todos revisados.");
            error_log("🎯 - Mensual: " . $estados['formulario_mensual']);
            error_log("🎯 - Accidentes: " . $estados['formulario_accidentes']); 
            error_log("🎯 - Contingencias: " . $estados['formulario_contingencias']);
        }
        
        error_log("🎯 === FIN VERIFICACIÓN ===");
    }
    // Actualizar estado general automáticamente
    public function actualizarEstadoGeneralAutomatico($generador_id, $anio) {
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
        $estado_general = $this->calcularEstadoGeneral(
            $estados['formulario_mensual'],
            $estados['formulario_accidentes'],
            $estados['formulario_contingencias']
        );
        
        $this->actualizarEstadoGeneral($generador_id, $anio, $estado_general);
    }

    // Calcular estado general basado en los tres formularios
    private function calcularEstadoGeneral($mensual, $accidentes, $contingencias) {
        if ($mensual === 'rechazado' || $accidentes === 'rechazado' || $contingencias === 'rechazado') {
            return 'rechazado';
        }
        
        if ($mensual === 'aprobado' && $accidentes === 'aprobado' && $contingencias === 'aprobado') {
            return 'aprobado';
        }
        
        return 'pendiente';
    }

    // Actualizar estado de revisión de accidentes - CORREGIDO
    public function actualizarRevisionAccidentes($data) {
        error_log("=== ACTUALIZANDO ACCIDENTES VIA WEB ===");
        error_log("Datos recibidos: " . print_r($data, true));
        
        // Verificar si está finalizado
        if ($this->estaFinalizado($data['generador_id'], $data['anio'])) {
            error_log("❌ Ya está finalizado, no se puede modificar");
            throw new Exception("Esta revisión ya ha sido finalizada y no puede ser modificada.");
        }
        
        // Asegurar que el registro exista
        $this->crearRevisionSiNoExiste($data['generador_id'], $data['anio']);
        
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_accidentes = ?, 
                observaciones_accidentes = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        $success = $stmt->execute([
            $data['formulario_accidentes'],
            $data['observaciones_accidentes'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
        
        if ($success) {
            error_log("✅ Actualización de accidentes exitosa");
            
            // ✅ NUEVO: Actualizar estado de contingencias si hay rechazo
            if ($data['formulario_accidentes'] === 'rechazado') {
                $this->actualizarEstadoContingencias($data['generador_id'], 'rechazado');
            }
            
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es el último formulario y enviar notificaciones
            $this->verificarYEnviarNotificaciones($data['generador_id'], $data['anio']);
        } 
        else {
            error_log("❌ Error en la actualización de accidentes");
        }
        
        return $success;
    }
    // Actualizar estado de revisión del formulario de contingencias - CORREGIDO
    public function actualizarRevisionContingencias($data) {
        error_log("=== ACTUALIZANDO CONTINGENCIAS VIA WEB ===");
        error_log("Datos recibidos: " . print_r($data, true));
        
        // Verificar si está finalizado
        if ($this->estaFinalizado($data['generador_id'], $data['anio'])) {
            error_log("❌ Ya está finalizado, no se puede modificar");
            throw new Exception("Esta revisión ya ha sido finalizada y no puede ser modificada.");
        }
        
        // Asegurar que el registro exista
        $this->crearRevisionSiNoExiste($data['generador_id'], $data['anio']);
        
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET formulario_contingencias = ?, 
                observaciones_contingencias = ?,
                fecha_revision = NOW(),
                revisado_por = ?,
                estado_general = ?
            WHERE generador_id = ? AND anio = ?
        ");
        
        $success = $stmt->execute([
            $data['formulario_contingencias'],
            $data['observaciones_contingencias'],
            $data['revisado_por'],
            $data['estado_general'],
            $data['generador_id'],
            $data['anio']
        ]);
        
        if ($success) {
            error_log("✅ Actualización de contingencias exitosa");
            
            // ✅ NUEVO: Actualizar estado de contingencias si hay rechazo
            if ($data['formulario_contingencias'] === 'rechazado') {
                $this->actualizarEstadoContingencias($data['generador_id'], 'rechazado');
            }
            
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es el último formulario y enviar notificaciones
            $this->verificarYEnviarNotificaciones($data['generador_id'], $data['anio']);
        } else {
            error_log("❌ Error en la actualización de contingencias");
        }
        
        return $success;
    }
    
    // Verificar si todos los formularios están aprobados
    public function verificarFormulariosCompletos($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT formulario_mensual, formulario_accidentes, formulario_contingencias
            FROM revisiones_anuales
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ✅ Asegúrate que solo devuelva true si los 3 están APROBADOS
        return ($revision['formulario_mensual'] === 'aprobado' &&
                $revision['formulario_accidentes'] === 'aprobado' &&
                $revision['formulario_contingencias'] === 'aprobado');
    }

    // Actualizar el estado general de la revisión
    public function actualizarEstadoGeneral($generador_id, $anio, $estado_general) {
        $stmt = $this->conn->prepare("
            UPDATE revisiones_anuales 
            SET estado_general = ?,
                fecha_revision = NOW()
            WHERE generador_id = ? AND anio = ?
        ");
        
        return $stmt->execute([
            $estado_general,
            $generador_id,
            $anio
        ]);
    }
      
    // Obtener revisiones con filtros - SOLO para generadores que reportaron datos en 2024
    public function obtenerRevisionesConFiltros($tipo_sujeto = '', $estado_general = '') {
        $sql = "
            SELECT 
                r.*, 
                g.nom_generador, 
                g.nom_responsable, 
                g.tipo_sujeto, 
                s.nom_clase AS nom_tipo
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            JOIN subcategoria s ON g.tipo_sujeto = s.id
            WHERE r.anio = 2024  -- Solo año 2024
            AND r.formulario_mensual != 'sin_datos'  -- Excluir sin datos
            AND r.formulario_accidentes != 'sin_datos' 
            AND r.formulario_contingencias != 'sin_datos'
        ";
        
        $params = [];
        
        if (!empty($tipo_sujeto)) {
            $sql .= " AND g.tipo_sujeto = ?";
            $params[] = $tipo_sujeto;
        }
        
        if (!empty($estado_general)) {
            $sql .= " AND r.estado_general = ?";
            $params[] = $estado_general;
        }
        
        $sql .= " ORDER BY g.nom_generador ASC, r.fecha_revision DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Verificar si existe registro de revisión para un generador y año
    public function existeRevision($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchColumn() > 0;
    }
    // Crear registro de revisión si no existe
    public function crearRevisionSiNoExiste($generador_id, $anio) {
        if (!$this->existeRevision($generador_id, $anio)) {
            $stmt = $this->conn->prepare("
                INSERT INTO revisiones_anuales (generador_id, anio, estado_general)
                VALUES (?, ?, 'pendiente')
            ");
            return $stmt->execute([$generador_id, $anio]);
        }
        return true;
    }
    // Obtener tipos de sujeto únicos para el filtro
    public function obtenerTiposSujeto() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT g.tipo_sujeto, s.nom_clase AS nom_tipo 
            FROM generador g
            JOIN subcategoria s ON g.tipo_sujeto = s.id 
            WHERE g.tipo_sujeto IS NOT NULL 
            ORDER BY nom_tipo ASC
        ");
        $stmt->execute();
        
        // Devolver un array asociativo id => nombre
        $tipos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tipos[$row['tipo_sujeto']] = $row['nom_tipo'];
        }
        return $tipos;
    }

    // Método para verificar si un formulario tiene datos
        public function formularioTieneDatos($generador_id, $anio, $tipo_formulario) {
        switch ($tipo_formulario) {
            case 'mensual':
                // ✅ NUEVA LÓGICA: Verificar si existe el soporte_pdf en revisiones_anuales
                $stmt = $this->conn->prepare("
                    SELECT soporte_pdf 
                    FROM revisiones_anuales 
                    WHERE generador_id = ? AND anio = ?
                ");
                $stmt->execute([$generador_id, $anio]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si existe soporte_pdf, el formulario está diligenciado
                return ($resultado && !empty($resultado['soporte_pdf']));
                    
            case 'accidentes':
                // Verificar si existe al menos un registro en reporte_anual_adicional
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM reporte_anual_adicional 
                    WHERE generador_id = ? AND anio = ?
                ");
                $stmt->execute([$generador_id, $anio]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $resultado['total'] > 0;
                    
            case 'contingencias':
                // Verificar si existe al menos un registro en la tabla de contingencias
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM contingencias 
                    WHERE generador_id = ? AND anio = ?
                ");
                $stmt->execute([$generador_id, $anio]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $resultado['total'] > 0;
                    
            default:
                return false;
        }
    }

    // Método para obtener el estado actual del formulario
    public function obtenerEstadoFormulario($generador_id, $anio, $tipo_formulario) {
        // Primero verificar si hay datos
        $tieneDatos = $this->formularioTieneDatos($generador_id, $anio, $tipo_formulario);
        
        if (!$tieneDatos) {
            return 'sin_datos';
        }
        
        // Si hay datos, obtener el estado de la revisión
        $campo_formulario = '';
        switch ($tipo_formulario) {
            case 'mensual': $campo_formulario = 'formulario_mensual'; break;
            case 'accidentes': $campo_formulario = 'formulario_accidentes'; break;
            case 'contingencias': $campo_formulario = 'formulario_contingencias'; break;
            default: return 'sin_datos';
        }
        
        $stmt = $this->conn->prepare("
            SELECT $campo_formulario 
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado[$campo_formulario] ?? 'pendiente';
    }

    // Determinar a qué formulario redirigir después de guardar
    public function determinarSiguienteFormulario($generador_id, $anio) {
        // Obtener el estado actual de todos los formularios
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
        // Debug: Ver qué estados estamos obteniendo (eliminar en producción)
        error_log("Estados formularios - Mensual: " . $estados['formulario_mensual'] . 
                ", Accidentes: " . $estados['formulario_accidentes'] . 
                ", Contingencias: " . $estados['formulario_contingencias']);
        
        // Lógica de redirección basada en los estados
        
        // Si el formulario de accidentes está pendiente, ir allí
        if ($estados['formulario_accidentes'] === 'pendiente' || 
            $estados['formulario_accidentes'] === 'sin_datos') {
            return "revisar_formulario_accidentes.php?generador_id=$generador_id&anio=$anio";
        }
        
        // Si accidentes está revisado pero contingencias está pendiente
        if (($estados['formulario_accidentes'] === 'aprobado' || $estados['formulario_accidentes'] === 'rechazado') && 
            ($estados['formulario_contingencias'] === 'pendiente' || $estados['formulario_contingencias'] === 'sin_datos')) {
            return "revisar_formulario_contingencias.php?generador_id=$generador_id&anio=$anio";
        }
        
        // Si todos los formularios han sido revisados
        if (($estados['formulario_mensual'] === 'aprobado' || $estados['formulario_mensual'] === 'rechazado') &&
            ($estados['formulario_accidentes'] === 'aprobado' || $estados['formulario_accidentes'] === 'rechazado') &&
            ($estados['formulario_contingencias'] === 'aprobado' || $estados['formulario_contingencias'] === 'rechazado')) {
            
            // Verificar si todos están aprobados
            if ($this->verificarFormulariosCompletos($generador_id, $anio)) {
                // Aquí podrías agregar lógica para generar el certificado
                error_log("Todos los formularios aprobados para generador $generador_id, año $anio");
            }
            
            return "listado_revisiones_view.php";
        }
        
        // Por defecto, volver al listado
        return "listado_revisiones_view.php";
    }
    // Obtener datos básicos del generador
    public function obtenerDatosGenerador($generador_id) {
        $stmt = $this->conn->prepare("
            SELECT id, nom_generador, nit, dir_establecimiento, tipo_sujeto, nom_responsable
            FROM generador
            WHERE id = ?
        ");
        $stmt->execute([$generador_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener el estado de los tres formularios para un generador y año específico
    public function obtenerEstadoFormularios($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT 
                formulario_mensual,
                formulario_accidentes, 
                formulario_contingencias,
                estado_general
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no existe el registro, devolver estados por defecto
        if (!$resultado) {
            return [
                'formulario_mensual' => 'sin_datos',
                'formulario_accidentes' => 'sin_datos',
                'formulario_contingencias' => 'sin_datos',
                'estado_general' => 'sin_datos'
            ];
        }
        
        // Asegurar que los valores no sean nulos
        return [
            'formulario_mensual' => $resultado['formulario_mensual'] ?? 'sin_datos',
            'formulario_accidentes' => $resultado['formulario_accidentes'] ?? 'sin_datos',
            'formulario_contingencias' => $resultado['formulario_contingencias'] ?? 'sin_datos',
            'estado_general' => $resultado['estado_general'] ?? 'sin_datos'
        ];
    }
    // En el método enviarNotificaciones - RUTAS CORREGIDAS
    public function enviarNotificaciones($generador_id, $anio) {
        error_log("=== INICIANDO ENVIO DE NOTIFICACIONES ===");
        error_log("Directorio actual: " . __DIR__);
        
        // RUTAS ABSOLUTAS CORRECTAS
        $pdfControllerPath = __DIR__ . '/certificado_pdf_controller.php';
        $emailControllerPath = __DIR__ . '/email_controller.php';
        
        error_log("Buscando archivos:");
        error_log(" - PDF: $pdfControllerPath");
        error_log(" - Email: $emailControllerPath");
        error_log(" - ¿Existe PDF?: " . (file_exists($pdfControllerPath) ? 'SÍ' : 'NO'));
        error_log(" - ¿Existe Email?: " . (file_exists($emailControllerPath) ? 'SÍ' : 'NO'));
        
        // Si no existen en esta ruta, probar rutas alternativas
        if (!file_exists($pdfControllerPath)) {
            error_log("⚠️ Probando rutas alternativas...");
            
            // Intentar con diferentes rutas posibles
            $rutas_alternativas = [
                __DIR__ . '/../../procesos/admin/certificado_pdf_controller.php',
                dirname(__DIR__) . '/procesos/admin/certificado_pdf_controller.php',
                'C:/xampp/htdocs/reportegestionresiduos/procesos/admin/certificado_pdf_controller.php'
            ];
            
            foreach ($rutas_alternativas as $ruta) {
                if (file_exists($ruta)) {
                    $pdfControllerPath = $ruta;
                    error_log("✅ Encontrado en: $ruta");
                    break;
                }
            }
        }
        
        if (!file_exists($pdfControllerPath) || !file_exists($emailControllerPath)) {
            error_log("❌ ERROR: Archivos de controlador no encontrados");
            error_log("❌ PDF: " . (file_exists($pdfControllerPath) ? 'EXISTE' : 'NO EXISTE'));
            error_log("❌ Email: " . (file_exists($emailControllerPath) ? 'EXISTE' : 'NO EXISTE'));
            return false;
        }
        
        error_log("✅ Cargando controladores...");
        
        require_once $pdfControllerPath;
        require_once $emailControllerPath;
        
        $pdfController = new CertificadoPdfController($this->conn);
        $emailController = new EmailController($this->conn);
        
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        $observaciones = $this->obtenerObservaciones($generador_id, $anio);

        error_log("Estados para notificación: " . print_r($estados, true));
        
        
        // ✅ SOLO SI TODOS ESTÁN APROBADOS - enviar certificado y finalizar
        if ($this->verificarFormulariosCompletos($generador_id, $anio)) {
            error_log("✅ TODOS APROBADOS - Generando certificado...");
            
            try {
                // Generar PDF
                $nombre_pdf = $pdfController->generarCertificadoAprobacion($generador_id, $anio);
                $ruta_pdf = "../../procesos/uploads/certificados/" . $nombre_pdf;
                
                error_log("PDF generado: " . $nombre_pdf);
                
                // Enviar email con certificado
                $email_enviado = $emailController->enviarCertificadoAprobacion($generador_id, $anio, $ruta_pdf);
                error_log("Email enviado: " . ($email_enviado ? '✅ SÍ' : '❌ NO'));
                
                // ✅ SOLO AQUÍ marcar como finalizado (con PDF)
                $finalizado = $this->marcarComoFinalizado($generador_id, $anio, $nombre_pdf);
                error_log("Marcado como finalizado: " . ($finalizado ? '✅ SÍ' : '❌ NO'));
                
            } catch (Exception $e) {
                error_log("❌ ERROR en generación de certificado: " . $e->getMessage());
            }
            
        } 
        // ✅ SI HAY RECHAZOS - solo enviar notificación, NO finalizar
        elseif ($estados['formulario_mensual'] === 'rechazado' || 
                $estados['formulario_accidentes'] === 'rechazado' || 
                $estados['formulario_contingencias'] === 'rechazado') {
            
            error_log("⚠️ HAY RECHAZOS - Enviando notificación de correcciones...");
            
            try {
                $email_enviado = $emailController->enviarNotificacionRechazo($generador_id, $anio, $observaciones);
                error_log("Email de rechazo enviado: " . ($email_enviado ? '✅ SÍ' : '❌ NO'));
                
                // ❌ ELIMINAR ESTA LÍNEA - NO marcar como finalizado
                // $finalizado = $this->marcarComoFinalizado($generador_id, $anio);
                
                error_log("✅ Revisión NO finalizada - esperando correcciones del usuario");
                
            } catch (Exception $e) {
                error_log("❌ ERROR en envío de notificación de rechazo: " . $e->getMessage());
            }
        } else {
            error_log("❓ Estado no reconocido para notificación");
        }
        
        error_log("=== FINALIZANDO ENVIO DE NOTIFICACIONES ===");
    }

    // Obtener todas las observaciones para el rechazo
    private function obtenerObservaciones($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT observaciones_mensual, observaciones_accidentes, observaciones_contingencias
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $observaciones = [];
        
        if (!empty($resultado['observaciones_mensual'])) {
            $observaciones[] = "Reporte Mensual: " . $resultado['observaciones_mensual'];
        }
        
        if (!empty($resultado['observaciones_accidentes'])) {
            $observaciones[] = "Capacitaciones y Accidentes: " . $resultado['observaciones_accidentes'];
        }
        
        if (!empty($resultado['observaciones_contingencias'])) {
            $observaciones[] = "Plan de Contingencias: " . $resultado['observaciones_contingencias'];
        }
        
        return implode("\n\n", $observaciones);
    }

    // Marcar revisión como finalizada (bloquear ediciones)
    // Marcar revisión como finalizada (bloquear ediciones) - CORREGIDO
    private function marcarComoFinalizado($generador_id, $anio, $nombre_pdf = null) {
        error_log("Intentando marcar como finalizado: generador_id=$generador_id, anio=$anio");
        error_log("PDF a guardar: " . ($nombre_pdf ?: 'Ninguno'));
        
        $sql = "
            UPDATE revisiones_anuales 
            SET estado_finalizado = 1,
                fecha_finalizacion = NOW(),
                certificado_generado = 1
        ";
        
        // Si hay un PDF, agregar el campo soporte_pdf
        if ($nombre_pdf) {
            $sql .= ", certificado_pdf = ?";
            $params = [$nombre_pdf, $generador_id, $anio];
        } else {
            $params = [$generador_id, $anio];
        }
        
        $sql .= " WHERE generador_id = ? AND anio = ?";
        
        $stmt = $this->conn->prepare($sql);
        $resultado = $stmt->execute($params);
        $filas_afectadas = $stmt->rowCount();
        
        error_log("Resultado update: " . ($resultado ? 'true' : 'false'));
        error_log("Filas afectadas: " . $filas_afectadas);
        
        // Verificar que se actualizó correctamente
        if ($resultado && $filas_afectadas > 0) {
            error_log("✅ Revisión marcada como finalizada correctamente");
            if ($nombre_pdf) {
                error_log("✅ PDF guardado en base de datos: $nombre_pdf");
            }
        } else {
            error_log("❌ Error al marcar como finalizado");
        }
        
        return $resultado;
    }

    // Verificar si la revisión está finalizada (bloqueada)
    public function estaFinalizado($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT estado_finalizado 
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado && $resultado['estado_finalizado'] == 1;
    }   
    
    // Método de debug temporal
    public function debugEstadoFinalizado($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT estado_finalizado, fecha_finalizacion, certificado_generado, estado_general,
                formulario_mensual, formulario_accidentes, formulario_contingencias
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG - Estado finalizado para $generador_id, $anio: " . print_r($resultado, true));
        
        return $resultado;
    }
    // Agrega esta función después de obtenerObservaciones()
    private function actualizarEstadoContingencias($generador_id, $estado) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE contingencias 
                SET estado = ?
                WHERE generador_id = ? 
                AND estado != 'confirmado'  -- No sobreescribir si ya está confirmado
            ");
            $success = $stmt->execute([$estado, $generador_id]);
            
            if ($success) {
                error_log("✅ Estado de contingencias actualizado a: $estado para generador: $generador_id");
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("❌ Error al actualizar estado de contingencias: " . $e->getMessage());
            return false;
        }
    }
    // Agrega esta función al final de la clase
    public function usuarioPuedeEditarFormulario($generador_id, $anio, $tipo_formulario) {
        $estado = $this->obtenerEstadoFormulario($generador_id, $anio, $tipo_formulario);
        
        // ✅ El usuario solo puede editar si el formulario está RECHAZADO
        return ($estado === 'rechazado');
    }

    public function obtenerEstadoParaEdicion($generador_id, $anio) {
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
        return [
            'mensual' => $estados['formulario_mensual'] === 'rechazado',
            'accidentes' => $estados['formulario_accidentes'] === 'rechazado', 
            'contingencias' => $estados['formulario_contingencias'] === 'rechazado'
        ];
    }
}
?>