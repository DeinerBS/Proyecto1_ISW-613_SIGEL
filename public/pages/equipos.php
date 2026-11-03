<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/paginacion.php';
require_once __DIR__ . '/../../utils/equipos.php';
require_once __DIR__ . '/../../utils/recientes.php';

requiereLogin($conn);
requiereRol(['admin', 'operador']);

// El operador solo consulta
$puedeGestionar = $_SESSION['rol'] === 'admin';

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim(mb_substr($q, 0, 80)) : '';

$porPagina = obtenerPorPagina('equipos');

$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($pagina === false) {
    $pagina = 1;
}

$filtro = '%' . $q . '%';

$stmt = $conn->prepare(
    'SELECT COUNT(*) FROM equipos
     WHERE eliminado_en IS NULL AND (codigo LIKE :filtro1 OR nombre LIKE :filtro2)'
);
$stmt->execute([':filtro1' => $filtro, ':filtro2' => $filtro]);
$total = (int) $stmt->fetchColumn();

$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $conn->prepare(
    'SELECT e.id, e.codigo, e.nombre, e.estado, c.nombre AS categoria
     FROM equipos e
     JOIN categorias c ON c.id = e.categoria_id
     WHERE e.eliminado_en IS NULL AND (e.codigo LIKE :filtro1 OR e.nombre LIKE :filtro2)
     ORDER BY e.codigo
     LIMIT :limite OFFSET :offset'
);
$stmt->bindValue(':filtro1', $filtro);
$stmt->bindValue(':filtro2', $filtro);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$equipos = $stmt->fetchAll();

$recientes = obtenerEquiposRecientes($conn);

$coloresEstado = [
    'operativo'     => 'success',
    'mantenimiento' => 'warning',
    'dado_de_baja'  => 'secondary',
];

$titulo = 'Equipos';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Equipos</h1>
    <?php if ($puedeGestionar): ?>
      <a href="/pages/equipo_form.php" class="btn btn-primary">Nuevo equipo</a>
    <?php endif; ?>
  </div>

    <?php if ($recientes): ?>
    <div class="mb-3">
      <span class="text-muted small mr-2">Consultados recientemente:</span>
      <?php foreach ($recientes as $r): ?>
        <a href="/pages/equipo_ver.php?id=<?= (int) $r['id'] ?>" class="badge badge-light border p-2 mr-1">
          <?= e($r['codigo']) ?> · <?= e($r['nombre']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="GET" class="form-inline mb-3">
    <input type="text" name="q" value="<?= e($q) ?>" class="form-control mr-2" placeholder="Buscar por código o nombre" maxlength="80">
    <select name="por_pagina" class="form-control mr-2">
      <?php foreach (OPCIONES_POR_PAGINA as $opcion): ?>
        <option value="<?= $opcion ?>" <?= $opcion === $porPagina ? 'selected' : '' ?>><?= $opcion ?> por página</option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Aplicar</button>
  </form>

  <table class="table table-striped bg-white">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Estado</th>
        <th class="text-right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$equipos): ?>
        <tr><td colspan="5" class="text-center text-muted">No se encontraron equipos.</td></tr>
      <?php endif; ?>

      <?php foreach ($equipos as $eq): ?>
        <tr>
          <td><?= e($eq['codigo']) ?></td>
          <td><?= e($eq['nombre']) ?></td>
          <td><?= e($eq['categoria']) ?></td>
          <td>
            <span class="badge badge-<?= $coloresEstado[$eq['estado']] ?>"><?= e(ESTADOS_EQUIPO[$eq['estado']]) ?></span>
          </td>
                    <td class="text-right">
            <a href="/pages/equipo_ver.php?id=<?= (int) $eq['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
            <?php if ($puedeGestionar): ?>
              <a href="/pages/equipo_form.php?id=<?= (int) $eq['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <form action="/actions/equipo_eliminar.php" method="POST" class="d-inline"
                    onsubmit="return confirm('¿Eliminar este equipo?');">
                <?php csrfCampo(); ?>
                <input type="hidden" name="id" value="<?= (int) $eq['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPaginas > 1): ?>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
          <a class="page-link" href="?<?= e(http_build_query(['q' => $q, 'pagina' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  <?php endif; ?>

  <p class="text-muted small">Total: <?= $total ?></p>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>