<?php
// microservices/back-admision/views/ejecutivo/ingresar-caso.php
require_once __DIR__ . '/../../init.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar Caso - Back de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/?vista=home"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item"><a href="/?vista=back-admision">Back de Admisión</a></li>
                        <li class="breadcrumb-item active">Ingresar Caso</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Ingresar Nuevo Caso</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['notificacion_confirmacion'])): ?>
                            <!-- Modal de Confirmación para Reasignación -->
                            <div class="modal fade show" id="modalConfirmacion" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Reasignación</h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo $_SESSION['notificacion_confirmacion']['mensaje']; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <form method="post" action="/?vista=admision-api-ingresar-caso">
                                                <input type="hidden" name="sr_hijo" value="<?php echo $_SESSION['notificacion_confirmacion']['sr_hijo']; ?>">
                                                <input type="hidden" name="confirmar_reasignacion" value="1">
                                                <button type="submit" class="btn btn-warning">Sí, Reasignar</button>
                                            </form>
                                            <a href="/?vista=admision-ingresar-caso" class="btn btn-secondary">Cancelar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php unset($_SESSION['notificacion_confirmacion']); ?>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Instrucciones</h6>
                            <p class="mb-0">Ingresa el número de SR hijo para que el sistema lo asigne de manera equilibrada entre los ejecutivos disponibles del área Micro&SOHO.</p>
                        </div>

                        <form method="post" action="/?vista=admision-api-ingresar-caso">
                            <div class="mb-3">
                                <label for="sr_hijo" class="form-label">
                                    <strong>Número de SR Hijo *</strong>
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="sr_hijo" 
                                       name="sr_hijo" 
                                       placeholder="Ej: SR123456789"
                                       required
                                       maxlength="50">
                                <div class="form-text">
                                    Ingresa el número completo de la SR hijo con la que se trabajará.
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/?vista=back-admision" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Volver
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Ingresar Caso
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../../templates/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>