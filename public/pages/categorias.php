<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/paginacion.php';

requiereLogin($conn);
requiereRol(['admin']);

// control #6: Parámetros de la URL, entrada no confiable 
$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim(mb_substr($q, 0, 80)) : '';

$porPagina = obtenerPorPagina('categorias');

$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($pagina === false) {
    $pagina = 1;
}

$filtro = '%' . $q . '%';

// Total de resultados, para calcular las páginas
$stmt = $conn->prepare('SELECT COUNT(*) FROM categorias WHERE eliminado_en IS NULL AND nombre LIKE :filtro');
$stmt->execute([':filtro' => $filtro]);
$total = (int) $stmt->fetchColumn();

$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $conn->prepare(
    'SELECT id, nombre, dias_max_prestamo
     FROM categorias
     WHERE eliminado_en IS NULL AND nombre LIKE :filtro
     ORDER BY nombre
     LIMIT :limite OFFSET :offset'
);
// bind values para indicar valor es entero, evitando inyección SQL
$stmt->bindValue(':filtro', $filtro);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categorias = $stmt->fetchAll();

$titulo = 'Categorías';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Categorías</h1>
    <a href="/pages/categoria_form.php" class="btn btn-primary">Nueva categoría</a>
  </div>

  <form method="GET" class="form-inline mb-3">
    <input type="text" name="q" value="<?= e($q) ?>" class="form-control mr-2" placeholder="Buscar por nombre" maxlength="80">
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
        <th>Nombre</th>
        <th>Días máx. de préstamo</th>
        <th class="text-right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$categorias): ?>
        <tr><td colspan="3" class="text-center text-muted">No se encontraron categorías.</td></tr>
      <?php endif; ?>

      <?php foreach ($categorias as $c): ?>
        <tr>
          <td><?= e($c['nombre']) ?></td>
          <td><?= (int) $c['dias_max_prestamo'] ?></td>
          <td class="text-right">
            <a href="/pages/categoria_form.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <form action="/actions/categoria_eliminar.php" method="POST" class="d-inline"
              onsubmit="return confirm('¿Eliminar esta categoría?');">
              <?php csrfCampo(); ?>
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
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