<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/paginacion.php';

requiereLogin($conn);
requiereRol(['admin']);

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim(mb_substr($q, 0, 80)) : '';

$porPagina = obtenerPorPagina('usuarios');

$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($pagina === false) {
    $pagina = 1;
}

$filtro = '%' . $q . '%';

// Contar el total de usuarios que coinciden con el filtro
$stmt = $conn->prepare(
    'SELECT COUNT(*) FROM usuarios
     WHERE eliminado_en IS NULL AND (nombre LIKE :filtro1 OR correo LIKE :filtro2)'
);
$stmt->execute([':filtro1' => $filtro, ':filtro2' => $filtro]);
$total = (int) $stmt->fetchColumn();

$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $conn->prepare(
    'SELECT u.id, u.nombre, u.correo, u.activo, r.nombre AS rol
     FROM usuarios u
     JOIN roles r ON r.id = u.rol_id
     WHERE u.eliminado_en IS NULL AND (u.nombre LIKE :filtro1 OR u.correo LIKE :filtro2)
     ORDER BY u.nombre
     LIMIT :limite OFFSET :offset'
);
$stmt->bindValue(':filtro1', $filtro);
$stmt->bindValue(':filtro2', $filtro);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();

$titulo = 'Usuarios';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Usuarios</h1>
    <a href="/pages/usuario_form.php" class="btn btn-primary">Nuevo usuario</a>
  </div>

  <form method="GET" class="form-inline mb-3">
    <input type="text" name="q" value="<?= e($q) ?>" class="form-control mr-2" placeholder="Buscar por nombre o correo" maxlength="80">
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
        <th>Correo</th>
        <th>Rol</th>
        <th>Estado</th>
        <th class="text-right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$usuarios): ?>
        <tr><td colspan="5" class="text-center text-muted">No se encuentran usuarios.</td></tr>
      <?php endif; ?>

      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= e($u['nombre']) ?></td>
          <td><?= e($u['correo']) ?></td>
          <td><?= e($u['rol']) ?></td>
          <td>
            <?php if ((int) $u['activo'] === 1): ?>
              <span class="badge badge-success">Activo</span>
            <?php else: ?>
              <span class="badge badge-secondary">Inactivo</span>
            <?php endif; ?>
          </td>
          <td class="text-right">
            <a href="/pages/usuario_form.php?id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php if ((int) $u['id'] !== $_SESSION['usuario_id']): ?>
              <form action="/actions/usuario_eliminar.php" method="POST" class="d-inline"
                    onsubmit="return confirm('¿Eliminar este usuario?');">
                <?php csrfCampo(); ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
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