<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/messages.php';

requiereLogin($conn);

$titulo = 'Inicio';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <?php mostrarAlerta(); ?>
  <h1 class="h3">Bienvenido, <?= e($_SESSION['nombre']) ?></h1>
  <p class="text-muted">Use el menú superior para navegar.</p>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>