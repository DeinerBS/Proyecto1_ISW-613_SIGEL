<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/horario.php';

requiereLogin($conn);
requiereRol(['admin']);

$horario = obtenerHorario($conn);

$titulo = 'Configuración';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-1">Horario de atención</h1>
          <p class="text-muted small">Inicio y entrega de préstamos: lunes a viernes, dentro de este horario, ambos límites incluidos.</p>
          <?php mostrarAlerta(); ?>

          <form action="/actions/configuracion_guardar.php" method="POST">
            <?php csrfCampo(); ?>
            <div class="form-row">
              <div class="form-group col">
                <label for="hora_inicio">Hora de inicio</label>
                <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?= e($horario['inicio']) ?>" required>
              </div>
              <div class="form-group col">
                <label for="hora_fin">Hora de fin</label>
                <input type="time" class="form-control" id="hora_fin" name="hora_fin" value="<?= e($horario['fin']) ?>" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>