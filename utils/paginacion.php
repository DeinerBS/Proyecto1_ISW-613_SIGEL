<?php
require_once __DIR__ . '/auth.php';

const OPCIONES_POR_PAGINA = [5, 10, 25, 50];
const POR_PAGINA_DEFECTO  = 10;

// La cookie es entrada no confiable, se valida contra la lista
function obtenerPorPagina($crud)
{
    $nombreCookie = 'sigel_pp_' . $crud;

    // El usuario eligió un valor en el formulario: se guarda en la cookie
    if (isset($_GET['por_pagina'])) {
        $valor = filter_var($_GET['por_pagina'], FILTER_VALIDATE_INT);
        if (in_array($valor, OPCIONES_POR_PAGINA, true)) {
            setcookie($nombreCookie, (string) $valor, [
                'expires'  => time() + 365 * 24 * 60 * 60,
                'path'     => '/',
                'secure'   => esHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            return $valor;
        }
    }

    $valor = filter_var($_COOKIE[$nombreCookie] ?? '', FILTER_VALIDATE_INT);
    return in_array($valor, OPCIONES_POR_PAGINA, true) ? $valor : POR_PAGINA_DEFECTO;
}