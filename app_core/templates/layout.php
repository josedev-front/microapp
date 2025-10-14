<?php
// Layout global para todas las vistas y microservicios
include __DIR__ . '/../inc/head.php';

if (!in_array($vista, ['login', '404', 'logout', 'confirmar_pago'])) {
    include __DIR__ . '/../inc/nav.php';
}
?>

<main class="content">
  <?php include $rutaVista; ?>
</main>

<?php
if (!in_array($vista, ['login', '404', 'logout', 'confirmar_pago'])) {
    include __DIR__ . '/../inc/footer.php';
}
?>

<script src="./js/ajax.js"></script>
