<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';

requiereLogin($conn);
requiereRol(['admin']);

$titulo = 'Categorías';
require __DIR__ . '/common/header.php';
?>
<div class="container">
  <h1 class="h3">Categorías</h1>
  <p class="text-muted">En construcción.</p>
</div>
<?php require __DIR__ . '/common/footer.php'; ?>