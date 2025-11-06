<?php
session_start();
session_destroy();
header("Location: vistas/login/login.php");
exit();
?>