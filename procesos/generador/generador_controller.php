<?php
// DEPURACIÓN TEMPORAL - Escribe en /tmp/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugData = "=== " . date('Y-m-d H:i:s') . " ===\n";
    $debugData .= "POST:\n" . print_r($_POST, true) . "\n";
    $debugData .= "FILES:\n" . print_r($_FILES, true) . "\n";
    $debugData .= "SERVER:\n" . print_r($_SERVER, true) . "\n";
    $debugData .= "========================\n\n";
    
    // Escribir en /tmp/ (esto SIEMPRE funciona)
    file_put_contents('/tmp/gestionresiduos_debug.txt', $debugData, FILE_APPEND);
    
    // También crear un archivo único por cada envío
    $uniqueFile = '/tmp/gestionresiduos_form_' . date('Ymd_His') . '.txt';
    file_put_contents($uniqueFile, $debugData);
}

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$conn = null;
require_once __DIR__ . '/../../includes/conexion.php';
if (!$conn) {
    throw new Exception("No se pudo establecer una conexión con la base de datos.");
}
require_once __DIR__ . '/../../procesos/admin/revisiones_controller.php';

class GeneradorController {
    private $conn;
    public $error;
    public $success;

    // ✅ UN SOLO CONSTRUCTOR (con la validación)
    public function __construct($conn) {
        if (!$conn) {
            throw new Exception("No se recibió una conexión válida a la base de datos");
        }
        $this->conn = $conn;
    }

    private function verificarConexion() {
        if (!$this->conn) {
            $this->error = "Error de conexión a la base de datos";
            return false;
        }
        return true;
    }

    public function getTiposGenerador() {
        if (!$this->verificarConexion()) return [];
        
        try {
            $stmt = $this->conn->query("SELECT id as id_sujeto, nom_sujeto FROM categoria ORDER BY nom_sujeto");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = "Error al cargar tipos de generador: " . $e->getMessage();
            return [];
        }
    }
    
    // Método para obtener subcategorías por id_sujeto
    public function getSubcategoriasPorSujeto($id_sujeto) {
        if (!$this->verificarConexion()) return [];
        
        try {
            $stmt = $this->conn->prepare("SELECT id, nom_clase FROM subcategoria WHERE id_sujeto = ? ORDER BY nom_clase");
            $stmt->execute([$id_sujeto]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = "Error al cargar subcategorías: " . $e->getMessage();
            error_log("Error en getSubcategoriasPorSujeto: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerGeneradorPorId($id) {
        if (!$this->verificarConexion()) return null;
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM generador WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->error = "Error al obtener generador: " . $e->getMessage();
            return null;
        }
    }

    public function checkAccess() {
        if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin', 'generador'])) {
            header("Location: ../../vistas/login/acceso_denegado.php");
            exit();
        }
    }

    public function handleRequest() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processForm();
        }
    }

    // Método para validar si puede enviar correcciones
    public function puedeEnviarCorreccion($generador_id, $anio, $tipo_formulario) {
        if (!$this->verificarConexion()) return ['puede_editar' => false, 'mensaje' => 'Error de conexión'];
        
        try {
            $revisionController = new RevisionesController($this->conn);
            
            if ($revisionController->estaFinalizado($generador_id, $anio)) {
                return [
                    'puede_editar' => false,
                    'mensaje' => 'Esta revisión ya ha sido finalizada y no puede ser modificada.'
                ];
            }
            
            if (!$revisionController->puedeReenviarCorreccion($generador_id, $anio)) {
                return [
                    'puede_editar' => false,
                    'mensaje' => 'Has alcanzado el número máximo de intentos de corrección permitidos.'
                ];
            }
            
            $estado = $revisionController->obtenerEstadoFormulario($generador_id, $anio, $tipo_formulario);
            
            if ($estado !== 'rechazado') {
                return [
                    'puede_editar' => false,
                    'mensaje' => 'Este formulario no requiere correcciones en este momento.'
                ];
            }
            
            return [
                'puede_editar' => true,
                'mensaje' => 'Puede realizar correcciones.'
            ];
            
        } catch (Exception $e) {
            error_log("Error en puedeEnviarCorreccion: " . $e->getMessage());
            return [
                'puede_editar' => false,
                'mensaje' => 'Error al verificar permisos de corrección.'
            ];
        }
    }

    // Método para validar envío inicial
    public function puedeEnviarFormulario($generador_id, $anio, $tipo_formulario) {
        if (!$this->verificarConexion()) return ['puede_editar' => false, 'mensaje' => 'Error de conexión'];
        
        try {
            $revisionController = new RevisionesController($this->conn);
            
            if ($revisionController->estaFinalizado($generador_id, $anio)) {
                return [
                    'puede_editar' => false,
                    'mensaje' => 'Esta revisión ya ha sido finalizada y no puede ser modificada.'
                ];
            }
            
            $estado = $revisionController->obtenerEstadoFormulario($generador_id, $anio, $tipo_formulario);
            
            if ($estado === 'pendiente' || $estado === 'sin_datos') {
                return [
                    'puede_editar' => true,
                    'mensaje' => 'Puede enviar el formulario.'
                ];
            }
            
            if ($estado === 'aprobado') {
                return [
                    'puede_editar' => false,
                    'mensaje' => 'Este formulario ya ha sido aprobado y no requiere modificaciones.'
                ];
            }
            
            if ($estado === 'rechazado') {
                return $this->puedeEnviarCorreccion($generador_id, $anio, $tipo_formulario);
            }
            
            return [
                'puede_editar' => false,
                'mensaje' => 'Estado del formulario no reconocido.'
            ];
            
        } catch (Exception $e) {
            error_log("Error en puedeEnviarFormulario: " . $e->getMessage());
            return [
                'puede_editar' => false,
                'mensaje' => 'Error al verificar permisos de envío.'
            ];
        }
    }

    // Función para verificar si ya existe un generador
    private function existeGenerador($nom_generador, $nit, $excluir_id = null) {
        if (!$this->verificarConexion()) return false;
        
        try {
            $sql = "SELECT COUNT(*) as count FROM generador 
                    WHERE nom_generador = ? AND nit = ?";
            
            $params = [$nom_generador, $nit];
            
            if ($excluir_id !== null) {
                $sql .= " AND id != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            $this->error = "Error al verificar duplicados: " . $e->getMessage();
            return false;
        }
    }

    private function processForm() {
        // LÍNEAS DE PRUEBA - INICIO
        file_put_contents('/tmp/1_entra_processForm.txt', date('Y-m-d H:i:s') . " - Entró a processForm\n", FILE_APPEND);
        // LÍNEAS DE PRUEBA - FIN

        if (!$this->verificarConexion()) return;
        
        try {
            // LÍNEA DE PRUEBA
            file_put_contents('/tmp/2_validacion.txt', date('Y-m-d H:i:s') . " - Pasó verificación\n", FILE_APPEND);
            // Validar campos requeridos
            $campos_requeridos = [
                'periodo_reporte', 'nom_generador', 'nit', 
                'tipo_sujeto', 'dir_establecimiento', 'nom_responsable', 'id_comuna'
            ];
            
            foreach ($campos_requeridos as $campo) {
                if (empty($_POST[$campo])) {
                    $this->error = "El campo " . str_replace('_', ' ', $campo) . " es requerido.";
                    return;
                }
            }
            
            $nom_generador = trim($_POST['nom_generador']);
            $nit = trim($_POST['nit']);
            $id_comuna = $_POST['id_comuna'];
            
            // Verificar si estamos actualizando un generador existente
            if (isset($_POST['id_generador']) && is_numeric($_POST['id_generador'])) {
                $id_generador = $_POST['id_generador'];
                
                if ($this->existeGenerador($nom_generador, $nit, $id_generador)) {
                    $this->error = "Ya existe un establecimiento registrado con el nombre '$nom_generador' y NIT '$nit'. Verifique los datos e intente nuevamente.";
                    return;
                }
                
                $stmt = $this->conn->prepare("UPDATE generador SET
                    periodo_reporte = ?, 
                    nom_generador = ?,
                    razon_social = ?,
                    nit = ?, 
                    id_sujeto = ?,
                    tipo_sujeto = ?, 
                    dir_establecimiento = ?, 
                    tel_establecimiento = ?, 
                    nom_responsable = ?, 
                    cargo_responsable = ?,
                    id_comuna = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $_POST['periodo_reporte'],
                    $nom_generador,
                    $_POST['razon_social'],
                    $nit,
                    $_POST['id_sujeto'],
                    $_POST['tipo_sujeto'],
                    $_POST['dir_establecimiento'],
                    $_POST['tel_establecimiento'],
                    $_POST['nom_responsable'],
                    $_POST['cargo_responsable'],
                    $id_comuna,
                    $id_generador
                ]);

                // Después del execute() del INSERT o UPDATE
                file_put_contents('/tmp/3_antes_header.txt', date('Y-m-d H:i:s') . " - ID: " . ($id_generador ?? 'N/A') . "\n", FILE_APPEND);
                file_put_contents('/tmp/4_sesion_set.txt', date('Y-m-d H:i:s') . " - Sesión escrita\n", FILE_APPEND);
                $_SESSION['mensaje_exito'] = "Generador actualizado exitosamente!";
                
            } else {
                if ($this->existeGenerador($nom_generador, $nit)) {
                    $this->error = "Ya existe un establecimiento registrado con el nombre '$nom_generador' y NIT '$nit'. Verifique los datos e intente nuevamente.";
                    return;
                }
                
                $stmt = $this->conn->prepare("INSERT INTO generador (
                    periodo_reporte, 
                    nom_generador, 
                    razon_social,
                    nit,
                    id_sujeto,
                    tipo_sujeto, 
                    dir_establecimiento, 
                    tel_establecimiento, 
                    nom_responsable, 
                    cargo_responsable,
                    id_comuna
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_POST['periodo_reporte'],
                    $nom_generador,
                    $_POST['razon_social'],
                    $nit,
                    $_POST['id_sujeto'], 
                    $_POST['tipo_sujeto'],
                    $_POST['dir_establecimiento'],
                    $_POST['tel_establecimiento'],
                    $_POST['nom_responsable'],
                    $_POST['cargo_responsable'],
                    $id_comuna
                ]);

                $id_generador = $this->conn->lastInsertId();

                if ($_SESSION['usuario_rol'] === 'generador') {
                    $stmt = $this->conn->prepare("INSERT INTO usuario_generador (usuario_id, generador_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['usuario_id'], $id_generador]);
                }

                $_SESSION['mensaje_exito'] = "Generador registrado exitosamente!";
            }

            header("Location: listado_generadores_view.php");
            exit();

        } catch (PDOException $e) {
            $this->error = "Error al procesar el formulario: " . $e->getMessage();
            error_log("Error en processForm: " . $e->getMessage());
        }
    }
    
    // función para obtener los barrios
    public function obtenerBarrios() {
        if (!$this->verificarConexion()) return [];
        
        try {
            $stmt = $this->conn->query("SELECT b.id, b.nom_barrio, b.id_comuna, c.nom_comuna 
                                    FROM barrio b 
                                    JOIN comuna c ON b.id_comuna = c.id 
                                    ORDER BY b.nom_barrio");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = "Error al cargar barrios: " . $e->getMessage();
            return [];
        }
    }
}

// Uso del controlador
try {
    $controller = new GeneradorController($conn);
    $controller->handleRequest();
} catch (Exception $e) {
    error_log("Error inicializando controlador: " . $e->getMessage());
    die("Error de configuración del sistema. Contacte al administrador.");
}
?>