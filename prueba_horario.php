<?php
require __DIR__ . '/utils/database.php';
require __DIR__ . '/utils/horario.php';

$casos = ['2026-11-09 10:00', '2026-11-09 08:00', '2026-11-09 17:00', '2026-11-09 17:01',
          '2026-11-09 07:59', '2026-11-14 10:00', '2026-11-15 10:00'];

foreach ($casos as $c) {
    $fecha = new DateTime($c);
    echo $c . ' (' . $fecha->format('l') . '): ' . (dentroDeHorario($conn, $fecha) ? 'SI' : 'NO') . PHP_EOL;
}