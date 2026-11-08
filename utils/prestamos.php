<?php
// Máximo de equipos por préstamo
const MAX_EQUIPOS_PRESTAMO = 10;

// "AAAA-MM-DDTHH:MM" (input datetime-local) a DateTime; false si no es válida
function leerFechaHora($texto)
{
    if (!is_string($texto)) {
        return false;
    }

    $fecha = DateTime::createFromFormat('!Y-m-d\TH:i', $texto);

    // Rechaza fechas imposibles, como el 31 de febrero
    return ($fecha && $fecha->format('Y-m-d\TH:i') === $texto) ? $fecha : false;
}

const ESTADOS_PRESTAMO = [
    'solicitado' => 'Solicitado',
    'aprobado'   => 'Aprobado',
    'entregado'  => 'Entregado',
    'devuelto'   => 'Devuelto',
    'cancelado'  => 'Cancelado',
];

const COLORES_ESTADO_PRESTAMO = [
    'solicitado' => 'info',
    'aprobado'   => 'primary',
    'entregado'  => 'warning',
    'devuelto'   => 'success',
    'cancelado'  => 'secondary',
];

// Transiciones permitidas
const TRANSICIONES = [
    'aprobar'  => ['desde' => ['solicitado'],             'hacia' => 'aprobado',  'boton' => 'Aprobar',              'estilo' => 'primary'],
    'entregar' => ['desde' => ['aprobado'],               'hacia' => 'entregado', 'boton' => 'Marcar entregado',     'estilo' => 'warning'],
    'devolver' => ['desde' => ['entregado'],              'hacia' => 'devuelto',  'boton' => 'Registrar devolución', 'estilo' => 'success'],
    'cancelar' => ['desde' => ['solicitado', 'aprobado'], 'hacia' => 'cancelado', 'boton' => 'Cancelar',             'estilo' => 'outline-danger'],
];

// Regla 5: acciones que deben hacerse dentro del horario
const ACCIONES_CON_HORARIO = ['entregar', 'devolver'];

function formatoFecha($fechaBD)
{
    return (new DateTime($fechaBD))->format('d/m/Y H:i');
}