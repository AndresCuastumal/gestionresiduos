<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
class RevisionesController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // ========== MÉTODOS PARA CONTROL DE INTENTOS ==========
    /**
     * Verificar si el generador puede reenviar correcciones
     * MODIFICADO: Solo 1 intento permitido
     */
    public function puedeReenviarCorreccion($generador_id, $anio) {
        $info = $this->obtenerInfoIntentos($generador_id, $anio);
        
        if (!$info) {
            return true; // Si no existe revisión, permitir
        }
        
        // ✅ MODIFICADO: Solo 1 intento permitido
        // Si ya tiene 1 o más intentos, NO puede reenviar
        if ($info['intentos_correccion'] >= 2) {
            error_log("❌ LÍMITE ALCANZADO - Intentos: " . $info['intentos_correccion'] . ", Máximo: 2");
            return false;
        }
        
        error_log("🔍 puedeReenviarCorreccion - Verificando:");
        error_log("🔍 Intentos: " . $info['intentos_correccion']);
        error_log("🔍 Máximo: 1 (configuración fija)");
        error_log("🔍 Estado general: " . $info['estado_general']);
        
        return true;
    }
    
    /**
     * Incrementar contador de intentos cuando se envía notificación de rechazo
     */
    public function incrementarIntentoCorreccion($generador_id, $anio) {
        $sql = "UPDATE revisiones_anuales 
                SET intentos_correccion = intentos_correccion + 1, 
                    fecha_ultimo_rechazo = NOW() 
                WHERE generador_id = ? AND anio = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$generador_id, $anio]);

        error_log("📈 INTENTO INCREMENTADO para generador_id: $generador_id, año: $anio");
        error_log("Filas afectadas: " . $stmt->rowCount());
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obtener información de intentos de corrección
     */
    // En revisiones_controller.php, en el método obtenerInfoIntentos():
    public function obtenerInfoIntentos($generador_id, $anio) {
        try {
            // Verificar que $this->conn existe
            if (!$this->conn) {
                error_log("ERROR: Conexión a BD no disponible en RevisionesController");
                return $this->getValoresPorDefecto();
            }
            
            $stmt = $this->conn->prepare("SELECT 
                intentos_correccion, estado_general,
                fecha_ultimo_rechazo
                FROM revisiones_anuales 
                WHERE generador_id = ? AND anio = ?");
            
            $stmt->execute([$generador_id, $anio]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                // ✅ Agregar valor por defecto para max_intentos_permitidos
                $info['max_intentos_permitidos'] = 1; // Valor por defecto
            }
            
            return $info ?: $this->getValoresPorDefecto();
            
        } catch (Exception $e) {
            error_log("Error al obtener info de rechazos: " . $e->getMessage());
            return $this->getValoresPorDefecto();
        }
    }

    private function getValoresPorDefecto() {
        return [
            'intentos_correccion' => 0,
            'max_intentos_permitidos' => 1,
            'fecha_ultimo_rechazo' => null
        ];
    }
    
    /**
     * Verificar si ya se incrementaron intentos para este ciclo de corrección
     */
    public function yaSeIncrementoIntento($generador_id, $anio) {
        $sql = "SELECT intentos_correccion, fecha_ultimo_rechazo 
                FROM revisiones_anuales 
                WHERE generador_id = ? AND anio = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$generador_id, $anio]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$info) {
            return false;
        }
        
        // Si ya tiene intentos > 0 y tiene fecha de último rechazo RECIENTE
        // (mismo día), entonces ya se incrementó para este ciclo
        if ($info['intentos_correccion'] > 0 && $info['fecha_ultimo_rechazo']) {
            $hoy = date('Y-m-d');
            $fecha_rechazo = date('Y-m-d', strtotime($info['fecha_ultimo_rechazo']));
            
            // Si el último rechazo fue hoy, ya se incrementó
            return ($fecha_rechazo === $hoy);
        }
        
        return false;
    }
    
    // ========== MÉTODOS PARA ACTUALIZAR REVISIONES ==========
    
    /**
     * Actualizar estado de revisión del formulario mensual
     * MODIFICADO: Agregar validación de intentos cuando se rechaza
     */
    public function actualizarRevision($data) {
        // Verificar si está finalizado
        if ($this->estaFinalizado($data['generador_id'], $data['anio'])) {
            throw new Exception("Esta revisión ya ha sido finalizada y no puede ser modificada.");
        }
        
        // Asegurar que el registro exista
        $this->crearRevisionSiNoExiste($data['generador_id'], $data['anio']);
        
        // ✅ NUEVO: Si se va a rechazar, verificar que el usuario aún tenga intentos
        if ($data['formulario_mensual'] === 'rechazado') {
            $infoIntentos = $this->obtenerInfoIntentos($data['generador_id'], $data['anio']);
            if ($infoIntentos['intentos_correccion'] >= 2) {
                throw new Exception("El generador ya ha agotado su oportunidad de corrección. No se puede rechazar nuevamente.");
            }
        }
        
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
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es necesario enviar notificaciones
            $this->verificarNotificaciones($data['generador_id'], $data['anio']);
        }        
        return $success;
    }
    
    /**
     * Actualizar estado de revisión de accidentes
     */
    public function actualizarRevisionAccidentes($data) {
        error_log("=== ACTUALIZANDO ACCIDENTES VIA WEB ===");
        error_log("Datos recibidos: " . print_r($data, true));

        // ✅ NUEVO: Si se va a rechazar, verificar que el usuario aún tenga intentos
        if ($data['formulario_accidentes'] === 'rechazado') { // Cambiar según el método
            $infoIntentos = $this->obtenerInfoIntentos($data['generador_id'], $data['anio']);
            if ($infoIntentos['intentos_correccion'] >= 2) {
                throw new Exception("El generador ya ha agotado su oportunidad de corrección. No se puede rechazar nuevamente.");
            }
        }
        
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
            
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es necesario enviar notificaciones
            $this->verificarNotificaciones($data['generador_id'], $data['anio']);
        } 
        else {
            error_log("❌ Error en la actualización de accidentes");
        }
        
        return $success;
    }
    
    /**
     * Actualizar estado de revisión del formulario de contingencias
     */
    public function actualizarRevisionContingencias($data) {
        error_log("=== ACTUALIZANDO CONTINGENCIAS VIA WEB ===");
        error_log("Datos recibidos: " . print_r($data, true));

        // ✅ NUEVO: Si se va a rechazar, verificar que el usuario aún tenga intentos
        if ($data['formulario_contingencias'] === 'rechazado') { // Cambiar según el método
            $infoIntentos = $this->obtenerInfoIntentos($data['generador_id'], $data['anio']);
            if ($infoIntentos['intentos_correccion'] >= 2) {
                throw new Exception("El generador ya ha agotado su oportunidad de corrección. No se puede rechazar nuevamente.");
            }
        }
        
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
            
            // Actualizar el estado general automáticamente
            $this->actualizarEstadoGeneralAutomatico($data['generador_id'], $data['anio']);
            
            // Verificar si es necesario enviar notificaciones
            $this->verificarNotificaciones($data['generador_id'], $data['anio']);
        } else {
            error_log("❌ Error en la actualización de contingencias");
        }
        
        return $success;
    }
    
    // ========== MÉTODOS PARA VERIFICAR Y ENVIAR NOTIFICACIONES ==========
    
    /**
     * Método principal para verificar y enviar notificaciones
     */
    private function verificarNotificaciones($generador_id, $anio) {
        error_log("🎯 === VERIFICANDO SI ES NECESARIO ENVIAR NOTIFICACIONES ===");
        
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
        }
        
        error_log("🎯 === FIN VERIFICACIÓN ===");
    }
    
    /**
     * Enviar notificaciones (aprobación o rechazo) según el estado
     */
    /**
     * Enviar notificaciones (aprobación o rechazo) según el estado
     */
    public function enviarNotificaciones($generador_id, $anio) {
        error_log("=== INICIANDO ENVIO DE NOTIFICACIONES ===");
        
        // RUTAS ABSOLUTAS CORRECTAS
        $pdfControllerPath = __DIR__ . '/certificado_pdf_controller.php';
        $emailControllerPath = __DIR__ . '/email_controller.php';
        
        if (!file_exists($pdfControllerPath) || !file_exists($emailControllerPath)) {
            error_log("❌ ERROR: Archivos de controlador no encontrados");
            return false;
        }
        
        require_once $pdfControllerPath;
        require_once $emailControllerPath;
        
        $pdfController = new CertificadoPdfController($this->conn);
        $emailController = new EmailController($this->conn);
        
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        $observaciones = $this->obtenerObservaciones($generador_id, $anio);

        error_log("Estados para notificación: " . print_r($estados, true));
        
        // ✅ SI TODOS ESTÁN APROBADOS - enviar certificado y finalizar
        if ($this->verificarFormulariosCompletos($generador_id, $anio)) {
            error_log("✅ TODOS APROBADOS - Generando certificado...");
            
            try {
                $resultado_pdf = $pdfController->generarCertificadoAprobacion($generador_id, $anio);
                $ruta_pdf = $resultado_pdf['ruta_completa'];
                $nombre_pdf = $resultado_pdf['nombre_archivo'];
                
                if (!file_exists($ruta_pdf)) {
                    throw new Exception("El archivo PDF no se encontró en: $ruta_pdf");
                }
                
                // Enviar email con certificado
                $email_enviado = $emailController->enviarCertificadoAprobacion($generador_id, $anio, $ruta_pdf);
                error_log("📧 Email de aprobación enviado: " . ($email_enviado ? '✅ SÍ' : '❌ NO'));
                
                // Marcar como finalizado
                $finalizado = $this->marcarComoFinalizado($generador_id, $anio, $nombre_pdf);
                error_log("📝 Marcado como finalizado: " . ($finalizado ? '✅ SÍ' : '❌ NO'));
                
            } catch (Exception $e) {
                error_log("❌ ERROR en generación de certificado: " . $e->getMessage());
            }
        } 
        // ✅ SI HAY RECHAZOS - enviar notificación de correcciones
        elseif ($estados['formulario_mensual'] === 'rechazado' || 
                $estados['formulario_accidentes'] === 'rechazado' || 
                $estados['formulario_contingencias'] === 'rechazado') {
            
            error_log("⚠️ HAY RECHAZOS - Enviando notificación de correcciones...");
            
            try {
                // ✅ CRÍTICO: INCREMENTAR INTENTOS SOLO CUANDO SE ENVÍA EL CORREO DE RECHAZO
                // Verificar que no se haya incrementado ya para este ciclo
                if (!$this->yaSeIncrementoIntento($generador_id, $anio)) {
                    $infoActual = $this->obtenerInfoIntentos($generador_id, $anio);
                    
                    if ($infoActual['intentos_correccion'] < 2) {
                        error_log("📈 INCREMENTANDO INTENTOS (" . ($infoActual['intentos_correccion'] + 1) . " de 2)");
                        $incrementado = $this->incrementarIntentoCorreccion($generador_id, $anio);
                        error_log("✅ Intentos incrementados: " . ($incrementado ? 'SÍ' : 'NO'));
                        
                        $infoActualizada = $this->obtenerInfoIntentos($generador_id, $anio);
                        error_log("📊 Estado actual: " . $infoActualizada['intentos_correccion'] . " de 2 intentos utilizados");
                    } else {
                        error_log("❌ No se puede incrementar - Ya alcanzó el límite de 2 intentos");
                        // Podrías enviar un email diferente aquí
                    }
                }
                
                // Enviar notificación de rechazo
                $email_enviado = $emailController->enviarNotificacionRechazo($generador_id, $anio, $observaciones);
                
                if ($email_enviado) {
                    error_log("📧 Email de rechazo enviado exitosamente");
                    error_log("⚠️ IMPORTANTE: El generador ha usado su única oportunidad de corrección");
                } else {
                    error_log("❌ Falló el envío del email de rechazo");
                }
                
                // NO finalizar - esperar correcciones del usuario
                error_log("✅ Revisión NO finalizada - esperando correcciones del usuario");
                
            } catch (Exception $e) {
                error_log("❌ ERROR en envío de notificación de rechazo: " . $e->getMessage());
            }
        } else {
            error_log("❓ Estado no reconocido para notificación");
        }
        
        error_log("=== FINALIZANDO ENVIO DE NOTIFICACIONES ===");
    }
    
    // ========== MÉTODOS DE APOYO ==========
    
    /**
     * Obtener todas las observaciones para el rechazo
     */
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
    
    /**
     * Marcar revisión como finalizada (bloquear ediciones)
     */
    private function marcarComoFinalizado($generador_id, $anio, $nombre_pdf = null) {
        error_log("🎯 === MARCANDO COMO FINALIZADO ===");
        error_log("🎯 generador_id: $generador_id, anio: $anio");
        error_log("🎯 nombre_pdf: " . ($nombre_pdf ?: 'Ninguno'));
        
        $sql = "
            UPDATE revisiones_anuales 
            SET estado_finalizado = 1,
                fecha_finalizacion = NOW(),
                certificado_generado = 1
        ";
        
        if ($nombre_pdf) {
            $sql .= ", certificado_pdf = ?";
            $params = [$nombre_pdf, $generador_id, $anio];
        } else {
            $params = [$generador_id, $anio];
        }
        
        $sql .= " WHERE generador_id = ? AND anio = ?";
        
        error_log("🎯 SQL: $sql");
        
        $stmt = $this->conn->prepare($sql);
        $resultado = $stmt->execute($params);
        $filas_afectadas = $stmt->rowCount();
        
        error_log("🎯 Resultado: " . ($resultado ? 'true' : 'false'));
        error_log("🎯 Filas afectadas: $filas_afectadas");
        
        return $resultado;
    }
    
    /**
     * Actualizar estado general automáticamente
     */
    public function actualizarEstadoGeneralAutomatico($generador_id, $anio) {
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
        $estado_general = $this->calcularEstadoGeneral(
            $estados['formulario_mensual'],
            $estados['formulario_accidentes'],
            $estados['formulario_contingencias']
        );
        
        $this->actualizarEstadoGeneral($generador_id, $anio, $estado_general);
    }
    
    /**
     * Calcular estado general basado en los tres formularios
     */
    private function calcularEstadoGeneral($mensual, $accidentes, $contingencias) {
        if ($mensual === 'rechazado' || $accidentes === 'rechazado' || $contingencias === 'rechazado') {
            return 'rechazado';
        }
        
        if ($mensual === 'aprobado' && $accidentes === 'aprobado' && $contingencias === 'aprobado') {
            return 'aprobado';
        }
        
        return 'pendiente';
    }
    
    /**
     * Verificar si todos los formularios están aprobados
     */
    public function verificarFormulariosCompletos($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT formulario_mensual, formulario_accidentes, formulario_contingencias
            FROM revisiones_anuales
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($revision['formulario_mensual'] === 'aprobado' &&
                $revision['formulario_accidentes'] === 'aprobado' &&
                $revision['formulario_contingencias'] === 'aprobado');
    }
    
    /**
     * Actualizar el estado general de la revisión
     */
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
    
    // ========== MÉTODOS DE CONSULTA ==========
    
    /**
     * Obtener todas las revisiones pendientes
     */
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
    
    /**
     * Obtener revisión específica
     */
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
    
    /**
     * Obtener revisiones con filtros - SOLO para generadores que reportaron datos en 2024
     */
    public function obtenerRevisionesConFiltros($tipo_sujeto = '', $estado_general = '', $busqueda = '') {
        
        $anio_revision = date('Y') - 1; // Año anterior
        $sql = "
            SELECT 
                r.*, 
                g.nom_generador, 
                g.nom_responsable, 
                g.tipo_sujeto, 
                s.nom_clase AS nom_tipo,
                g.nit
            FROM revisiones_anuales r
            JOIN generador g ON r.generador_id = g.id
            JOIN subcategoria s ON g.tipo_sujeto = s.id
            WHERE r.anio = ?
            AND r.formulario_mensual != 'sin_datos'
            AND r.formulario_accidentes != 'sin_datos' 
            AND r.formulario_contingencias != 'sin_datos'
        ";
        
        $params = [$anio_revision];
        
        if (!empty($tipo_sujeto)) {
            $sql .= " AND g.tipo_sujeto = ?";
            $params[] = $tipo_sujeto;
        }
        
        if (!empty($estado_general)) {
            $sql .= " AND r.estado_general = ?";
            $params[] = $estado_general;
        }

        // FORZAR LA BÚSQUEDA PARA DEPURAR
        error_log("=== APLICANDO FILTRO DE BÚSQUEDA ===");
        error_log("Valor de busqueda antes de procesar: '" . $busqueda . "'");
        
        if (!empty($busqueda)) {
            $sql .= " AND (g.nom_generador LIKE ? OR g.nit LIKE ?)";
            $busquedaParam = "%" . $busqueda . "%";
            $params[] = $busquedaParam;
            $params[] = $busquedaParam;
            
            error_log("SQL después de agregar búsqueda: " . $sql);
            error_log("Parámetros a ejecutar: " . print_r($params, true));
            error_log("busquedaParam: " . $busquedaParam);
        } else {
            error_log("⚠️ BÚSQUEDA ESTÁ VACÍA - No se aplicará filtro");
        }
        
        $sql .= " ORDER BY g.nom_generador ASC, r.fecha_revision DESC";
        
        error_log("SQL FINAL: " . $sql);
        
        $stmt = $this->conn->prepare($sql);
        
        // Depuración adicional: muestra los bindings
        if (!empty($params)) {
            error_log("Ejecutando con " . count($params) . " parámetros");
            for ($i = 0; $i < count($params); $i++) {
                error_log("Parámetro " . ($i+1) . ": '" . $params[$i] . "'");
            }
        }
        
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            error_log("❌ ERROR en execute: " . print_r($stmt->errorInfo(), true));
        }
        
        $revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Resultados encontrados: " . count($revisiones));
        
        return $revisiones;
    }
    
    /**
     * Verificar si existe registro de revisión para un generador y año
     */
    public function existeRevision($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM revisiones_anuales 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Crear registro de revisión si no existe
     */
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
    
    /**
     * Obtener tipos de sujeto únicos para el filtro
     */
    public function obtenerTiposSujeto() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT g.tipo_sujeto, s.nom_clase AS nom_tipo 
            FROM generador g
            JOIN subcategoria s ON g.tipo_sujeto = s.id 
            WHERE g.tipo_sujeto IS NOT NULL 
            ORDER BY nom_tipo ASC
        ");
        $stmt->execute();
        
        $tipos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tipos[$row['tipo_sujeto']] = $row['nom_tipo'];
        }
        return $tipos;
    }
    
    /**
     * Método para verificar si un formulario tiene datos
     */
    public function formularioTieneDatos($generador_id, $anio, $tipo_formulario) {
        switch ($tipo_formulario) {
            case 'mensual':
                $stmt = $this->conn->prepare("
                    SELECT soporte_pdf 
                    FROM revisiones_anuales 
                    WHERE generador_id = ? AND anio = ?
                ");
                $stmt->execute([$generador_id, $anio]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return ($resultado && !empty($resultado['soporte_pdf']));
                    
            case 'accidentes':
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM reporte_anual_adicional 
                    WHERE generador_id = ? AND anio = ?
                ");
                $stmt->execute([$generador_id, $anio]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $resultado['total'] > 0;
                    
            case 'contingencias':
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
    
    /**
     * Método para obtener el estado actual del formulario
     */
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
    
    /**
     * Obtener el estado de los tres formularios para un generador y año específico
     */
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
    
    /**
     * Obtener datos básicos del generador
     */
    public function obtenerDatosGenerador($generador_id) {
        $stmt = $this->conn->prepare("
            SELECT id, nom_generador, nit, dir_establecimiento, tipo_sujeto, nom_responsable
            FROM generador
            WHERE id = ?
        ");
        $stmt->execute([$generador_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Determinar a qué formulario redirigir después de guardar
     */
    public function determinarSiguienteFormulario($generador_id, $anio) {
        // Obtener el estado actual de todos los formularios
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
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
        
        // Por defecto, volver al listado
        return "listado_revisiones_view.php";
    }
    
    /**
     * Verificar si la revisión está finalizada (bloqueada)
     */
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
    
    /**
     * Verificar si el generador puede editar/corregir un formulario específico
     */
    public function generadorPuedeCorregirFormulario($generador_id, $anio, $tipo_formulario) {
        // 1. Verificar que no esté finalizado
        if ($this->estaFinalizado($generador_id, $anio)) {
            return false;
        }
        
        // 2. Verificar que tenga intentos disponibles
        if (!$this->puedeReenviarCorreccion($generador_id, $anio)) {
            return false;
        }
        
        // 3. Verificar que el formulario específico esté rechazado
        $estado = $this->obtenerEstadoFormulario($generador_id, $anio, $tipo_formulario);
        return ($estado === 'rechazado');
    }
    
    /**
     * Obtener estado de formularios para edición
     */
    public function obtenerEstadoParaEdicion($generador_id, $anio) {
        $estados = $this->obtenerEstadoFormularios($generador_id, $anio);
        
        return [
            'mensual' => $estados['formulario_mensual'] === 'rechazado',
            'accidentes' => $estados['formulario_accidentes'] === 'rechazado', 
            'contingencias' => $estados['formulario_contingencias'] === 'rechazado'
        ];
    }
    
    // ========== MÉTODOS DEPRECADOS (mantenidos por compatibilidad) ==========
    
    /**
     * @deprecated Use puedeReenviarCorreccion() en su lugar
     */
    public function usuarioPuedeEditarFormulario($generador_id, $anio, $tipo_formulario) {
        return $this->generadorPuedeCorregirFormulario($generador_id, $anio, $tipo_formulario);
    }
}
?>