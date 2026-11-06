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