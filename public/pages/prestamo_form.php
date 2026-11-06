<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/horario.php';
require_once __DIR__ . '/../../utils/recientes.php';
require_once __DIR__ . '/../../utils/prestamos.php';

requiereLogin($conn);
requiereRol(['operador']);

$profesores = $conn->query(
    "SELECT u.id, u.nombre
     FROM usuarios u
     JOIN roles r ON r.id = u.rol_id
     WHERE r.nombre = 'profesor' AND u.activo = 1 AND u.eliminado_en IS NULL
     ORDER BY u.nombre"
)->fetchAll();

$equipos = $conn->query(
    "SELECT e.id, e.codigo, e.nombre, c.nombre AS categoria, c.dias_max_prestamo
     FROM equipos e
     JOIN categorias c ON c.id = e.categoria_id
     WHERE e.estado = 'operativo' AND e.eliminado_en IS NULL
     ORDER BY e.codigo"
)->fetchAll();

// Sección 8.3: los consultados recientemente aparecen primero
$idsRecientes = leerRecientes();
$recientes = [];
$otros = [];
foreach ($equipos as $eq) {
    if (in_array((int) $eq['id'], $idsRecientes, true)) {
        $recientes[] = $eq;
    } else {
        $otros[] = $eq;
    }
}

$horario = obtenerHorario($conn);

$titulo = 'Nuevo préstamo';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-1">Nuevo préstamo</h1>
          <p class="text-muted small">
            Horario de atención: lunes a viernes de <?= e($horario['inicio']) ?> a <?= e($horario['fin']) ?>.
          </p>
          <?php mostrarAlerta(); ?>

          <form action="/actions/prestamo_guardar.php" method="POST">
            <?php csrfCampo(); ?>

            <div class="form-group">
              <label for="usuario_id">Profesor</label>
              <select class="form-control" id="usuario_id" name="usuario_id" required>
                <option value="">Seleccione…</option>
                <?php foreach ($profesores as $p): ?>
                  <option value="<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group col">
                <label for="fecha_inicio">Inicio</label>
                <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
              </div>
              <div class="form-group col">
                <label for="fecha_fin">Fin (devolución)</label>
                <input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin" required>
              </div>
            </div>

            <label>Equipos (máximo <?= MAX_EQUIPOS_PRESTAMO ?>)</label>
            <?php foreach (['Consultados recientemente' => $recientes, 'Equipos operativos' => $otros] as $tituloGrupo => $lista): ?>
              <?php if ($lista): ?>
                <h2 class="h6 mt-2 text-muted"><?= e($tituloGrupo) ?></h2>
                <?php foreach ($lista as $eq): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="equipos[]"
                           value="<?= (int) $eq['id'] ?>" id="eq<?= (int) $eq['id'] ?>">
                    <label class="form-check-label" for="eq<?= (int) $eq['id'] ?>">
                      <?= e($eq['codigo']) ?> · <?= e($eq['nombre']) ?>
                      <small class="text-muted">(<?= e($eq['categoria']) ?>, máx. <?= (int) $eq['dias_max_prestamo'] ?> días)</small>
                    </label>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary mt-3">Registrar préstamo</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>