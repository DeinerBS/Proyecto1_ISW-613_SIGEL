<?php $rol = $_SESSION['rol']; ?>
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
  <a class="navbar-brand" href="/pages/dashboard.php">SIGEL</a>

  <ul class="navbar-nav mr-auto">
    <?php if ($rol === 'admin'): ?>
      <li class="nav-item"><a class="nav-link" href="/pages/usuarios.php">Usuarios</a></li>
      <li class="nav-item"><a class="nav-link" href="/pages/categorias.php">Categorías</a></li>
      <li class="nav-item"><a class="nav-link" href="/pages/equipos.php">Equipos</a></li>
      <li class="nav-item"><a class="nav-link" href="/pages/reportes.php">Reportes</a></li>
    <?php elseif ($rol === 'operador'): ?>
      <li class="nav-item"><a class="nav-link" href="/pages/prestamos.php">Préstamos</a></li>
      <li class="nav-item"><a class="nav-link" href="/pages/equipos.php">Equipos</a></li>
    <?php else: ?>
      <li class="nav-item"><a class="nav-link" href="/pages/prestamos.php">Mis préstamos</a></li>
    <?php endif; ?>
  </ul>

  <span class="navbar-text mr-3"><?= e($_SESSION['nombre']) ?> (<?= e($rol) ?>)</span>

  <form action="/actions/logout.php" method="POST" class="form-inline">
    <?php csrfCampo(); ?>
    <button type="submit" class="btn btn-sm btn-outline-light">Cerrar sesión</button>
  </form>
</nav>