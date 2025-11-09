<?php
// microservices/back-admision/api/get_horarios.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/TeamController.php';

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    echo '<div class="alert alert-danger">Usuario no especificado</div>';
    exit;
}

$teamController = new TeamController();
$horarios = $teamController->getHorariosEjecutivo($user_id);

if (empty($horarios)) {
    echo '<div class="alert alert-warning">No se encontraron horarios para este usuario</div>';
    exit;
}
?>

<form id="formHorarios" data-user="<?php echo $user_id; ?>">
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th>Día</th>
                    <th>Activo</th>
                    <th>Hora Entrada</th>
                    <th>Hora Salida</th>
                    <th>Almuerzo Inicio</th>
                    <th>Almuerzo Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horarios as $horario): ?>
                <tr>
                    <td>
                        <strong><?php echo ucfirst($horario['dia_semana']); ?></strong>
                    </td>
                    <td>
                        <input type="checkbox" 
                               name="horarios[<?php echo $horario['dia_semana']; ?>][activo]" 
                               value="1" 
                               <?php echo $horario['activo'] ? 'checked' : ''; ?>
                               class="form-check-input">
                    </td>
                    <td>
                        <input type="time" 
                               name="horarios[<?php echo $horario['dia_semana']; ?>][hora_entrada]" 
                               value="<?php echo $horario['hora_entrada']; ?>" 
                               class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="time" 
                               name="horarios[<?php echo $horario['dia_semana']; ?>][hora_salida]" 
                               value="<?php echo $horario['hora_salida']; ?>" 
                               class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="time" 
                               name="horarios[<?php echo $horario['dia_semana']; ?>][hora_almuerzo_inicio]" 
                               value="<?php echo $horario['hora_almuerzo_inicio']; ?>" 
                               class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="time" 
                               name="horarios[<?php echo $horario['dia_semana']; ?>][hora_almuerzo_fin]" 
                               value="<?php echo $horario['hora_almuerzo_fin']; ?>" 
                               class="form-control form-control-sm">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar Horarios
        </button>
    </div>
</form>

<script>
$('#formHorarios').submit(function(e) {
    e.preventDefault();
    
    const user_id = $(this).data('user');
    const formData = $(this).serialize();
    
    $.post('/?vista=admision-api-guardar-horarios&user_id=' + user_id, formData, function(response) {
        if (response.success) {
            alert('Horarios guardados correctamente');
            $('#modalHorarios').modal('hide');
        } else {
            alert('Error: ' + response.message);
        }
    }, 'json');
});
</script>