<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';

requiereLogin($conn);
requiereRol(['admin']);

// Formulario vacío: crear
$categoria = ['id' => null, 'nombre' => '', 'dias_max_prestamo' => ''];

// Con ?id: editar
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $categoria = false;

    if ($id !== false) {
        $stmt = $conn->prepare(
            'SELECT id, nombre, dias_max_prestamo FROM categorias WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $categoria = $stmt->fetch();
    }

    if ($categoria === false) {
        redirect('/pages/categorias.php?error=categoria_no_encontrada');
    }
}

$esEdicion = $categoria['id'] !== null;
$titulo = $esEdicion ? 'Editar categoría' : 'Nueva categoría';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3"><?= e($titulo) ?></h1>
          <?php mostrarAlerta(); ?>

          <form action="/actions/categoria_guardar.php" method="POST">
            <?php csrfCampo(); ?>
            <?php if ($esEdicion): ?>
              <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label for="nombre">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                     value="<?= e($categoria['nombre']) ?>" maxlength="80" required>
            </div>
            <div class="form-group">
              <label for="dias">Días máximos de préstamo</label>
              <input type="number" class="form-control" id="dias" name="dias_max_prestamo"
                     value="<?= e($categoria['dias_max_prestamo']) ?>" min="1" max="90" required>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/pages/categorias.php" class="btn btn-link">Cancelar</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>