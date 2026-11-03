<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/equipos.php';
require_once __DIR__ . '/../../utils/imagenes.php';
require_once __DIR__ . '/../../utils/recientes.php';

requiereLogin($conn);
requiereRol(['admin', 'operador']);

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/equipos.php?error=equipo_no_encontrado');
}

$stmt = $conn->prepare(
    'SELECT e.id, e.codigo, e.nombre, e.estado, c.nombre AS categoria, c.dias_max_prestamo
     FROM equipos e
     JOIN categorias c ON c.id = e.categoria_id
     WHERE e.id = :id AND e.eliminado_en IS NULL'
);
$stmt->execute([':id' => $id]);
$equipo = $stmt->fetch();

if ($equipo === false) {
    redirect('/pages/equipos.php?error=equipo_no_encontrado');
}

$stmt = $conn->prepare('SELECT id FROM equipo_imagenes WHERE equipo_id = :id ORDER BY id');
$stmt->execute([':id' => $id]);
$imagenes = $stmt->fetchAll();

// Agrega a recientes y determina si el usuario puede gestionar imágenes
agregarReciente($id);

$puedeGestionar = $_SESSION['rol'] === 'admin';

$titulo = $equipo['codigo'];
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>

  <a href="/pages/equipos.php" class="btn btn-link px-0 mb-2">&larr; Volver a equipos</a>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h1 class="h4"><?= e($equipo['codigo']) ?> · <?= e($equipo['nombre']) ?></h1>
      <p class="mb-1"><strong>Categoría:</strong> <?= e($equipo['categoria']) ?>
        (máximo <?= (int) $equipo['dias_max_prestamo'] ?> días de préstamo)</p>
      <p class="mb-0"><strong>Estado:</strong> <?= e(ESTADOS_EQUIPO[$equipo['estado']]) ?></p>
    </div>
  </div>

  <h2 class="h5">Imágenes</h2>
  <div class="row">
    <?php if (!$imagenes): ?>
      <div class="col"><p class="text-muted">Este equipo no tiene imágenes.</p></div>
    <?php endif; ?>

    <?php foreach ($imagenes as $img): ?>
      <div class="col-md-4 mb-3">
        <img src="/imagen.php?id=<?= (int) $img['id'] ?>" class="img-fluid img-thumbnail" alt="Imagen de <?= e($equipo['codigo']) ?>">
        <?php if ($puedeGestionar): ?>
          <form action="/actions/equipo_imagen_eliminar.php" method="POST" class="mt-1"
                onsubmit="return confirm('¿Eliminar esta imagen?');">
            <?php csrfCampo(); ?>
            <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger btn-block">Eliminar imagen</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($puedeGestionar && count($imagenes) < MAX_IMAGENES_EQUIPO): ?>
    <div class="card shadow-sm mt-2">
      <div class="card-body">
        <h3 class="h6">Agregar imagen</h3>
        <!-- multipart/form-data: necesario para enviar archivos -->
        <form action="/actions/equipo_imagen_subir.php" method="POST" enctype="multipart/form-data" class="form-inline">
          <?php csrfCampo(); ?>
          <input type="hidden" name="equipo_id" value="<?= (int) $equipo['id'] ?>">
          <input type="file" name="imagen" class="form-control-file mr-2" accept=".jpg,.jpeg,.png,.webp" required>
          <button type="submit" class="btn btn-primary btn-sm">Subir</button>
        </form>
        <small class="text-muted">JPG, PNG o WEBP · máximo 2 MB · hasta <?= MAX_IMAGENES_EQUIPO ?> imágenes.</small>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>