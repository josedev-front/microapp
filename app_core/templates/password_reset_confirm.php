<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0 rounded-4" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Nueva Contraseña</h4>
                <p class="text-muted">Ingresa tu nueva contraseña</p>
            </div>

            <form method="POST">
                <div class="alert alert-danger d-none">
                    <strong>Error:</strong> Por favor corrige los errores below.
                </div>

                <div class="mb-3">
                    <label for="new_password1" class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-control rounded-pill" id="new_password1" 
                           name="new_password1" placeholder="Nueva contraseña" required>
                </div>

                <div class="mb-3">
                    <label for="new_password2" class="form-label">Confirmar Contraseña</label>
                    <input type="password" class="form-control rounded-pill" id="new_password2" 
                           name="new_password2" placeholder="Confirmar contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 rounded-pill">Cambiar Contraseña</button>
            </form>
        </div>
    </div>
</div>