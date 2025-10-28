<?php
require_once '../../includes/conexion.php';
header('Content-Type: application/json');

try {
    if (isset($_GET['id_sujeto']) && is_numeric($_GET['id_sujeto'])) {
        $id_sujeto = (int)$_GET['id_sujeto'];
        
        $stmt = $conn->prepare("SELECT id, nom_clase FROM subcategoria WHERE id_sujeto = ? ORDER BY nom_clase");
        $stmt->execute([$id_sujeto]);
        $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($subcategorias);
    } else {
        echo json_encode(['error' => 'ID de sujeto no válido']);
    }
} catch (PDOException $e) {
    error_log("Error en obtener_subcategorias.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar subcategorías']);
}
?>