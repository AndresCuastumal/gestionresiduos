<?php
session_start();
require_once '../../includes/conexion.php';

class GeneradorController {
    private $conn;
    public $error;
    public $success;

    public function getTiposGenerador() {
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
        try {
            $stmt = $this->conn->prepare("SELECT * FROM GENERADOR WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = "Error al obtener generador: " . $e->getMessage();
            return [];
        }
    }

    public function __construct($conn) {
        $this->conn = $conn;
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

    // Función para verificar si ya existe un generador con el mismo nombre y NIT
    private function existeGenerador($nom_generador, $nit, $excluir_id = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM GENERADOR 
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
    try {
        // Validar campos requeridos (agregar id_comuna)
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
            
                // Verificar si ya existe otro generador con el mismo nombre y NIT
                if ($this->existeGenerador($nom_generador, $nit, $id_generador)) {
                    $this->error = "Ya existe un establecimiento registrado con el nombre '$nom_generador' y NIT '$nit'. Verifique los datos e intente nuevamente.";
                    return;
                }
                
                // Actualizar generador existente (agregar id_comuna)
                $stmt = $this->conn->prepare("UPDATE GENERADOR SET
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

                $_SESSION['mensaje_exito'] = "Generador actualizado exitosamente!";
                
            } else {
                // Crear nuevo generador - Verificar si ya existe
                if ($this->existeGenerador($nom_generador, $nit)) {
                    $this->error = "Ya existe un establecimiento registrado con el nombre '$nom_generador' y NIT '$nit'. Verifique los datos e intente nuevamente.";
                    return;
                }
                
                // Crear nuevo generador (agregar id_comuna)
                $stmt = $this->conn->prepare("INSERT INTO GENERADOR (
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

                // Asociar el generador al usuario en la tabla de relación
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
        }
    }
    // función para obtener los barrios
    public function obtenerBarrios() {
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
$controller = new GeneradorController($conn);
$controller->handleRequest();
?>