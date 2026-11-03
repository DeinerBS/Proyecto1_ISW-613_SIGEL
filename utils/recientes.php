<?php
require_once __DIR__ . '/auth.php';

const COOKIE_RECIENTES = 'sigel_recientes';
const MAX_RECIENTES    = 5;

function leerRecientes()
{
    $cookie = $_COOKIE[COOKIE_RECIENTES] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $cookie) as $parte) {
        $id = filter_var($parte, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return array_slice($ids, 0, MAX_RECIENTES);
}

// Pone el equipo de primero y conserva máximo 5
function agregarReciente($equipoId)
{
    $ids = array_diff(leerRecientes(), [$equipoId]);
    array_unshift($ids, $equipoId);
    $ids = array_slice($ids, 0, MAX_RECIENTES);

    setcookie(COOKIE_RECIENTES, implode(',', $ids), [
        'expires'  => time() + 30 * 24 * 60 * 60,
        'path'     => '/',
        'secure'   => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Datos de los recientes que sigan vigentes
function obtenerEquiposRecientes(PDO $conn)
{
    $ids = leerRecientes();
    if (!$ids) {
        return [];
    }

    // Un "?" por cada id, los valores igual van como parámetros
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare(
        "SELECT id, codigo, nombre FROM equipos WHERE eliminado_en IS NULL AND id IN ($marcadores)"
    );
    $stmt->execute($ids);

    $porId = [];
    foreach ($stmt->fetchAll() as $eq) {
        $porId[$eq['id']] = $eq;
    }

    $resultado = [];
    foreach ($ids as $id) {
        if (isset($porId[$id])) {
            $resultado[] = $porId[$id];
        }
    }
    return $resultado;
}