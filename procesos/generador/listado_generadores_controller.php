<?php
session_start();
require_once '../../includes/conexion.php';

class GeneradoresController {
    private $conn;
    private $generadores = [];
    private $estados_revision = [];
    private $error = null;

    public function __construct($conn) {
        $this->conn = $conn;
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
                $stmt = $this->conn->query("SELECT * FROM GENERADOR ORDER BY nom_generador");
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