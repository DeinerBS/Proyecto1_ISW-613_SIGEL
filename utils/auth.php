<?php
require_once __DIR__ . '/helpers.php';

// Inactividad antes de cerrar la sesión en minutos (30 minutos)
const SESION_TIMEOUT_SEGUNDOS = 30 * 60;

// Control #9: Limitación de intentos fallidos de inicio de sesión 
const MAX_INTENTOS_LOGIN = 3;
const MINUTOS_BLOQUEO    = 2;

// Inicia la sesión con la cookie configurada de forma segura
function iniciarSesion()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Guardar en la sesión los datos del usuario autenticado
function loginUsuario(array $usuario)
{
    iniciarSesion();
    session_regenerate_id(true);
    
    // El token de antes del login no se reutiliza en la sesión autenticada
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

// Exige sesión activa y no una expirada
function requiereLogin()
{
    iniciarSesion();

    if (!isset($_SESSION['usuario_id'])) {
        redirect('/index.php?error=login_requerido');
    }

    if (time() - $_SESSION['ultima_actividad'] > SESION_TIMEOUT_SEGUNDOS) {
        logoutUsuario();
        redirect('/index.php?error=sesion_expirada');
    }

    $_SESSION['ultima_actividad'] = time();
}