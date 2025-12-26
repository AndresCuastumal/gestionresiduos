<?php
session_start();
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';
class GeneradoresController {
    private $conn;
    private $generadores = [];
    private $estados_revision = [];
    private $error = null;

    public function __construct($conn) {
        $this->conn = $conn;
        // ✅ NUEVO: Crear instancia del controlador de revisiones
        require_once '../../procesos/admin/revisiones_controller.php';
        $this->revisionesController = new RevisionesController($conn);
    }

    public function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../../vistas/login/login.php");
            exit();
        }
    }

    public function obtenerGeneradores() {
        try {
            if ($_SESSION['usuario_rol'] === 'admin') {
                // Admin ve todos los generadores
                $stmt = $this->conn->query("SELECT * FROM generador ORDER BY nom_generador");
                $this->generadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Usuario normal: usar la tabla de relación
                $sql = "SELECT g.* 
                        FROM generador g
                        INNER JOIN usuario_generador ug ON g.id = ug.generador_id
                        WHERE ug.usuario_id = ?
                        ORDER BY g.nom_generador";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$_SESSION['usuario_id']]);
                $this->generadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $this->error = "Error al cargar generadores: " . $e->getMessage();
            $this->generadores = [];
        }
    }

    public function obtenerEstadosRevision() {
    try {
            // CORRECCIÓN: Obtener el año anterior correctamente
            $anio_anterior = date('Y') - 1;
            error_log("Año anterior calculado: " . $anio_anterior);
            
            if ($_SESSION['usuario_rol'] === 'admin') {
                // Admin: obtener estados de todos los generadores
                $stmt = $this->conn->prepare("SELECT generador_id, estado_general AS estado 
                                        FROM revisiones_anuales 
                                        WHERE anio = ?");
                $stmt->execute([$anio_anterior]);
            } else {
                // Usuario normal: solo sus generadores
                $generadores_ids = array_column($this->generadores, 'id');
                
                if (empty($generadores_ids)) {
                    $this->estados_revision = [];
                    return;
                }
                
                $placeholders = implode(',', array_fill(0, count($generadores_ids), '?'));
                $stmt = $this->conn->prepare("SELECT generador_id, estado_general AS estado 
                                        FROM revisiones_anuales 
                                        WHERE generador_id IN ($placeholders) AND anio = ?");
                
                $params = array_merge($generadores_ids, [$anio_anterior]);
                $stmt->execute($params);
            }
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($resultados as $revision) {
                $this->estados_revision[$revision['generador_id']] = $revision['estado'];
            }
            
        } catch (PDOException $e) {
            error_log("Error al obtener estados de revisión: " . $e->getMessage());
            $this->estados_revision = [];
        }
    }

    public function procesarEliminacion() {
        if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
            try {
                $this->conn->beginTransaction();
                
                // 1. Eliminar de la tabla de relación
                $stmt = $this->conn->prepare("DELETE FROM usuario_generador WHERE generador_id = ?");
                $stmt->execute([$_GET['eliminar']]);
                
                // 2. Eliminar reportes mensuales asociados
                $stmt = $this->conn->prepare("DELETE FROM cantidad_x_mes WHERE id_generador = ?");
                $stmt->execute([$_GET['eliminar']]);
                
                // 3. Eliminar revisiones anuales
                $stmt = $this->conn->prepare("DELETE FROM revisiones_anuales WHERE generador_id = ?");
                $stmt->execute([$_GET['eliminar']]);
                
                // 4. Eliminar generador
                $stmt = $this->conn->prepare("DELETE FROM generador WHERE id = ?");
                $stmt->execute([$_GET['eliminar']]);
                
                $this->conn->commit();
                $_SESSION['mensaje_exito'] = "Generador eliminado correctamente";
                header("Location: listado_generadores_view.php");
                exit();
            } catch (PDOException $e) {
                $this->conn->rollBack();
                $this->error = "Error al eliminar: " . $e->getMessage();
            }
        }
    }

    public function getGeneradores() {
        return $this->generadores;
    }

    public function getEstadosRevision() {
        return $this->estados_revision;
    }

    public function getError() {
        return $this->error;
    }

    public function getClaseEstado($estado) {
    $mapeo_estados = [
        'pendiente' => 'badge-warning',
        'aprobado' => 'badge-success',
        'rechazado' => 'badge-danger'
    ];
    
    return $mapeo_estados[$estado] ?? 'badge-secondary';
 }

    public function getTextoEstado($estado) {
        $mapeo_textos = [
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado', 
            'rechazado' => 'Rechazado'
        ];
        
        return $mapeo_textos[$estado] ?? 'Sin revisión';
    }

    // Agrega esta función después del método getTextoEstado()
    public function obtenerEstadoContingencias($generador_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT estado 
                FROM contingencias 
                WHERE generador_id = ? 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->execute([$generador_id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultado ? $resultado['estado'] : null;
        } catch (PDOException $e) {
            error_log("Error al obtener estado de contingencias: " . $e->getMessage());
            return null;
        }
    }

        public function obtenerCertificadoPdf($generador_id) {
        try {
            $anio_actual = date('Y', strtotime('-1 year')); // Año anterior
            
            $stmt = $this->conn->prepare("
                SELECT certificado_pdf 
                FROM revisiones_anuales 
                WHERE generador_id = ? AND anio = ?
            ");
            $stmt->execute([$generador_id, $anio_actual]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultado['certificado_pdf'] ?? null;
            
        } catch (PDOException $e) {
            error_log("Error al obtener certificado PDF: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Verificar si el generador puede corregir formularios rechazados
     */
    public function puedeCorregirFormularios($generador_id) {
        try {
            $anio_actual = date('Y') - 1; // Año anterior
            error_log("🔍 Verificando puedeCorregirFormularios para generador_id: $generador_id, año: $anio_actual");
            
            // 1. Verificar si hay revisión para este año
            $stmt = $this->conn->prepare("
                SELECT ra.*, COUNT(*) as intentos_restantes
                FROM revisiones_anuales ra
                WHERE ra.generador_id = ? AND ra.anio = ?
            ");
            $stmt->execute([$generador_id, $anio_actual]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 Resultado de consulta revisión: " . ($revision ? 'ENCONTRADO' : 'NO ENCONTRADO'));

            if (!$revision) {
                error_log("❌ No hay revisión para este año");
                return false; // No hay revisión para este año
            }
            
            // 2. Verificar que todos los formularios estén revisados
            $todosRevisados = (
                $revision['formulario_mensual'] !== 'pendiente' && 
                $revision['formulario_mensual'] !== 'sin_datos' &&
                $revision['formulario_accidentes'] !== 'pendiente' && 
                $revision['formulario_accidentes'] !== 'sin_datos' &&
                $revision['formulario_contingencias'] !== 'pendiente' && 
                $revision['formulario_contingencias'] !== 'sin_datos'
            );
            error_log("🔍 ¿Todos revisados?: " . ($todosRevisados ? 'SÍ' : 'NO'));

            if (!$todosRevisados) {
                error_log("❌ Aún no se han revisado todos los formularios");
                return false; // Aún no se han revisado todos los formularios
            }
            
            // 3. Verificar que el estado general sea "rechazado"
            error_log("🔍 Estado general: " . ($revision['estado_general'] ?? 'NO DEFINIDO'));
            if ($revision['estado_general'] !== 'rechazado') {
                error_log("❌ Estado general NO es 'rechazado'");
                return false; // No hay rechazos que corregir
            }
            
            // 4. Verificar que no haya excedido los intentos
            error_log("🔍 Intentos: " . ($revision['intentos_correccion'] ?? 0) . "/" . ($revision['max_intentos_permitidos'] ?? 1));
            if ($revision['intentos_correccion'] >= $revision['max_intentos_permitidos']) {
                error_log("❌ Ya excedió los intentos permitidos");
                return false; // Ya usó todos los intentos
            }
            
            // 5. Verificar que no esté finalizado
             error_log("🔍 ¿Está finalizado?: " . ($revision['estado_finalizado'] == 1 ? 'SÍ' : 'NO'));
            if ($revision['estado_finalizado'] == 1) {
                error_log("❌ Ya está finalizado");
                return false; // Ya está finalizado
            }
            error_log("✅ PUEDE corregir - todas las verificaciones pasaron");
            return true;
            
        } catch (PDOException $e) {
            error_log("❌ ERROR en puedeCorregirFormularios: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información detallada de la revisión para mostrar al usuario
     */
    public function obtenerInfoRechazos($generador_id) {
        try {
            $anio_actual = date('Y') - 1;
            
            $stmt = $this->conn->prepare("
                SELECT 
                    formulario_mensual,
                    formulario_accidentes,
                    formulario_contingencias,
                    estado_general,
                    intentos_correccion,
                    max_intentos_permitidos,
                    fecha_ultimo_rechazo,
                    observaciones_mensual,
                    observaciones_accidentes,
                    observaciones_contingencias
                FROM revisiones_anuales 
                WHERE generador_id = ? AND anio = ?
            ");
            $stmt->execute([$generador_id, $anio_actual]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revision) {
                return null;
            }
            
            // Determinar qué formularios están rechazados
            $formularios_rechazados = [];
            
            if ($revision['formulario_mensual'] === 'rechazado') {
                $formularios_rechazados[] = [
                    'tipo' => 'mensual',
                    'nombre' => 'Reporte Mensual',
                    'observaciones' => $revision['observaciones_mensual']
                ];
            }
            
            if ($revision['formulario_accidentes'] === 'rechazado') {
                $formularios_rechazados[] = [
                    'tipo' => 'accidentes',
                    'nombre' => 'Capacitaciones y Accidentes',
                    'observaciones' => $revision['observaciones_accidentes']
                ];
            }
            
            if ($revision['formulario_contingencias'] === 'rechazado') {
                $formularios_rechazados[] = [
                    'tipo' => 'contingencias',
                    'nombre' => 'Plan de Contingencias',
                    'observaciones' => $revision['observaciones_contingencias']
                ];
            }
            
            return [
                'revision' => $revision,
                'formularios_rechazados' => $formularios_rechazados,
                'puede_corregir' => $this->puedeCorregirFormularios($generador_id)
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener info de rechazos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Reenviar formularios para corrección
     */
    public function reenviarParaCorreccion($generador_id) {
        try {
            $anio_actual = date('Y') - 1;
            
            error_log("🔧 INICIANDO reenviarParaCorreccion()");
            error_log("🔧 generador_id: $generador_id, año: $anio_actual");

            // Verificar que puede corregir
            if (!$this->puedeCorregirFormularios($generador_id)) {
                error_log("❌ NO puede corregir - permiso denegado");
                return ['success' => false, 'message' => 'No tiene permiso para corregir'];
            }

            error_log("✅ PUEDE corregir - procediendo...");
            
            // ✅ CORRECCIÓN: Usar el método del controlador de revisiones
            // Este método YA existe en RevisionesController
            $incrementado = $this->revisionesController->incrementarIntentoCorreccion($generador_id, $anio_actual);
             error_log("🔧 Resultado de incrementarIntentoCorreccion: " . ($incrementado ? 'TRUE' : 'FALSE'));

            if (!$incrementado) {
                return ['success' => false, 'message' => 'Error al incrementar intentos'];
            }
            
            // 2. Cambiar formularios rechazados a "pendiente"
            $revision = $this->obtenerInfoRechazos($generador_id);
            
            if ($revision && !empty($revision['formularios_rechazados'])) {
                $updates = [];
                $params = [];
                
                foreach ($revision['formularios_rechazados'] as $formulario) {
                    $campo = "formulario_{$formulario['tipo']}";
                    $updates[] = "$campo = 'pendiente'";
                }
                
                if (!empty($updates)) {
                    $sql = "UPDATE revisiones_anuales SET " . implode(", ", $updates);
                    $sql .= ", estado_general = 'pendiente'";
                    $sql .= ", fecha_revision = NULL";
                    $sql .= " WHERE generador_id = ? AND anio = ?";
                    
                    $params[] = $generador_id;
                    $params[] = $anio_actual;
                    
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute($params);
                }
            }
            
            return ['success' => true, 'message' => 'Correcciones enviadas correctamente'];
            
        } catch (PDOException $e) {
            error_log("Error al reenviar correcciones: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar correcciones'];
        }
    }
}

// Uso del controlador
$controller = new GeneradoresController($conn);
$controller->verificarSesion();
$controller->procesarEliminacion();
$controller->obtenerGeneradores();
$controller->obtenerEstadosRevision(); // Nueva línea importante

$generadores = $controller->getGeneradores();
$estados_revision = $controller->getEstadosRevision(); // Obtener los estados
$error = $controller->getError();
?>