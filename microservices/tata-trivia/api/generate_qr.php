<?php
require_once __DIR__ . '/../init.php';

// Verificar si tenemos la librería QR Code
function hasQRCodeLibrary() {
    return class_exists('chillerlan\QRCode\QRCode');
}

// Generar código QR
function generateQRCode($data, $size = 300) {
    if (!hasQRCodeLibrary()) {
        return null;
    }
    
    try {
        $qrcode = new chillerlan\QRCode\QRCode();
        return $qrcode->render($data);
    } catch (Exception $e) {
        error_log('Error generating QR: ' . $e->getMessage());
        return null;
    }
}

// Manejar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $joinCode = $_GET['code'] ?? '';
    $size = $_GET['size'] ?? 300;
    
    if (empty($joinCode)) {
        http_response_code(400);
        echo 'Código requerido';
        exit;
    }
    
    // Construir URL de unión
    $joinUrl = "http://" . $_SERVER['HTTP_HOST'] . "/microservices/tata-trivia/player/join?code=" . urlencode($joinCode);
    
    // Generar QR
    $qrImage = generateQRCode($joinUrl, $size);
    
    if ($qrImage) {
        header('Content-Type: image/png');
        echo $qrImage;
    } else {
        // Fallback: generar QR simple con GD
        generateSimpleQR($joinUrl, $size);
    }
} else {
    http_response_code(405);
    echo 'Método no permitido';
}

// Fallback si no hay librería QR
function generateSimpleQR($data, $size = 300) {
    // URL de servicio externo para generar QR (fallback)
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=" . $size . "x" . $size . "&data=" . urlencode($data);
    
    $qrImage = file_get_contents($qrUrl);
    
    if ($qrImage) {
        header('Content-Type: image/png');
        echo $qrImage;
    } else {
        // Último fallback: imagen de error
        header('Content-Type: image/svg+xml');
        echo '<?xml version="1.0"?>
        <svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#f8f9fa"/>
            <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="14" fill="#6c757d">
                QR no disponible
            </text>
        </svg>';
    }
}
?>