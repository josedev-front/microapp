<?php
// app_core/config/helpers.php

// Definir constantes de rutas
define('BASE_URL', 'http://localhost:3000/');
define('ASSETS_URL', BASE_URL . 'public/assets/');
define('APP_ROOT', dirname(__DIR__));

// Función para generar URLs
function url($vista = '') {
    return BASE_URL . '?vista=' . $vista;
}

// Función para incluir assets
function asset($path) {
    return ASSETS_URL . $path;
}

// Función para debug
function debug($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

// Función para log de errores personalizado
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - " . json_encode($context);
    }
    error_log($logMessage);
}

// Función para sanitizar datos de formularios
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para formatear fecha
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

function obtenerUsuarioActual() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        // Aquí tu lógica para obtener el usuario de la base de datos
        $pdo = conexion();
        $stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    return null;
}

// Mover los use statements al principio del archivo, fuera de cualquier función
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// En helpers.php - función corregida
function enviarEmailRecuperacion($email, $nombre, $reset_link) {
    // Verificar si PHPMailer está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Intentar cargar manualmente si no está via Composer
        $phpmailer_path = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($phpmailer_path)) {
            require_once $phpmailer_path;
        } else {
            // Cargar PHPMailer manualmente
            require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../../phpmailer/src/SMTP.php';
            require_once __DIR__ . '/../../phpmailer/src/Exception.php';
        }
    }

    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'mail.jjdevelopers.net';  // Tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'microapps@jjdevelopers.net';  // Tu email completo
        $mail->Password = 'Micr0-buff';  // Tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Encryption
        $mail->Port = 587;  // Puerto para TLS
        
        // Configuración del remitente y destinatario
        $mail->setFrom('microapps@jjdevelopers.net', 'MicroApps System');
        $mail->addAddress($email, $nombre);
        $mail->addReplyTo('microapps@jjdevelopers.net', 'MicroApps Support');
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = 'Recuperacion de Clave - MicroApps';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Arial', sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px;
                    background: #f5f5f5;
                }
                .container {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center;
                }
                .content { 
                    padding: 30px; 
                }
                .button { 
                    background: #667eea; 
                    color: white; 
                    padding: 15px 30px; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    display: inline-block; 
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 20px; 
                    padding: 20px; 
                    color: #666; 
                    font-size: 12px;
                    border-top: 1px solid #eee;
                }
                .warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 MicroApps</h2>
                    <p>Recuperacion de clave</p>
                </div>
                <div class='content'>
                    <h3>Hola {$nombre},</h3>
                    <p>Has solicitado restablecer tu contraseña en <strong>MicroApps</strong>.</p>
                    <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>🔑 Restablecer Contraseña</a>
                    </div>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='background: #f8f9fa; padding: 15px; border-radius: 5px; word-break: break-all; font-family: monospace;'>
                        {$reset_link}
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Este enlace expirará en <strong>1 hora</strong> por seguridad.
                    </div>
                    
                    <p>Si no solicitaste este cambio, puedes ignorar este email de manera segura.</p>
                    
                    <p>Saludos cordiales,<br>
                    <strong>Equipo MicroApps</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                    <p>&copy; " . date('Y') . " MicroApps. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Versión alternativa en texto plano
        $mail->AltBody = "Hola {$nombre},\n\nHas solicitado restablecer tu contraseña en MicroApps.\n\nPara crear una nueva contraseña, visita este enlace:\n{$reset_link}\n\nEste enlace expirará en 1 hora.\n\nSi no solicitaste este cambio, ignora este email.\n\nSaludos,\nEquipo MicroApps";
        
        // Enviar email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}