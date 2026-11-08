<?php
// "AAAA-MM-DD" input date a DateTime
function leerFecha($texto)
{
    if (!is_string($texto)) {
        return false;
    }

    $fecha = DateTime::createFromFormat('!Y-m-d', $texto);
    return ($fecha && $fecha->format('Y-m-d') === $texto) ? $fecha : false;
}

// Rango desde la URL, del primer dia del mes a hoy
function leerRangoFechas()
{
    $hoy = new DateTime('today');
    $desde = leerFecha($_GET['desde'] ?? $hoy->format('Y-m-01'));
    $hasta = leerFecha($_GET['hasta'] ?? $hoy->format('Y-m-d'));

    if ($desde === false || $hasta === false || $desde > $hasta) {
        return null;
    }
    return [$desde, $hasta];
}

// Préstamos entregados o devueltos, por fecha de inicio dentro del rango
function equiposMasPrestados(PDO $conn, DateTime $desde, DateTime $hasta)
{
    // Hasta el inicio del día siguiente, o sea, todo el día final
    $hastaExclusivo = (clone $hasta)->modify('+1 day');

    $stmt = $conn->prepare(
        "SELECT e.codigo, e.nombre, c.nombre AS categoria, COUNT(*) AS veces
         FROM prestamo_detalle d
         JOIN prestamos p ON p.id = d.prestamo_id
         JOIN equipos e ON e.id = d.equipo_id
         JOIN categorias c ON c.id = e.categoria_id
         WHERE p.estado IN ('entregado', 'devuelto')
           AND p.fecha_inicio >= :desde
           AND p.fecha_inicio < :hasta
         GROUP BY e.id, e.codigo, e.nombre, c.nombre
         ORDER BY veces DESC, e.codigo"
    );
    $stmt->execute([
        ':desde' => $desde->format('Y-m-d 00:00:00'),
        ':hasta' => $hastaExclusivo->format('Y-m-d 00:00:00'),
    ]);
    return $stmt->fetchAll();
}

// Control #12: evita que Excel interprete la celda como fórmula
function neutralizarCelda($valor)
{
    $valor = (string) $valor;

    if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $valor;
    }
    return $valor;
}