<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/reportes.php';

requiereLogin($conn);
requiereRol(['admin']);

$rango = leerRangoFechas();
if ($rango === null) {
    redirect('/pages/reportes.php?error=rango_invalido');
}
[$desde, $hasta] = $rango;

$filas = equiposMasPrestados($conn, $desde, $hasta);

// Mismo rango para el enlace de exportación
$consulta = http_build_query(['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')]);

$titulo = 'Reportes';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <h1 class="h3 mb-3">Equipos más prestados</h1>

  <form method="GET" class="form-inline mb-3">
    <label for="desde" class="mr-2">Desde</label>
    <input type="date" id="desde" name="desde" class="form-control mr-3" value="<?= e($desde->format('Y-m-d')) ?>" required>
    <label for="hasta" class="mr-2">Hasta</label>
    <input type="date" id="hasta" name="hasta" class="form-control mr-3" value="<?= e($hasta->format('Y-m-d')) ?>" required>
    <button type="submit" class="btn btn-secondary mr-2">Consultar</button>
    <?php if ($filas): ?>
      <a href="/reporte_csv.php?<?= e($consulta) ?>" class="btn btn-success">Exportar CSV</a>
    <?php endif; ?>
  </form>

  <p class="text-muted small">Se cuentan los préstamos entregados o devueltos cuya fecha de inicio esté en el rango.</p>

  <table class="table table-striped bg-white">
    <thead>
      <tr><th>#</th><th>Código</th><th>Equipo</th><th>Categoría</th><th class="text-right">Veces prestado</th></tr>
    </thead>
    <tbody>
      <?php if (!$filas): ?>
        <tr><td colspan="5" class="text-center text-muted">No hay préstamos en ese rango.</td></tr>
      <?php endif; ?>

      <?php foreach ($filas as $i => $fila): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($fila['codigo']) ?></td>
          <td><?= e($fila['nombre']) ?></td>
          <td><?= e($fila['categoria']) ?></td>
          <td class="text-right"><?= (int) $fila['veces'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>