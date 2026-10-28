<?php
require_once __DIR__ . '/helpers.php';

const MENSAJES = [
    'error' => [
        'datos_invalidos'        => 'Debe completar todos los campos correctamente.',
        'credenciales_invalidas' => 'Correo o contraseña incorrectos.',
        'login_requerido'        => 'Debe iniciar sesión para acceder a esa página.',
        'sesion_expirada'        => 'Su sesión expiró por inactividad. Vuelva a iniciar sesión.',
        'error_servidor'         => 'Ocurrió un error. Intente de nuevo más tarde.',
        'cuenta_bloqueada' => 'Demasiados intentos fallidos. Espere unos minutos y vuelva a intentarlo.',
    ],
    'exito' => [
        'sesion_cerrada' => 'Sesión cerrada correctamente.',
    ],
];

// Muestra una alerta si en la URL viene ?error=codigo o ?exito=codigo
function mostrarAlerta()
{
    foreach (['error' => 'danger', 'exito' => 'success'] as $tipo => $estilo) {
        $codigo = $_GET[$tipo] ?? null;
        if (is_string($codigo) && isset(MENSAJES[$tipo][$codigo])) {
            echo '<div class="alert alert-' . $estilo . '">' . e(MENSAJES[$tipo][$codigo]) . '</div>';
        }
    }
}