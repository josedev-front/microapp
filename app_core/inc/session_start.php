<?php
// Asegura que la sesión esté iniciada correctamente en todo el proyecto
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Location: ../../public/index.php');
    exit;
}
?>