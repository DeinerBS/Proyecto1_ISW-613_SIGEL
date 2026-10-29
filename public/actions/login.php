<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';

// Solo aceptar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

verificarCsrf();

$correo   = $_POST['correo'] ?? '';
$password = $_POST['password'] ?? '';

// Validación en el servidor (control #6)
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

try {
    // Buscar al usuario, su rol y si está bloqueado en este momento
    $stmt = $conn->prepare(
        'SELECT u.id, u.nombre, u.password_hash, u.activo, u.intentos_fallidos,
                (u.bloqueado_hasta IS NOT NULL AND u.bloqueado_hasta > NOW()) AS bloqueado,
                r.nombre AS rol
         FROM usuarios u
         JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = :correo AND u.eliminado_en IS NULL
         LIMIT 1'
    );
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch();

    // Si está bloqueado, no hace falta revisar a la contraseña
    if ($usuario !== false && (int) $usuario['bloqueado'] === 1) {
        redirect('/index.php?error=cuenta_bloqueada');
    }

    // Control #9: Mismo mensaje para cualquier fallo 
    if (
        $usuario === false
        || !password_verify($password, $usuario['password_hash'])
        || (int) $usuario['activo'] !== 1
    ) {
        // Si existe el usuario, se cuenta el intento fallido
        if ($usuario !== false) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;

            if ($intentos >= MAX_INTENTOS_LOGIN) {
                // Llegó al límite, bloquear y reiniciar el contador
                $upd = $conn->prepare(
                    'UPDATE usuarios
                     SET intentos_fallidos = 0,
                         bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :minutos MINUTE)
                     WHERE id = :id'
                );
                $upd->execute([':minutos' => MINUTOS_BLOQUEO, ':id' => $usuario['id']]);
                redirect('/index.php?error=cuenta_bloqueada');
            }

            $upd = $conn->prepare('UPDATE usuarios SET intentos_fallidos = :intentos WHERE id = :id');
            $upd->execute([':intentos' => $intentos, ':id' => $usuario['id']]);
        }

        redirect('/index.php?error=credenciales_invalidas');
    }

    // Login correcto: limpiar contador y bloqueo
    $upd = $conn->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id');
    $upd->execute([':id' => $usuario['id']]);
} catch (PDOException $ex) {
    error_log('Error en login: ' . $ex->getMessage());
    redirect('/index.php?error=error_servidor');
}

loginUsuario($usuario);

if (isset($_POST['recordarme'])) {
    try {
        crearTokenRecordarme($conn, (int) $usuario['id']);
    } catch (PDOException $ex) {
        // Si falla, el login sigue, solo que sin "Recordarme"
        error_log('Error al crear token Recordarme: ' . $ex->getMessage());
    }
}

redirect('/pages/dashboard.php');