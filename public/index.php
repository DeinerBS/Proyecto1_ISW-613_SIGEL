<?php
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/messages.php';

// Si ya hay sesión, no hay porque mostrar el login
iniciarSesion();
if (isset($_SESSION['usuario_id'])) {
    redirect('/pages/dashboard.php');
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/pages/common/header.php';
?>
<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 text-center mb-1">SIGEL</h1>
          <p class="text-center text-muted mb-4">Préstamo de equipos de laboratorio</p>

          <?php mostrarAlerta(); ?>

          <form action="/actions/login.php" method="POST">
            <div class="form-group">
              <label for="correo">Correo</label>
              <input type="email" class="form-control" id="correo" name="correo" maxlength="150" required autofocus>
            </div>
            <div class="form-group">
              <label for="password">Contraseña</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/pages/common/footer.php'; ?>