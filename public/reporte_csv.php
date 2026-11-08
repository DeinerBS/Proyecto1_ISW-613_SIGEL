<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/reportes.php';

requiereLogin($conn);
requiereRol(['admin']);

$rango = leerRangoFechas();
if ($rango === null) {
    redirect('/pages/reportes.php?error=rango_invalido');
}
[$desde, $hasta] = $rango;

$filas = equiposMasPrestados($conn, $desde, $hasta);

// El nombre se arma con fechas ya validadas
$nombreArchivo = 'equipos_mas_prestados_' . $desde->format('Y-m-d') . '_' . $hasta->format('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('X-Content-Type-Options: nosniff');

$salida = fopen('php://output', 'w');
fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel reconoce las tildes 

fputcsv($salida, ['Posición', 'Código', 'Equipo', 'Categoría', 'Veces prestado']);

foreach ($filas as $i => $fila) {
    fputcsv($salida, [
        $i + 1,
        neutralizarCelda($fila['codigo']),
        neutralizarCelda($fila['nombre']),
        neutralizarCelda($fila['categoria']),
        (int) $fila['veces'],
    ]);
}

fclose($salida);