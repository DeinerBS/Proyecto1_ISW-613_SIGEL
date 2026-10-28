<?php
require_once __DIR__ . '/auth.php';

// Devuelve el token CSRF de la sesión o lo crea si aún no existe
function csrfToken()
{
    iniciarSesion();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Imprime el campo oculto con el token. Se pone dentro de cada <form> POST
function csrfCampo()
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

// Detiene la petición si el token enviado no coincide con el de la sesión
function verificarCsrf()
{
    iniciarSesion();

    $enviado = $_POST['csrf_token'] ?? '';

    if (
        !is_string($enviado)
        || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $enviado)
    ) {
                http_response_code(403);
        exit('Solicitud no válida. <a href="/index.php">Volver al inicio</a> e intente de nuevo.');
    }
}