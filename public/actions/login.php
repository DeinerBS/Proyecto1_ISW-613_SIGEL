<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';

// Solo aceptar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$correo   = $_POST['correo'] ?? '';
$password = $_POST['password'] ?? '';

// Control #6: Validación en el servidor 
if (!is_string($correo) || !is_string($password)) {
    redirect('/index.php?error=datos_invalidos');
}

$correo = trim($correo);

if (
    $correo === ''
    || strlen($correo) > 150
    || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
    || $password === ''
) {
    redirect('/index.php?error=datos_invalidos');
}

// Buscar al usuario junto con el nombre de su rol
try {
    $stmt = $conn->prepare(
        'SELECT u.id, u.nombre, u.password_hash, u.activo, r.nombre AS rol
         FROM usuarios u
         JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = :correo AND u.eliminado_en IS NULL
         LIMIT 1'
    );
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch();
} catch (PDOException $ex) {
    error_log('Error en login: ' . $ex->getMessage());
    redirect('/index.php?error=error_servidor');
}

// Control #9: Mismo mensaje para cualquier fallo 
if (
    $usuario === false
    || !password_verify($password, $usuario['password_hash'])
    || (int) $usuario['activo'] !== 1
) {
    redirect('/index.php?error=credenciales_invalidas');
}

loginUsuario($usuario);
redirect('/pages/dashboard.php');