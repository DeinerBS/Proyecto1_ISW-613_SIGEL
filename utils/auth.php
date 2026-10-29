<?php
require_once __DIR__ . '/helpers.php';

// Inactividad máxima de la sesión
const SESION_TIMEOUT_SEGUNDOS = 30 * 60;

// Control #9: limitación de intentos fallidos
const MAX_INTENTOS_LOGIN = 3;
const MINUTOS_BLOQUEO    = 2;

// Recordarme
const COOKIE_RECORDARME = 'sigel_remember';
const DIAS_RECORDARME   = 14;

// Secure solo aplica si la conexión es HTTPS
function esHttps()
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

// Control #3: cookie de sesión con HttpOnly, SameSite y Secure
function iniciarSesion()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function loginUsuario(array $usuario)
{
    iniciarSesion();
    session_regenerate_id(true); // evita fijación de sesión

    // Token CSRF nuevo para la sesión autenticada
    unset($_SESSION['csrf_token']);

    $_SESSION['usuario_id']       = (int) $usuario['id'];
    $_SESSION['nombre']           = $usuario['nombre'];
    $_SESSION['rol']              = $usuario['rol'];
    $_SESSION['ultima_actividad'] = time();
}

// Destruye la sesión y su cookie
function logoutUsuario()
{
    iniciarSesion();
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);

    session_destroy();
}

// Exige sesión válida, si no hay, intenta con la cookie "Recordarme"
function requiereLogin(PDO $conn)
{
    iniciarSesion();
    $expirada = false;

    if (isset($_SESSION['usuario_id']) && time() - $_SESSION['ultima_actividad'] > SESION_TIMEOUT_SEGUNDOS) {
        logoutUsuario();
        $expirada = true;
    }

    if (!isset($_SESSION['usuario_id']) && !loginConCookie($conn)) {
        redirect('/index.php?error=' . ($expirada ? 'sesion_expirada' : 'login_requerido'));
    }

    $_SESSION['ultima_actividad'] = time();
}

// ---------- Recordarme, esquema selector:validador ----------

function opcionesCookieRecordarme($expira)
{
    return [
        'expires'  => $expira,
        'path'     => '/',
        'secure'   => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

// Genera el token, guarda selector + hash del validador y envía la cookie
function crearTokenRecordarme(PDO $conn, $usuarioId)
{
    $selector  = bin2hex(random_bytes(9));   
    $validador = bin2hex(random_bytes(32)); 

    $stmt = $conn->prepare(
        'INSERT INTO auth_tokens (usuario_id, selector, validator_hash, expira_en)
         VALUES (:usuario_id, :selector, :hash, DATE_ADD(NOW(), INTERVAL :dias DAY))'
    );
    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':selector'   => $selector,
        ':hash'       => hash('sha256', $validador), // en BD nunca el validador
        ':dias'       => DIAS_RECORDARME,
    ]);

    setcookie(COOKIE_RECORDARME, $selector . ':' . $validador,
        opcionesCookieRecordarme(time() + DIAS_RECORDARME * 24 * 60 * 60));
}

function borrarCookieRecordarme()
{
    setcookie(COOKIE_RECORDARME, '', opcionesCookieRecordarme(time() - 3600));
    unset($_COOKIE[COOKIE_RECORDARME]);
}

// Inicia sesión con la cookie si es válid, rota el token
function loginConCookie(PDO $conn)
{
    $cookie = $_COOKIE[COOKIE_RECORDARME] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return false;
    }

    // La cookie es entrada no confiable, validar formato
    $partes = explode(':', $cookie);
    if (count($partes) !== 2 || strlen($partes[0]) !== 18 || strlen($partes[1]) !== 64) {
        borrarCookieRecordarme();
        return false;
    }
    [$selector, $validador] = $partes;

    try {
        $stmt = $conn->prepare(
            'SELECT t.id, t.validator_hash, u.id AS usuario_id, u.nombre, r.nombre AS rol
             FROM auth_tokens t
             JOIN usuarios u ON u.id = t.usuario_id
             JOIN roles r ON r.id = u.rol_id
             WHERE t.selector = :selector AND t.expira_en > NOW()
               AND u.activo = 1 AND u.eliminado_en IS NULL
             LIMIT 1'
        );
        $stmt->execute([':selector' => $selector]);
        $token = $stmt->fetch();

        if ($token === false) {
            borrarCookieRecordarme();
            return false;
        }

        $borrar = $conn->prepare('DELETE FROM auth_tokens WHERE id = :id');
        $borrar->execute([':id' => $token['id']]);

        // Selector correcto pero validador incorrecto, cookie manipulada, se revoca
        if (!hash_equals($token['validator_hash'], hash('sha256', $validador))) {
            borrarCookieRecordarme();
            return false;
        }

        loginUsuario(['id' => $token['usuario_id'], 'nombre' => $token['nombre'], 'rol' => $token['rol']]);
        crearTokenRecordarme($conn, (int) $token['usuario_id']); // rotación
        return true;
    } catch (PDOException $ex) {
        error_log('Error en Recordarme: ' . $ex->getMessage());
        return false;
    }
}

// Logout, revoca el token en base de datos y elimina la cookie
function revocarRecordarme(PDO $conn)
{
    $cookie = $_COOKIE[COOKIE_RECORDARME] ?? '';

    if (is_string($cookie) && str_contains($cookie, ':')) {
        $selector = explode(':', $cookie)[0];
        try {
            $stmt = $conn->prepare('DELETE FROM auth_tokens WHERE selector = :selector');
            $stmt->execute([':selector' => $selector]);
        } catch (PDOException $ex) {
            error_log('Error al revocar Recordarme: ' . $ex->getMessage());
        }
    }

    borrarCookieRecordarme();
}