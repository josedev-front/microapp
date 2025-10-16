<?php
// ==========================================================
// main.php - Funciones principales y conexión a la base de datos
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------
// CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
// ----------------------------------------------------------
function conexion() {
    static $pdo = null;

    if ($pdo === null) {
        $host = "localhost";
        $dbname = "microapps";    // ← cambia al nombre real de tu base
        $usuario = "root";
        $password = "";
        $charset = "utf8mb4";

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        try {
            $pdo = new PDO($dsn, $usuario, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    return $pdo;
}

// ----------------------------------------------------------
// FUNCIONES DE SEGURIDAD Y UTILIDAD
// ----------------------------------------------------------

function limpiarCadena($cadena) {
    $cadena = trim($cadena);
    $cadena = stripslashes($cadena);
    $cadena = htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
    return $cadena;
}

function redireccionar($url) {
    header("Location: $url");
    exit();
}

// ----------------------------------------------------------
// VALIDAR SESIÓN ACTIVA
// ----------------------------------------------------------
function validarSesion() {
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}

// ----------------------------------------------------------
// OBTENER USUARIO LOGUEADO
// ----------------------------------------------------------
function obtenerUsuarioActual() {
    if (!validarSesion()) return null;

    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM Core_customuser WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetch();
}

// ----------------------------------------------------------
// MENSAJES FLASH (para mostrar alertas temporales)
// ----------------------------------------------------------
function setFlash($tipo, $mensaje) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
// ----------------------------------------------------------
// FUNCIÓN DEBUG (solo en desarrollo)
// ----------------------------------------------------------
function debug($variable, $exit = false) {
    echo "<pre>";
    print_r($variable);
    echo "</pre>";
    if ($exit) exit();
}

// ----------------------------------------------------------
// VERIFICAR CONTRASEÑAS ESTILO DJANGO (PBKDF2-SHA256)
// ----------------------------------------------------------
function verificarPasswordDjango($passwordIngresada, $hashDjango) {
    if (!preg_match('/^pbkdf2_sha256\$(\d+)\$([A-Za-z0-9\/+]+)\$([A-Za-z0-9\/+=]+)$/', $hashDjango, $matches)) {
        return false;
    }

    [$full, $iteraciones, $salt, $hashEsperado] = $matches;

    $hashCalculado = base64_encode(hash_pbkdf2('sha256', $passwordIngresada, $salt, (int)$iteraciones, 32, true));

    return hash_equals($hashEsperado, $hashCalculado);
}
