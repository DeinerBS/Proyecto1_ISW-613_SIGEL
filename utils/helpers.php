<?php
// Funciones reutilizables a lo largo del proyecto

// Escapa un texto antes de mostrarlo en HTML. Control #5: XSS
function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Redirige a otra página y detiene el script
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}