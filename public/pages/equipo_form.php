<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';
require_once __DIR__ . '/../../utils/equipos.php';

requiereLogin($conn);
requiereRol(['admin']);

// Solo categorías actuales, no eliminadas
$categorias = $conn->query(
    'SELECT id, nombre FROM categorias WHERE eliminado_en IS NULL ORDER BY nombre'
)->fetchAll();

$equipo = ['id' => null, 'codigo' => '', 'nombre' => '', 'categoria_id' => null, 'estado' => 'operativo', 'version' => null];

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $equipo = false;

    if ($id !== false) {
        $stmt = $conn->prepare(
            'SELECT id, codigo, nombre, categoria_id, estado, version
             FROM equipos WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $equipo = $stmt->fetch();
    }

    if ($equipo === false) {
        redirect('/pages/equipos.php?error=equipo_no_encontrado');
    }
}

$esEdicion = $equipo['id'] !== null;
$titulo = $esEdicion ? 'Editar equipo' : 'Nuevo equipo';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3"><?= e($titulo) ?></h1>
          <?php mostrarAlerta(); ?>

          <form action="/actions/equipo_guardar.php" method="POST">
            <?php csrfCampo(); ?>
            <?php if ($esEdicion): ?>
              <input type="hidden" name="id" value="<?= (int) $equipo['id'] ?>">
              <!-- Versión leída: detecta si otro la cambió (bloqueo optimista) -->
              <input type="hidden" name="version" value="<?= (int) $equipo['version'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label for="codigo">Código</label>
              <input type="text" class="form-control" id="codigo" name="codigo"
                     value="<?= e($equipo['codigo']) ?>" maxlength="30" required>
            </div>

            <div class="form-group">
              <label for="nombre">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                     value="<?= e($equipo['nombre']) ?>" maxlength="120" required>
            </div>

            <div class="form-group">
              <label for="categoria_id">Categoría</label>
              <select class="form-control" id="categoria_id" name="categoria_id" required>
                <option value="">Seleccione…</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int) $cat['id'] ?>" <?= (int) $cat['id'] === (int) $equipo['categoria_id'] ? 'selected' : '' ?>>
                    <?= e($cat['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="estado">Estado</label>
              <select class="form-control" id="estado" name="estado" required>
                <?php foreach (ESTADOS_EQUIPO as $valor => $texto): ?>
                  <option value="<?= e($valor) ?>" <?= $valor === $equipo['estado'] ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/pages/equipos.php" class="btn btn-link">Cancelar</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>