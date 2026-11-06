<?php
// Regla 5: horario de atención leído de la tabla configuracion
function obtenerHorario(PDO $conn)
{
    $stmt = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('hora_inicio', 'hora_fin')");
    $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'inicio' => $config['hora_inicio'] ?? '08:00',
        'fin'    => $config['hora_fin'] ?? '17:00',
    ];
}

// Lunes a viernes, con ambos límites inclusivos
function dentroDeHorario(PDO $conn, DateTime $momento)
{
    $horario = obtenerHorario($conn);
    $dia  = (int) $momento->format('N'); // 1 = lunes ... 7 = domingo
    $hora = $momento->format('H:i');

    return $dia <= 5 && $hora >= $horario['inicio'] && $hora <= $horario['fin'];
}

// Formato HH:MM de 24 horas
function horaValida($hora)
{
    return is_string($hora) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
}