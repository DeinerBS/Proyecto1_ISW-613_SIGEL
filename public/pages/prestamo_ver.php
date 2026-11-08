<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/horario.php';
require_once __DIR__ . '/../../utils/prestamos.php';

requiereLogin($conn);
requiereRol(['operador', 'profesor']);

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/prestamos.php?error=prestamo_no_encontrado');
}

$sql = 'SELECT p.id, p.estado, p.fecha_inicio, p.fecha_fin, p.creado_en,
               u.nombre AS profesor, o.nombre AS operador
        FROM prestamos p
        JOIN usuarios u ON u.id = p.usuario_id
        JOIN usuarios o ON o.id = p.operador_id
        WHERE p.id = :id';
$params = [':id' => $id];

// El profesor solo puede ver sus propios préstamos
if ($_SESSION['rol'] === 'profesor') {
    $sql .= ' AND p.usuario_id = :yo';
    $params[':yo'] = $_SESSION['usuario_id'];
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$prestamo = $stmt->fetch();

if ($prestamo === false) {
    redirect('/pages/prestamos.php?error=prestamo_no_encontrado');
}

$stmt = $conn->prepare(
    'SELECT e.codigo, e.nombre, d.fecha_devolucion
     FROM prestamo_detalle d
     JOIN equipos e ON e.id = d.equipo_id
     WHERE d.prestamo_id = :id
     ORDER BY e.codigo'
);
$stmt->execute([':id' => $id]);
$equipos = $stmt->fetchAll();

// Botones según el estado actual para el operador
$acciones = [];
if ($_SESSION['rol'] === 'operador') {
    foreach (TRANSICIONES as $accion => $t) {
        if (in_array($prestamo['estado'], $t['desde'], true)) {
            $acciones[$accion] = $t;
        }
    }
} 

$horario = obtenerHorario($conn);

$titulo = 'Préstamo #' . $prestamo['id'];
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <a href="/pages/prestamos.php" class="btn btn-link px-0 mb-2">&larr; Volver a préstamos</a>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h1 class="h4">
        Préstamo #<?= (int) $prestamo['id'] ?>
        <span class="badge badge-<?= COLORES_ESTADO_PRESTAMO[$prestamo['estado']] ?>"><?= e(ESTADOS_PRESTAMO[$prestamo['estado']]) ?></span>
      </h1>
      <p class="mb-1"><strong>Profesor:</strong> <?= e($prestamo['profesor']) ?></p>
      <p class="mb-1"><strong>Registrado por:</strong> <?= e($prestamo['operador']) ?> el <?= e(formatoFecha($prestamo['creado_en'])) ?></p>
      <p class="mb-0"><strong>Período:</strong> <?= e(formatoFecha($prestamo['fecha_inicio'])) ?> a <?= e(formatoFecha($prestamo['fecha_fin'])) ?></p>
    </div>
  </div>

  <h2 class="h5">Equipos</h2>
  <table class="table bg-white">
    <thead>
      <tr><th>Código</th><th>Nombre</th><th>Devuelto</th></tr>
    </thead>
    <tbody>
      <?php foreach ($equipos as $eq): ?>
        <tr>
          <td><?= e($eq['codigo']) ?></td>
          <td><?= e($eq['nombre']) ?></td>
          <td><?= $eq['fecha_devolucion'] ? e(formatoFecha($eq['fecha_devolucion'])) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($acciones): ?>
    <div class="d-flex">
      <?php foreach ($acciones as $accion => $t): ?>
        <form action="/actions/prestamo_estado.php" method="POST" class="mr-2"
              onsubmit="return confirm('¿<?= e($t['boton']) ?>?');">
          <?php csrfCampo(); ?>
          <input type="hidden" name="id" value="<?= (int) $prestamo['id'] ?>">
          <input type="hidden" name="accion" value="<?= e($accion) ?>">
          <button type="submit" class="btn btn-<?= $t['estilo'] ?>"><?= e($t['boton']) ?></button>
        </form>
      <?php endforeach; ?>
    </div>
    <p class="text-muted small mt-2">
      Entregar y registrar devoluciones: lunes a viernes de <?= e($horario['inicio']) ?> a <?= e($horario['fin']) ?>.
    </p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>