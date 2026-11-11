<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/paginacion.php';
require_once __DIR__ . '/../../utils/prestamos.php';

requiereLogin($conn);
requiereRol(['operador', 'profesor']);

$esProfesor = $_SESSION['rol'] === 'profesor';

// Solo valores de la lista
$estado = $_GET['estado'] ?? '';
if (!is_string($estado) || !array_key_exists($estado, ESTADOS_PRESTAMO)) {
    $estado = '';
}

$porPagina = obtenerPorPagina('prestamos');

$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($pagina === false) {
    $pagina = 1;
}

// Condiciones, el texto es nuestro, los valores van como parámetros
$condiciones = ['1 = 1'];
$params = [];

if ($esProfesor) {
    $condiciones[] = 'p.usuario_id = :yo'; // el profesor solo ve los suyos
    $params[':yo'] = $_SESSION['usuario_id'];
}
if ($estado !== '') {
    $condiciones[] = 'p.estado = :estado';
    $params[':estado'] = $estado;
}
$where = implode(' AND ', $condiciones);

$stmt = $conn->prepare("SELECT COUNT(*) FROM prestamos p WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $conn->prepare(
    "SELECT p.id, p.estado, p.fecha_inicio, p.fecha_fin, u.nombre AS profesor,
            GROUP_CONCAT(e.codigo ORDER BY e.codigo SEPARATOR ', ') AS equipos
     FROM prestamos p
     JOIN usuarios u ON u.id = p.usuario_id
     JOIN prestamo_detalle d ON d.prestamo_id = p.id
     JOIN equipos e ON e.id = d.equipo_id
     WHERE $where
     GROUP BY p.id, p.estado, p.fecha_inicio, p.fecha_fin, u.nombre
     ORDER BY p.fecha_inicio DESC
     LIMIT :limite OFFSET :offset"
);
foreach ($params as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$prestamos = $stmt->fetchAll();

$titulo = $esProfesor ? 'Mis préstamos' : 'Préstamos';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($titulo) ?></h1>
    <?php if (!$esProfesor): ?>
      <a href="/pages/prestamo_form.php" class="btn btn-primary">Nuevo préstamo</a>
    <?php endif; ?>
  </div>

  <form method="GET" class="form-inline mb-3">
    <select name="estado" class="form-control mr-2">
      <option value="">Todos los estados</option>
      <?php foreach (ESTADOS_PRESTAMO as $valor => $texto): ?>
        <option value="<?= e($valor) ?>" <?= $valor === $estado ? 'selected' : '' ?>><?= e($texto) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="por_pagina" class="form-control mr-2">
      <?php foreach (OPCIONES_POR_PAGINA as $opcion): ?>
        <option value="<?= $opcion ?>" <?= $opcion === $porPagina ? 'selected' : '' ?>><?= $opcion ?> por página</option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Aplicar</button>
  </form>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th>
        <?php if (!$esProfesor): ?><th>Profesor</th><?php endif; ?>
        <th>Equipos</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$prestamos): ?>
        <tr><td colspan="7" class="text-center text-muted">No se encontraron préstamos.</td></tr>
      <?php endif; ?>

      <?php foreach ($prestamos as $p): ?>
        <tr>
          <td><?= (int) $p['id'] ?></td>
          <?php if (!$esProfesor): ?><td><?= e($p['profesor']) ?></td><?php endif; ?>
          <td><?= e($p['equipos']) ?></td>
          <td><?= e(formatoFecha($p['fecha_inicio'])) ?></td>
          <td><?= e(formatoFecha($p['fecha_fin'])) ?></td>
          <td>
            <span class="badge badge-<?= COLORES_ESTADO_PRESTAMO[$p['estado']] ?>"><?= e(ESTADOS_PRESTAMO[$p['estado']]) ?></span>
          </td>
          <td class="text-right">
            <a href="/pages/prestamo_ver.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPaginas > 1): ?>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
          <a class="page-link" href="?<?= e(http_build_query(['estado' => $estado, 'pagina' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  <?php endif; ?>

  <p class="text-muted small">Total: <?= $total ?></p>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>