<?php
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/csrf.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo ?? 'SIGEL') ?> · SIGEL</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php if (isset($_SESSION['usuario_id'])) { require __DIR__ . '/nav.php'; } ?>