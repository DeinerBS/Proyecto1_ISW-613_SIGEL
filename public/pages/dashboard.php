<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';

requiereLogin($conn);

$titulo = 'Inicio';
require __DIR__ . '/common/header.php';
?>
<div class="container mt-5">
  <h1 class="h3">Bienvenido, <?= e($_SESSION['nombre']) ?></h1>
  <p class="text-muted">Rol: <?= e($_SESSION['rol']) ?></p>

  <form action="/actions/logout.php" method="POST">
    <?php csrfCampo(); ?>
    <button type="submit" class="btn btn-outline-danger">Cerrar sesión</button>
  </form>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>