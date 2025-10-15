<div class="container" style="margin-top: 20%; margin-bottom: 24%;">
    <div class="row">
        <div class="col-12">
            
            <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>      
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-users"></i> Gestión de Equipo
                </h2>
                <a href="crear_usuario.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Crear Usuario
                </a>
            </div>
            
            <div class="alert alert-info">
                <strong>Nivel de acceso:</strong>
                <span class="badge bg-success">Acceso Completo (Developer/Superuser)</span>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre Completo</th>
                                    <th>Rol</th>
                                    <th>Jefe Directo</th>
                                    <th>Área</th>
                                    <th>Email</th>
                                    <th>ID Empleado</th>
                                    <th>Teléfono</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Juan Pérez García</strong>
                                        <br>
                                        <small class="text-muted">
                                            Antonio López
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Administrador</span>
                                    </td>
                                    <td>
                                        María González
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">TI</span>
                                    </td>
                                    <td>juan.perez@empresa.com</td>
                                    <td>EMP001</td>
                                    <td>555-1234</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar_usuario.php?id=1" class="btn btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="eliminar_usuario.php" method="post" class="d-inline">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>María García López</strong>
                                        <br>
                                        <small class="text-muted">
                                            Elena Martínez
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Supervisor</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">Sin asignar</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Recursos Humanos</span>
                                    </td>
                                    <td>maria.garcia@empresa.com</td>
                                    <td>EMP002</td>
                                    <td>-</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar_usuario.php?id=2" class="btn btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="eliminar_usuario.php" method="post" class="d-inline">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5>15</h5>
                            <p>Total Usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>8</h5>
                            <p>En mi área</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5>5</h5>
                            <p>Mis subordinados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5>Administrador</h5>
                            <p>Mi Rol</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>