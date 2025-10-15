<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0 rounded-4" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Restablecer Contraseña</h4>
                <p class="text-muted">Ingresa tu email para recibir instrucciones</p>
            </div>

            <form method="POST">
                <div class="alert alert-danger d-none">
                    <strong>Error:</strong> Por favor corrige los errores below.
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control rounded-pill" id="email" name="email" 
                           placeholder="Tu email registrado" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 rounded-pill">Enviar enlace de recuperación</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">← Volver al login</a>
            </div>
        </div>
    </div>
</div>