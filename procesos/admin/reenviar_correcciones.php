<?php
require_once '../../includes/conexion.php';
require_once '../../procesos/admin/revisiones_controller.php';

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'generador') {
    header("Location: ../login/login.php");
    exit();
}

if (!isset($_GET['generador_id']) || !isset($_GET['anio'])) {
    header("Location: ../generador/dashboard.php");
    exit();
}

$generador_id = $_GET['generador_id'];
$anio = $_GET['anio'];

$revisionController = new RevisionesController($conn);

try {
    if ($revisionController->usuarioReenviaCorrecciones($generador_id, $anio)) {
        $_SESSION['success'] = "Correcciones enviadas correctamente. El administrador revisará los cambios.";
    } else {
        $_SESSION['error'] = "No se pudieron enviar las correcciones.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../generador/dashboard.php");
exit();
?>