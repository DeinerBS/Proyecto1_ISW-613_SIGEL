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
        'acceso_denegado' => 'No cuentas con permisos para acceder a esa sección.',
        'categoria_invalida'      => 'Ingrese un nombre y días entre 1 y 60.',
        'categoria_duplicada'     => 'Ya existe una categoría con ese nombre.',
        'categoria_no_encontrada' => 'La categoría no existe.',
        'categoria_con_equipos' => 'No se puede eliminar porque la categoría tiene equipos asignados.',
    ],
    'exito' => [
        'sesion_cerrada' => 'Sesión cerrada correctamente.',
        'categoria_creada'      => 'Categoría creada correctamente.',
        'categoria_actualizada' => 'Categoría actualizada correctamente.',
        'categoria_eliminada' => 'Categoría eliminada correctamente.',
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