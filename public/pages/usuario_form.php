<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';

requiereLogin($conn);
requiereRol(['admin']);

// Sin datos externos, no requiere prepare
$roles = $conn->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll();

$usuario = ['id' => null, 'nombre' => '', 'correo' => '', 'rol_id' => null, 'activo' => 1];

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $usuario = false;

    if ($id !== false) {
        // Nunca se carga el password_hash en el formulario
        $stmt = $conn->prepare(
            'SELECT id, nombre, correo, rol_id, activo FROM usuarios WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch();
    }

    if ($usuario === false) {
        redirect('/pages/usuarios.php?error=usuario_no_encontrado');
    }
}

$esEdicion = $usuario['id'] !== null;
$titulo = $esEdicion ? 'Editar usuario' : 'Nuevo usuario';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3"><?= e($titulo) ?></h1>
          <?php mostrarAlerta(); ?>

          <form action="/actions/usuario_guardar.php" method="POST">
            <?php csrfCampo(); ?>
            <?php if ($esEdicion): ?>
              <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label for="nombre">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                     value="<?= e($usuario['nombre']) ?>" maxlength="100" required>
            </div>

            <div class="form-group">
              <label for="correo">Correo</label>
              <input type="email" class="form-control" id="correo" name="correo"
                     value="<?= e($usuario['correo']) ?>" maxlength="150" required>
            </div>

            <div class="form-group">
              <label for="rol_id">Rol</label>
              <select class="form-control" id="rol_id" name="rol_id" required>
                <?php foreach ($roles as $rol): ?>
                  <option value="<?= (int) $rol['id'] ?>" <?= (int) $rol['id'] === (int) $usuario['rol_id'] ? 'selected' : '' ?>>
                    <?= e($rol['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="password"><?= $esEdicion ? 'Nueva contraseña o conservar la actual' : 'Contraseña' ?></label>
              <input type="password" class="form-control" id="password" name="password"
                     minlength="8" maxlength="72" <?= $esEdicion ? '' : 'required' ?>>
            </div>

            <div class="form-group form-check">
              <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1"
                     <?= (int) $usuario['activo'] === 1 ? 'checked' : '' ?>>
              <label class="form-check-label" for="activo">Activo</label>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/pages/usuarios.php" class="btn btn-link">Cancelar</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>