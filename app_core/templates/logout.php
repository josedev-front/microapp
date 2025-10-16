<?php
require_once __DIR__ . '/../php/main.php';

// Destruir completamente la sesión
session_unset();
session_destroy();

// Redireccionar al login
header('Location: ./?vista=login');
exit;