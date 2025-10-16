<?php
if (session_status() === PHP_SESSION_NONE) session_start();
global $rutaVista, $vistas_sin_nav;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Microapps</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/font-awesome.css">
    <link rel="stylesheet" href="./css/lava.css">
    <link rel="stylesheet" href="./css/owl-carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">

    <!-- JS -->
    <script defer src="./js/desplegador.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="bg-dark text-light">
    <?php include __DIR__ . '/nav.php'; ?>

    <main class="container-fluid p-4 mt-5">
        <?php include $rutaVista; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
