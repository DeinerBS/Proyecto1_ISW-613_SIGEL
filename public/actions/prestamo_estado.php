<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/horario.php';
require_once __DIR__ . '/../../utils/prestamos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/prestamos.php');
}

requiereLogin($conn);
requiereRol(['operador']);
verificarCsrf();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$accion = $_POST['accion'] ?? '';

if ($id === false) {
    redirect('/pages/prestamos.php?error=prestamo_no_encontrado');
}

$urlVer = '/pages/prestamo_ver.php?id=' . $id . '&';

// Solo acciones de la lista
if (!is_string($accion) || !isset(TRANSICIONES[$accion])) {
    redirect($urlVer . 'error=transicion_invalida');
}
$t = TRANSICIONES[$accion];

// Regla 5: entregar y devolver solo dentro del horario
if (in_array($accion, ACCIONES_CON_HORARIO, true) && !dentroDeHorario($conn, new DateTime())) {
    redirect($urlVer . 'error=fuera_de_horario');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare('SELECT estado FROM prestamos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $actual = $stmt->fetchColumn();

    if ($actual === false) {
        $conn->rollBack();
        redirect('/pages/prestamos.php?error=prestamo_no_encontrado');
    }

    if (!in_array($actual, $t['desde'], true)) {
        $conn->rollBack();
        redirect($urlVer . 'error=transicion_invalida');
    }

    // Al entregar los equipos deben seguir operativos
    if ($accion === 'entregar') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*)
             FROM prestamo_detalle d
             JOIN equipos e ON e.id = d.equipo_id
             WHERE d.prestamo_id = :id AND (e.estado <> 'operativo' OR e.eliminado_en IS NOT NULL)"
        );
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $conn->rollBack();
            redirect($urlVer . 'error=equipo_no_disponible');
        }
    }

    // Solo cambia si el estado sigue siendo el que leímos
    $stmt = $conn->prepare('UPDATE prestamos SET estado = :nuevo WHERE id = :id AND estado = :actual');
    $stmt->execute([':nuevo' => $t['hacia'], ':id' => $id, ':actual' => $actual]);

    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        redirect($urlVer . 'error=prestamo_conflicto');
    }

    if ($accion === 'devolver') {
        $stmt = $conn->prepare('UPDATE prestamo_detalle SET fecha_devolucion = NOW() WHERE prestamo_id = :id');
        $stmt->execute([':id' => $id]);
    }

    registrarBitacora($conn, 'editar', 'prestamos', $id, ['estado' => $actual], ['estado' => $t['hacia']]);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

redirect($urlVer . 'exito=prestamo_actualizado');